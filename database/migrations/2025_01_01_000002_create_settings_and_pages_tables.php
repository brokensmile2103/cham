<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Nội dung 3 trang tĩnh lưu trong database để Super Admin tự sửa, không cần deploy lại.
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('pages')->insert([
            [
                'slug' => 'gioi-thieu',
                'title' => 'Giới thiệu',
                'content' => '<p>Chăm là phần mềm quản lý chăn nuôi dành cho hộ gia đình, trại nhỏ và người nuôi thú cưng — theo dõi lứa nuôi, kho thức ăn/thuốc, nhật ký chăm sóc, công việc lặp lại và lời/lỗ ngay trên điện thoại.</p><p>"Chăm" mang hai nghĩa: chăm chỉ và chăm sóc. Đăng ký là dùng được ngay, không cần cài đặt máy chủ, không cần biết kỹ thuật.</p><p>Nội dung trang này do quản trị viên nền tảng cập nhật.</p>',
                'meta_description' => 'Chăm — phần mềm quản lý chăn nuôi: lứa nuôi, kho, nhật ký chăm sóc, việc định kỳ, xuất bán và lời/lỗ. Đăng ký miễn phí, dùng ngay trên điện thoại.',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'slug' => 'dieu-khoan-su-dung',
                'title' => 'Điều khoản sử dụng',
                'content' => '<p><em>Đây là bản khung tạm thời, cần được luật sư rà soát trước khi phát hành chính thức.</em></p><h3>1. Chấp nhận điều khoản</h3><p>Bằng việc đăng ký và sử dụng Chăm, bạn đồng ý tuân thủ các điều khoản được nêu tại trang này.</p><h3>2. Tài khoản người dùng</h3><p>Bạn chịu trách nhiệm bảo mật thông tin đăng nhập và mọi hoạt động diễn ra dưới tài khoản của mình.</p><h3>3. Dữ liệu của bạn</h3><p>Dữ liệu chăn nuôi bạn nhập vào hệ thống thuộc quyền sở hữu của bạn. Nhà cung cấp có trách nhiệm lưu trữ và bảo vệ dữ liệu đó theo Chính sách bảo mật.</p><h3>4. Giới hạn trách nhiệm</h3><p>Dịch vụ được cung cấp trên cơ sở "nguyên trạng". Chăm là công cụ ghi chép và hỗ trợ quản lý, không thay thế tư vấn thú y hay chuyên môn chăn nuôi. Nhà cung cấp không chịu trách nhiệm cho các thiệt hại gián tiếp phát sinh từ việc sử dụng dịch vụ.</p><h3>5. Thay đổi điều khoản</h3><p>Điều khoản có thể được cập nhật theo thời gian. Việc tiếp tục sử dụng dịch vụ sau khi thay đổi đồng nghĩa với việc chấp nhận điều khoản mới.</p>',
                'meta_description' => 'Điều khoản sử dụng dịch vụ Chăm — quyền và trách nhiệm của bạn khi đăng ký, sử dụng phần mềm quản lý chăn nuôi Chăm.',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'slug' => 'chinh-sach-bao-mat',
                'title' => 'Chính sách bảo mật',
                'content' => '<p><em>Đây là bản khung tạm thời, cần được luật sư rà soát trước khi phát hành chính thức.</em></p><h3>1. Thông tin thu thập</h3><p>Chúng tôi thu thập thông tin bạn cung cấp khi đăng ký (tên, số điện thoại, tên trại) và dữ liệu chăn nuôi phát sinh trong quá trình sử dụng.</p><h3>2. Mục đích sử dụng</h3><p>Thông tin được dùng để vận hành dịch vụ, hỗ trợ kỹ thuật, và cải thiện chất lượng phần mềm.</p><h3>3. Chia sẻ thông tin</h3><p>Chúng tôi không bán hoặc cho thuê dữ liệu cá nhân của bạn cho bên thứ ba vì mục đích quảng cáo.</p><h3>4. Bảo mật dữ liệu</h3><p>Dữ liệu nhạy cảm (mật khẩu) được mã hoá khi lưu trữ.</p><h3>5. Quyền của bạn</h3><p>Bạn có quyền xuất hoặc xoá dữ liệu của mình ngay trong phần Cài đặt / Hồ sơ cá nhân.</p>',
                'meta_description' => 'Chính sách bảo mật của Chăm — cách chúng tôi thu thập, sử dụng và bảo vệ dữ liệu cá nhân và dữ liệu chăn nuôi của bạn.',
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
        Schema::dropIfExists('settings');
    }
};
