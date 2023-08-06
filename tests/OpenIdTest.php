<?php declare(strict_types=1);

namespace danielburger1337\SteamOpenId\Tests;

use danielburger1337\SteamOpenId\Exception\CheckAuthenticationException;
use danielburger1337\SteamOpenId\Exception\InvalidParameterException;
use danielburger1337\SteamOpenId\SteamOpenID;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class OpenIdTest extends TestCase
{
    private const REALM = 'http://localhost:5000';
    private const RETURN_TO = 'http://localhost:5000/Callback.php';

    public function testGetRealm(): void
    {
        $this->assertEquals(self::REALM, $this->getInstance()->getRealm());
    }

    public function testGetReturnTo(): void
    {
        $this->assertEquals(self::RETURN_TO, $this->getInstance()->getReturnTo());
    }

    public function testCreateCheckIdSetupData(): void
    {
        $openId = $this->getInstance();

        $data = $openId->createCheckIdSetupData();

        $this->assertIsArray($data);

        $this->assertArrayHasKey('openid.ns', $data);
        $this->assertEquals('http://specs.openid.net/auth/2.0', $data['openid.ns']);

        $this->assertArrayHasKey('openid.mode', $data);
        $this->assertEquals('checkid_setup', $data['openid.mode']);

        $this->assertArrayHasKey('openid.realm', $data);
        $this->assertEquals(self::REALM, $data['openid.realm']);

        $this->assertArrayHasKey('openid.return_to', $data);
        $this->assertEquals(self::RETURN_TO, $data['openid.return_to']);

        $this->assertArrayHasKey('openid.identity', $data);
        $this->assertEquals('http://specs.openid.net/auth/2.0/identifier_select', $data['openid.identity']);

        $this->assertArrayHasKey('openid.claimed_id', $data);
        $this->assertEquals('http://specs.openid.net/auth/2.0/identifier_select', $data['openid.claimed_id']);
    }

    public function testValidVerifyCallback(): void
    {
        $client = new MockHttpClient(function (string $method, string $url): ResponseInterface {
            $this->assertSame('POST', $method);
            $this->assertStringStartsWith(SteamOpenID::OP_ENDPOINT, $url);

            return new MockResponse(\file_get_contents(__DIR__.'/Fixtures/response.txt'), [
                'http_code' => 200,
                'response_headers' => [
                    'content-type' => 'text/plain',
                ],
            ]);
        });

        $instance = $this->getInstance(new Psr18Client($client));

        $steamId = $instance->verifyCallback($this->createValidCallbackData(), false);
        $this->assertSame('foo', $steamId);
    }

    public function testSteamErrorVerifyCallback(): void
    {
        $client = new MockHttpClient(function (string $method, string $url): ResponseInterface {
            $this->assertSame('POST', $method);
            $this->assertStringStartsWith(SteamOpenID::OP_ENDPOINT, $url);

            return new MockResponse('', [
                'http_code' => 500,
                'response_headers' => [
                    'content-type' => 'text/plain',
                ],
            ]);
        });

        $this->expectException(CheckAuthenticationException::class);
        $this->expectExceptionCode(CheckAuthenticationException::STEAM_ERROR);

        $this->getInstance(new Psr18Client($client))->verifyCallback($this->createValidCallbackData(), false);
    }

    public function testDeniedVerifyCallback(): void
    {
        $client = new MockHttpClient(function (string $method, string $url): ResponseInterface {
            return new MockResponse(\file_get_contents(__DIR__.'/Fixtures/denied_response.txt'), [
                'http_code' => 200,
                'response_headers' => [
                    'content-type' => 'text/plain',
                ],
            ]);
        });

        $this->expectException(CheckAuthenticationException::class);
        $this->expectExceptionCode(CheckAuthenticationException::ACCESS_DENIED);

        $this->getInstance(new Psr18Client($client))->verifyCallback($this->createValidCallbackData(), false);
    }

    public function testIncompleteCallbackData(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('The parameter "openid.ns" was not found.');

        $data = $this->createValidCallbackData();
        unset($data['openid.ns']);

        $this->getInstance()->verifyCallback($data, false);
    }

    public function testInvalidCallbackData(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('The "openid.mode" parameter must always equal "id_res".');

        $data = $this->createValidCallbackData();
        $data['openid.mode'] = 'foobar';

        $this->getInstance()->verifyCallback($data, false);
    }

    private function createValidCallbackData(): array
    {
        return [
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'id_res',
            'openid.op_endpoint' => SteamOpenID::OP_ENDPOINT,
            'openid.return_to' => self::RETURN_TO,
            'openid.assoc_handle' => '1234567890',

            'openid.response_nonce' => '123456',
            'openid.signed' => '123456',
            'openid.sig' => '123456',

            'openid.claimed_id' => 'https://steamcommunity.com/openid/id/foo',
            'openid.identity' => 'bar',
        ];
    }

    private function getInstance(ClientInterface $client = null): SteamOpenID
    {
        return new SteamOpenID(self::REALM, self::RETURN_TO, $client);
    }
}
