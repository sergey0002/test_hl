<?php

namespace App\Services\Task2;

use Illuminate\Support\Facades\DB;

/**
 * Формирует SQL-дамп таблиц задания №2 из текущего состояния PostgreSQL.
 */
final class DatabaseDumpService
{
    /** @var list<string> Таблицы хоккейной модели в dump */
    private const TABLES = ['clubs', 'seasons', 'players', 'player_season_club'];

    /**
     * Собирает полный текст dump: DROP, CREATE, INSERT, индексы, sequences.
     */
    public function generate(): string
    {
        $pdo = DB::connection()->getPdo();
        $lines = [
            '--',
            '-- PostgreSQL database dump (Laravel dynamic)',
            '-- Generated: ' . gmdate('Y-m-d H:i:s') . ' UTC',
            '--',
            '',
            'SET client_encoding = \'UTF8\';',
            'SET standard_conforming_strings = on;',
            '',
        ];

        $lines[] = 'DROP TABLE IF EXISTS public.player_season_club CASCADE;';
        $lines[] = 'DROP TABLE IF EXISTS public.players CASCADE;';
        $lines[] = 'DROP TABLE IF EXISTS public.seasons CASCADE;';
        $lines[] = 'DROP TABLE IF EXISTS public.clubs CASCADE;';
        $lines[] = '';

        foreach (self::TABLES as $table) {
            $lines = array_merge($lines, $this->dumpTableCreate($pdo, $table));
        }

        foreach (self::TABLES as $table) {
            $lines = array_merge($lines, $this->dumpTableData($pdo, $table));
        }

        foreach (self::TABLES as $table) {
            $lines = array_merge($lines, $this->dumpSequence($pdo, $table));
        }

        $lines = array_merge($lines, $this->dumpConstraintsAndIndexes($pdo));
        $lines[] = '';
        $lines[] = '-- PostgreSQL database dump complete';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    private function dumpTableCreate(\PDO $pdo, string $table): array
    {
        $exists = $pdo->query(
            "SELECT 1 FROM information_schema.tables
             WHERE table_schema = 'public' AND table_name = " . $pdo->quote($table)
        )->fetchColumn();

        if ($exists === false) {
            return ['--', "-- Table public.{$table} not found", '--', ''];
        }

        return [
            '--',
            "-- Name: {$table}; Type: TABLE; Schema: public",
            '--',
            '',
            $this->fetchCreateTableSql($pdo, $table),
            '',
        ];
    }

    private function fetchCreateTableSql(\PDO $pdo, string $table): string
    {
        $sql = <<<SQL
SELECT
    'CREATE TABLE public.' || c.relname || E' (\n' ||
    string_agg(
        '    ' || quote_ident(a.attname) || ' ' ||
        pg_catalog.format_type(a.atttypid, a.atttypmod) ||
        CASE WHEN a.attnotnull THEN ' NOT NULL' ELSE '' END ||
        CASE
            WHEN ad.adbin IS NOT NULL THEN ' DEFAULT ' || pg_get_expr(ad.adbin, ad.adrelid)
            ELSE ''
        END,
        E',\n' ORDER BY a.attnum
    ) || E'\n);' AS ddl
FROM pg_catalog.pg_class c
JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
JOIN pg_catalog.pg_attribute a ON a.attrelid = c.oid
LEFT JOIN pg_catalog.pg_attrdef ad ON ad.adrelid = c.oid AND ad.adnum = a.attnum
WHERE n.nspname = 'public'
  AND c.relname = :table
  AND c.relkind = 'r'
  AND a.attnum > 0
  AND NOT a.attisdropped
GROUP BY c.relname
SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['table' => $table]);
        $ddl = $stmt->fetchColumn();

        if ($ddl === false || $ddl === '') {
            throw new \RuntimeException("Не удалось получить DDL для таблицы {$table}");
        }

        $checkStmt = $pdo->prepare(
            "SELECT con.conname, pg_get_constraintdef(con.oid) AS def
             FROM pg_constraint con
             JOIN pg_class rel ON rel.oid = con.conrelid
             JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
             WHERE nsp.nspname = 'public' AND rel.relname = :table AND con.contype = 'c'
             ORDER BY con.conname"
        );
        $checkStmt->execute(['table' => $table]);
        $checks = $checkStmt->fetchAll(\PDO::FETCH_ASSOC);

        if ($checks === []) {
            return (string) $ddl;
        }

        $ddl = rtrim((string) $ddl, ';');
        foreach ($checks as $check) {
            $ddl .= ",\n    CONSTRAINT " . $check['conname'] . ' ' . $check['def'];
        }

        return $ddl . ';';
    }

