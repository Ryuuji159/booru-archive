<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class KonachanService
{
    /**
     * @param  array<int, string>  $tags
     * @return array<int, array<string, mixed>>
     */
    public function posts(array $tags = [], ?int $limit = null, int $page = 1): array
    {
        $host = config('services.konachan.host');

        $response = Http::appIdentity()
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(20)
            ->get("{$host}/post.json", [
                'tags' => implode(' ', $tags),
                'limit' => $limit,
                'page' => $page,
            ]);

        if ($response->failed()) {
            return [];
        }

        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }
}
