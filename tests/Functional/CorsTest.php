<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CorsTest extends WebTestCase
{
    public function testPreflightAllowedOrigin(): void
    {
        $client = static::createClient();
        $client->request('OPTIONS', '/api/v1/auth/login', [], [], [
            'HTTP_ORIGIN' => 'http://localhost:3000',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->has('Access-Control-Allow-Origin'));
    }
}
