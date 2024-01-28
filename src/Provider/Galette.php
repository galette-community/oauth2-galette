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
     * @var array
     */
    protected array $scope;

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

    public function getBaseAuthorizationUrl()
    {
        return $this->getBaseURL() . '/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getBaseURL() . '/access_token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->getBaseURL() . '/user';
    }

    protected function getDefaultScopes()
    {
        return [];
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode > 400) {
            throw new IdentityProviderException(
                $data['message'] ?: $response->getReasonPhrase(),
                $statusCode,
                $response
            );
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new GaletteResourceOwner($response);
    }
}
