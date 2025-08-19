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
        'success' => [
            'color' => 'text-green-600',
            'btn'   => 'bg-green-600 hover:bg-green-700',
            'title' => 'إتمت عملية الدفع بنجاح',
        ],
        'cancel'  => [
            'color' => 'text-yellow-600',
            'btn'   => 'bg-yellow-600 hover:bg-yellow-700',
            'title' => 'تم إلغاء عملية الدفع',
            'msg'   => 'لقد قمت بإلغاء عملية الدفع. يمكنك المحاولة مرة أخرى لاحقاً.',
        ],
        'error'   => [
            'color' => 'text-red-600',
            'btn'   => 'bg-red-600 hover:bg-red-700',
            'title' => 'فشلت عملية الدفع',
            'msg'   => 'حدث خطأ أثناء معالجة الدفع. يرجى المحاولة مرة أخرى لاحقاً.',
        ],
    ];
    $status = $status ?? 'success'; // القيمة الافتراضية نجاح
@endphp

<div class="bg-white shadow-lg rounded-2xl p-16 text-center max-w-2xl w-full">
    <h2 class="{{ $styles[$status]['color'] }} text-4xl font-extrabold mb-8">
        {{ $styles[$status]['title'] }}
    </h2>
    <p class="text-gray-700 text-2xl mb-10">
        {{ $message }}
    </p>

    <a href="http://localhost:5173/home"
       class="px-8 py-4 text-lg text-white font-bold rounded-xl transition-all duration-300 {{ $styles[$status]['btn'] }} shadow-md">
        العودة ل Adventra
    </a>
</div>

</body>
</html>
