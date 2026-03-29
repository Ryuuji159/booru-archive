<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\RateLimiter;

class KonachanDownloadRateLimit
{
    public const KEY = 'konachan:post-download:requests';

    public function key(): string
    {
        return self::KEY;
    }

    public function cooldownSeconds(): int
    {
        return max(0, (int) config('services.konachan.download_request_cooldown_seconds', 2));
    }

    public function attempts(): int
    {
        return RateLimiter::attempts($this->key());
    }

    public function availableIn(): int
    {
        return RateLimiter::availableIn($this->key());
    }

    public function isBlocked(): bool
    {
        return RateLimiter::tooManyAttempts($this->key(), 1);
    }

    public function nextAvailableAt(): ?CarbonImmutable
    {
        $availableIn = $this->availableIn();

        if ($availableIn <= 0) {
            return null;
        }

        return now()->addSeconds($availableIn);
    }

    public function recordAttempt(): void
    {
        $cooldownSeconds = $this->cooldownSeconds();

        if ($cooldownSeconds <= 0) {
            return;
        }

        RateLimiter::hit($this->key(), $cooldownSeconds);
    }

    public function clear(): void
    {
        RateLimiter::clear($this->key());
    }
}
