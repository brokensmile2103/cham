<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * CLOUD — chủ trại TỰ XOÁ tài khoản: xoá toàn bộ trại sở hữu (cascade ở tầng
 * database lo hết dữ liệu con: loài, lứa, nhật ký, kho, bán, chi phí, lịch định kỳ...),
 * rồi các thành viên "mồ côi" (không còn thuộc trại nào khác), cuối cùng là chính chủ trại.
 * Các cột created_by đều nullOnDelete nên xoá user không bao giờ vướng khoá ngoại.
 */
class AccountDeletionService
{
    public function delete(User $owner): void
    {
        DB::transaction(function () use ($owner) {
            $candidateStaffIds = collect();

            foreach ($owner->ownedFarms()->get() as $farm) {
                $candidateStaffIds = $candidateStaffIds->merge(
                    $farm->staff()->where('role', 'staff')->pluck('users.id')
                );

                $farm->delete();
            }

            foreach ($candidateStaffIds->unique() as $staffId) {
                $staff = User::find($staffId);

                if ($staff && ! $staff->farms()->exists()) {
                    $staff->delete();
                }
            }

            $owner->delete();
        });
    }
}
