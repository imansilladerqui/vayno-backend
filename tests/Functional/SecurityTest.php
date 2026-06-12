<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityTest extends WebTestCase
{
    public function testCannotEscalateRoleViaProfileUpdate(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'renter@example.com',
            'password' => 'password12345',
            'full_name' => 'Test Renter',
        ], JSON_THROW_ON_ERROR));
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/v1/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'renter@example.com',
            'password' => 'password12345',
        ], JSON_THROW_ON_ERROR));
        $this->assertResponseIsSuccessful();
        $login = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $client->request('PATCH', '/api/v1/users/me', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$login['access_token'],
        ], json_encode(['role' => 'OWNER'], JSON_THROW_ON_ERROR));
        $this->assertResponseIsSuccessful();

        $profile = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('RENTER', $profile['role']);
    }

    public function testInvalidBearerDoesNotBlockPublicEndpoint(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/slots/available', [
            'date' => '2026-06-12',
            'start_dt' => '10:00:00',
            'end_dt' => '12:00:00',
        ], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid-token',
        ]);

        $this->assertResponseIsSuccessful();
    }
}
