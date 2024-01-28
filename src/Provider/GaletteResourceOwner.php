<?php

namespace Galette\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class GaletteResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $response;

    /**
     * @param array<string, mixed> $response
     */
    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    /**
     * Get resource owner id
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->getValueByKey($this->response, 'id');
    }

    /**
     * Get resource owner email
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->getValueByKey($this->response, 'email');
    }

    /**
     * Get resource owner username
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->getValueByKey($this->response, 'username');
    }

    /**
     * Get resource owner language
     *
     * @return string|null
     */
    public function getLang(): ?string
    {
        return $this->getValueByKey($this->response, 'language');
    }

    /**
     * Get resource owner membership status
     *
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return (int)$this->getValueByKey($this->response, 'status');
    }


    /**
     * Return all owner details available as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->response;
    }
}
