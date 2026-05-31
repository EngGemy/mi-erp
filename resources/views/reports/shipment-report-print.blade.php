<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تقرير الحمولات — {{ $project->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Cairo', sans-serif; }
        body { margin: 0; padding: 1.5rem; color: #111; direction: rtl; }
        h1 { font-size: 1.25rem; margin: 0 0 0.25rem; }
        .meta { font-size: 0.875rem; color: #555; margin-bottom: 1.25rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; margin-bottom: 1.5rem; }
        th, td { border: 1px solid #ccc; padding: 0.5rem 0.625rem; text-align: right; }
        th { background: #222; color: #fff; }
        .num { text-align: center; }
        .section-title { font-weight: 700; margin: 1rem 0 0.5rem; font-size: 0.9375rem; }
        .items-table th { background: #444; }
        @media print {
            body { padding: 0.5rem; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()" style="margin-bottom:1rem;padding:0.5rem 1rem;cursor:pointer;">طباعة / حفظ PDF</button>

    <h1>تقرير حمولات المشروع: {{ $project->name }}</h1>
    <div class="meta">
        الكود: {{ $project->code }} —
        عدد الحمولات: {{ count($shipments) }} —
        إجمالي المُسلّم: {{ number_format($projectTotalDelivered) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>اسم الحمولة</th>
                <th>التاريخ</th>
                <th>السائق</th>
                <th>السيارة</th>
                <th>المسؤول</th>
                <th>موعد الوصول</th>
                <th class="num">عدد الأصناف</th>
                <th class="num">إجمالي الكمية</th>
                <th class="num">نسبة المساهمة %</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($shipments as $sh)
                <tr>
                    <td>{{ $sh['name'] }}</td>
                    <td>{{ $sh['shipped_at'] ?? '—' }}</td>
                    <td>{{ $sh['driver_name'] ?? '—' }}</td>
                    <td>{{ $sh['vehicle_no'] ?? '—' }}</td>
                    <td>{{ $sh['responsible'] ?? '—' }}</td>
                    <td>{{ $sh['arrival_time'] ?? '—' }}</td>
                    <td class="num">{{ $sh['items_count'] }}</td>
                    <td class="num">{{ number_format($sh['total_qty']) }}</td>
                    <td class="num">{{ $sh['contribution_pct'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @foreach ($shipments as $sh)
        @if (count($sh['items']) > 0)
            <div class="section-title">{{ $sh['name'] }} — تفاصيل الأصناف</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>الكود</th>
                        <th>بيان الصنف</th>
                        <th class="num">الكمية</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sh['items'] as $item)
                        <tr>
                            <td>{{ $item['code'] }}</td>
                            <td>{{ $item['name'] }}</td>
                            <td class="num">{{ number_format($item['quantity']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
</body>
</html>
