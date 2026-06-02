{{-- Стартовая: ссылки на задания и блок контактов --}}
@extends('layouts.app', [
    'title' => 'Тестовое задание — стартовая страница',
    'activePage' => 'contacts',
])

@section('content')
<div class="card">
    <h2>Задания</h2>
    <ul>
        <li><a href="{{ route('task1.index') }}">Задание 1: пузырьковая сортировка</a></li>
        <li><a href="{{ route('task2.index') }}">Задание 2: структура БД и SQL-выборки</a></li>
        <li><a href="{{ route('task3.index') }}">Задание 3: экспорт пользователей в CSV</a></li>
    </ul>
</div>

<div class="card" id="contacts">
    <h2>Контакты</h2>
    <ul class="contacts">
        <li><b>Резюме:</b> <a href="https://barnaul.hh.ru/resume/9eeca420ff0eb070140039ed1f337551516d6f?print=true" target="_blank" rel="noopener noreferrer">Открыть резюме на hh.ru</a></li>
        <li><b>Telegram:</b> <a href="https://t.me/sergey0002" target="_blank" rel="noopener noreferrer">@sergey0002</a></li>
        <li><b>Телефон:</b> <a href="tel:+79236470002">+7 923 647-00-02</a></li>
    </ul>
</div>
@endsection
