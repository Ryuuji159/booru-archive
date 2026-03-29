<?php

namespace App\Services;

use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class KonachanService
{
    public function __construct(
        private readonly KonachanDownloadRateLimit $downloadRateLimit,
    ) {}

    public function posts(array $tags = [], ?int $limit = null, int $page = 1): array
    {
        $host = config('services.konachan.host');

        $response = $this->executeRequest(
            callback: fn (PendingRequest $request) => $request->get("{$host}/post.json", [
                'tags' => implode(' ', $tags),
                'limit' => $limit,
                'page' => $page,
            ]),
            acceptJson: true,
        );

        if ($response->failed()) {
            return [];
        }

        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }

    public function downloadToTemporaryFile(string $url, string $temporaryFile, string $label): void
    {
        if (blank($url)) {
            throw new RuntimeException("The post has no source {$label} URL to download.");
        }

        $response = $this->executeRequest(
            callback: fn (PendingRequest $request) => $request
                ->withOptions([
                    'sink' => $temporaryFile,
                ])
                ->get($url),
            timeout: 120,
        );

        if ($response->failed()) {
            throw new RuntimeException("The {$label} download failed with status [{$response->status()}].");
        }

        if (filesize($temporaryFile) === 0) {
            file_put_contents($temporaryFile, $response->body());
        }

        if (filesize($temporaryFile) === 0) {
            throw new RuntimeException("The downloaded {$label} is empty.");
        }
    }

    /**
     * @template TReturn
     *
     * @param  Closure(PendingRequest): TReturn  $callback
     * @return TReturn
     */
    private function executeRequest(Closure $callback, bool $acceptJson = false, int $timeout = 20): mixed
    {
        $this->waitForSourceRequestSlot();

        $request = Http::appIdentity()
            ->connectTimeout(10)
            ->timeout($timeout)
            ->retry([250, 750, 1500], throw: false);

        if ($acceptJson) {
            $request->acceptJson();
        }

        try {
            return $callback($request);
        } finally {
            $this->recordSourceRequestAttempt();
        }
    }

    private function waitForSourceRequestSlot(): void
    {
        while ($this->downloadRateLimit->isBlocked()) {
            $availableIn = $this->downloadRateLimit->availableIn();

            if ($availableIn <= 0) {
                break;
            }

            sleep($availableIn);
        }
    }

    private function recordSourceRequestAttempt(): void
    {
        $this->downloadRateLimit->recordAttempt();
    }
}
