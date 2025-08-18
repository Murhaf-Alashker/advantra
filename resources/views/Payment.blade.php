<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حالة الدفع</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

@php
    $styles = [
        'success' => ['color' => 'text-green-600', 'title' => 'إتمت عملية الدفع بنجاح',],
        'cancel'  => ['color' => 'text-yellow-600', 'title' => 'تم إلغاء عملية الدفع', 'msg' => 'لقد قمت بإلغاء عملية الدفع. يمكنك المحاولة مرة أخرى لاحقاً.'],
        'error'   => ['color' => 'text-red-600', 'title' => 'فشلت عملية الدفع', 'msg' => 'حدث خطأ أثناء معالجة الدفع. يرجى المحاولة مرة أخرى لاحقاً.'],
    ];
    $status = $status ?? 'success'; // القيمة الافتراضية نجاح
@endphp

<div class="bg-white shadow-lg rounded-2xl p-12 text-center max-w-2xl">
    <h2 class="{{ $styles[$status]['color'] }} text-3xl font-extrabold mb-6">
        {{ $styles[$status]['title'] }}
    </h2>
    <p class="text-gray-700 text-xl">
        {{ $message }}
    </p>
</div>

</body>
</html>
