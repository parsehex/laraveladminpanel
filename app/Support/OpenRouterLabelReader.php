<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OpenRouterLabelReader
{
    /**
     * @param  list<string>  $paths  Paths on the public disk.
     * @return array{brand: ?string, model_number: ?string, product_name: ?string, serial_number: ?string}
     */
    public function read(array $paths): array
    {
        $key = (string) config('services.openrouter.key');

        if ($key === '') {
            throw new LabelReadException('Check-in needs an OpenRouter API key. Set OPENROUTER_API_KEY and try again.');
        }

        $content = [
            [
                'type' => 'text',
                'text' => 'Read the furniture or appliance label in these photos. Return JSON only, with keys brand, model_number, product_name, and serial_number. Use null when a value is not visible. serial_number is the unit serial or the SSCC barcode number.',
            ],
        ];

        foreach ($paths as $path) {
            $bytes = Storage::disk('public')->get($path);

            if ($bytes === null) {
                throw new LabelReadException('The label photo could not be read. Nothing was saved.');
            }

            $mime = $this->mimeFor($path);
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'data:'.$mime.';base64,'.base64_encode($bytes),
                ],
            ];
        }

        try {
            $response = Http::withToken($key)
                ->withHeaders([
                    'HTTP-Referer' => (string) config('app.url'),
                    'X-Title' => (string) config('app.name'),
                ])
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(60)
                ->post((string) config('services.openrouter.url'), [
                    'model' => (string) config('services.openrouter.model'),
                    'messages' => [
                        ['role' => 'user', 'content' => $content],
                    ],
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('OpenRouter label read failed', [
                'error' => $exception->getMessage(),
            ]);

            throw new LabelReadException('The label could not be read. Nothing was saved. Try the photo again.');
        }

        if (! $response->successful()) {
            Log::warning('OpenRouter label read failed', [
                'status' => $response->status(),
            ]);

            throw new LabelReadException('The label could not be read. Nothing was saved. Try the photo again.');
        }

        return $this->extraction($response->json('choices.0.message.content'));
    }

    /**
     * @return array{brand: ?string, model_number: ?string, product_name: ?string, serial_number: ?string}
     */
    private function extraction(mixed $content): array
    {
        $text = $this->contentText($content);
        $text = trim($text);

        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text) ?? $text;
            $text = trim($text);
        }

        if (! str_starts_with($text, '{') && preg_match('/\{.*\}/s', $text, $matches) === 1) {
            $text = $matches[0];
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            Log::warning('OpenRouter label read returned unreadable JSON');

            throw new LabelReadException('The label could not be read. Nothing was saved. Try the photo again.');
        }

        $brand = $this->plainText($decoded['brand'] ?? null);
        $productName = $this->plainText($decoded['product_name'] ?? null);
        $modelNumber = LabelIdentifier::normalize($this->plainText($decoded['model_number'] ?? null) ?? '');
        $serialNumber = LabelIdentifier::normalize($this->plainText($decoded['serial_number'] ?? null) ?? '');

        return [
            'brand' => $brand,
            'model_number' => $modelNumber === '' ? null : $modelNumber,
            'product_name' => $productName,
            'serial_number' => $serialNumber === '' ? null : $serialNumber,
        ];
    }

    private function contentText(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }

        if (! is_array($content)) {
            return '';
        }

        $parts = [];

        foreach ($content as $part) {
            if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                $parts[] = $part['text'];
            }
        }

        return implode("\n", $parts);
    }

    private function plainText(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function mimeFor(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/jpeg',
        };
    }
}
