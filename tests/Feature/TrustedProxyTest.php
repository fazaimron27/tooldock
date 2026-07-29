<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_forwarded_https_requests_generate_secure_urls(): void
    {
        Route::get('/test/forwarded-url', fn (): string => route('login'));

        $response = $this->withServerVariables([
            'HTTP_HOST' => '127.0.0.1:8000',
            'HTTP_X_FORWARDED_HOST' => 'tooldock.my.id',
            'HTTP_X_FORWARDED_PORT' => '443',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'REMOTE_ADDR' => '127.0.0.1',
        ])->get('/test/forwarded-url');

        $response->assertOk();
        $response->assertSeeText('https://tooldock.my.id/tooldock/login');
    }
}
