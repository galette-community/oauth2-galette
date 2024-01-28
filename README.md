# Galette Provider for OAuth 2.0 Client

This package provides Galette OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

The [Galette OAuth plugin](https://galette-community.github.io/plugin-oauth2/) must be installed on you [Galette](https://galette.eu) instance.

## Installation

```cmd
composer require galette-community/oauth2-galette
```

## Usage

```php
$galetteProvider = new \Galette\OAuth2\Client\Provider\Galette([
    //information related to the app where you will use galette-oauth2
    'clientId'      => 'yourId',          // The client ID assigned to you
    'clientSecret'  => 'yourSecret',      // The client password assigned to you
    'redirectUri'   => 'yourRedirectUri', // The return URL you specified for your app
    //information related to the galette instance you want to connect to
    'instance'      => 'yourInstance',    // The instance of Galette you want to connect to
    'pluginDir'     => 'yourPluginDir',   // The directory where the plugin is installed - defaults to 'plugin-oauth2'
]);

// Get authorization code
if (!isset($_GET['code'])) {
    // Options are optional, defaults to 'read_prefs' only
    $options = ['instance' => 'https://my.galette'];

    // Get authorization URL
    $authorizationUrl = $galetteProvider->getAuthorizationUrl($options);

    // Get state and store it to the session
    $_SESSION['oauth2state'] = $galetteProvider->getState();

    // Redirect user to authorization URL
    header('Location: ' . $authorizationUrl);
    exit;
// Check for errors
} elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {
    if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
    }
    exit('Invalid state');
} else {
    // Get access token
    try {
        $accessToken = $galetteProvider->getAccessToken(
            'authorization_code',
            [
                'code' => $_GET['code']
            ]
        );
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        exit($e->getMessage());
    }

    // Get resource owner
    try {
        $resourceOwner = $galetteProvider->getResourceOwner($accessToken);
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        exit($e->getMessage());
    }
        
    // Now you can store the results to session etc.
    $_SESSION['accessToken'] = $accessToken;
    $_SESSION['resourceOwner'] = $resourceOwner;
    
    var_dump(
        $resourceOwner->getId(),
        $resourceOwner->getEmail(),
        $resourceOwner->getUsername(),
        $resourceOwner->getLang(),
        $resourceOwner->getStatus(),
        $resourceOwner->toArray()
    );
}
```

For more information see the PHP League's general usage examples.

## Testing

``` bash
./vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](https://github.com/jbelien/oauth2-openstreetmap/blob/master/LICENSE) for more information.
