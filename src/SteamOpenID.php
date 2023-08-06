<?php declare(strict_types=1);

namespace danielburger1337\SteamOpenId;

use danielburger1337\SteamOpenId\Exception\CheckAuthenticationException;
use danielburger1337\SteamOpenId\Exception\InvalidParameterException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\HttpClient\Psr18Client;

class SteamOpenID
{
    /**
     * @var string The OpenID login endpoint.
     */
    public const OP_ENDPOINT = 'https://steamcommunity.com/openid/login';

    /**
     * @param string                       $realm          Your OpenID "realm".
     * @param string                       $returnTo       Your OpenID "return_to" URI.
     * @param ClientInterface|null         $httpClient     [optional] Your psr-18 http client implementation.
     *                                                     If none is provided, a "symfony/http-client" is tried to be instantied.
     * @param RequestFactoryInterface|null $requestFactory [optional] Your psr-17 http factory implementation.
     *                                                     If none is provided, "nyholm/psr-7" is tried to be instantied.
     */
    public function __construct(
        private string $realm,
        private string $returnTo,
        private ?ClientInterface $httpClient = null,
        private ?RequestFactoryInterface $requestFactory = null,
    ) {
    }

    /**
     * Get your OpenID "realm".
     */
    public function getRealm(): string
    {
        return $this->realm;
    }

    /**
     * Get your OpenID "return_to" URI.
     */
    public function getReturnTo(): string
    {
        return $this->returnTo;
    }

    /**
     * Construct a "checkid_setup" URI.
     *
     * When the user is redirected to this URI, the authentication flow is started.
     *
     * @return string The "checkid_setup" URI.
     */
    public function constructCheckIdSetupUri(): string
    {
        return self::OP_ENDPOINT.'?'.\http_build_query($this->createCheckIdSetupData());
    }

    /**
     * Create the "checkid_setup" data.
     *
     * You can send this data via a POST request in the request body
     * OR you can send this data via a GET request in the query parameters
     * to the OP endpoint to start the authentication flow.
     *
     * @return array The setup data.
     */
    public function createCheckIdSetupData(): array
    {
        return [
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',

            'openid.realm' => $this->realm,
            'openid.return_to' => $this->returnTo,

            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ];
    }

    /**
     * Verify the parameters returned by the OpenID provider (steam).
     *
     * @param array $parameters The returned parameters ($_GET).
     *
     * @return string The users 64-bit SteamID.
     *
     * @throws InvalidParameterException    If a parameter was malformed / missing.
     * @throws CheckAuthenticationException If verification during the "check_authentication" step failed.
     */
    public function verifyCallback(array $parameters, bool $areDotsSpaces = true): string
    {
        // locally verify all given parameters
        $assertions = new AssertionValidator($parameters, $areDotsSpaces);
        $postData = [
            'openid.ns' => $assertions->assertKeyValuePair('openid.ns', 'http://specs.openid.net/auth/2.0'),
            'openid.mode' => $assertions->assertKeyValuePair('openid.mode', 'id_res'),
            'openid.op_endpoint' => $assertions->assertKeyValuePair('openid.op_endpoint', self::OP_ENDPOINT),
            'openid.return_to' => $assertions->assertKeyValuePair('openid.return_to', $this->returnTo),
            'openid.assoc_handle' => $assertions->assertKeyValuePair('openid.assoc_handle', '1234567890'),

            'openid.response_nonce' => $assertions->ensureParameterExists('openid.response_nonce'),
            'openid.signed' => $assertions->ensureParameterExists('openid.signed'),
            'openid.sig' => $assertions->ensureParameterExists('openid.sig'),

            'openid.claimed_id' => $assertions->ensureParameterExists('openid.claimed_id'),
            'openid.identity' => $assertions->ensureParameterExists('openid.identity'),
        ];

        // To verify the given parameters with steam, make a call to the OP endpoint
        // copying every parameter from the callback with one exception:
        // replace "openid.mode=id_res" with "openid.mode=check_authentication".
        $postData['openid.mode'] = 'check_authentication';

        try {
            $request = $this->getRequestFactory()
                ->createRequest('POST', self::OP_ENDPOINT.'?'.\http_build_query($postData));

            $response = $this->getHttpClient()->sendRequest($request);

            $isValid = $response->getStatusCode() === 200 && \str_contains((string) $response->getBody(), 'is_valid:true');
        } catch (ClientExceptionInterface $e) {
            throw new CheckAuthenticationException('Failed to verify with steam.', previous: $e);
        }

        if (!$isValid) {
            throw new CheckAuthenticationException('Steam denied the authentication request.');
        }

        return \str_replace('https://steamcommunity.com/openid/id/', '', $postData['openid.claimed_id']);
    }

    /**
     * Lazy-load the psr-18 http client.
     */
    private function getHttpClient(): ClientInterface
    {
        if (null === $this->httpClient) {
            if (!\class_exists(Psr18Client::class)) {
                throw new \LogicException(
                    \sprintf('No psr-18 client passed and failed to load %s. Try `composer require symfony/http-client`.', Psr18Client::class)
                );
            }

            $this->httpClient = new Psr18Client();
        }

        return $this->httpClient;
    }

    /**
     * Lazy-load the psr-17 http factory.
     */
    private function getRequestFactory(): RequestFactoryInterface
    {
        if (null === $this->requestFactory) {
            if (!\class_exists(Psr17Factory::class)) {
                throw new \LogicException(
                    \sprintf('No psr-17 factory passed and failed to load %s. Try `composer require nyholm/psr7`.', Psr17Factory::class)
                );
            }

            $this->requestFactory = new Psr17Factory();
        }

        return $this->requestFactory;
    }
}
