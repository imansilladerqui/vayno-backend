<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthTest extends WebTestCase
{
    public function testRegisterOwnerAndLogin(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/auth/register-owner', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'owner@example.com',
            'password' => 'password12345',
            'full_name' => 'Test Owner',
        ], JSON_THROW_ON_ERROR));

        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/v1/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'owner@example.com',
            'password' => 'password12345',
        ], JSON_THROW_ON_ERROR));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('refresh_token', $data);
        $this->assertSame('bearer', $data['token_type']);
    }

    public function testLoginInvalidCredentials(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'missing@example.com',
            'password' => 'wrongpassword',
        ], JSON_THROW_ON_ERROR));

        $this->assertResponseStatusCodeSame(401);
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Incorrect email or password', $data['detail']);
    }
}
