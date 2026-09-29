<?php

namespace App\Imports;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StagedCsvImport
{
    public function __construct(private readonly string $sessionKey) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function stage(Request $request, UploadedFile $file, array $context = []): void
    {
        $this->discard($request);

        $token = (string) Str::uuid();
        $relativePath = 'private/csv-imports/'.$request->user()->id.'/'.$token.'.csv';
        Storage::disk('local')->put($relativePath, file_get_contents($file->getRealPath()));

        $request->session()->put($this->sessionKey, [
            ...$context,
            'token' => $token,
            'path' => $relativePath,
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]);
    }

    /**
     * @param  array<string, mixed>  $expectedContext
     * @return array{token: string, path: string, expires_at: int}
     */
    public function require(Request $request, array $expectedContext = []): array
    {
        $staged = $request->session()->get($this->sessionKey);

        if (! is_array($staged)
            || empty($staged['path'])
            || empty($staged['expires_at'])
            || (int) $staged['expires_at'] < now()->timestamp
            || ! Storage::disk('local')->exists($staged['path'])) {
            $this->discard($request);

            throw ValidationException::withMessages([
                'csv_file' => ['The staged import expired or was not found. Upload the CSV again.'],
            ]);
        }

        foreach ($expectedContext as $key => $value) {
            if (($staged[$key] ?? null) != $value) {
                $this->discard($request);

                throw ValidationException::withMessages([
                    'csv_file' => ['The staged import expired or was not found. Upload the CSV again.'],
                ]);
            }
        }

        return $staged;
    }

    /**
     * @param  array{path: string}  $staged
     */
    public function absolutePath(array $staged): string
    {
        return Storage::disk('local')->path($staged['path']);
    }

    public function discard(Request $request): void
    {
        $staged = $request->session()->pull($this->sessionKey);

        if (is_array($staged) && ! empty($staged['path'])) {
            Storage::disk('local')->delete($staged['path']);
        }
    }
}
