<?php
namespace tests;

include __DIR__ . '/../vendor/autoload.php';

use Sil\IdpPw\PhoneVerification\Nexmo\Base;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    public function testFormat()
    {
        $config = include __DIR__ . '/config.local.php';
        $client = $this->getClient();
        $format = $client->format('14085551212');
        $this->assertEquals('1 (408) 555-1212', $format);
    }

    public function testFormatError()
    {
        $config = include __DIR__ . '/config.local.php';
        $client = $this->getClient();
        $this->setExpectedException('\Exception','',1469727752);
        $format = $client->format('085551212');
    }

    private function getClient($extra = [])
    {
        $config = include __DIR__ . '/config.local.php';
        $config = array_merge_recursive($config, $extra);
        $client = new Base();
        $client->apiKey = $config['api_key'];
        $client->apiSecret = $config['api_secret'];

        return $client;
    }
}