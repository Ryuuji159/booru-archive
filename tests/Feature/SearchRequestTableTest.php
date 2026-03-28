<?php

use App\Filament\Resources\SearchRequests\Pages\ListSearchRequests;
use App\Models\ScrapeRequest;
use App\Models\User;
use Livewire\Livewire;

it('can search search requests by partial tags', function () {
    $user = User::factory()->create();

    $matching = ScrapeRequest::query()->create([
        'site' => 'konachan',
        'parameters' => [
            'tags' => ['cat', 'blue_eyes'],
        ],
        'status' => ScrapeRequest::STATUS_PENDING,
        'discovered_posts_count' => 0,
        'started_at' => null,
        'finished_at' => null,
        'last_error' => null,
    ]);

    $nonMatching = ScrapeRequest::query()->create([
        'site' => 'konachan',
        'parameters' => [
            'tags' => ['dog'],
        ],
        'status' => ScrapeRequest::STATUS_PENDING,
        'discovered_posts_count' => 0,
        'started_at' => null,
        'finished_at' => null,
        'last_error' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(ListSearchRequests::class)
        ->searchTable('blue')
        ->assertCanSeeTableRecords([$matching])
        ->assertCanNotSeeTableRecords([$nonMatching]);
});
