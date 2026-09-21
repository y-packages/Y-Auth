<?php

namespace YakNet\YAuth\Tests\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use YakNet\YAuth\Provider\YakNetProvider;
use YakNet\YAuth\Provider\YakNetResourceOwner;

class YakNetProviderTest extends TestCase
{
    protected YakNetProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new YakNetProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'https://mock.app/callback',
            'baseUrl' => 'https://developer-console.yakhub.com.tr',
        ]);
    }

    public function testAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/oauth/authorize', $uri['path'] ?? '');

        parse_str($uri['query'] ?? '', $query);

        $this->assertEquals('mock_client_id', $query['client_id'] ?? '');
        $this->assertEquals('https://mock.app/callback', $query['redirect_uri'] ?? '');
        $this->assertEquals('code', $query['response_type'] ?? '');
        $this->assertNotEmpty($query['state'] ?? '');
    }

    public function testBaseAccessTokenUrl(): void
    {
        $url = $this->provider->getBaseAccessTokenUrl([]);
        $this->assertStringContainsString('/oauth/token', $url);
    }

    public function testGetResourceOwnerDetailsUrl(): void
    {
        $token = new AccessToken([
            'access_token' => 'mock_access_token',
        ]);

        $url = $this->provider->getResourceOwnerDetailsUrl($token);
        $this->assertStringContainsString('/api/user', $url);
    }

    public function testGetAccessTokenAndResourceOwner(): void
    {
        // Mock Response for Access Token request
        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('getStatusCode')->willReturn(200);
        $tokenResponse->method('getHeader')->willReturn(['content-type' => 'application/json']);
        
        $tokenStream = $this->createMock(StreamInterface::class);
        $tokenStream->method('__toString')->willReturn(json_encode([
            'access_token' => 'mock_access_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'mock_refresh_token',
        ]) ?: '');
        $tokenResponse->method('getBody')->willReturn($tokenStream);

        // Mock Response for User Profile request
        $userResponse = $this->createMock(ResponseInterface::class);
        $userResponse->method('getStatusCode')->willReturn(200);
        $userResponse->method('getHeader')->willReturn(['content-type' => 'application/json']);
        
        $userStream = $this->createMock(StreamInterface::class);
        $userStream->method('__toString')->willReturn(json_encode([
            'id' => 123,
            'name' => 'Enes Yakıcı',
            'email' => 'enes@yakhub.com.tr',
        ]) ?: '');
        $userResponse->method('getBody')->willReturn($userStream);

        // Mock HTTP Client
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls($tokenResponse, $userResponse);

        // Recreate provider with mock HTTP client
        $provider = new YakNetProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'https://mock.app/callback',
        ], [
            'httpClient' => $httpClient,
        ]);

        // 1. Get Access Token
        $token = $provider->getAccessToken('authorization_code', [
            'code' => 'mock_authorization_code',
        ]);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
        $this->assertInstanceOf(AccessToken::class, $token);

        // 2. Get Resource Owner (User Details)
        $user = $provider->getResourceOwner($token);

        $this->assertInstanceOf(YakNetResourceOwner::class, $user);
        $this->assertEquals(123, $user->getId());
        $this->assertEquals('Enes Yakıcı', $user->getName());
        $this->assertEquals('enes@yakhub.com.tr', $user->getEmail());
        
        $rawArray = $user->toArray();
        $this->assertEquals(123, $rawArray['id']);
        $this->assertEquals('Enes Yakıcı', $rawArray['name']);
        $this->assertEquals('enes@yakhub.com.tr', $rawArray['email']);
    }

    public function testCheckResponseThrowsExceptionOnError(): void
    {
        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(400);
        $errorResponse->method('getReasonPhrase')->willReturn('Bad Request');
        
        $errorStream = $this->createMock(StreamInterface::class);
        $errorStream->method('__toString')->willReturn(json_encode([
            'error' => 'invalid_grant',
            'message' => 'The authorization code is invalid.',
        ]) ?: '');
        $errorResponse->method('getBody')->willReturn($errorStream);

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('send')->willReturn($errorResponse);

        $provider = new YakNetProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'https://mock.app/callback',
        ], [
            'httpClient' => $httpClient,
        ]);

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('The authorization code is invalid.');
        
        $provider->getAccessToken('authorization_code', [
            'code' => 'invalid_code',
        ]);
    }

    public function testLoginButtonHtml(): void
    {
        $html = $this->provider->getLoginButtonHtml('state123', [
            'theme' => 'dark',
            'text' => 'YakNet ile Giriş',
        ]);

        $this->assertStringContainsString('<yaknet-login-button', $html);
        $this->assertStringContainsString('client-id="mock_client_id"', $html);
        $this->assertStringContainsString('redirect-uri="https://mock.app/callback"', $html);
        $this->assertStringContainsString('state="state123"', $html);
        $this->assertStringContainsString('theme="dark"', $html);
        $this->assertStringContainsString('YakNet ile Giriş', $html);
        $this->assertStringContainsString('/oauth/authorize?client_id=mock_client_id', $html);
    }
}
