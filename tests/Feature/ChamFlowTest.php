<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Event;
use App\Models\Farm;
use App\Models\InventoryItem;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Luồng nghiệp vụ chính của Chăm + "quét" mọi trang GET (chủ trại & thành viên) để bắt lỗi 500.
 * Chạy được với SQLite (mặc định) hoặc MySQL: DB_CONNECTION=mysql DB_DATABASE=cham_test ... vendor/bin/phpunit
 */
class ChamFlowTest extends TestCase
{
    use RefreshDatabase;

    private bool $createdLock = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! file_exists(storage_path('installed.lock'))) {
            file_put_contents(storage_path('installed.lock'), 'test');
            $this->createdLock = true;
        }
    }

    protected function tearDown(): void
    {
        if ($this->createdLock) {
            @unlink(storage_path('installed.lock'));
        }

        parent::tearDown();
    }

    /** @return array{0: User, 1: Farm} */
    private function owner(): array
    {
        $owner = User::forceCreate(['name' => 'Chủ Trại', 'phone' => '0900000001', 'password' => Hash::make('password'), 'role' => 'owner', 'is_active' => true]);
        $farm = Farm::forceCreate(['owner_id' => $owner->id, 'name' => 'Trại Thử', 'is_active' => true]);
        $farm->staff()->attach($owner->id);

        return [$owner, $farm];
    }

    private function as(User $user, Farm $farm): self
    {
        return $this->actingAs($user)->withSession(['current_farm_id' => $farm->id]);
    }

    /** Dựng 1 trại có dữ liệu: 2 lứa heo, 1 vật tư có tồn kho. */
    private function seedFarm(User $owner, Farm $farm): array
    {
        $this->as($owner, $farm)->post(route('catalog.quick-add'), ['presets' => ['heo', 'ga']])->assertRedirect();
        $heo = $farm->species()->where('name', 'Heo')->firstOrFail();

        $this->as($owner, $farm)->post(route('batches.store'), [
            'kind' => 'batch', 'species_id' => $heo->id, 'started_on' => now()->subDays(30)->toDateString(),
            'initial_quantity' => 10, 'cost_mode' => 'unit', 'purchase_cost' => '1500000', 'zone_name' => 'Chuồng A',
        ])->assertRedirect();
        $this->as($owner, $farm)->post(route('batches.store'), [
            'kind' => 'batch', 'species_id' => $heo->id, 'started_on' => now()->subDays(10)->toDateString(),
            'initial_quantity' => 30, 'purchase_cost' => '30000000',
        ])->assertRedirect();

        [$a, $b] = $farm->batches()->orderBy('id')->get()->all();

        $this->as($owner, $farm)->post(route('inventory.store'), [
            'name' => 'Cám heo', 'category' => 'thuc_an', 'unit' => 'kg', 'low_stock_threshold' => 20,
            'initial_quantity' => 1000, 'initial_cost' => 12000000,
        ])->assertRedirect();
        $item = $farm->items()->firstOrFail();

        return [$a, $b, $item];
    }

    public function test_complete_task_logs_care_and_consumes_stock_once(): void
    {
        [$owner, $farm] = $this->owner();
        [$a, $b, $item] = $this->seedFarm($owner, $farm);

        // Lịch định kỳ cho cả trại kèm vật tư — đúng tình huống lỗi "logCare(): Argument #1 ($farm) ... null".
        $this->as($owner, $farm)->post(route('routines.store'), [
            'title' => 'Cho heo ăn', 'type' => 'cho_an', 'target' => 'all', 'item_id' => $item->id, 'item_quantity' => 40,
            'frequency' => 'daily', 'times' => ['07:00', '17:00'], 'starts_on' => now()->toDateString(),
        ])->assertRedirect(route('routines.index'));

        $task = Task::query()->where('farm_id', $farm->id)->orderBy('due_at')->firstOrFail();

        $this->as($owner, $farm)->post(route('tasks.complete', $task->id), ['item_quantity' => '40'])->assertRedirect();
        // Bấm "Xong" lần 2 không được ghi/trừ kho thêm.
        $this->as($owner, $farm)->post(route('tasks.complete', $task->id), ['item_quantity' => '40'])->assertRedirect();

        $this->assertSame('done', $task->fresh()->status);
        $this->assertSame(2, Event::query()->where('type', 'cho_an')->count());
        $this->assertEqualsWithDelta(960, (float) $item->fresh()->current_stock, 0.001);
        // Chia theo số con: 10 / 30 → 10kg / 30kg.
        $this->assertEqualsWithDelta(10, (float) Event::query()->where('batch_id', $a->id)->value('item_quantity'), 0.001);
        $this->assertEqualsWithDelta(120000, (float) Event::query()->where('batch_id', $a->id)->value('cost'), 0.01);
    }

    public function test_population_events_sales_and_deletions_keep_counts_consistent(): void
    {
        [$owner, $farm] = $this->owner();
        [$a, $b, $item] = $this->seedFarm($owner, $farm);
        $today = now()->toDateString();

        $this->as($owner, $farm)->post(route('events.store'), ['type' => 'chet', 'batch_ids' => [$a->id], 'quantity' => 2, 'occurred_on' => $today])->assertSessionHasNoErrors();
        $this->as($owner, $farm)->post(route('events.store'), ['type' => 'sinh', 'batch_ids' => [$a->id], 'quantity' => 3, 'occurred_on' => $today])->assertSessionHasNoErrors();
        // Chuyển ra ngoài trại (không chọn lứa đích) — form cho phép bỏ trống.
        $this->as($owner, $farm)->post(route('events.store'), ['type' => 'chuyen_di', 'batch_ids' => [$a->id], 'quantity' => 1, 'occurred_on' => $today])->assertSessionHasNoErrors();
        // Chuyển sang lứa B.
        $this->as($owner, $farm)->post(route('events.store'), ['type' => 'chuyen_di', 'batch_ids' => [$a->id], 'quantity' => 4, 'target_batch_id' => $b->id, 'occurred_on' => $today])->assertSessionHasNoErrors();

        $this->assertSame(6, $a->fresh()->current_quantity);  // 10 - 2 + 3 - 1 - 4
        $this->assertSame(34, $b->fresh()->current_quantity);

        // Bán hết lứa A → tự kết thúc; xoá đơn bán → mở lại.
        $this->as($owner, $farm)->post(route('sales.store'), [
            'batch_id' => $a->id, 'sold_on' => $today, 'quantity' => 6, 'weight_kg' => 600, 'price_unit' => 'kg', 'unit_price' => '60000',
        ])->assertSessionHasNoErrors();
        $this->assertSame('closed', $a->fresh()->status);
        $sale = $farm->sales()->firstOrFail();
        $this->assertEqualsWithDelta(36000000, (float) $sale->total_amount, 0.01);

        // Ghi nhật ký cho lứa đã kết thúc bị chặn.
        $this->as($owner, $farm)->post(route('events.store'), ['type' => 'cho_an', 'batch_ids' => [$a->id], 'occurred_on' => $today])->assertSessionHasErrors('batch_ids');

        $this->as($owner, $farm)->delete(route('sales.destroy', $sale->id))->assertRedirect();
        $this->assertSame('active', $a->fresh()->status);
        $this->assertSame(6, $a->fresh()->current_quantity);

        // Xoá cặp chuyển đàn → hoàn về cả 2 lứa.
        $transfer = Event::query()->where('type', 'chuyen_di')->whereNotNull('related_batch_id')->firstOrFail();
        $this->as($owner, $farm)->delete(route('events.destroy', $transfer->id))->assertRedirect();
        $this->assertSame(10, $a->fresh()->current_quantity);
        $this->assertSame(30, $b->fresh()->current_quantity);

        // Lứa chủ trại chủ động "Kết thúc" (còn con) không được tự mở lại khi xoá 1 ghi nhận khác.
        $this->as($owner, $farm)->post(route('batches.close', $b->id))->assertRedirect();
        $this->as($owner, $farm)->post(route('events.store'), ['type' => 'cho_an', 'batch_ids' => [$a->id], 'item_id' => $item->id, 'item_quantity' => 5, 'occurred_on' => $today])->assertSessionHasNoErrors();
        $care = Event::query()->where('type', 'cho_an')->firstOrFail();
        $this->as($owner, $farm)->delete(route('events.destroy', $care->id))->assertRedirect();
        $this->assertSame('closed', $b->fresh()->status);
        $this->assertEqualsWithDelta(1000, (float) $item->fresh()->current_stock, 0.001);

        // Xoá lứa → hoàn kho, mã lứa mới không trùng.
        $this->as($owner, $farm)->post(route('events.store'), ['type' => 'cho_an', 'batch_ids' => [$a->id], 'item_id' => $item->id, 'item_quantity' => 7, 'occurred_on' => $today])->assertSessionHasNoErrors();
        $this->as($owner, $farm)->delete(route('batches.destroy', $a->id))->assertRedirect(route('batches.index'));
        $this->assertEqualsWithDelta(1000, (float) $item->fresh()->current_stock, 0.001);
        $this->as($owner, $farm)->post(route('batches.store'), ['kind' => 'individual', 'species_id' => $b->species_id, 'started_on' => $today, 'name' => 'Lu'])->assertRedirect();
        $this->as($owner, $farm)->post(route('batches.store'), ['kind' => 'batch', 'species_id' => $b->species_id, 'started_on' => $today, 'initial_quantity' => 5])->assertRedirect();
        $codes = $farm->batches()->pluck('code')->all();
        $this->assertSame(count($codes), count(array_unique($codes)));
    }

    public function test_every_get_page_renders_for_owner_and_staff(): void
    {
        [$owner, $farm] = $this->owner();
        [$a, $b, $item] = $this->seedFarm($owner, $farm);
        $today = now()->toDateString();

        $this->as($owner, $farm)->post(route('events.store'), ['type' => 'can', 'batch_ids' => [$a->id], 'weight_kg' => 25.5, 'occurred_on' => $today])->assertSessionHasNoErrors();
        $this->as($owner, $farm)->post(route('events.store'), ['type' => 'cho_an', 'batch_ids' => [$a->id, $b->id], 'item_id' => $item->id, 'item_quantity' => 20, 'occurred_on' => $today])->assertSessionHasNoErrors();
        $this->as($owner, $farm)->post(route('sales.store'), ['batch_id' => $b->id, 'sold_on' => $today, 'quantity' => 2, 'price_unit' => 'con', 'unit_price' => '2000000'])->assertSessionHasNoErrors();
        $this->as($owner, $farm)->post(route('expenses.store'), ['batch_id' => $a->id, 'category' => 'dien_nuoc', 'amount' => '300000', 'occurred_on' => $today])->assertSessionHasNoErrors();
        $this->as($owner, $farm)->post(route('routines.store'), [
            'title' => 'Vệ sinh', 'type' => 've_sinh', 'target' => 'batch', 'batch_id' => $a->id,
            'frequency' => 'weekly', 'weekdays' => [1, 3, 5], 'times' => ['08:00'], 'starts_on' => $today,
        ])->assertSessionHasNoErrors();
        $this->as($owner, $farm)->post(route('tasks.store'), ['title' => 'Tiêm', 'type' => 'tiem_phong', 'target' => 'zone', 'zone_id' => $farm->zones()->value('id'), 'due_date' => $today, 'due_time' => '09:00'])->assertSessionHasNoErrors();
        $this->as($owner, $farm)->post(route('staff.store'), ['name' => 'Cô Ba', 'phone' => '0900000002', 'password' => 'password1'])->assertSessionHasNoErrors();
        $staff = User::query()->where('phone', '0900000002')->firstOrFail();

        $params = [
            'batch' => $a->id, 'item' => $item->id, 'template' => $farm->taskTemplates()->value('id'),
            'farm' => $farm->id, 'slug' => 'gioi-thieu', 'user' => $staff->id, 'token' => 'abc',
        ];

        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true) || str_starts_with($route->uri(), 'install') || str_starts_with($route->uri(), 'sieu-quan-tri')
                || str_contains($route->uri(), 'auth/google') || $route->uri() === '{fallbackPlaceholder}' || str_starts_with($route->uri(), 'storage')) {
                continue;
            }

            $missing = array_diff($route->parameterNames(), array_keys($params));
            if ($missing) {
                continue;
            }

            $url = url(strtr($route->uri(), collect($params)->mapWithKeys(fn ($v, $k) => ['{'.$k.'}' => $v])->all()));

            foreach ([[$owner, true], [$staff, false]] as [$user, $isOwner]) {
                $res = $this->as($user, $farm)->get($url);
                $this->assertLessThan(500, $res->getStatusCode(), "GET {$url} as ".($isOwner ? 'owner' : 'staff')." → {$res->getStatusCode()}\n".substr((string) $res->getContent(), 0, 2000));
                $checked++;
            }
        }

        $this->assertGreaterThan(30, $checked);

        // Super Admin: mọi trang quản trị nền tảng.
        $admin = User::forceCreate(['name' => 'Admin', 'phone' => '0911111111', 'password' => Hash::make('password'), 'role' => 'platform_admin', 'is_active' => true]);
        foreach (['admin.dashboard' => [], 'admin.farms.show' => [$farm->id], 'admin.users.index' => [], 'admin.pages.edit' => ['gioi-thieu'], 'profile.edit' => []] as $name => $args) {
            $this->actingAs($admin)->get(route($name, $args))->assertOk();
        }
        $this->actingAs($admin)->get(route('admin.dashboard', ['q' => 'Trại']))->assertOk()->assertSee('Trại Thử');

        // Thành viên không vào được trang tiền bạc.
        $this->as($staff, $farm)->get(route('reports.index'))->assertForbidden();
        $this->as($staff, $farm)->get(route('sales.index'))->assertForbidden();

        // Ngày rác trên URL không làm lỗi 500.
        $this->as($owner, $farm)->get(route('reports.index', ['from' => 'abc', 'to' => '2026-13-45']))->assertOk();
        $this->as($owner, $farm)->get(route('sales.index', ['from' => 'x']))->assertOk();
        $this->as($owner, $farm)->get(route('events.index', ['from' => 'zzz']))->assertOk();
        $this->as($owner, $farm)->get(route('reports.export'))->assertOk();
        $this->as($owner, $farm)->get(route('settings.data-export'))->assertOk();
    }

    public function test_locked_staff_is_logged_out_and_number_inputs_are_machine_readable(): void
    {
        [$owner, $farm] = $this->owner();
        [$a, $b, $item] = $this->seedFarm($owner, $farm);

        $html = $this->as($owner, $farm)->get(route('inventory.index'))->assertOk()->getContent();
        // 1.000 kg tồn kho phải điền vào ô số là "1000" (không phải "1.000" = 1 kg).
        $this->assertStringContainsString('name="actual"', $html);
        $this->assertMatchesRegularExpression('/name="actual"[^>]*value="1000"|value="1000"[^>]*name="actual"/s', $html);

        $staff = User::forceCreate(['name' => 'NV', 'phone' => '0900000003', 'password' => Hash::make('password'), 'role' => 'staff', 'is_active' => false]);
        $farm->staff()->attach($staff->id);
        $this->as($staff, $farm)->get(route('today'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_scheduler_skips_closed_batch_routines(): void
    {
        [$owner, $farm] = $this->owner();
        [$a, $b, $item] = $this->seedFarm($owner, $farm);

        $this->as($owner, $farm)->post(route('routines.store'), [
            'title' => 'Thuốc', 'type' => 'thuoc', 'target' => 'batch', 'batch_id' => $a->id,
            'frequency' => 'daily', 'times' => ['07:00'], 'starts_on' => now()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->assertGreaterThan(0, Task::query()->count());

        $this->as($owner, $farm)->post(route('batches.close', $a->id));
        Task::query()->delete();
        $farm->taskTemplates()->update(['last_generated_on' => null]);

        app(TaskScheduler::class)->generateForFarm($farm->fresh());
        $this->assertSame(0, Task::query()->count());
    }
}
