{{-- Задание 2: дамп, схема БД, SQL-редактор и пресеты запросов --}}
@extends('layouts.app', [
    'title' => 'Задание 2 — презентация БД',
    'activePage' => 'task2',
])

@section('content')
@php
    $schemaImagePath = public_path('assets/img/shem.jpg');
    $hasSchemaImage = is_file($schemaImagePath);
@endphp

<section class="card task2-result-card">
    <h2>Итоговый результат задания №2</h2>
    <p class="muted">
        Выгрузка из БД: структура таблиц и актуальные данные формируются динамически при скачивании
        (клубы, сезоны, игроки, связи <code>player_season_club</code>).
    </p>
    <div class="task2-result-actions">
        <a class="dump-download-btn" href="{{ route('task2.dump') }}">Скачать дамп БД (SQL)</a>
    </div>

    <div class="db-schema-slot">
        <div class="db-schema-title">Схема базы данных</div>
        @if ($hasSchemaImage)
            <img
                src="{{ asset('assets/img/shem.jpg') }}"
                alt="Схема связей таблиц задания №2"
                class="db-schema-image"
            >
        @else
            <div class="db-schema-placeholder">
                <p>Место для изображения схемы БД</p>
                <p class="muted">Положите файл <code>public/assets/img/shem.jpg</code> — он отобразится здесь автоматически.</p>
            </div>
        @endif
    </div>
</section>

<h1>Презентация БД для задания №2</h1>
<p class="muted">
    SQL-консоль для демонстрации структуры БД и выборок. Выберите готовый запрос, при необходимости отредактируйте
    или вставьте свой SQL и нажмите «Выполнить запрос».
</p>

<div class="layout layout-task2">
    <div class="card">
        <h2>Меню выборок</h2>
        <div class="menu-list">
            @foreach ($queries as $key => $item)
                <button
                    type="button"
                    class="menu-btn{{ $selectedKey === $key ? ' active' : '' }}"
                    data-key="{{ $key }}"
                    data-sql="{{ $item['sql'] }}"
                >
                    <strong>{{ $item['title'] }}</strong>
                    @if (!empty($item['description']))
                        <div class="muted" style="margin-top: 4px; font-size: 13px; line-height: 1.35;">
                            {{ $item['description'] }}
                        </div>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
    <div class="card">
        <h2>SQL редактор и выполнение</h2>
        <form method="post" action="{{ route('task2.query') }}" id="sqlForm">
            @csrf
            <input type="hidden" name="preset" id="presetInput" value="{{ $selectedKey }}">
            <textarea name="sql" id="sqlInput" class="sql-editor">{{ $sqlInput }}</textarea>
            <div class="actions">
                <button type="submit" class="run-btn">Выполнить запрос</button>
            </div>
        </form>
    </div>
</div>

@if ($execMessage !== null)
    <div class="card">
        <div class="{{ str_starts_with($execMessage, 'Ошибка') ? 'err' : 'ok' }}">{{ $execMessage }}</div>
        <h2 style="margin-top: 12px;">Результат выполнения</h2>
        @if (count($resultRows) === 0)
            <p class="empty">Нет данных для отображения.</p>
        @else
            <table>
                <thead>
                <tr>
                    @foreach (array_keys($resultRows[0]) as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach ($resultRows as $row)
                    <tr>
                        @foreach ($row as $value)
                            <td>{{ $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endif

<script>
const presetInput = document.getElementById('presetInput');
const sqlInput = document.getElementById('sqlInput');
const menuButtons = document.querySelectorAll('.menu-btn');
menuButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
        presetInput.value = btn.dataset.key;
        sqlInput.value = btn.dataset.sql || '';
        menuButtons.forEach((item) => item.classList.remove('active'));
        btn.classList.add('active');
    });
});
</script>
@endsection
