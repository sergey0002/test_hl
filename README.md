# Тестовое задание  

Стек: **PHP 8.2**, **Laravel 12**, **PostgreSQL 15**, **Redis 7**, **Nginx**, **Docker Compose**.

---

## Быстрый запуск

```bash
docker compose up -d --build
```

Инициализация БД (миграции + хоккейные демо-данные + **500 000** пользователей):

```bash
docker compose exec app sh -lc "cd laravel-app && php artisan migrate:fresh --seed --force"
```

Пересоздать только пользователей (без полного `migrate:fresh`):

```bash
docker compose exec app sh -lc "cd laravel-app && php artisan users:generate 500000 --fresh"
```

Пересоздать воркер очереди (после изменений в `docker-compose.yml`):

```bash
docker compose up -d --force-recreate worker
docker compose logs worker --tail 20
```

Приложение: **http://localhost:8081**

| URL | Описание |
|-----|----------|
| `/` | Ссылки на задания, контакты |
| `/task1` | Пузырьковая / нативная сортировка |
| `/task2` | SQL-консоль, схема БД, дамп |
| `/task2/dump` | Скачивание SQL dump |
| `/export` | Экспорт 500k пользователей в CSV |

Переменные БД Laravel: префикс **`LARAVEL_DB_*`** в `laravel-app/.env` (отдельная БД `test_db_laravel`, чтобы не конфликтовать с `DB_*` в Compose).

---

## Пункт 1. Пузырьковая сортировка

### Требование

Отсортировать массив чисел (в т.ч. ~200 000 элементов) **по возрастанию**; учитывать скорость и расход памяти; показать и обосновать решение.

### Реализация

| Аспект | Решение |
|--------|---------|
| Алгоритм (демо) | Оптимизированный пузырёк: сужение правой границы по последнему обмену, ранний выход |
| Алгоритм (production) | Встроенный `sort($items, SORT_NUMERIC)` — O(n log n), реализация на C |
| Память | Сортировка **in-place** (`array &$items`), без дублирования массива |
 

При первом открытии страницы в textarea генерируется **200 000** случайных чисел (как в условии демонстрации). После «Сортировать» выводятся количество элементов, время (сек) и **полный** отсортированный массив.

### Ключевые файлы

- `laravel-app/app/Services/SortService.php` — логика сортировки  
- `laravel-app/app/Http/Controllers/Task1Controller.php` — страница и обработка формы  
- `laravel-app/resources/views/task1.blade.php` — интерфейс  

---

## Пункт 2. База данных хоккея + SQL dump

### Требование

Таблицы для клубов, сезонов, игроков; игровой номер в контексте клуба и сезона; выгрузка **структуры и данных** (SQL dump); демо-данные (минимум 3 клуба, 2 сезона, несколько игроков).

### Реализация

**Схема (миграции):**

| Таблица | Назначение |
|---------|------------|
| `clubs` | name_ru, name_en, city_ru, city_en |
| `seasons` | name, year_start, year_end |
| `players` | full_name_ru, full_name_en, weight, height |
| `player_season_club` | связь игрок–сезон–клуб + **player_number** |

**Целостность:** `UNIQUE(player_id, season_id, club_id)`; индексы `idx_psc_player`, `idx_psc_season`, `idx_psc_club`.

**Демо-данные (сидер):** 3 клуба (СКА, ЦСКА, Ак Барс), 2 сезона, 9 игроков, 18 записей в `player_season_club`.

**Страница `/task2`:**

- Карточка итога: кнопка **«Скачать дамп БД (SQL)»** → `GET /task2/dump`
- Место под изображение схемы: `public/assets/img/shem.jpg`
- Меню из 3 пресетов (JOIN, GROUP BY, оконная функция) + SQL-редактор
- `POST /task2/query` — выполнение SELECT / других команд

**Динамический dump (`DatabaseDumpService`):**

- DROP / CREATE (через `pg_catalog`)
- INSERT по фактическим строкам
- ALTER constraints, индексы, `setval` для sequences  
- Не используется несуществующий `pg_get_tabledef`

### Ключевые файлы

- `laravel-app/database/migrations/2026_06_02_190100_create_clubs_table.php`  
- `laravel-app/database/migrations/2026_06_02_190110_create_seasons_table.php`  
- `laravel-app/database/migrations/2026_06_02_190120_create_players_table.php`  
- `laravel-app/database/migrations/2026_06_02_190130_create_player_season_club_table.php`  
- `laravel-app/database/seeders/DatabaseSeeder.php` — хоккей + users  
- `laravel-app/app/Services/Task2/DatabaseDumpService.php`  
- `laravel-app/app/Http/Controllers/Task2Controller.php`  
- `laravel-app/resources/views/task2.blade.php`  

