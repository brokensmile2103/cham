<?php

/**
 * Cấu hình nghiệp vụ của Chăm: loại sự kiện (event), nhóm chi phí, danh mục kho,
 * tần suất lặp, và danh sách loài gợi ý để thêm nhanh. Chỉnh ở đây là đổi toàn app.
 */
return [

    // group: care = ghi chăm sóc (không đổi số lượng con) | pop = thay đổi số lượng con
    // sign: +1 tăng, -1 giảm, 0 không đổi, 2 = tuỳ chọn (điều chỉnh)
    // item: có dùng vật tư trong kho hay không; item_categories: nhóm kho gợi ý
    'event_types' => [
        'cho_an' => ['label' => 'Cho ăn', 'icon' => 'fa-bowl-food', 'color' => 'amber', 'group' => 'care', 'sign' => 0, 'item' => true, 'item_categories' => ['thuc_an']],
        'thuoc' => ['label' => 'Cho uống thuốc', 'icon' => 'fa-pills', 'color' => 'rose', 'group' => 'care', 'sign' => 0, 'item' => true, 'item_categories' => ['thuoc', 'vat_tu']],
        'tiem_phong' => ['label' => 'Tiêm phòng', 'icon' => 'fa-syringe', 'color' => 'blue', 'group' => 'care', 'sign' => 0, 'item' => true, 'item_categories' => ['vaccine', 'thuoc']],
        'tay_giun' => ['label' => 'Tẩy giun', 'icon' => 'fa-bug', 'color' => 'violet', 'group' => 'care', 'sign' => 0, 'item' => true, 'item_categories' => ['thuoc']],
        've_sinh' => ['label' => 'Vệ sinh chuồng', 'icon' => 'fa-broom', 'color' => 'emerald', 'group' => 'care', 'sign' => 0, 'item' => true, 'item_categories' => ['vat_tu']],
        'thay_nuoc' => ['label' => 'Thay nước', 'icon' => 'fa-droplet', 'color' => 'sky', 'group' => 'care', 'sign' => 0, 'item' => true, 'item_categories' => ['vat_tu']],
        'can' => ['label' => 'Cân trọng lượng', 'icon' => 'fa-weight-scale', 'color' => 'orange', 'group' => 'care', 'sign' => 0, 'item' => false, 'item_categories' => []],
        'khac' => ['label' => 'Ghi chú / việc khác', 'icon' => 'fa-note-sticky', 'color' => 'neutral', 'group' => 'care', 'sign' => 0, 'item' => true, 'item_categories' => []],

        'nhap_them' => ['label' => 'Nhập thêm con', 'icon' => 'fa-circle-plus', 'color' => 'emerald', 'group' => 'pop', 'sign' => 1, 'item' => false, 'item_categories' => []],
        'sinh' => ['label' => 'Sinh / nở thêm', 'icon' => 'fa-egg', 'color' => 'emerald', 'group' => 'pop', 'sign' => 1, 'item' => false, 'item_categories' => []],
        'chet' => ['label' => 'Chết', 'icon' => 'fa-skull', 'color' => 'red', 'group' => 'pop', 'sign' => -1, 'item' => false, 'item_categories' => []],
        'hao_hut' => ['label' => 'Hao hụt / thất lạc', 'icon' => 'fa-arrow-trend-down', 'color' => 'red', 'group' => 'pop', 'sign' => -1, 'item' => false, 'item_categories' => []],
        'chuyen_di' => ['label' => 'Chuyển đàn đi', 'icon' => 'fa-right-from-bracket', 'color' => 'blue', 'group' => 'pop', 'sign' => -1, 'item' => false, 'item_categories' => []],
        'chuyen_den' => ['label' => 'Chuyển đàn đến', 'icon' => 'fa-right-to-bracket', 'color' => 'blue', 'group' => 'pop', 'sign' => 1, 'item' => false, 'item_categories' => []],
        'dieu_chinh' => ['label' => 'Điều chỉnh số lượng', 'icon' => 'fa-pen-to-square', 'color' => 'neutral', 'group' => 'pop', 'sign' => 2, 'item' => false, 'item_categories' => []],
        'ban' => ['label' => 'Xuất bán', 'icon' => 'fa-sack-dollar', 'color' => 'emerald', 'group' => 'pop', 'sign' => -1, 'item' => false, 'item_categories' => []],
    ],

    // Loại có thể chọn trong form "Ghi nhật ký" (không gồm các loại hệ thống tự sinh)
    'loggable_types' => [
        'cho_an', 'thuoc', 'tiem_phong', 'tay_giun', 've_sinh', 'thay_nuoc', 'can', 'khac',
        'chet', 'hao_hut', 'nhap_them', 'sinh', 'chuyen_di', 'dieu_chinh',
    ],

    // Loại công việc định kỳ/việc cần làm (chỉ các loại "care")
    'task_types' => ['cho_an', 'thuoc', 'tiem_phong', 'tay_giun', 've_sinh', 'thay_nuoc', 'can', 'khac'],

    'cost_categories' => [
        'con_giong' => 'Con giống',
        'thuc_an' => 'Thức ăn',
        'thuoc' => 'Thuốc & vaccine',
        'vat_tu' => 'Vật tư',
        'khac' => 'Chi phí khác',
    ],

    'item_categories' => [
        'thuc_an' => ['label' => 'Thức ăn', 'icon' => 'fa-wheat-awn', 'cost' => 'thuc_an'],
        'thuoc' => ['label' => 'Thuốc', 'icon' => 'fa-pills', 'cost' => 'thuoc'],
        'vaccine' => ['label' => 'Vaccine', 'icon' => 'fa-syringe', 'cost' => 'thuoc'],
        'vat_tu' => ['label' => 'Vật tư', 'icon' => 'fa-box-open', 'cost' => 'vat_tu'],
        'khac' => ['label' => 'Khác', 'icon' => 'fa-cube', 'cost' => 'khac'],
    ],

    'expense_categories' => [
        'dien_nuoc' => 'Điện nước',
        'nhan_cong' => 'Nhân công',
        'mat_bang' => 'Thuê mặt bằng / chuồng',
        'thu_y' => 'Thú y',
        'van_chuyen' => 'Vận chuyển',
        'khac' => 'Khác',
    ],

    'species_groups' => [
        'gia_suc' => 'Gia súc',
        'gia_cam' => 'Gia cầm',
        'thuy_san' => 'Thuỷ sản',
        'thu_cung' => 'Thú cưng',
        'khac' => 'Khác',
    ],

    'frequencies' => [
        'daily' => 'Mỗi ngày',
        'weekly' => 'Mỗi tuần',
        'monthly' => 'Mỗi tháng',
        'interval' => 'Cách N ngày',
    ],

    'weekday_labels' => [1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5', 5 => 'T6', 6 => 'T7', 7 => 'CN'],

    // Số ngày tới mà công việc định kỳ được sinh sẵn (tạo lười khi mở app + lệnh cham:generate-tasks)
    'task_horizon_days' => 2,

    // Gợi ý loài để thêm nhanh ở màn Danh mục / lần đầu sử dụng
    'species_presets' => [
        ['key' => 'heo', 'name' => 'Heo', 'emoji' => '🐖', 'group' => 'gia_suc', 'sale_unit' => 'kg', 'breeds' => ['Landrace', 'Yorkshire', 'Duroc', 'Pietrain', 'Heo lai', 'Heo ta']],
        ['key' => 'bo', 'name' => 'Bò', 'emoji' => '🐄', 'group' => 'gia_suc', 'sale_unit' => 'kg', 'breeds' => ['Bò vàng', 'Bò lai Sind', 'Brahman', 'Bò sữa']],
        ['key' => 'de', 'name' => 'Dê', 'emoji' => '🐐', 'group' => 'gia_suc', 'sale_unit' => 'kg', 'breeds' => ['Dê cỏ', 'Boer', 'Bách Thảo']],
        ['key' => 'ga', 'name' => 'Gà', 'emoji' => '🐓', 'group' => 'gia_cam', 'sale_unit' => 'kg', 'breeds' => ['Gà ta', 'Gà ri', 'Gà lai', 'Gà công nghiệp', 'Gà tre']],
        ['key' => 'vit', 'name' => 'Vịt', 'emoji' => '🦆', 'group' => 'gia_cam', 'sale_unit' => 'kg', 'breeds' => ['Vịt cỏ', 'Vịt siêu thịt', 'Vịt biển']],
        ['key' => 'ngan', 'name' => 'Ngan', 'emoji' => '🦆', 'group' => 'gia_cam', 'sale_unit' => 'kg', 'breeds' => []],
        ['key' => 'ngong', 'name' => 'Ngỗng', 'emoji' => '🦢', 'group' => 'gia_cam', 'sale_unit' => 'kg', 'breeds' => []],
        ['key' => 'cut', 'name' => 'Chim cút', 'emoji' => '🐦', 'group' => 'gia_cam', 'sale_unit' => 'con', 'breeds' => []],
        ['key' => 'bocau', 'name' => 'Bồ câu', 'emoji' => '🕊️', 'group' => 'gia_cam', 'sale_unit' => 'con', 'breeds' => ['Bồ câu Pháp', 'Bồ câu ta']],
        ['key' => 'ca', 'name' => 'Cá', 'emoji' => '🐟', 'group' => 'thuy_san', 'sale_unit' => 'kg', 'breeds' => ['Cá rô phi', 'Cá điêu hồng', 'Cá tra', 'Cá trê', 'Cá lóc', 'Cá chép']],
        ['key' => 'tom', 'name' => 'Tôm', 'emoji' => '🦐', 'group' => 'thuy_san', 'sale_unit' => 'kg', 'breeds' => ['Tôm thẻ chân trắng', 'Tôm sú', 'Tôm càng xanh']],
        ['key' => 'tho', 'name' => 'Thỏ', 'emoji' => '🐇', 'group' => 'gia_suc', 'sale_unit' => 'kg', 'breeds' => []],
        ['key' => 'cho', 'name' => 'Chó', 'emoji' => '🐕', 'group' => 'thu_cung', 'sale_unit' => 'con', 'breeds' => ['Poodle', 'Corgi', 'Golden Retriever', 'Phú Quốc', 'Chó ta']],
        ['key' => 'meo', 'name' => 'Mèo', 'emoji' => '🐈', 'group' => 'thu_cung', 'sale_unit' => 'con', 'breeds' => ['Mèo ta', 'Anh lông ngắn', 'Ba Tư', 'Munchkin']],
        ['key' => 'cacanh', 'name' => 'Cá cảnh', 'emoji' => '🐠', 'group' => 'thu_cung', 'sale_unit' => 'con', 'breeds' => ['Cá koi', 'Cá betta', 'Cá vàng']],
        ['key' => 'chimcanh', 'name' => 'Chim cảnh', 'emoji' => '🦜', 'group' => 'thu_cung', 'sale_unit' => 'con', 'breeds' => []],
        ['key' => 'hamster', 'name' => 'Hamster', 'emoji' => '🐹', 'group' => 'thu_cung', 'sale_unit' => 'con', 'breeds' => []],
        ['key' => 'khac', 'name' => 'Vật nuôi khác', 'emoji' => '🐾', 'group' => 'khac', 'sale_unit' => 'con', 'breeds' => []],
    ],

    'accent_colors' => ['emerald' => 'Xanh lá', 'amber' => 'Vàng nghệ', 'orange' => 'Cam', 'blue' => 'Xanh dương', 'rose' => 'Hồng', 'violet' => 'Tím'],
];
