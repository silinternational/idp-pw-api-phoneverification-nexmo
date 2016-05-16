<?php
namespace Sil\IdpPw\PhoneVerification\Nexmo;

use GuzzleHttp\Exception\RequestException;
use Nexmo\Sms as NexmoClient;
use Sil\IdpPw\Common\PhoneVerification\PhoneVerificationInterface;
use yii\base\Component;

/**
 * Class Verify
 * @package Sil\IdpPw\PhoneVerification\Nexmo
 * @link https://docs.nexmo.com/api-ref/verify Nexmo Verify API documentation
 */
class Sms extends Component implements PhoneVerificationInterface
{

    /**
     * Required
     * @var string
     */
    public $apiKey;

    /**
     * Required
     * @var string
     */
    public $apiSecret;

    /**
     * Required - The Nexmo phone number to send SMS from
     * @var string
     */
    public $from;

    /**
     * Initiate phone verification
     * @param string $phoneNumber The mobile or landline phone number to verify. Unless you are setting country
     *                            explicitly, this number must be in E.164 format. For example, 4478342080934.
     * @param string $code This is ignored in Nexmo Verify implementation
     * @return string The verification code used, or another identifier to be used with self::verify() later
     * @throws \Exception
     */
    public function send($phoneNumber, $code)
    {
        $client = new NexmoClient([
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret,
        ]);

        /*
         * Parameters for API call
         */
        $requestData = [
            'number' => $phoneNumber,
            'from' => $this->from,
            'text' => sprintf('Verification code: %s', $code),
            'to' => $phoneNumber,
        ];

        try {
            $results = $client->send($requestData);
            $results = $results['messages'][0];
            if ((string)$results['status'] == '0') {
                return $code;
            }

            throw new \Exception(
                sprintf('Error: [%s]  %s', $results['status'], $results['error-text']),
                1460146367
            );
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $body = $response->json();
                throw new \Exception(
                    sprintf('Error: [%s] %s', $body['status'], $body['error-text']),
                    1460146928
                );
            }
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    /**
     * Verify that previously stored $resetCode matches the code provided by the user, $userProvided
     * Component may use $resetCode as a key for it's own purposes, as is the case with the Nexmo Verify service.
     * Return true on success or throw NotMatchException when match fails.
     * Throw \Exception when other exception occurs like network issue with service provider
     * @param string $resetCode
     * @param string $userProvided
     * @return boolean
     * @throws \Exception
     * @throws \Sil\IdpPw\Common\PhoneVerification\NotMatchException
     */
    public function verify($resetCode, $userProvided)
    {
        return $resetCode === $userProvided;
    }

}
