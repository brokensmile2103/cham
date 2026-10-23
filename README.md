# Chăm — Phần mềm quản lý chăn nuôi (tự host, multi-tenant)

Nuôi chăm chỉ, chăm sóc chu đáo. Quản lý heo, gà, cá, thú cưng… cho trại nhỏ, hộ gia đình, người nuôi thú cưng. Xây trên nền Laravel của Rót.

## Luồng nghiệp vụ
Vật nuôi / Lứa → Kho → Nhật ký chăm sóc → Công việc định kỳ → Xuất bán → Lời/lỗ.

- **Event log là nguồn sự thật**: mọi thay đổi (cho ăn, thuốc, chết, bán, chuyển đàn…) là 1 event. `batches.current_quantity` chỉ là cache, tính lại từ event.
- **Kho** dùng giá vốn bình quân gia quyền. Ghi nhật ký có vật tư → tự trừ kho, tự ghi chi phí vào lứa (chia theo số con nếu chọn nhiều lứa). Tồn kho được phép âm (có cảnh báo).
- **Công việc định kỳ**: tạo lịch → hệ thống tự sinh việc (khi mở app + lệnh `php artisan cham:generate-tasks`, đã schedule 00:10 hằng ngày). Không có cron vẫn chạy đúng nhờ sinh việc "lười".
- **Lời/lỗ**: giá vốn con giống + thức ăn + thuốc + vật tư + chi phí khác so với doanh thu; có giá thành/con, lãi/con bán, tỉ lệ hao hụt.
- **Phân quyền**: Super Admin (đồng thời là chủ trại đầu tiên, dùng được mọi tính năng như tài khoản thường; ngoài ra xem số liệu tổng và **đóng/mở đăng ký công khai** tại `/sieu-quan-tri/cai-dat`) · Chủ trại (toàn quyền, nhiều trại) · Thành viên (xem/ghi nhật ký, làm việc; không thấy tiền).

## Chạy local
```bash
composer install
npm install && npm run build      # hoặc npm run dev
cp .env.example .env              # sửa DB_*
php artisan key:generate
php artisan migrate
php artisan serve                 # mở http://localhost:8000/install để tạo Super Admin (cũng là chủ trại đầu tiên)
```
Production: trỏ document root vào `public/`, bật `SESSION_SECURE_COOKIE=true` khi có HTTPS, thêm cron `* * * * * php artisan schedule:run`.

## Cấu hình
`config/cham.php`: loại sự kiện, nhóm chi phí, nhóm kho, tần suất lặp, danh sách loài gợi ý, màu giao diện.

## Giới hạn MVP
- Chuyển con giữa 2 lứa chưa chuyển kèm giá vốn.
- Chưa có AI / IoT / camera / RFID, chưa có app native (dùng PWA).

## Sửa lỗi đợt rà soát 10/2026
- Bấm "Xong" công việc định kỳ lỗi 500 `logCare(): Argument #1 ($farm) ... null` — model `Task` thiếu quan hệ `farm()` (bổ sung cho Task, TaskTemplate, Zone, Sale, Expense, StockMovement, Breed). Bấm "Xong" 2 lần không còn ghi nhật ký/trừ kho 2 lần (khoá dòng).
- Ô số điền sẵn dùng định dạng VN (`1.500` → trình duyệt hiểu 1,5): kiểm kê kho, ngưỡng cảnh báo, số lượng vật tư, cân nặng — đổi sang `input_number()`.
- "Chuyển đàn đi" không chọn lứa đích (chuyển ra ngoài) bị báo lỗi dù form cho phép bỏ trống.
- Ghi nhật ký được cho lứa đã kết thúc; xoá 1 ghi nhận làm lứa chủ động "Kết thúc" tự mở lại.
- Mã lứa trùng sau khi xoá lứa; lịch định kỳ của lứa đã kết thúc vẫn sinh việc; badge Công việc đếm cả việc quá 7 ngày không hiện trong danh sách.
- Ngày rác trên URL (`?from=abc`) gây lỗi 500 ở Báo cáo / Xuất bán / Nhật ký; CSV báo cáo chống "CSV injection".
- Tài khoản bị khoá vẫn dùng tiếp được tới khi hết phiên → nay bị đăng xuất ngay (`EnsureActiveUser`). Trại đang chọn bị khoá → tự chuyển sang trại khác còn hoạt động.
- Tên có dấu nháy làm hỏng hộp xác nhận xoá (JS) → dùng `Js::from`.
- Icon "Sinh / nở thêm" đổi từ em bé sang 🥚 `fa-egg`.
- Thêm `tests/Feature/ChamFlowTest.php` (luồng chính + quét mọi trang GET theo vai trò).
