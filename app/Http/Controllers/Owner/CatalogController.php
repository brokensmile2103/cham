<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentFarm;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Danh mục: loài, giống, khu vực/chuồng. */
class CatalogController extends Controller
{
    use ResolvesCurrentFarm;

    public function index(Request $request): View
    {
        $farm = $this->currentFarm($request, true);

        $species = $farm->species()->with('breeds')->withCount('batches')->orderBy('sort_order')->orderBy('name')->get();
        $existing = $species->pluck('name')->map(fn ($n) => mb_strtolower($n))->all();

        return view('catalog.index', [
            'farm' => $farm,
            'species' => $species,
            'zones' => $farm->zones()->withCount(['batches as active_batches_count' => fn ($q) => $q->where('status', 'active')])->orderBy('sort_order')->orderBy('name')->get(),
            'presets' => collect(config('cham.species_presets'))->reject(fn ($p) => in_array(mb_strtolower($p['name']), $existing, true))->values(),
        ]);
    }

    /** Thêm nhanh nhiều loài từ danh sách gợi ý (kèm các giống thường gặp). */
    public function quickAdd(Request $request): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);

        $data = $request->validate([
            'presets' => 'required|array|min:1',
            'presets.*' => 'string',
        ], ['presets.required' => 'Hãy chọn ít nhất 1 loài.']);

        $added = 0;
        foreach (config('cham.species_presets') as $preset) {
            if (! in_array($preset['key'], $data['presets'], true) || $farm->species()->where('name', $preset['name'])->exists()) {
                continue;
            }

            $species = $farm->species()->create([
                'name' => $preset['name'],
                'emoji' => $preset['emoji'],
                'group' => $preset['group'],
                'sale_unit' => $preset['sale_unit'],
                'sort_order' => $farm->species()->count(),
            ]);

            foreach ($preset['breeds'] as $breedName) {
                $species->breeds()->create(['farm_id' => $farm->id, 'name' => $breedName]);
            }

            $added++;
        }

        return back()->with('status', $added ? "Đã thêm {$added} loài." : 'Các loài đã chọn đều đã có sẵn.');
    }

    public function storeSpecies(Request $request): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $data = $this->validateSpecies($request);

        $farm->species()->create($data + ['sort_order' => $farm->species()->count()]);

        return back()->with('status', 'Đã thêm loài "'.$data['name'].'".');
    }

    public function updateSpecies(Request $request, int $species): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $species = $farm->species()->findOrFail($species);

        $species->update($this->validateSpecies($request));

        return back()->with('status', 'Đã cập nhật loài "'.$species->name.'".');
    }

    public function destroySpecies(Request $request, int $species): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $species = $farm->species()->withCount('batches')->findOrFail($species);

        if ($species->batches_count > 0) {
            return back()->with('warning', 'Không thể xoá "'.$species->name.'" vì đang có '.$species->batches_count.' lứa/vật nuôi thuộc loài này.');
        }

        $species->delete();

        return back()->with('status', 'Đã xoá loài "'.$species->name.'".');
    }

    public function storeBreed(Request $request, int $species): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $species = $farm->species()->findOrFail($species);

        $data = $request->validate(['name' => 'required|string|max:80']);

        if (! $species->breeds()->where('name', $data['name'])->exists()) {
            $species->breeds()->create(['farm_id' => $farm->id, 'name' => $data['name']]);
        }

        return back()->with('status', 'Đã thêm giống "'.$data['name'].'".');
    }

    public function destroyBreed(Request $request, int $breed): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $farm->breeds()->findOrFail($breed)->delete();

        return back()->with('status', 'Đã xoá giống.');
    }

    public function storeZone(Request $request): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $data = $this->validateZone($request);

        $farm->zones()->create($data + ['sort_order' => $farm->zones()->count()]);

        return back()->with('status', 'Đã thêm khu vực "'.$data['name'].'".');
    }

    public function updateZone(Request $request, int $zone): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $zone = $farm->zones()->findOrFail($zone);
        $zone->update($this->validateZone($request));

        return back()->with('status', 'Đã cập nhật khu vực.');
    }

    public function destroyZone(Request $request, int $zone): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $zone = $farm->zones()->findOrFail($zone);
        $zone->delete(); // lứa thuộc khu này tự về "chưa phân khu" (nullOnDelete)

        return back()->with('status', 'Đã xoá khu vực "'.$zone->name.'".');
    }

    private function validateSpecies(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:60',
            'emoji' => 'nullable|string|max:16',
            'group' => ['required', Rule::in(array_keys(config('cham.species_groups')))],
            'sale_unit' => 'required|in:kg,con',
        ]);

        $data['emoji'] = $data['emoji'] ?: '🐾';

        return $data;
    }

    private function validateZone(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:80',
            'capacity' => 'nullable|integer|min:0|max:10000000',
            'note' => 'nullable|string|max:255',
        ]);
    }
}
