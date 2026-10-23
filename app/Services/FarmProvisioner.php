<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Tạo trại đầu tiên cho 1 chủ — dùng cho trình cài đặt và việc cấp
 * trại riêng cho Super Admin (Super Admin cũng dùng được mọi tính năng như
 * chủ trại). Bản đã cài từ trước (Super Admin chưa có trại nào) sẽ được
 * tạo tự động ở lần đăng nhập đầu tiên.
 */
class FarmProvisioner
{
    public function createFor(User $owner, string $name): Farm
    {
        $farm = Farm::forceCreate([
            'owner_id' => $owner->id,
            'name' => $name,
            'is_active' => true,
        ]);

        $farm->staff()->attach($owner->id);

        return $farm;
    }

    /** Chỉ tạo khi Super Admin hoàn toàn chưa sở hữu trại nào (trại đang bị khoá vẫn tính là có). */
    public function ensureFor(User $user): ?Farm
    {
        if (! $user->isPlatformAdmin()) {
            return null;
        }

        return $user->ownedFarms()->orderBy('id')->first()
            ?? DB::transaction(fn () => $this->createFor($user, 'Trại của '.$user->name));
    }
}
