{{-- Верхнее меню: как в legacy, единый блок на всех страницах --}}
@php
    $activePage = $activePage ?? '';
    $items = [
        ['key' => 'contacts', 'label' => 'Контакты', 'href' => '/#contacts'],
        ['key' => 'task1', 'label' => 'Задание 1: Сортировка', 'href' => '/task1'],
        ['key' => 'task2', 'label' => 'Задание 2: БД и выборки', 'href' => '/task2'],
        ['key' => 'task3', 'label' => 'Задание 3: Экспорт CSV', 'href' => '/export'],
    ];
@endphp
<nav class="top-nav">
    @foreach ($items as $item)
        @php
            $classes = [];
            if ($item['key'] === $activePage) {
                $classes[] = 'active';
            }
            if ($item['key'] === 'contacts') {
                $classes[] = 'contacts-link';
            }
            $classAttr = $classes !== [] ? ' class="'.implode(' ', $classes).'"' : '';
        @endphp
        <a href="{{ $item['href'] }}"{!! $classAttr !!}>{{ $item['label'] }}</a>
    @endforeach
</nav>
