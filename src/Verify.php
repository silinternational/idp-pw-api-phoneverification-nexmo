<?php
namespace Sil\IdpPw\PhoneVerification\Nexmo;

use GuzzleHttp\Exception\RequestException;
use Nexmo\Verify as NexmoClient;
use Sil\IdpPw\Common\PhoneVerification\PhoneVerificationInterface;
use yii\base\Component;

/**
 * Class Verify
 * @package Sil\IdpPw\PhoneVerification\Nexmo
 * @link https://docs.nexmo.com/api-ref/verify Nexmo Verify API documentation
 */
class Verify extends Component implements PhoneVerificationInterface
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
     * Required - The name of the company or App you are using Verify for. This 18 character alphanumeric
     * string is used in the body of Verify message. For example: "Your brand PIN is ..".
     * @var string
     */
    public $brand;

    /**
     * Optional - The length of the PIN. Possible values are 6 or 4 characters. The default value is 4.
     * @var int [default=4]
     */
    public $codeLength = 4;

    /**
     * Optional - If do not set number in international format or you are not sure if number is correctly
     * formatted, set country with the two-character country code. For example, GB, US. Verify
     * works out the international phone number for you.
     * @var string
     * @link https://docs.nexmo.com/api-ref/voice-api/supported-languages
     */
    public $country;

    /**
     * Optional - By default, TTS are generated in the locale that matches number. For example,
     * the TTS for a 33* number is sent in French. Use this parameter to explicitly control the
     * language, accent and gender used for the Verify request. The default language is en-us.
     * @var string
     */
    public $language;

    /**
     * Optional - The PIN validity time from generation. This is an integer value between 30 and 3600
     * seconds. The default is 300 seconds. When specified together, pin_expiry must be an integer
     * multiple of next_event_wait. Otherwise, pin_expiry is set to next_event_wait.
     * @var int
     */
    public $pinExpiry;

    /**
     * Optional - An integer value between 60 and 900 seconds inclusive that specifies the wait
     * time between attempts to deliver the PIN. Verify calculates the default value based on the
     * average time taken by users to complete verification.
     * @var int
     */
    public $nextEventWait;

    /**
     * Optional - An 11 character alphanumeric string to specify the SenderID for SMS sent by Verify.
     * Depending on the destination of the phone number you are applying, restrictions may apply.
     * By default, sender_id is VERIFY.
     * @var string
     */
    public $senderId;


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
            'brand' => $this->brand,
        ];

        /*
         * Only add optional parameters if we have a value for them
         */
        if ( ! is_null($this->codeLength)){
            $requestData['code_length'] = $this->codeLength;
        }
        if ( ! is_null($this->country)) {
            $requestData['country'] = $this->country;
        }
        if ( ! is_null($this->language)) {
            $requestData['lg'] = $this->language;
        }
        if ( ! is_null($this->pinExpiry)) {
            $requestData['pin_expiry'] = $this->pinExpiry;
        }
        if ( ! is_null($this->nextEventWait)) {
            $requestData['next_event_wait'] = $this->nextEventWait;
        }
        if ( ! is_null($this->senderId)) {
            $requestData['sender_id'] = $this->senderId;
        }

        try {
            $results = $client->verify($requestData);
            if ((string)$results['status'] === '0') {
                if (isset($results['request_id']) && ! empty($results['request_id'])) {
                    return $results['request_id'];
                }
            }

            throw new \Exception(
                sprintf('Error: [%s]  %s', $results['status'], $results['error_text']),
                1460146281
            );
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $body = $response->json();
                throw new \Exception(
                    sprintf('Error: [%s] %s', $body['status'], $body['error_text']),
                    1460146801
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
        $client = $this->getClient();

        try {
            $results = $client->check([
                'request_id' => $resetCode,
                'code' => $userProvided,
            ]);
            if ((string)$results['status'] == '0') {
                return true;
            }

            throw new \Exception(
                sprintf('Error: [%s]  %s', $results['status'], $results['error_text']),
                1460146282
            );

        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $body = $response->json();
                throw new \Exception(
                    sprintf('Error: [%s] %s', $body['status'], $body['error_text']),
                    1460146802
                );
            }
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    private function getClient()
    {
        return new NexmoClient([
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret,
        ]);
    }
}
