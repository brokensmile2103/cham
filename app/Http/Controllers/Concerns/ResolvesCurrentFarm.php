<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Farm;
use Illuminate\Http\Request;

/**
 * Dùng chung cho MỌI controller thao tác trên "trại đang chọn" (session current_farm_id).
 * KHÔNG BAO GIỜ tin farm_id từ client — luôn lấy từ session rồi đối chiếu quyền với DB
 * ở mọi request. Mọi truy vấn theo id (lứa, kho, event...) đều phải đi qua
 * $farm->relation()->findOrFail($id) để không đọc chéo dữ liệu giữa các trại.
 */
trait ResolvesCurrentFarm
{
    protected function currentFarm(Request $request, bool $onlyOwner = false): Farm
    {
        $user = $request->user();
        $id = $request->session()->get('current_farm_id');
        $farm = $id ? Farm::find($id) : null;

        abort_if($onlyOwner && ! $user->isOwner(), 403, 'Chỉ chủ trại mới được vào mục này.');

        if (! $farm || ! $farm->is_active || ! $this->canAccessFarm($farm, $user->id, $user->isOwner(), $onlyOwner)) {
            // Tự chữa: chọn lại trại đầu tiên còn hoạt động mà người dùng được phép vào
            // (VD: trại đang chọn vừa bị khoá nhưng chủ trại còn trại khác).
            // Super Admin của bản cài cũ chưa có trại riêng → tạo tự động.
            app(\App\Services\FarmProvisioner::class)->ensureFor($user);

            $farm = $user->isOwner()
                ? $user->ownedFarms()->where('is_active', true)->orderBy('id')->first()
                : $user->farms()->where('farms.is_active', true)->orderBy('farms.id')->first();

            abort_unless($farm, 403, 'Trại đang tạm khoá hoặc bạn chưa thuộc trại nào đang hoạt động. Vui lòng liên hệ hỗ trợ.');

            $request->session()->put('current_farm_id', $farm->id);
        }

        return $farm;
    }

    private function canAccessFarm(Farm $farm, int $userId, bool $isOwner, bool $onlyOwner): bool
    {
        if ($isOwner) {
            return $farm->owner_id === $userId;
        }

        return ! $onlyOwner && $farm->staff()->where('users.id', $userId)->exists();
    }

    /** Chỉ cho quay lại URL nội bộ (chống open-redirect qua trường return_to). */
    protected function safeReturn(Request $request, string $default): string
    {
        $to = (string) $request->input('return_to', '');

        return ($to !== '' && str_starts_with($to, '/') && ! str_starts_with($to, '//')) ? $to : $default;
    }
}
