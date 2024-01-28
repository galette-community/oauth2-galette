<?php

declare(strict_types=1);

namespace Galette\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Galette extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * Galette instance URL
     *
     * @var string
     */
    protected string $instance;

    /**
     * Plugin installation directory
     *
     * @var string
     */
    protected string $pluginDirectory = 'plugin-oauth2';

    /**
     * @var string[]
     */
    protected array $scope;

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        if (!isset($options['instance'])) {
            throw new \InvalidArgumentException(
                'The "instance" option not set. Please set a Galette instance URL.'
            );
        }
        $this->instance = $options['instance'];

        if (isset($options['scope'])) {
            $this->scope = $options['scope'];
        }

        if (isset($options['pluginDir'])) {
            $this->pluginDirectory = $options['pluginDir'];
        }

        parent::__construct($options, $collaborators);
    }

    public function getBaseURL(): string
    {
        return sprintf(
            '%s/plugins/%s',
            trim($this->instance, '/'),
            $this->pluginDirectory
        );
    }

    public function getBaseAuthorizationUrl(): string
    {
        return $this->getBaseURL() . '/authorize';
    }

    /**
     * @param array<string, mixed> $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->getBaseURL() . '/access_token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->getBaseURL() . '/user';
    }

    /**
     * @return string[]
     */
    protected function getDefaultScopes(): array
    {
        return [];
    }

    /**
     * @param ResponseInterface $response
     * @param string|array<string, string> $data
     * @return void
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode > 400) {
            throw new IdentityProviderException(
                (is_array($data) && $data['message']) ? $data['message'] : $response->getReasonPhrase(),
                $statusCode,
                $response
            );
        }
    }

    /**
     * @param array<string, mixed> $response
     * @param AccessToken $token
     * @return GaletteResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new GaletteResourceOwner($response);
    }
}
