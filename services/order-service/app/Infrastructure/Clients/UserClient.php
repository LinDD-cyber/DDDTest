<?php

namespace App\Infrastructure\Clients;

use Illuminate\Support\Facades\Http;

class UserClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('USER_SERVICE_URL', 'http://user-service:8000');
    }

    public function getUser(int $id): ?array
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/api/users/{$id}");
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            // Log or handle
        }
        return null;
    }
}