Справочный статический SQL (схема + пример данных): `database/sql/final_dump.sql`.

---

## Пункт 3. Экспорт 500 000 пользователей в CSV

### Требование

>500 000 пользователей; кнопка выгрузки; **без перезагрузки** страницы; **AJAX** с прогрессом; ссылка на скачивание; поля: Фамилия, Имя, Телефон, E-mail.

### Реализация

| Этап | Механизм |
|------|----------|
| Данные | **500 000** строк в `users` через PostgreSQL `generate_series` (`UserBulkInsertService`, ~10 с) |
| Старт | `POST /api/export/start` → запись в `exports`, `ProcessCsvExport::dispatch()` |
| Очередь | `QUEUE_CONNECTION=redis`, контейнер **worker**: `php artisan queue:work redis` |
| Обработка | Job читает users **chunkById(5000)**, прогресс в БД каждые 5000 строк (`ProcessCsvExport`) |
| Прогресс | `GET /api/export/status/{id}` — `status`, `processed`, `total`, `progress` % |
| Файл | `GET /api/export/download/{id}` — отдача готового CSV (`;`, заголовки на русском) |
| UI | `/export` — описание шагов, polling раз в 1 с, ссылка «Скачать готовый CSV» |

При создании экспорта сразу записывается `total = User::count()`, чтобы не показывать `0/0` в ожидании воркера. При падении job — `status=failed`, текст в `error_message`.

### Ключевые файлы

- `laravel-app/app/Services/UserBulkInsertService.php` — массовая вставка users  
- `laravel-app/routes/console.php` — команда `users:generate`  
- `laravel-app/app/Http/Controllers/Task3Controller.php` — API и страница  
- `laravel-app/app/Jobs/ProcessCsvExport.php` — фоновая запись CSV  
- `laravel-app/app/Models/ExportStatus.php` — модель таблицы `exports`  
- `laravel-app/app/Models/User.php` — поля first_name, last_name, phone  
- `laravel-app/database/migrations/2026_06_02_190000_add_profile_fields_to_users_table.php`  
- `laravel-app/database/migrations/2026_06_02_190140_create_exports_table.php`  
- `laravel-app/routes/api.php` — маршруты экспорта  
- `laravel-app/resources/views/task3.blade.php` — UI + JavaScript  

---

## Инфраструктура репозитория  

| Файл | Назначение |
|------|------------|
| `docker-compose.yml` | Сервисы app, worker, nginx, postgres, redis |
| `Dockerfile` | PHP 8.2-fpm, pdo_pgsql, redis, composer |
| `docker/php-entrypoint.sh` | Права на `storage` / `bootstrap/cache` при старте |
| `nginx/default.conf` | Document root: `laravel-app/public` |
| `php/99-custom.ini` | `max_execution_time=1800`, память 1G (долгая сортировка) |
| `database/sql/final_dump.sql` | Эталонный SQL dump для справки |

---

## Файлы приложения  

 

### `app/Http/Controllers/`

| Файл | Назначение |
|------|------------|
| `HomeController.php` | Стартовая `/`: задания, контакты |
| `Task1Controller.php` | `/task1`: демо-данные, форма сортировки, подсветка кода |
| `Task2Controller.php` | `/task2`, dump, выполнение SQL |
| `Task3Controller.php` | `/export`, API start/status/download |

### `app/Services/`

| Файл | Назначение |
|------|------------|
| `SortService.php` | Пузырёк (оптимизированный) и `sort()` для задания 1 |
| `UserBulkInsertService.php` | Вставка 500k users через `generate_series` |
| `Task2/DatabaseDumpService.php` | Генерация SQL dump из PostgreSQL |

### `app/Jobs/`

| Файл | Назначение |
|------|------------|
| `ProcessCsvExport.php` | Фоновая запись CSV, обновление прогресса в `exports` |

### `app/Models/`

| Файл | Назначение |
|------|------------|
| `ExportStatus.php` | Eloquent для таблицы `exports` (статус экспорта) |
| `User.php` | *Изменён:* fillable `first_name`, `last_name`, `phone` для CSV |

### `database/migrations/`

