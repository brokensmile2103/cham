<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentFarm;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/** Thành viên của trại (người làm / người nhà): xem + ghi nhật ký, làm việc; không thấy tiền, không sửa cài đặt. */
class StaffController extends Controller
{
    use ResolvesCurrentFarm;

    public function index(Request $request): View
    {
        $farm = $this->currentFarm($request, true);

        return view('staff.index', [
            'farm' => $farm,
            'staff' => $farm->staff()->where('role', 'staff')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8',
        ]);

        $staff = User::forceCreate([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $farm->staff()->attach($staff->id);

        return back()->with('status', 'Đã thêm thành viên "'.$staff->name.'".');
    }

    public function toggleActive(Request $request, int $user): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $staff = $farm->staff()->where('role', 'staff')->findOrFail($user);
        $staff->forceFill(['is_active' => ! $staff->is_active])->save();

        return back()->with('status', $staff->is_active ? 'Đã kích hoạt lại tài khoản.' : 'Đã khoá tài khoản.');
    }

    public function resetPassword(Request $request, int $user): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $staff = $farm->staff()->where('role', 'staff')->findOrFail($user);

        $data = $request->validate(['password' => 'required|string|min:8']);
        $staff->update(['password' => Hash::make($data['password'])]);

        return back()->with('status', 'Đã đổi mật khẩu cho '.$staff->name.'.');
    }

    public function remove(Request $request, int $user): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $staff = $farm->staff()->where('role', 'staff')->findOrFail($user);
        $farm->staff()->detach($staff->id);

        return back()->with('status', 'Đã gỡ thành viên khỏi trại này.');
    }
}
