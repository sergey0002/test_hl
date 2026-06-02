{{-- Общий каркас страниц: CSS из public/assets, меню сверху --}}
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Тестовое задание' }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>
<body @if(($bodyClass ?? '') !== '') class="{{ $bodyClass }}" @endif>
@include('partials.top-nav', ['activePage' => $activePage ?? ''])

@yield('content')
</body>
</html>
