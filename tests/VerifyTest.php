<?php
namespace tests;

include __DIR__ . '/../vendor/autoload.php';

use Sil\IdpPw\PhoneVerification\Nexmo\Verify;

class VerifyTest extends \PHPUnit_Framework_TestCase
{
    public function testVerify()
    {
        $config = include __DIR__ . '/config.local.php';
        $client = $this->getClient();
        $response = $client->send($config['number']);
        echo $response;
        $this->assertNotNull($response);
    }

    public function testCheck()
    {
        $config = include __DIR__ . '/config.local.php';
        $client = $this->getClient();
        $response = $client->verify($config['request_id'], $config['code']);
        $this->assertTrue($response);
    }

    private function getClient($extra = [])
    {
        $config = include __DIR__ . '/config.local.php';
        $config = array_merge_recursive($config, $extra);
        $client = new Verify();
        $client->apiKey = $config['api_key'];
        $client->apiSecret = $config['api_secret'];
        $client->brand = 'Verify Test';

        return $client;
    }
}