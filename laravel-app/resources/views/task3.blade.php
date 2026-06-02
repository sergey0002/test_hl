{{-- Задание 3: экспорт CSV без перезагрузки, polling статуса --}}
@extends('layouts.app', [
    'title' => 'Демо экспорта CSV',
    'activePage' => 'task3',
    'bodyClass' => 'page-export',
])

@section('content')
<div class="card">
    <h1>Экспорт пользователей в CSV</h1>
    <p class="muted">
        Экспорт запускается без перезагрузки страницы, выполняется на бэкенде в фоне,
        а интерфейс получает статус через AJAX polling.
    </p>
    <ul class="desc-list">
        <li><b>Шаг 1:</b> нажимаем кнопку — создается задача экспорта и ставится в очередь Redis.</li>
        <li><b>Шаг 2:</b> воркер обрабатывает пользователей батчами и пишет CSV построчно.</li>
        <li><b>Шаг 3:</b> фронт опрашивает статус, показывает прогресс и технические этапы.</li>
        <li><b>Шаг 4:</b> после завершения появляется ссылка для скачивания готового файла.</li>
    </ul>
    <p class="muted">
        Для ~500 000 пользователей обработка занимает несколько минут. Пока статус <code>pending</code>,
        задача ждёт воркер — прогресс пойдёт после перехода в <code>processing</code>.
    </p>

    <div class="relations-card" style="margin-top: 14px;">
        <h2 style="margin-top: 0; font-size: 1.1rem;">Параметры батча выгрузки</h2>
        <ul class="desc-list" style="margin-bottom: 0;">
            <li><b>Размер батча (chunk):</b> {{ number_format($chunkSize, 0, '', ' ') }} пользователей за один проход чтения из БД.</li>
            <li><b>Обновление прогресса:</b> каждые {{ number_format($progressEvery, 0, '', ' ') }} обработанных строк (запись в таблицу <code>exports</code>).</li>
            <li> константы <code>CHUNK_SIZE</code> и <code>PROGRESS_EVERY</code> в файле <code>{{ $jobSourcePath }}</code>.</li>
            <li><b>Воркер очереди:</b> <code>docker-compose.yml</code> → сервис <code>worker</code> → <code>php artisan queue:work redis</code> (обрабатывает по одной задаче экспорта).</li>
        </ul>
    </div>
</div>

<div class="card">
    <button id="startExport" class="run-btn">Выгрузить пользователей</button>
    <p class="muted" style="margin-top: 10px;">Ниже отображаются шаги выполнения и текущий прогресс.</p>
</div>
<div id="status"></div>

<script>
const statusEl = document.getElementById('status');
const startButton = document.getElementById('startExport');
let pollTimer = null;

function mapStatusToText(status) {
    if (status === 'pending') {
        return 'Задача в очереди Redis. Воркер возьмёт её, когда освободится.';
    }
    if (status === 'processing') {
        return 'Воркер пишет CSV. Прогресс обновляется каждые 5000 строк.';
    }
    if (status === 'completed') return 'Экспорт завершен. Файл готов к скачиванию.';
    if (status === 'failed') return 'Экспорт завершился ошибкой.';
    return 'Неизвестный статус задачи.';
}

function formatProgress(status) {
    if (status.status === 'pending') {
        return 'Прогресс: ожидание воркера (0%, задача в очереди)';
    }
    return `Прогресс: ${status.progress ?? 0}%`;
}

function renderStatus(exportId, status, extraHtml) {
    statusEl.innerHTML =
        `Экспорт #${exportId}<br>` +
        `Статус: ${status.status}<br>` +
        `${mapStatusToText(status.status)}<br>` +
        `Обработано: ${status.processed}/${status.total}<br>` +
        formatProgress(status) +
        (extraHtml || '');
}

startButton.addEventListener('click', async () => {
    if (pollTimer) clearInterval(pollTimer);
    startButton.disabled = true;
    statusEl.textContent = 'Инициализация экспорта...';

    let start;
    try {
        start = await fetch('/api/export/start', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        });
    } catch (e) {
        statusEl.textContent = 'Ошибка сети: ' + e.message;
        startButton.disabled = false;
        return;
    }

    if (!start.ok) {
        statusEl.textContent = 'Ошибка API ' + start.status + '. Проверьте: docker compose ps worker';
        startButton.disabled = false;
        return;
    }

    const data = await start.json();
    if (!data.export_id) {
        statusEl.textContent = 'Не удалось создать экспорт. См. docker compose logs worker';
        startButton.disabled = false;
        return;
    }

    renderStatus(data.export_id, data);
    poll(data.export_id);
});

async function poll(exportId) {
    pollTimer = setInterval(async () => {
        let res;
        try {
            res = await fetch('/api/export/status/' + exportId, {headers: {'Accept': 'application/json'}});
        } catch (e) {
            statusEl.textContent = 'Ошибка опроса: ' + e.message;
            return;
        }

        if (!res.ok) {
            statusEl.textContent = 'Статус недоступен (HTTP ' + res.status + ')';
            return;
        }

        const status = await res.json();
        let extraHtml = '';

        if (status.status === 'completed' && status.download_url) {
            clearInterval(pollTimer);
            pollTimer = null;
            extraHtml = `<br><a href="${status.download_url}">Скачать готовый CSV</a>`;
            startButton.disabled = false;
        }

        if (status.status === 'failed') {
            clearInterval(pollTimer);
            pollTimer = null;
            extraHtml = `<br>Ошибка: ${status.error || 'неизвестно'}`;
            startButton.disabled = false;
        }

        renderStatus(exportId, status, extraHtml);
    }, 1500);
}
</script>
@endsection
