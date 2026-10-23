<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\Owner\CatalogController;
use App\Http\Controllers\Owner\DataExportController;
use App\Http\Controllers\Owner\ExpenseController;
use App\Http\Controllers\Owner\FarmController;
use App\Http\Controllers\Owner\ReportController;
use App\Http\Controllers\Owner\RoutineController;
use App\Http\Controllers\Owner\SaleController;
use App\Http\Controllers\Owner\SettingsController;
use App\Http\Controllers\Owner\StaffController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Work\BatchController;
use App\Http\Controllers\Work\EventController;
use App\Http\Controllers\Work\InventoryController;
use App\Http\Controllers\Work\TaskController;
use App\Http\Controllers\Work\TodayController;
use Illuminate\Support\Facades\Route;

// ===== INSTALL WIZARD (chạy 1 LẦN để khởi tạo nền tảng Cloud — tạo Super Admin, KHÔNG tạo trại nào) =====
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'welcome'])->name('welcome');
    Route::get('/requirements', [InstallController::class, 'requirements'])->name('requirements');
    Route::get('/database', [InstallController::class, 'showDatabaseForm'])->name('database');
    Route::post('/database', [InstallController::class, 'saveDatabase'])->name('database.save');
    Route::get('/account', [InstallController::class, 'showAccountForm'])->name('account');
    Route::post('/account', [InstallController::class, 'saveAccount'])->name('account.save');
    Route::get('/finish', [InstallController::class, 'finish'])->name('finish');
});

// ===== AUTH =====
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
    Route::middleware('registration.open')->group(function () {
        Route::get('/dang-ky', [RegisterController::class, 'show'])->name('register');
        Route::post('/dang-ky', [RegisterController::class, 'store'])->name('register.store');
    });

    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
    Route::middleware('registration.open')->group(function () {
        Route::get('/dang-ky/hoan-tat', [RegisterController::class, 'showGooglePhone'])->name('register.google-phone');
        Route::post('/dang-ky/hoan-tat', [RegisterController::class, 'storeGooglePhone'])->name('register.google-phone.store');
    });

    Route::get('/quen-mat-khau', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/quen-mat-khau', [ForgotPasswordController::class, 'send'])->name('password.email');
    Route::get('/dat-lai-mat-khau/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/dat-lai-mat-khau', [ResetPasswordController::class, 'update'])->name('password.update');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// ===== TRANG TĨNH — nội dung lấy từ database, Super Admin sửa được =====
Route::get('/gioi-thieu', fn (PageController $c) => $c->show('gioi-thieu'))->name('pages.about');
Route::get('/dieu-khoan-su-dung', fn (PageController $c) => $c->show('dieu-khoan-su-dung'))->name('pages.terms');
Route::get('/chinh-sach-bao-mat', fn (PageController $c) => $c->show('chinh-sach-bao-mat'))->name('pages.privacy');

Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [PageController::class, 'robots'])->name('robots');

// ===== SUPER ADMIN — quản lý toàn bộ trại trên nền tảng =====
Route::middleware(['auth', 'role:platform_admin'])->prefix('sieu-quan-tri')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/trai/{farm}', [DashboardController::class, 'show'])->name('farms.show');
    Route::put('/trai/{farm}/khoa-mo', [DashboardController::class, 'toggleActive'])->name('farms.toggle');
    Route::get('/cai-dat', [AdminSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/cai-dat', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::get('/trang/{slug}', [AdminPageController::class, 'edit'])->name('pages.edit');
    Route::put('/trang/{slug}', [AdminPageController::class, 'update'])->name('pages.update');
    Route::get('/nguoi-dung', [AdminUserController::class, 'index'])->name('users.index');
    Route::put('/nguoi-dung/{user}/mat-khau', [AdminUserController::class, 'resetPassword'])->name('users.password');
    Route::put('/nguoi-dung/{user}/khoa-mo', [AdminUserController::class, 'toggleActive'])->name('users.toggle');
});

Route::get('/', [HomeController::class, 'index'])->name('home');

