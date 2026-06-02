{{-- Задание 1: UI как в legacy — textarea, радиокнопки, полный вывод результата --}}
@extends('layouts.app', [
    'title' => 'Задание 1 — пузырьковая сортировка',
    'activePage' => 'task1',
])

@section('content')
<div class="card">
    <h1>Задание 1 — пузырьковая сортировка</h1>
    <p>
        Интерактивная презентация алгоритма: слева пошаговое описание и ввод пользовательского массива,
        справа исходный PHP-код с подсветкой.
    </p>
    <div class="layout">
        <div>
            <div class="card">
                <h2>  описание алгоритма</h2>
                <ol class="algo-list">
                    <li>Берем массив чисел и сортируем его по возрастанию.</li>
                    <li>Внешний цикл задает количество проходов по массиву.</li>
                    <li>Внутренний цикл сравнивает соседние элементы <code>items[j]</code> и <code>items[j+1]</code>.</li>
                    <li>Если левый элемент больше правого, меняем их местами.</li>
                    <li>После каждого прохода максимальный элемент "всплывает" в конец.</li>
                    <li>С каждым новым проходом конец массива исключается из проверок.</li>
                    <li>Если за проход не было ни одного обмена, массив уже отсортирован — выходим раньше.</li>
                    <li>Алгоритм работает <code>in-place</code>: дополнительный массив не создается.</li>
                </ol>
            </div>

            <div class="card">
                <h2>Ввод исходных данных</h2>
                <form method="post" action="{{ route('task1.sort') }}">
                    @csrf
                    <label for="numbers">Введите свой массив чисел (через запятую/пробел/перенос):</label>
                    <textarea id="numbers" name="numbers" placeholder="Пример: 15, 23, 1, -234, 400, 92">{{ $rawInput }}</textarea>
                    <div class="algo-choice">
                        <b>Алгоритм:</b>
                        <label>
                            <input type="radio" name="algorithm" value="bubble" @checked($algorithm === 'bubble')>
                            Пузырек (демо)
                        </label>
                        <label>
                            <input type="radio" name="algorithm" value="prod" @checked($algorithm === 'prod')>
                            Продакшен (нативный sort)
                        </label>
                    </div>
                    <div class="algo-note">
                        Для режима "Пузырек" на 200 000 случайных значений выполнение может занимать около 15 минут.<br>
                        Лимиты в Docker-контейнере увеличены до 30 минут:<br>
                        PHP: <code>max_execution_time=1800</code>, <code>default_socket_timeout=1800</code><br>
                        Nginx: <code>fastcgi_connect_timeout=1800s</code>, <code>fastcgi_send_timeout=1800s</code>, <code>fastcgi_read_timeout=1800s</code>.
                    </div>
                    <button class="run-btn" type="submit">Сортировать</button>
                </form>
            </div>
        </div>

        <div class="card">
            <h2>PHP-код алгоритма</h2>
            <p class="muted"><code>{{ $sourceRelativePath }}</code></p>
            <div class="code-view">{!! $highlightedCode !!}</div>
        </div>
    </div>
</div>

<div class="card">
    <h2>Результат выполнения</h2>
    @if ($error !== null)
        <div class="error">{{ $error }}</div>
    @elseif (! $hasRun)
        <div class="stats">
            Нажмите <b>Сортировать</b>, чтобы запустить пузырьковую сортировку для данных из textarea.
        </div>
    @else
        <div class="stats">
            <b>Количество элементов:</b> {{ count($sortedResult) }}<br>
            <b>Время сортировки:</b> {{ number_format($elapsed, 6) }} сек
        </div>
        <h3>Полный отсортированный массив (единый вывод)</h3>
        <div class="result">{{ implode(', ', array_map(static fn ($n) => (string) $n, $sortedResult)) }}</div>
    @endif
</div>
@endsection
