<?php

namespace Galette\OAuth2\Client\Test\Provider;

use JBelien\OAuth2\Client\Provider\OpenStreetMap;
use League\OAuth2\Client\Tool\QueryBuilderTrait;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Stream;

class GaletteTest extends TestCase
{
    use QueryBuilderTrait;

    protected string $instance = 'https://galette.localhost/galette';
    protected string $pluginDir = 'oauth2Plugin';
    protected \Galette\OAuth2\Client\Provider\Galette $provider;

    protected function setUp(): void
    {
        $this->provider = new \Galette\OAuth2\Client\Provider\Galette([
            'clientId'      => 'mock_client_id',
            'clientSecret'  => 'mock_secret',
            'redirectUri'   => 'mock_redirect_uri',
            'instance'      => $this->instance,
            'pluginDir'     => $this->pluginDir,
        ]);
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/galette/plugins/oauth2Plugin/access_token', $uri['path']);
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/galette/plugins/oauth2Plugin/authorize', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        //$response->shouldReceive('getBody')->andReturn('{"access_token": "mock_access_token", "token_type": "bearer", "account_id": "12345", "uid": "deprecated_id"}');
        $response
            ->shouldReceive('getBody')
            ->andReturn(
                new Stream(
                    fopen(
                        'data://text/plain,{"access_token": "mock_access_token"}',
                        'r'
                    )
                )
            );
        $response
            ->shouldReceive('getStatusCode')
            ->andReturn(200);

        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $this->assertEquals('mock_access_token', $token->getToken());
    }

    public function testUserData()
    {
        $userId = 42;
        $username = 'mock_username';
        $email = 'mock_mail@mock_domain.com';
        $lang = 'fr_FR';
        $status = 1;

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse
            ->shouldReceive('getBody')
            ->andReturn(
                new Stream(
                    fopen(
                        'data://text/plain,{"access_token": "mock_access_token"}',
                        'r'
                    )
                )
            );

        $postResponse
            ->shouldReceive('getStatusCode')
            ->andReturn(200);

        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse
            ->shouldReceive('getBody')
            ->andReturn(
                new Stream(
                    fopen(
                        'data://text/plain,{"id": '.$userId.', "username": "'.$username.'", "email": "'.$email.'", "language": "'.$lang.'", "status": '.$status.'}',
                        'r'
                    )
                )
            );

        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($username, $user->getUsername());
        $this->assertEquals($lang, $user->getLang());
        $this->assertEquals($status, $user->getStatus());

        $this->assertSame(
            [
                'id' => $userId,
                'username' => $username,
                'email' => $email,
                'language' => $lang,
                'status' => $status,
            ],
            $user->toArray()
        );
    }

    public function testUserDataFails()
    {
        $this->expectException(\League\OAuth2\Client\Provider\Exception\IdentityProviderException::class);
        $status = rand(400,600);

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse
            ->shouldReceive('getBody')
            ->andReturn(
                new Stream(
                    fopen(
                        'data://text/plain,{"access_token": "mock_access_token"}',
                        'r'
                    )
                )
            );

        $postResponse
            ->shouldReceive('getStatusCode')
            ->andReturn(200);

        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse
            ->shouldReceive('getBody')
            ->andReturn(
                new Stream(
                    fopen(
                        'data://text/plain,{"error": "invalid_request","message": "Unknown request"}',
                        'r'
                    )
                )
            );
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn($status);
        $userResponse->shouldReceive('getReasonPhrase')->andReturn('Unknown request');

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);
    }
}