// ===== KHU VỰC LÀM VIỆC (chủ trại + thành viên) =====
Route::middleware('auth')->group(function () {
    Route::get('/hom-nay', [TodayController::class, 'index'])->name('today');

    // Công việc
    Route::get('/cong-viec', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/cong-viec', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('/cong-viec/bo-qua-qua-han', [TaskController::class, 'skipOverdue'])->name('tasks.skip-overdue');
    Route::post('/cong-viec/{task}/hoan-thanh', [TaskController::class, 'complete'])->whereNumber('task')->name('tasks.complete');
    Route::post('/cong-viec/{task}/bo-qua', [TaskController::class, 'skip'])->whereNumber('task')->name('tasks.skip');
    Route::post('/cong-viec/{task}/mo-lai', [TaskController::class, 'reopen'])->whereNumber('task')->name('tasks.reopen');
    Route::delete('/cong-viec/{task}', [TaskController::class, 'destroy'])->whereNumber('task')->name('tasks.destroy');

    // Vật nuôi / lứa nuôi (xem)
    Route::get('/vat-nuoi', [BatchController::class, 'index'])->name('batches.index');
    Route::get('/vat-nuoi/{batch}', [BatchController::class, 'show'])->whereNumber('batch')->name('batches.show');

    // Nhật ký
    Route::get('/nhat-ky', [EventController::class, 'index'])->name('events.index');
    Route::get('/nhat-ky/ghi', [EventController::class, 'create'])->name('events.create');
    Route::post('/nhat-ky', [EventController::class, 'store'])->name('events.store');
    Route::delete('/nhat-ky/{event}', [EventController::class, 'destroy'])->whereNumber('event')->name('events.destroy');

    // Kho (xem)
    Route::get('/kho', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/kho/{item}/lich-su', [InventoryController::class, 'history'])->whereNumber('item')->name('inventory.history');

    // Hồ sơ
    Route::get('/ho-so', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/ho-so', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/ho-so/mat-khau', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/ho-so', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ===== CHỈ CHỦ TRẠI =====
Route::middleware(['auth', 'role:owner'])->group(function () {
    // Vật nuôi: tạo/sửa/đóng/xoá
    Route::get('/vat-nuoi/tao', [BatchController::class, 'create'])->name('batches.create');
    Route::post('/vat-nuoi', [BatchController::class, 'store'])->name('batches.store');
    Route::get('/vat-nuoi/{batch}/sua', [BatchController::class, 'edit'])->whereNumber('batch')->name('batches.edit');
    Route::put('/vat-nuoi/{batch}', [BatchController::class, 'update'])->whereNumber('batch')->name('batches.update');
    Route::post('/vat-nuoi/{batch}/chuyen-khu', [BatchController::class, 'move'])->whereNumber('batch')->name('batches.move');
    Route::post('/vat-nuoi/{batch}/ket-thuc', [BatchController::class, 'close'])->whereNumber('batch')->name('batches.close');
    Route::post('/vat-nuoi/{batch}/mo-lai', [BatchController::class, 'reopen'])->whereNumber('batch')->name('batches.reopen');
    Route::delete('/vat-nuoi/{batch}', [BatchController::class, 'destroy'])->whereNumber('batch')->name('batches.destroy');

    // Kho: nhập / sửa / kiểm kê
    Route::post('/kho', [InventoryController::class, 'store'])->name('inventory.store');
    Route::put('/kho/{item}', [InventoryController::class, 'update'])->whereNumber('item')->name('inventory.update');
    Route::delete('/kho/{item}', [InventoryController::class, 'destroy'])->whereNumber('item')->name('inventory.destroy');
    Route::post('/kho/{item}/khoi-phuc', [InventoryController::class, 'restore'])->whereNumber('item')->name('inventory.restore');
    Route::post('/kho/{item}/nhap', [InventoryController::class, 'stockIn'])->whereNumber('item')->name('inventory.stock-in');
    Route::post('/kho/{item}/kiem-ke', [InventoryController::class, 'adjust'])->whereNumber('item')->name('inventory.adjust');

    Route::prefix('quan-ly')->group(function () {
        // Danh mục: loài / giống / khu vực
        Route::get('/danh-muc', [CatalogController::class, 'index'])->name('catalog.index');
        Route::post('/danh-muc/them-nhanh', [CatalogController::class, 'quickAdd'])->name('catalog.quick-add');
        Route::post('/loai', [CatalogController::class, 'storeSpecies'])->name('species.store');
        Route::put('/loai/{species}', [CatalogController::class, 'updateSpecies'])->whereNumber('species')->name('species.update');
        Route::delete('/loai/{species}', [CatalogController::class, 'destroySpecies'])->whereNumber('species')->name('species.destroy');
        Route::post('/loai/{species}/giong', [CatalogController::class, 'storeBreed'])->whereNumber('species')->name('breeds.store');
        Route::delete('/giong/{breed}', [CatalogController::class, 'destroyBreed'])->whereNumber('breed')->name('breeds.destroy');
        Route::post('/khu-vuc', [CatalogController::class, 'storeZone'])->name('zones.store');
        Route::put('/khu-vuc/{zone}', [CatalogController::class, 'updateZone'])->whereNumber('zone')->name('zones.update');
        Route::delete('/khu-vuc/{zone}', [CatalogController::class, 'destroyZone'])->whereNumber('zone')->name('zones.destroy');

        // Công việc định kỳ
        Route::get('/lich-dinh-ky', [RoutineController::class, 'index'])->name('routines.index');
        Route::get('/lich-dinh-ky/tao', [RoutineController::class, 'create'])->name('routines.create');
        Route::post('/lich-dinh-ky', [RoutineController::class, 'store'])->name('routines.store');
        Route::get('/lich-dinh-ky/{template}/sua', [RoutineController::class, 'edit'])->whereNumber('template')->name('routines.edit');
        Route::put('/lich-dinh-ky/{template}', [RoutineController::class, 'update'])->whereNumber('template')->name('routines.update');
        Route::post('/lich-dinh-ky/{template}/bat-tat', [RoutineController::class, 'toggle'])->whereNumber('template')->name('routines.toggle');
        Route::delete('/lich-dinh-ky/{template}', [RoutineController::class, 'destroy'])->whereNumber('template')->name('routines.destroy');

        // Xuất bán & chi phí
        Route::get('/xuat-ban', [SaleController::class, 'index'])->name('sales.index');
        Route::post('/xuat-ban', [SaleController::class, 'store'])->name('sales.store');
        Route::delete('/xuat-ban/{sale}', [SaleController::class, 'destroy'])->whereNumber('sale')->name('sales.destroy');
        Route::get('/chi-phi', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/chi-phi', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('/chi-phi/{expense}', [ExpenseController::class, 'destroy'])->whereNumber('expense')->name('expenses.destroy');

        // Báo cáo lời/lỗ
        Route::get('/bao-cao', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/bao-cao/xuat-csv', [ReportController::class, 'export'])->name('reports.export');

        // Thành viên
        Route::get('/thanh-vien', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/thanh-vien', [StaffController::class, 'store'])->name('staff.store');
        Route::post('/thanh-vien/{user}/kich-hoat', [StaffController::class, 'toggleActive'])->whereNumber('user')->name('staff.toggle');
        Route::post('/thanh-vien/{user}/doi-mat-khau', [StaffController::class, 'resetPassword'])->whereNumber('user')->name('staff.reset-password');
        Route::delete('/thanh-vien/{user}', [StaffController::class, 'remove'])->whereNumber('user')->name('staff.remove');

        // Cài đặt
        Route::get('/cai-dat', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/cai-dat/trai', [SettingsController::class, 'updateFarm'])->name('settings.farm');
        Route::put('/cai-dat/giao-dien', [SettingsController::class, 'updateAppearance'])->name('settings.appearance');
        Route::get('/cai-dat/xuat-du-lieu', [DataExportController::class, 'export'])->name('settings.data-export');

        // Nhiều trại
        Route::get('/cac-trai', [FarmController::class, 'index'])->name('farms.index');
        Route::post('/cac-trai', [FarmController::class, 'store'])->name('farms.store');
        Route::put('/cac-trai/{farm}', [FarmController::class, 'update'])->whereNumber('farm')->name('farms.update');
        Route::post('/cac-trai/{farm}/chuyen', [FarmController::class, 'switch'])->whereNumber('farm')->name('farms.switch');
    });
});
