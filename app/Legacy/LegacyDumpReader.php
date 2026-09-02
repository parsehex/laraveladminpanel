<?php

namespace App\Legacy;

use Generator;
use InvalidArgumentException;
use RuntimeException;

class LegacyDumpReader
{
    /** @var array<string, list<string>> */
    private array $columns = [];

    /** @var array<string, int> */
    private array $counts = [];

    public function __construct(
        private readonly string $path,
    ) {
        if (! is_readable($path)) {
            throw new InvalidArgumentException("Legacy dump not readable: {$path}");
        }

        $this->indexDump();
    }

    public function path(): string
    {
        return $this->path;
    }

    public function sha256(): string
    {
        return hash_file('sha256', $this->path) ?: '';
    }

    /**
     * @return list<string>
     */
    public function tables(): array
    {
        return array_keys($this->columns);
    }

    public function hasTable(string $table): bool
    {
        return isset($this->columns[$table]);
    }

    public function count(string $table): int
    {
        return $this->counts[$table] ?? 0;
    }

    /**
     * @return list<string>
     */
    public function columns(string $table): array
    {
        if (! isset($this->columns[$table])) {
            throw new InvalidArgumentException("Table not found in dump: {$table}");
        }

        return $this->columns[$table];
    }

    /**
     * @return Generator<int, array<string, mixed>>
     */
    public function rows(string $table): Generator
    {
        if (! isset($this->columns[$table])) {
            throw new InvalidArgumentException("Table not found in dump: {$table}");
        }

        $handle = fopen($this->path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Unable to open dump: {$this->path}");
        }

        $columns = $this->columns[$table];
        $inCopy = false;

        try {
            while (($line = fgets($handle)) !== false) {
                if (! $inCopy) {
                    if (preg_match('/^COPY public\.'.$table.' \((.+)\) FROM stdin;/', $line, $matches)) {
                        $inCopy = true;
                    }

                    continue;
                }

                if (rtrim($line, "\r\n") === '\\.') {
                    break;
                }

                $values = explode("\t", rtrim($line, "\r\n"));
                if (count($values) !== count($columns)) {
                    throw new RuntimeException("Column count mismatch in {$table} row");
                }

                $row = [];
                foreach ($columns as $index => $column) {
                    $row[$column] = LegacyCopyValue::parse($values[$index] ?? null);
                }

                yield $row;
            }
        } finally {
            fclose($handle);
        }
    }

    private function indexDump(): void
    {
        $handle = fopen($this->path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Unable to open dump: {$this->path}");
        }

        $currentTable = null;
        $rowCount = 0;

        try {
            while (($line = fgets($handle)) !== false) {
                if (preg_match('/^COPY public\.(\w+) \((.+)\) FROM stdin;/', $line, $matches)) {
                    $currentTable = $matches[1];
                    $this->columns[$currentTable] = array_map(
                        static fn (string $column) => trim($column, '"'),
                        explode(', ', $matches[2]),
                    );
                    $rowCount = 0;

                    continue;
                }

                if ($currentTable !== null) {
                    if (rtrim($line, "\r\n") === '\\.') {
                        $this->counts[$currentTable] = $rowCount;
                        $currentTable = null;

                        continue;
                    }

                    $rowCount++;
                }
            }
        } finally {
            fclose($handle);
        }
    }
}
