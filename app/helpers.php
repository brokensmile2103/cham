<?php

if (! function_exists('money')) {
    /**
     * Định dạng số tiền theo chuẩn Việt Nam — dấu CHẤM ngăn cách hàng nghìn,
     * KHÔNG dùng dấu phẩy như number_format() mặc định của PHP (kiểu Mỹ).
     * Dùng hàm này thay cho number_format() ở MỌI nơi hiển thị tiền trong app.
     */
    function money($value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals, ',', '.');
    }
}

if (! function_exists('quantity')) {
    /**
     * Định dạng SỐ LƯỢNG (kg, g, ml...) theo chuẩn Việt Nam — TỰ BỎ số 0 thừa
     * ở phần thập phân thay vì luôn ép đủ N chữ số như number_format() thường
     * làm (VD: 1920 hiện "1.920" thay vì "1.920,00"; 1920.5 vẫn hiện đúng
     * "1.920,5"). Khác với money() — tiền luôn muốn số nguyên, còn số lượng
     * nguyên liệu nhiều lúc cần phần thập phân thật (0.5g, 1.25kg...).
     */
    function quantity($value, int $maxDecimals = 2): string
    {
        $rounded = round((float) $value, $maxDecimals);
        $trimmed = rtrim(rtrim(number_format($rounded, $maxDecimals, '.', ''), '0'), '.');

        [$intPart, $decPart] = array_pad(explode('.', $trimmed), 2, null);
        $intFormatted = number_format((float) $intPart, 0, '', '.');

        return $decPart !== null ? $intFormatted.','.$decPart : $intFormatted;
    }
}

if (! function_exists('money_compact')) {
    /**
     * Số tiền RÚT GỌN cho những ô rất hẹp (VD: ô heatmap giờ cao điểm trên điện
     * thoại): 125.000 → "125k", 1.250.000 → "1,3tr", 12.000.000 → "12tr". CHỈ để
     * hiển thị gọn — chỗ nào cần số tiền chính xác vẫn dùng money().
     */
    function money_compact($value): string
    {
        $amount = (float) $value;

        // >= 999.500 làm tròn theo nghìn sẽ ra "1.000k" — chuyển sang đơn vị triệu cho gọn.
        if (abs($amount) >= 999_500) {
            $millions = round($amount / 1_000_000, 1);

            return rtrim(rtrim(number_format($millions, 1, ',', '.'), '0'), ',').'tr';
        }

        if (abs($amount) >= 1_000) {
            return number_format(round($amount / 1_000), 0, ',', '.').'k';
        }

        return number_format(round($amount), 0, ',', '.');
    }
}

if (! function_exists('color_badge')) {
    /**
     * Class Tailwind (liệt kê ĐẦY ĐỦ chuỗi để Tailwind quét được) cho huy hiệu/biểu tượng theo màu trong
     * config/cham.php. Không nối chuỗi kiểu "bg-{$color}-100" vì Tailwind sẽ không sinh CSS cho nó.
     */
    function color_badge(?string $color): string
    {
        return [
            'amber' => 'bg-amber-100 text-amber-700',
            'rose' => 'bg-rose-100 text-rose-700',
            'blue' => 'bg-blue-100 text-blue-700',
            'violet' => 'bg-violet-100 text-violet-700',
            'emerald' => 'bg-emerald-100 text-emerald-700',
            'sky' => 'bg-sky-100 text-sky-700',
            'orange' => 'bg-orange-100 text-orange-700',
            'red' => 'bg-red-100 text-red-700',
        ][$color] ?? 'bg-neutral-100 text-neutral-600';
    }
}

if (! function_exists('day_label')) {
    /** "Hôm nay" / "Hôm qua" / "Thứ ba, 06/10/2026" — dùng làm tiêu đề nhóm trong timeline. */
    function day_label(\Illuminate\Support\Carbon|\DateTimeInterface $date): string
    {
        $date = \Illuminate\Support\Carbon::instance($date)->startOfDay();

        if ($date->isToday()) {
            return 'Hôm nay';
        }

        if ($date->isYesterday()) {
            return 'Hôm qua';
        }

        $weekdays = [1 => 'Thứ hai', 2 => 'Thứ ba', 3 => 'Thứ tư', 4 => 'Thứ năm', 5 => 'Thứ sáu', 6 => 'Thứ bảy', 7 => 'Chủ nhật'];

        return $weekdays[$date->dayOfWeekIso].', '.$date->format('d/m/Y');
    }
}

if (! function_exists('ago')) {
    /** "2 ngày trước" / "hôm nay" — mức độ gần đây của 1 mốc thời gian. */
    function ago($date): string
    {
        if (! $date) {
            return 'chưa có';
        }

        $date = \Illuminate\Support\Carbon::parse($date);
        $days = (int) $date->copy()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::today());

        return match (true) {
            $days <= 0 => 'hôm nay',
            $days === 1 => 'hôm qua',
            default => $days.' ngày trước',
        };
    }
}

if (! function_exists('signed_money')) {
    /** Số tiền có dấu: +1.200.000 / −350.000 (dùng cho lãi/lỗ). */
    function signed_money($value): string
    {
        $v = (float) $value;

        return ($v > 0 ? '+' : ($v < 0 ? '−' : '')).money(abs($v));
    }
}

if (! function_exists('input_number')) {
    /**
     * Giá trị để ĐIỀN SẴN vào <input type="number">: số "sạch" kiểu máy (dấu chấm thập phân,
     * không phân cách nghìn, bỏ số 0 thừa). KHÔNG dùng quantity()/money() cho value của ô số —
     * "1.500" (1500 kiểu VN) sẽ bị trình duyệt hiểu thành 1,5 và "2,5" bị coi là không hợp lệ.
     */
    function input_number($value, int $maxDecimals = 3): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $s = number_format(round((float) $value, $maxDecimals), $maxDecimals, '.', '');

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    }
}

if (! function_exists('parse_date')) {
    /**
     * Đọc ngày từ query string (Y-m-d) an toàn: chuỗi rác/sai định dạng trả về $default
     * thay vì ném exception (lỗi 500) như Carbon::parse().
     */
    function parse_date($value, ?\Illuminate\Support\Carbon $default = null): ?\Illuminate\Support\Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $default;
        }

        try {
            $date = \Illuminate\Support\Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return $default;
        }

        return $date && $date->format('Y-m-d') === $value ? $date : $default;
    }
}

if (! function_exists('csv_safe')) {
    /** Chống "CSV injection": ô bắt đầu bằng = + - @ bị Excel hiểu là công thức. */
    function csv_safe($value)
    {
        return is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'".$value
            : $value;
    }
}
