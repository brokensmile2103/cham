<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $seoTitle = 'Chăm — Sổ chăn nuôi trên điện thoại: lứa nuôi, kho, việc hằng ngày và lời lỗ';
        $seoDescription = 'Chăm giúp trại nhỏ, hộ gia đình và người nuôi thú cưng ghi nhật ký chăm sóc, tự trừ kho thức ăn – thuốc, nhắc việc định kỳ, xuất bán theo kg, theo con hay trọn lô và biết từng lứa lời hay lỗ. Heo, gà, vịt, cá, tôm, bò, dê, chó mèo... Đăng ký miễn phí.';
        $seoImage = asset('og-image.png');
        $seoUrl = url()->current();
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $seoUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Chăm">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoUrl }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <meta name="theme-color" content="#1f3a2b">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400..800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /*
         * Chăm — trang giới thiệu. Ý tưởng: "sổ trại giữa đồng". Xanh ruộng đậm làm nền mở đầu, vàng rơm
         * cho hành động, trang sổ trắng cho số liệu. Một họ chữ duy nhất — Archivo — dùng độ rộng: tiêu đề
         * bản rộng, đậm (chắc như chữ in trên bao cám), nội dung bản thường.
         */
        .c-page {
            --field: #1f3a2b;
            --field-2: #2b4d3b;
            --field-ink: #dfe8df;
            --straw: #e0b23f;
            --straw-dark: #c6982a;
            --sage: #eef1ea;
            --paper: #ffffff;
            --text: #1c2a22;
            --muted: #586a5f;
            --rule: #d8ded5;
            --soil: #7a4e2d;
            --loss: #b3412e;
            --gain: #2e7d4a;
            font-family: 'Archivo', ui-sans-serif, system-ui, sans-serif;
            font-stretch: 100%;
            color: var(--text);
            background: var(--sage);
            line-height: 1.6;
        }
        .c-page *:focus-visible { outline: 3px solid var(--straw); outline-offset: 3px; border-radius: 4px; }
        .c-wide { font-stretch: 125%; font-weight: 800; letter-spacing: -0.02em; line-height: 1.04; }
        .c-wrap { max-width: 1160px; margin: 0 auto; padding-left: 20px; padding-right: 20px; }
        .c-num { font-variant-numeric: tabular-nums; }

        .c-btn { display: inline-flex; align-items: center; gap: 10px; padding: 15px 24px; border-radius: 10px; font-weight: 700; text-decoration: none; transition: background-color .15s; }
        .c-btn-straw { background: var(--straw); color: var(--field) !important; }
        .c-btn-straw:hover { background: #ebc35c; }
        .c-btn-sm { padding: 9px 15px; font-size: .9rem; }

        /* HERO tối */
        .c-hero-band { background: var(--field); color: var(--field-ink); }
        .c-head { display: flex; align-items: center; height: 76px; gap: 14px; }
        .c-logo { display: inline-flex; align-items: center; gap: 10px; color: #fff; text-decoration: none; }
        .c-logo i { width: 36px; height: 36px; border-radius: 10px; background: var(--straw); color: var(--field); display: grid; place-items: center; }
        .c-logo b { font-size: 1.5rem; }
        .c-head nav { margin-left: auto; display: flex; align-items: center; gap: 24px; white-space: nowrap; font-size: .95rem; }
        .c-head nav a { color: var(--field-ink); text-decoration: none; }
        .c-head nav a:hover { color: #fff; }
        .c-hide-sm { display: none; }
        @media (min-width: 760px) { .c-hide-sm { display: inline; } }
        @media (max-width: 400px) { .c-head nav { gap: 14px; font-size: .88rem; } }

        .c-hero { display: grid; gap: 52px; padding-top: 36px; padding-bottom: 88px; align-items: center; }
        @media (min-width: 980px) { .c-hero { grid-template-columns: 1.05fr .95fr; gap: 64px; padding-top: 56px; padding-bottom: 112px; } }
        .c-hero h1 { color: #fff; font-size: clamp(2.2rem, 4.8vw, 3.7rem); max-width: 16ch; text-wrap: balance; }
        .c-lede { margin-top: 24px; font-size: 1.15rem; max-width: 48ch; color: var(--field-ink); }
        .c-cta { margin-top: 34px; display: flex; flex-wrap: wrap; align-items: center; gap: 14px 24px; }
        .c-cta-link { color: #fff !important; font-weight: 600; text-decoration: underline; text-decoration-color: var(--straw); text-underline-offset: 5px; text-decoration-thickness: 2px; }

        /* Sổ lứa */
        .c-ledger { background: var(--paper); color: var(--text); border-radius: 6px; padding: 26px 26px 22px; box-shadow: 0 30px 60px -30px rgba(0, 0, 0, .55); position: relative; }
        .c-ledger::before { /* mép sổ */ content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 6px; background: var(--straw); border-radius: 6px 0 0 6px; }
        .c-ledger-head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; padding-bottom: 14px; border-bottom: 2px solid var(--text); }
        .c-ledger-head h2 { font-size: 1.3rem; }
        .c-ledger-head p { font-size: .86rem; color: var(--muted); margin-top: 2px; }
        .c-ledger-head span { font-size: .78rem; font-weight: 600; background: #e3efe6; color: var(--gain); border-radius: 99px; padding: 4px 10px; white-space: nowrap; }
        .c-ledger table { width: 100%; border-collapse: collapse; font-size: .93rem; margin-top: 6px; }
        .c-ledger th { text-align: left; font-weight: 600; font-size: .78rem; color: var(--muted); padding: 12px 0 4px; }
        .c-ledger td { padding: 6px 0; border-bottom: 1px solid #edf0ec; vertical-align: top; }
        .c-ledger td small { display: block; color: var(--muted); font-size: .8rem; }
        .c-ledger td:last-child { text-align: right; white-space: nowrap; padding-left: 12px; }
        .c-ledger .c-sum td { font-weight: 700; border-bottom: 0; border-top: 1px solid var(--text); }
        .c-profit { margin-top: 14px; display: flex; justify-content: space-between; align-items: baseline; gap: 12px; background: var(--field); color: #fff; border-radius: 6px; padding: 14px 16px; }
        .c-profit { flex-wrap: wrap; }
        .c-profit span { white-space: nowrap; }
        .c-profit b { font-size: clamp(1.3rem, 5.4vw, 1.6rem); color: var(--straw); }
        @media (max-width: 480px) { .c-ledger { padding: 22px 18px 18px 22px; } }
        .c-ledger-foot { margin-top: 10px; font-size: .84rem; color: var(--muted); display: flex; flex-wrap: wrap; gap: 4px 18px; }

        /* Section chung */
        .c-sec { padding-top: 72px; padding-bottom: 72px; }
        @media (min-width: 768px) { .c-sec { padding-top: 104px; padding-bottom: 104px; } }
        .c-white { background: var(--paper); }
        .c-h2 { font-size: clamp(1.9rem, 4vw, 3rem); color: var(--field); max-width: 20ch; }
        .c-sub { margin-top: 16px; color: var(--muted); font-size: 1.08rem; max-width: 58ch; }

        /* Vòng nuôi */
        .c-cycle { margin-top: 52px; display: grid; gap: 0; }
        @media (min-width: 980px) { .c-cycle { grid-template-columns: repeat(5, 1fr); } }
        .c-step { position: relative; padding: 0 0 32px 34px; border-left: 3px solid var(--field); }
        .c-step::before { content: ''; position: absolute; left: -9px; top: 4px; width: 15px; height: 15px; border-radius: 50%; background: var(--straw); border: 3px solid var(--field); }
        .c-step:last-child { border-left-color: transparent; }
        @media (min-width: 980px) {
            .c-step { padding: 34px 24px 0 0; border-left: 0; border-top: 3px solid var(--field); }
            .c-step::before { left: 0; top: -9px; }
            .c-step:last-child { border-top-color: var(--field); }
            .c-step:last-child::after { /* mũi tên quay vòng: bán xong lại nhập lứa mới */
                content: '\21BA'; position: absolute; right: 0; top: -21px; font-size: 1.4rem; color: var(--field); background: var(--paper); padding-left: 6px; line-height: 1;
            }
        }
        .c-step h3 { font-size: 1.15rem; color: var(--field); font-stretch: 112%; font-weight: 750; }
        .c-step p { margin-top: 6px; color: var(--muted); font-size: .96rem; }

        /* Hôm nay + kho */
        .c-board { margin-top: 48px; display: grid; gap: 20px; }
        @media (min-width: 980px) { .c-board { grid-template-columns: 1.1fr .9fr; gap: 28px; } }
        .c-panel { background: var(--paper); border-radius: 8px; padding: 22px; border: 1px solid var(--rule); }
        .c-panel-title { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--rule); }
        .c-panel-title b { font-weight: 700; color: var(--field); font-size: 1.05rem; }
        .c-panel-title span { color: var(--muted); font-size: .85rem; }
        .c-task { display: grid; grid-template-columns: 54px 1fr auto; gap: 14px; align-items: center; padding: 13px 0; border-bottom: 1px solid #eef1ec; }
        .c-task time { font-weight: 700; color: var(--soil); }
        .c-task b { font-weight: 600; display: block; }
        .c-task small { color: var(--muted); font-size: .84rem; }
        .c-task em { font-style: normal; font-size: .8rem; font-weight: 600; border-radius: 6px; padding: 5px 10px; background: var(--field); color: #fff; }
        .c-task.is-done { color: var(--muted); }
        .c-task.is-done b { text-decoration: line-through; }
        .c-task.is-done em { background: #e3efe6; color: var(--gain); }
        .c-task.is-late time { color: var(--loss); }
        .c-panel-note { margin-top: 14px; font-size: .88rem; color: var(--muted); display: flex; gap: 10px; }
        .c-panel-note i { color: var(--soil); margin-top: 4px; }
        .c-stock { padding: 13px 0; border-bottom: 1px solid #eef1ec; }
        .c-stock div { display: flex; justify-content: space-between; gap: 10px; font-size: .95rem; }
        .c-stock div span:last-child { color: var(--muted); }
        .c-days { margin-top: 8px; display: grid; grid-template-columns: repeat(14, 1fr); gap: 3px; }
        .c-days i { height: 9px; border-radius: 2px; background: #e6ebe4; }
        .c-days i.on { background: var(--field-2); }
        .c-stock.is-low .c-days i.on { background: var(--loss); }
        .c-stock.is-low div span:last-child { color: var(--loss); font-weight: 600; }

        /* Nuôi gì cũng được — khoảnh khắc chữ lớn */
        .c-species { margin-top: 44px; display: grid; gap: 22px; }
        .c-species div { display: grid; gap: 4px; padding-top: 18px; border-top: 1px solid var(--rule); }
        @media (min-width: 860px) { .c-species div { grid-template-columns: 180px 1fr; gap: 24px; align-items: baseline; } }
        .c-species dt { color: var(--muted); font-size: .95rem; }
        .c-species dd { font-stretch: 125%; font-weight: 800; color: var(--field); font-size: clamp(1.5rem, 3.6vw, 2.5rem); line-height: 1.15; letter-spacing: -0.02em; }
        .c-species dd span { display: inline-block; margin-right: .55em; }
        .c-kind { margin-top: 44px; display: grid; gap: 24px; }
        @media (min-width: 860px) { .c-kind { grid-template-columns: 1fr 1fr; gap: 48px; } }
        .c-kind h3 { font-weight: 700; color: var(--field); font-size: 1.12rem; }
        .c-kind p { margin-top: 6px; color: var(--muted); }

        /* Vai trò */
        .c-roles { margin-top: 44px; display: grid; gap: 28px; }
        @media (min-width: 860px) { .c-roles { grid-template-columns: repeat(3, 1fr); gap: 40px; } }
        .c-roles h3 { font-weight: 700; font-size: 1.12rem; color: var(--field); display: flex; gap: 10px; align-items: center; }
        .c-roles h3 i { color: var(--soil); }
        .c-roles p { margin-top: 6px; color: var(--muted); }

        /* Kết */
        .c-end { background: var(--field); color: var(--field-ink); }
        .c-end h2 { color: #fff; font-size: clamp(2.2rem, 5.4vw, 4rem); max-width: 16ch; }
        .c-end p { margin-top: 18px; max-width: 52ch; font-size: 1.08rem; }

        .c-foot { background: var(--field); color: #a9bcae; border-top: 1px solid var(--field-2); padding: 28px 0 40px; font-size: .9rem; }
        .c-foot .c-wrap { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 14px 32px; align-items: center; }
        .c-foot nav { display: flex; flex-wrap: wrap; gap: 8px 24px; }
        .c-foot a { color: #cfdcd2; text-decoration: none; }
        .c-foot a:hover { color: #fff; text-decoration: underline; }

        /* Chuyển động duy nhất: dòng "Lãi tạm tính" hiện sau cùng khi sổ được "cộng" xong. */
        @media (prefers-reduced-motion: no-preference) {
            .c-profit { animation: c-sum .6s ease-out .9s both; }
            @keyframes c-sum { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        }
    </style>
    <script type="application/ld+json">
    {!! json_encode(['@context' => 'https://schema.org', '@type' => 'SoftwareApplication', 'name' => 'Chăm', 'applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Web, Android, iOS', 'inLanguage' => 'vi', 'url' => $seoUrl, 'description' => $seoDescription, 'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'VND']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</head>
<body class="c-page antialiased">

<div class="c-hero-band">
    <header class="c-wrap c-head">
        <a href="{{ route('home') }}" class="c-logo" aria-label="Chăm — trang chủ"><i class="fa-solid fa-paw" aria-hidden="true"></i><b class="c-wide">Chăm</b></a>
        <nav aria-label="Điều hướng chính">
            <a href="#vong-nuoi" class="c-hide-sm">Vòng nuôi</a>
            <a href="#nuoi-gi" class="c-hide-sm">Nuôi gì</a>
            <a href="{{ route('login') }}">Đăng nhập</a>
            @if(\App\Models\PlatformSetting::registrationOpen())
                <a href="{{ route('register') }}" class="c-btn c-btn-straw c-btn-sm">Dùng miễn phí</a>
            @endif
        </nav>
    </header>

    {{-- HERO — giá trị cốt lõi: biết từng lứa lời hay lỗ, bằng chính cuốn sổ lứa Chăm tự cộng. --}}
    <section class="c-wrap c-hero">
        <div>
            <h1 class="c-wide">Lứa này lời hay lỗ, mở điện thoại là biết.</h1>
            <p class="c-lede">Chăm là sổ chăn nuôi cho trại nhỏ, hộ gia đình và người nuôi thú cưng. Ghi cho ăn, tiêm phòng, xuất bán — kho tự trừ, chi phí tự cộng vào từng lứa.</p>
            <div class="c-cta">
                @if(\App\Models\PlatformSetting::registrationOpen())
                    <a href="{{ route('register') }}" class="c-btn c-btn-straw"><i class="fa-solid fa-seedling" aria-hidden="true"></i>Tạo trại miễn phí</a>
                    <a href="{{ route('login') }}" class="c-cta-link">Tôi đã có tài khoản</a>
                @else
                    <a href="{{ route('login') }}" class="c-btn c-btn-straw">Đăng nhập</a>
                @endif
            </div>
        </div>

        <article class="c-ledger" aria-label="Ví dụ sổ lời lỗ của một lứa heo thịt">
            <div class="c-ledger-head">
                <div>
                    <h2 class="c-wide">Lứa heo thịt L2</h2>
                    <p>Chuồng B, nhập 45 con ngày 02/05</p>
                </div>
                <span>Đã bán xong</span>
            </div>
            <table class="c-num">
                <thead><tr><th scope="col">Chi phí</th><th scope="col" style="text-align:right">Số tiền</th></tr></thead>
                <tbody>
                    <tr><td>Con giống<small>45 con × 1.600.000đ</small></td><td>72.000.000</td></tr>
                    <tr><td>Thức ăn<small>12,4 tấn cám, tự trừ từ kho</small></td><td>142.600.000</td></tr>
                    <tr><td>Thuốc & vaccine</td><td>6.300.000</td></tr>
                    <tr><td>Điện nước, thú y</td><td>8.100.000</td></tr>
                    <tr class="c-sum"><td>Tổng chi</td><td>229.000.000</td></tr>
                </tbody>
                <tbody>
                    <tr><th scope="row" colspan="2">Doanh thu</th></tr>
                    <tr><td>Xuất bán 3 đợt<small>43 con, 4.690 kg × 58.000đ</small></td><td>272.020.000</td></tr>
                </tbody>
            </table>
            <div class="c-profit"><span>Lãi của lứa</span><b class="c-wide c-num">+43.020.000đ</b></div>
            <p class="c-ledger-foot c-num"><span>Giá thành 48.800đ/kg</span><span>Hao hụt 2 con (4,4%)</span><span>Lãi 1.000.000đ/con bán</span></p>
        </article>
    </section>
</div>

<main>
    {{-- VÒNG NUÔI — trình tự thật của một lứa, có mũi tên quay vòng vì bán xong lại nhập lứa mới. --}}
    <section id="vong-nuoi" class="c-sec c-white">
        <div class="c-wrap">
            <h2 class="c-wide c-h2">Một vòng nuôi, chỉ ghi một lần.</h2>
            <p class="c-sub">Mọi thứ đi ra từ nhật ký: ghi cho ăn là kho giảm và chi phí của lứa tăng, ghi xuất bán là số con giảm và doanh thu tăng. Không phải cộng sổ cuối tháng.</p>
            <ol class="c-cycle">
                <li class="c-step"><h3>Nhập con giống</h3><p>Loài, giống, số con, giá nhập, chuồng. Nuôi theo lứa, hoặc từng con với thú cưng.</p></li>
                <li class="c-step"><h3>Chăm sóc hằng ngày</h3><p>Cho ăn, thuốc, tiêm phòng, tẩy giun, thay nước, cân. Chọn nhiều lứa một lần, chi phí chia theo số con.</p></li>
                <li class="c-step"><h3>Việc tự nhắc</h3><p>Đặt lịch một lần — mỗi ngày, mỗi tuần, cách N ngày. Người làm bấm "Xong" là nhật ký và kho tự cập nhật.</p></li>
                <li class="c-step"><h3>Xuất bán</h3><p>Bán nhiều đợt, theo kg, theo con hoặc trọn lô. Chết, hao hụt, chuyển chuồng đều ghi lại.</p></li>
                <li class="c-step"><h3>Lời lỗ</h3><p>Giá thành, lãi trên mỗi con, tỉ lệ hao hụt cho từng lứa và cả trại. Tải báo cáo về mở bằng Excel.</p></li>
            </ol>
        </div>
    </section>

    {{-- HÔM NAY + KHO --}}
    <section class="c-sec">
        <div class="c-wrap">
            <h2 class="c-wide c-h2">Sáng ra biết ngay việc hôm nay, kho còn bao lâu.</h2>
            <p class="c-sub">Màn Hôm nay gom việc cần làm, việc quá hạn và vật tư sắp hết. Kho tính giá vốn bình quân và ước lượng còn đủ dùng bao nhiêu ngày theo mức dùng gần đây.</p>
            <div class="c-board">
                <div class="c-panel">
                    <div class="c-panel-title"><b>Hôm nay ở Trại Ba Tèo</b><span>Thứ ba 13/10</span></div>
                    @foreach([
                        ['06:30', 'Vệ sinh chuồng heo', 'Chuồng A, B', 'Xong', 'is-done'],
                        ['07:00', 'Cho heo thịt ăn', 'Lứa L2, L3 · 3,5 bao cám', 'Xong', 'is-done'],
                        ['09:30', 'Tiêm Gumboro gà lứa G5', '420 con · vaccine trong kho', 'Làm', ''],
                        ['14:00', 'Thay nước ao cá', 'Ao 1 · trễ từ hôm qua', 'Làm', 'is-late'],
                        ['16:00', 'Cân mẫu heo lứa L3', '10 con đại diện', 'Làm', ''],
                    ] as [$t, $title, $meta, $btn, $cls])
                        <div class="c-task {{ $cls }}"><time>{{ $t }}</time><span><b>{{ $title }}</b><small>{{ $meta }}</small></span><em>{{ $btn }}</em></div>
                    @endforeach
                    <p class="c-panel-note"><i class="fa-solid fa-user-group" aria-hidden="true"></i><span>Người làm chỉ thấy việc và nhật ký, không thấy tiền bạc.</span></p>
                </div>
                <div class="c-panel">
                    <div class="c-panel-title"><b>Kho</b><span>còn đủ dùng</span></div>
                    @foreach([
                        ['Cám heo thịt', '~12 ngày', 12, false],
                        ['Cám gà con', '~3 ngày, nên nhập thêm', 3, true],
                        ['Vaccine Gumboro', '~9 ngày', 9, false],
                        ['Men vi sinh xử lý ao', '~14 ngày', 14, false],
                    ] as [$name, $left, $days, $low])
                        <div class="c-stock {{ $low ? 'is-low' : '' }}">
                            <div><span>{{ $name }}</span><span>{{ $left }}</span></div>
                            <p class="c-days" aria-hidden="true">@for($i = 1; $i <= 14; $i++)<i class="{{ $i <= $days ? 'on' : '' }}"></i>@endfor</p>
                        </div>
                    @endforeach
                    <p class="c-panel-note"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i><span>Kiểm kê lệch thì sửa một lần, mọi thay đổi đều có lịch sử.</span></p>
                </div>
            </div>
        </div>
    </section>

    {{-- NUÔI GÌ --}}
    <section id="nuoi-gi" class="c-sec c-white">
        <div class="c-wrap">
            <h2 class="c-wide c-h2">Nuôi con gì cũng có chỗ ghi.</h2>
            <dl class="c-species">
                <div><dt>Gia súc</dt><dd><span>Heo</span><span>Bò</span><span>Dê</span><span>Thỏ</span></dd></div>
                <div><dt>Gia cầm</dt><dd><span>Gà</span><span>Vịt</span><span>Ngan</span><span>Ngỗng</span><span>Chim cút</span><span>Bồ câu</span></dd></div>
                <div><dt>Thuỷ sản</dt><dd><span>Cá</span><span>Tôm</span></dd></div>
                <div><dt>Thú cưng</dt><dd><span>Chó</span><span>Mèo</span><span>Cá cảnh</span><span>Chim cảnh</span><span>Hamster</span></dd></div>
            </dl>
            <div class="c-kind">
                <div>
                    <h3>Theo lứa, cho đàn đông</h3>
                    <p>Vài chục đến vài nghìn con chung một lứa: theo dõi số con còn lại, chết, hao hụt, chuyển chuồng, bán nhiều đợt. Lứa bán hết con tự kết thúc.</p>
                </div>
                <div>
                    <h3>Từng con, cho thú cưng</h3>
                    <p>Mỗi bé một hồ sơ: giống, ngày sinh, lịch tiêm, tẩy giun, cân nặng và chi phí đã bỏ ra.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- VAI TRÒ --}}
    <section class="c-sec">
        <div class="c-wrap">
            <h2 class="c-wide c-h2">Chủ trại nắm tiền, người làm nắm việc.</h2>
            <div class="c-roles">
                <div>
                    <h3><i class="fa-solid fa-house-chimney" aria-hidden="true"></i>Chủ trại</h3>
                    <p>Toàn quyền: lứa nuôi, kho, chi phí, xuất bán, báo cáo lời lỗ. Có nhiều trại vẫn dùng một tài khoản, chuyển qua lại một chạm.</p>
                </div>
                <div>
                    <h3><i class="fa-solid fa-person-digging" aria-hidden="true"></i>Người làm</h3>
                    <p>Mời bằng số điện thoại. Xem việc hôm nay, ghi nhật ký, bấm "Xong" — không thấy giá nhập, doanh thu hay lãi.</p>
                </div>
                <div>
                    <h3><i class="fa-solid fa-file-export" aria-hidden="true"></i>Dữ liệu của bạn</h3>
                    <p>Tải toàn bộ dữ liệu của trại về máy bất cứ lúc nào. Không cần máy chủ, không cần cài đặt — thêm vào màn hình chính điện thoại như một ứng dụng.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- KẾT --}}
    <section class="c-sec c-end">
        <div class="c-wrap">
            <h2 class="c-wide">Ghi lứa đầu tiên ngay chiều nay.</h2>
            <p>Đăng ký mất một phút. Nhập lứa đang nuôi, thêm vài món trong kho, đặt lịch cho ăn — từ ngày mai Chăm nhắc việc và cộng sổ giúp bạn.</p>
            <div class="c-cta">
                @if(\App\Models\PlatformSetting::registrationOpen())
                    <a href="{{ route('register') }}" class="c-btn c-btn-straw"><i class="fa-solid fa-seedling" aria-hidden="true"></i>Tạo trại miễn phí</a>
                    <a href="{{ route('login') }}" class="c-cta-link">Đăng nhập</a>
                @else
                    <a href="{{ route('login') }}" class="c-btn c-btn-straw">Đăng nhập</a>
                @endif
            </div>
        </div>
    </section>
</main>

<footer class="c-foot">
    <div class="c-wrap">
        <p>© {{ date('Y') }} Chăm — nuôi chăm chỉ, chăm sóc chu đáo.</p>
        <nav aria-label="Liên kết cuối trang">
            <a href="{{ route('pages.about') }}">Giới thiệu</a>
            <a href="{{ route('pages.terms') }}">Điều khoản sử dụng</a>
            <a href="{{ route('pages.privacy') }}">Chính sách bảo mật</a>
            <a href="{{ route('login') }}">Đăng nhập</a>
        </nav>
    </div>
</footer>
</body>
</html>
