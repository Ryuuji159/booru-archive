<?php

use App\Filament\Widgets\DownloadRateLimitWidget;
use App\Models\User;
use App\Services\KonachanDownloadRateLimit;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function (): void {
    app(KonachanDownloadRateLimit::class)->clear();
});

it('reports the current download rate limit state', function (): void {
    $rateLimit = app(KonachanDownloadRateLimit::class);

    expect($rateLimit->isBlocked())->toBeFalse()
        ->and($rateLimit->availableIn())->toBe(0)
        ->and($rateLimit->attempts())->toBe(0)
        ->and($rateLimit->nextAvailableAt())->toBeNull();

    RateLimiter::hit($rateLimit->key(), $rateLimit->cooldownSeconds());

    expect($rateLimit->isBlocked())->toBeTrue()
        ->and($rateLimit->availableIn())->toBeGreaterThan(0)
        ->and($rateLimit->attempts())->toBe(1)
        ->and($rateLimit->nextAvailableAt())->not->toBeNull();
});

it('renders the download rate limit widget', function (): void {
    $user = User::factory()->create();
    $rateLimit = app(KonachanDownloadRateLimit::class);

    RateLimiter::hit($rateLimit->key(), $rateLimit->cooldownSeconds());

    $this->actingAs($user);

    Livewire::test(DownloadRateLimitWidget::class)
        ->assertSee('Download rate limit')
        ->assertSee('Blocked')
        ->assertSee('Retry in');
});
