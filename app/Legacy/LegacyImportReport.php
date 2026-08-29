<?php

namespace App\Legacy;

use Illuminate\Support\Facades\File;

class LegacyImportReport
{
    /** @var array<string, array{read: int, inserted: int, updated: int, skipped: int}> */
    private array $tables = [];

    /** @var list<string> */
    private array $warnings = [];

    /** @var list<string> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $sections = [];

    public function recordTable(string $table, int $read, int $inserted, int $updated, int $skipped = 0): void
    {
        $this->tables[$table] = compact('read', 'inserted', 'updated', 'skipped');
    }

    public function warn(string $message): void
    {
        $this->warnings[] = $message;
    }

    public function error(string $message): void
    {
        $this->errors[] = $message;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function section(string $name, array $data): void
    {
        $this->sections[$name] = $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tables' => $this->tables,
            'warnings' => $this->warnings,
            'errors' => $this->errors,
            'sections' => $this->sections,
        ];
    }

    public function write(string $directory): string
    {
        File::ensureDirectoryExists($directory);
        $path = rtrim($directory, '/').'/report.json';
        File::put($path, json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $path;
    }
}
