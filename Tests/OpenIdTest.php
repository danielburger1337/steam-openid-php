<?php declare(strict_types=1);

namespace danielburger1337\SteamOpenId\Tests;

use danielburger1337\SteamOpenId\SteamOpenID;
use PHPUnit\Framework\TestCase;

class OpenIdTest extends TestCase
{
    private const REALM = 'http://localhost:5000';
    private const RETURN_TO = 'http://localhost:5000/Callback.php';

    /**
     * Test $openId->getRealm().
     */
    public function testGetRealm()
    {
        $this->assertEquals(self::REALM, $this->getInstance()->getRealm());
    }

    /**
     * Test $openId->getReturnTo().
     */
    public function testGetReturnTo()
    {
        $this->assertEquals(self::RETURN_TO, $this->getInstance()->getReturnTo());
    }

    /**
     * Test $openId->createCheckIdSetupData().
     */
    public function testCreateCheckIdSetupData()
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

    private function getInstance()
    {
        return new SteamOpenID(self::REALM, self::RETURN_TO);
    }
}