    /**
     * @return list<string>
     */
    private function dumpTableData(\PDO $pdo, string $table): array
    {
        $stmt = $pdo->query('SELECT * FROM public.' . $this->quoteIdentifier($table) . ' ORDER BY 1');
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $lines = [
            '--',
            "-- Data for Name: {$table}; Type: TABLE DATA; Schema: public",
            '--',
            '',
        ];

        if ($rows === []) {
            $lines[] = '-- (no rows)';
            $lines[] = '';

            return $lines;
        }

        $columns = array_keys($rows[0]);
        $columnList = implode(', ', array_map(fn (string $c) => $this->quoteIdentifier($c), $columns));

        foreach ($rows as $row) {
            $values = [];
            foreach ($columns as $column) {
                $values[] = $this->sqlLiteral($pdo, $row[$column] ?? null);
            }
            $lines[] = 'INSERT INTO public.' . $table
                . ' (' . $columnList . ') VALUES (' . implode(', ', $values) . ');';
        }

        $lines[] = '';

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function dumpSequence(\PDO $pdo, string $table): array
    {
        $sql = <<<SQL
SELECT pg_get_serial_sequence('public.' || :table, a.attname) AS seq_name
FROM pg_attribute a
JOIN pg_class c ON c.oid = a.attrelid
JOIN pg_namespace n ON n.oid = c.relnamespace
WHERE n.nspname = 'public'
  AND c.relname = :table
  AND a.attnum > 0
  AND NOT a.attisdropped
  AND pg_get_serial_sequence('public.' || :table, a.attname) IS NOT NULL
LIMIT 1
SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['table' => $table]);
        $seqName = $stmt->fetchColumn();

        if ($seqName === false || $seqName === null) {
            return [];
        }

        $maxId = $pdo->query(
            'SELECT COALESCE(MAX(id), 1) FROM public.' . $this->quoteIdentifier($table)
        )->fetchColumn();

        return [
            '--',
            "-- Name: {$seqName}; Type: SEQUENCE SET; Schema: public",
            '--',
            '',
            "SELECT pg_catalog.setval('{$seqName}', " . (int) $maxId . ', true);',
            '',
        ];
    }

    /**
     * @return list<string>
     */
    private function dumpConstraintsAndIndexes(\PDO $pdo): array
    {
        $lines = [];
        $inTables = implode(', ', array_map(fn (string $t) => $pdo->quote($t), self::TABLES));

        $pkFkSql = <<<SQL
SELECT
    'ALTER TABLE ONLY public.' || rel.relname || E'\n    ADD CONSTRAINT ' || con.conname || ' ' ||
    pg_get_constraintdef(con.oid) || ';' AS stmt
FROM pg_constraint con
JOIN pg_class rel ON rel.oid = con.conrelid
JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
WHERE nsp.nspname = 'public'
  AND rel.relname IN ({$inTables})
  AND con.contype IN ('p', 'u', 'f')
ORDER BY rel.relname, con.contype, con.conname
SQL;

        foreach ($pdo->query($pkFkSql)->fetchAll(\PDO::FETCH_COLUMN) as $statement) {
            $lines[] = (string) $statement;
            $lines[] = '';
        }

        $indexSql = <<<SQL
SELECT indexdef || ';' AS stmt
FROM pg_indexes
WHERE schemaname = 'public'
  AND tablename IN ({$inTables})
  AND indexname NOT LIKE '%_pkey'
ORDER BY tablename, indexname
SQL;

        foreach ($pdo->query($indexSql)->fetchAll(\PDO::FETCH_COLUMN) as $statement) {
            $lines[] = (string) $statement;
            $lines[] = '';
        }

        return $lines;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    private function sqlLiteral(\PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $pdo->quote((string) $value);
    }
}
