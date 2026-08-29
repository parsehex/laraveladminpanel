<?php

namespace App\Legacy;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class LegacyPatchLoader
{
    private readonly string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? database_path('legacy-import/patches');
    }

    /**
     * @return array<string, mixed>
     */
    public function load(string $name): array
    {
        $path = $this->basePath.'/'.$name.'.php';
        if (! is_file($path)) {
            return [];
        }

        $data = require $path;
        if (! is_array($data)) {
            throw new InvalidArgumentException("Patch file must return an array: {$path}");
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function hashes(): array
    {
        $hashes = [];

        foreach (File::files($this->basePath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $hashes[$file->getFilenameWithoutExtension()] = hash_file('sha256', $file->getPathname()) ?: '';
        }

        return $hashes;
    }
}