| Файл | Назначение |
|------|------------|
| `2026_06_02_190000_add_profile_fields_to_users_table.php` | first_name, last_name, phone |
| `2026_06_02_190100_create_clubs_table.php` | Клубы |
| `2026_06_02_190110_create_seasons_table.php` | Сезоны |
| `2026_06_02_190120_create_players_table.php` | Игроки |
| `2026_06_02_190130_create_player_season_club_table.php` | Связи + player_number, unique, индексы |
| `2026_06_02_190140_create_exports_table.php` | Задачи CSV-экспорта |

### `database/seeders/`

| Файл | Назначение |
|------|------------|
| `HockeySeeder.php` | Демо-данные хоккея (для сидера и тестов) |
| `DatabaseSeeder.php` | Хоккей + 500k users |

### `tests/` (PHPUnit)

| Файл | Назначение |
|------|------------|
| `Unit/SortServiceTest.php` | Пузырёк, native sort, граничные случаи |
| `Unit/ProcessCsvExportTest.php` | Job: CSV, статус completed |
| `Feature/HomePageTest.php` | GET `/` |
| `Feature/Task1PageTest.php` | GET/POST `/task1` |
| `Feature/Task2PageTest.php` | `/task2`, SQL, JOIN по демо-данным |
| `Feature/ExportApiTest.php` | API экспорта, `Queue::fake()` |

### `resources/views/`

| Файл | Назначение |
|------|------------|
| `layouts/app.blade.php` | Общий layout, подключение CSS |
| `partials/top-nav.blade.php` | Меню: Контакты, Задания 1–3 |
| `home.blade.php` | Стартовая страница |
| `task1.blade.php` | UI сортировки |
| `task2.blade.php` | UI БД и SQL |
| `task3.blade.php` | UI экспорта + AJAX |

  

### `laravel-app/.env`

| Параметр | Назначение |
|----------|------------|
| `LARAVEL_DB_*` | PostgreSQL `test_db_laravel` |
| `QUEUE_CONNECTION=redis` | Очередь для экспорта |
| `REDIS_HOST=redis` | Хост Redis в Docker |

---

## Тестирование (PHPUnit)

Конфигурация: `laravel-app/phpunit.xml` — **SQLite in-memory**, `QUEUE_CONNECTION=sync` (job выполняется сразу, без Redis).

```bash
docker compose exec app sh -lc "cd laravel-app && php artisan test"
# или
docker compose exec app sh -lc "cd laravel-app && composer test"
```

| Suite | Что проверяет |
|-------|----------------|
| **Unit** | `SortService` — сортировка, early exit |
| **Unit** | `ProcessCsvExport` — запись CSV и `completed` |
| **Feature** | Страницы `/`, `/task1`, `/task2`, `/export` |
| **Feature** | API `POST /api/export/start` → status → download |

**16 тестов**, покрытие по трём пунктам задания. Dump PostgreSQL (`DatabaseDumpService`) в автотестах не гоняется — нужен `pgsql`; проверяется вручную через `/task2/dump`.

Для production-БД после `migrate:fresh` на PostgreSQL CHECK на `seasons` создаётся только при драйвере `pgsql` (см. миграцию `create_seasons_table`).

---

## Полезные команды

```bash
# Миграции и сиды (включая 500k users)
docker compose exec app sh -lc "cd laravel-app && php artisan migrate:fresh --seed --force"

# Только users
docker compose exec app sh -lc "cd laravel-app && php artisan users:generate 500000 --fresh"

# Воркер и логи
docker compose up -d --force-recreate worker
docker compose logs worker --tail 20

# Тесты
docker compose exec app sh -lc "cd laravel-app && php artisan test"
```

---

## Архитектура (кратко)

```text
Браузер → Nginx → laravel-app/public/index.php
              ├── web: Blade (задания 1–3, home)
              └── api: JSON (экспорт)

Task3: Browser --AJAX--> Task3Controller --dispatch--> Redis Queue
              ↑                           ↓
              └──── poll status ──── Worker → ProcessCsvExport → CSV
```

---

## Соответствие вакансии

| Навык | Где в проекте |
|-------|----------------|
| Laravel 12 | Миграции, Eloquent, Jobs, Blade, API |
| PostgreSQL, SQL | Задание 2, dump, `generate_series` |
| Redis, очереди | Экспорт CSV, worker |
| PHPUnit | `tests/Unit`, `tests/Feature` |
| Docker | `docker-compose.yml` |
 