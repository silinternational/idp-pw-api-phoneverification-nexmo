<?php
namespace Sil\IdpPw\PhoneVerification\Nexmo;

use GuzzleHttp\Exception\RequestException;
use Sil\IdpPw\Common\PhoneVerification\PhoneVerificationInterface;

/**
 * Class Verify
 * @package Sil\IdpPw\PhoneVerification\Nexmo
 * @link https://docs.nexmo.com/api-ref/verify Nexmo Verify API documentation
 */
class VerifyThenSms extends Base implements PhoneVerificationInterface
{

    /**
     * Attempt to use Verify first, but if network not supported or fails, attempt SMS
     * @param string $phoneNumber The mobile or landline phone number to verify. Unless you are setting country
     *                            explicitly, this number must be in E.164 format. For example, 4478342080934.
     * @param string $code This is ignored in Nexmo Verify implementation
     * @return string The verification code used, or another identifier to be used with self::verify() later
     * @throws \Exception
     */
    public function send($phoneNumber, $code)
    {
        if (empty($code)) {
            throw new \Exception('Code cannot be empty', 1469712310);
        } elseif (empty($this->apiKey)) {
            throw new \Exception('API Key required for Nexmo', 1469712301);
        } elseif (empty($this->apiSecret)) {
            throw new \Exception('API Secret required for Nexmo', 1469712311);
        } elseif (empty($this->brand)) {
            throw new \Exception('Brand required for Nexmo', 1469712312);
        } elseif (empty($this->from)) {
            throw new \Exception('From is required for Nexmo', 1469712313);
        }

        $verify = $this->getVerifyClient();

        try {
            /*
             * Returns the Verify ID if successful
             */
            return $verify->send($phoneNumber, $code);
        } catch (\Exception $e) {
            /*
             * Verify failed, log it and, try using SMS
             */
            $previous = $e->getPrevious();
            if ($previous && $previous instanceof RequestException) {
                /** @var $e RequestException */
                if($e->hasResponse()) {
                    $response = $e->getResponse();
                    $body = $response->json();
                    $message = $body['error_text'];
                    $errorCode = $body['status'];
                } else {
                    $message = $e->getMessage();
                    $errorCode = $e->getCode();
                }
            } else {
                /** @var $e \Exception */
                $message = $e->getMessage();
                $errorCode = $e->getCode();
            }

            \Yii::error([
                'action' => 'phone verification',
                'type' => 'verify',
                'status' => 'error',
                'error' => $message,
                'code' => $errorCode,
            ]);
        }

        $sms = $this->getSmsClient();

        try {
            return $sms->send($phoneNumber, $code);
        } catch (\Exception $e) {
            /*
             * SMS also failed, just throw the exception back
             */
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
        if (empty($resetCode) || empty($userProvided)) {
            throw new \Exception('Reset code and user provided code cannot be empty', 1469713857);
        }

        /*
         * Verify codes are long IDs for the Verify service, so if $resetCode is same length as
         * $this->codeLength then SMS was used to send code
         */
        if (strlen($resetCode) === $this->codeLength) {
            $sms = $this->getSmsClient();
            return $sms->verify($resetCode, $userProvided);
        }

        $verify = $this->getVerifyClient();

        return $verify->verify($resetCode, $userProvided);
    }

    /**
     * @return Verify
     */
    private function getVerifyClient()
    {
        $verify = new Verify();
        $verify->apiKey = $this->apiKey;
        $verify->apiSecret = $this->apiSecret;
        $verify->brand = $this->brand;
        $verify->codeLength = $this->codeLength;
        $verify->country = $this->country;
        $verify->language = $this->language;
        $verify->pinExpiry = $this->pinExpiry;
        $verify->nextEventWait = $this->nextEventWait;
        $verify->senderId = $this->senderId;

        return $verify;
    }

    /**
     * @return Sms
     */
    private function getSmsClient()
    {
        $sms = new Sms();
        $sms->apiKey = $this->apiKey;
        $sms->apiSecret = $this->apiSecret;
        $sms->from = $this->from;

        return $sms;
    }

}
