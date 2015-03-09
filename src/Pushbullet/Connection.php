<?php

namespace Pushbullet;

/**
 * Connection
 *
 * @package Pushbullet
 */
class Connection
{
    const URL_PUSHES         = 'https://api.pushbullet.com/v2/pushes';
    const URL_DEVICES        = 'https://api.pushbullet.com/v2/devices';
    const URL_CONTACTS       = 'https://api.pushbullet.com/v2/contacts';
    const URL_UPLOAD_REQUEST = 'https://api.pushbullet.com/v2/upload-request';
    const URL_USERS          = 'https://api.pushbullet.com/v2/users';
    const URL_CHANNELS       = 'https://api.pushbullet.com/v2/channels';
    const URL_SUBSCRIPTIONS  = 'https://api.pushbullet.com/v2/subscriptions';
    const URL_CHANNEL_INFO   = 'https://api.pushbullet.com/v2/channel-info';
    const URL_EPHEMERALS     = 'https://api.pushbullet.com/v2/ephemerals';
    const URL_PHONEBOOK      = 'https://api.pushbullet.com/v2/permanents/phonebook';

    private static $curlCallback;

    /**
     * Add a callback function that will be invoked right before executing each cURL request.
     *
     * @param callable $callback The callback function.
     */
    public static function setCurlCallback(callable $callback)
    {
        self::$curlCallback = $callback;
    }

    /**
     * Send a request to a remote server using cURL.
     *
     * @param string $url        URL to send the request to.
     * @param string $method     HTTP method.
     * @param array  $data       Query data.
     * @param bool   $sendAsJSON Send the request as JSON.
     * @param string $apiKey     Use this API key to authenticate
     *
     * @return object Response.
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\InvalidTokenException
     * @throws Exceptions\NotFoundException
     */
    public static function sendCurlRequest($url, $method, $data = null, $sendAsJSON = true, $apiKey = null)
    {
        $curl = curl_init();

        if ($method == 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        curl_setopt($curl, CURLOPT_URL, $url);

        if (!empty($apiKey)) {
            curl_setopt($curl, CURLOPT_USERPWD, $apiKey);
        }

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        if ($method == 'POST' && $data !== null) {
            if ($sendAsJSON) {
                $data = json_encode($data);

                curl_setopt($curl, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data)
                ]);
            }

            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);

        if (self::$curlCallback !== null) {
            $curlCallback = self::$curlCallback;
            $curlCallback($curl);
        }

        $response = curl_exec($curl);

        if ($response === false) {
            $curlError = curl_error($curl);
            curl_close($curl);
            throw new Exceptions\ConnectionException('cURL Error: ' . $curlError);
        }

        $json = json_decode($response);

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode >= 400) {
            if ($httpCode === 401 || $httpCode === 403) {
                throw new Exceptions\InvalidTokenException($json->error->message);
            } else if ($httpCode === 404) {
                throw new Exceptions\NotFoundException($json->error->message);
            } else {
                throw new Exceptions\ConnectionException(
                    'HTTP Error ' . $httpCode . ' (' . $json->error->type . '): ' . $json->error->message,
                    $httpCode
                );
            }
        }

        return $json;
    }
}
