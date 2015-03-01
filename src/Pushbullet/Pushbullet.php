<?php

namespace Pushbullet;

/**
 * Pushbullet
 *
 * @version 3.0.0
 */
class Pushbullet
{
    private $apiKey;
    private static $curlCallback;
    private $devices;
    private $channels;
    private $myChannels;

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

    /**
     * Pushbullet constructor.
     *
     * @param string $apiKey API key.
     *
     * @throws Exceptions\PushbulletException
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;

        if (!function_exists('curl_init')) {
            throw new Exceptions\PushbulletException('cURL library is not loaded.');
        }
    }

    /**
     * Get push history.
     *
     * @param int    $modifiedAfter Request pushes modified after this UNIX timestamp.
     * @param string $cursor        Request the next page via its cursor from a previous response. See the API
     *                              documentation (https://docs.pushbullet.com/http/) for a detailed description.
     * @param int    $limit         Maximum number of objects on each page.
     *
     * @return Push[] Pushes.
     * @throws Exceptions\PushbulletException
     */
    public function getPushes($modifiedAfter = 0, $cursor = null, $limit = null)
    {
        $data = [];
        $data['modified_after'] = $modifiedAfter;

        if ($cursor !== null) {
            $data['cursor'] = $cursor;
        }

        if ($limit !== null) {
            $data['limit'] = $limit;
        }

        $pushes = self::sendCurlRequest(self::URL_PUSHES, 'GET', $data, false, $this->apiKey)->pushes;

        $objPushes = [];

        foreach ($pushes as $p) {
            $objPushes[] = new Push($p, $this->apiKey);
        }

        return $objPushes;
    }

    /**
     * Get a list of available devices.
     *
     * @param int    $modifiedAfter Request devices modified after this UNIX timestamp.
     * @param string $cursor        Request the next page via its cursor from a previous response. See the API
     *                              documentation (https://docs.pushbullet.com/http/) for a detailed description.
     * @param int    $limit         Maximum number of objects on each page.
     *
     * @return Device[] Devices.
     * @throws Exceptions\PushbulletException
     */
    public function getDevices($modifiedAfter = 0, $cursor = null, $limit = null)
    {
        $data = [];
        $data['modified_after'] = $modifiedAfter;

        if ($cursor !== null) {
            $data['cursor'] = $cursor;
        }

        if ($limit !== null) {
            $data['limit'] = $limit;
        }

        $devices = self::sendCurlRequest(self::URL_DEVICES, 'GET', $data, true, $this->apiKey)->devices;

        $objDevices = [];

        foreach ($devices as $d) {
            $objDevices[] = new Device($d, $this->apiKey, function () {
                return $this->getUserInformation();
            });
        }

        $this->devices = $objDevices;

        return $objDevices;
    }

    /**
     * Target a device by its iden or nickname.
     *
     * @param string $idenOrNickname device_iden or nickname of the device.
     *
     * @return Device The device.
     * @throws Exceptions\NotFoundException
     */
    public function device($idenOrNickname)
    {
        if ($this->devices === null) {
            $this->getDevices();
        }

        foreach ($this->devices as $d) {
            if ((isset($d->iden) && $d->iden == $idenOrNickname) || (isset($d->nickname) && $d->nickname == $idenOrNickname)) {
                return $d;
            }
        }

        throw new Exceptions\NotFoundException("Device not found.");
    }

    /**
     * Create a new contact.
     *
     * @param string $name  Name.
     * @param string $email Email address.
     *
     * @return Contact The newly created contact.
     * @throws Exceptions\PushbulletException
     */
    public function createContact($name, $email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new Exceptions\PushbulletException('Create contact: Invalid email address.');
        }

        $queryData = [
            'name'  => $name,
            'email' => $email
        ];

        return new Contact(
            self::sendCurlRequest(self::URL_CONTACTS, 'POST', $queryData, true, $this->apiKey),
            $this->apiKey
        );
    }

    /**
     * Get a list of contacts.
     *
     * @param int    $modifiedAfter Request contacts modified after this UNIX timestamp.
     * @param string $cursor        Request the next page via its cursor from a previous response. See the API
     *                              documentation (https://docs.pushbullet.com/http/) for a detailed description.
     * @param int    $limit         Maximum number of objects on each page.
     *
     * @return Contact[] Contacts.
     * @throws Exceptions\PushbulletException
     */
    public function getContacts($modifiedAfter = 0, $cursor = null, $limit = null)
    {
        $data = [];
        $data['modified_after'] = $modifiedAfter;

        if ($cursor !== null) {
            $data['cursor'] = $cursor;
        }

        if ($limit !== null) {
            $data['limit'] = $limit;
        }

        $contacts = self::sendCurlRequest(self::URL_CONTACTS, 'GET', $data, false, $this->apiKey)->contacts;

        $objContacts = [];

        foreach ($contacts as $c) {
            $objContacts[] = new Contact($c, $this->apiKey);
        }

        return $objContacts;
    }

    /**
     * Get information about the current user.
     *
     * @return object Response.
     * @throws Exceptions\PushbulletException
     */
    public function getUserInformation()
    {
        return self::sendCurlRequest(self::URL_USERS . '/me', 'GET', null, false, $this->apiKey);
    }

    /**
     * Update preferences for the current user.
     *
     * @param array $preferences Preferences.
     *
     * @return object Response.
     * @throws Exceptions\PushbulletException
     */
    public function updateUserPreferences($preferences)
    {
        return self::sendCurlRequest(self::URL_USERS . '/me', 'POST', ['preferences' => $preferences], true,
            $this->apiKey);
    }

    /**
     * Target a channel to subscribe to, unsubscribe from, or get information.
     *
     * @param $tag Channel tag.
     *
     * @return Channel Channel.
     * @throws Exceptions\PushbulletException
     */
    public function channel($tag)
    {
        if ($this->channels === null) {
            $this->getChannelSubscriptions();
        }

        if ($this->myChannels === null) {
            $this->getMyChannels();
        }

        foreach ($this->channels as $c) {
            if ($tag == $c->channel->tag) {
                return $c;
            }
        }

        foreach ($this->myChannels as $c) {
            if ($tag == $c->tag) {
                $c->myChannel = true;

                return $c;
            }
        }

        return new Channel(["tag" => $tag], $this->apiKey);
    }

    /**
     * Get a list of the channels the current user is subscribed to.
     *
     * @return Channel[] Channels.
     * @throws Exceptions\ConnectionException
     */
    public function getChannelSubscriptions()
    {
        $subscriptions = self::sendCurlRequest(self::URL_SUBSCRIPTIONS, 'GET', null, false, $this->apiKey)->subscriptions;

        $objChannels = [];

        foreach ($subscriptions as $s) {
            if (!empty($s->active)) {
                $objChannels[] = new Channel($s, $this->apiKey);
            }
        }

        $this->channels = $objChannels;

        return $objChannels;
    }

    /**
     * Get a list of channels created by the current user.
     *
     * @return Channel[] Channels.
     * @throws Exceptions\ConnectionException
     */
    public function getMyChannels()
    {
        $myChannels = self::sendCurlRequest(self::URL_CHANNELS, 'GET', null, false, $this->apiKey)->channels;

        $objChannels = [];

        foreach ($myChannels as $c) {
            if (!empty($c->active)) {
                $c->myChannel = true;
                $objChannels[] = new Channel($c, $this->apiKey);
            }
        }

        $this->myChannels = $objChannels;

        return $objChannels;
    }

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
     */
    public static function sendCurlRequest($url, $method, $data = null, $sendAsJSON = true, $apiKey = null)
    {
        $curl = curl_init();

        if ($method == 'GET' && $data !== null) {
            $url .= '?' . http_build_query($data);
        }

        curl_setopt($curl, CURLOPT_URL, $url);

        if ($apiKey) {
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

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($httpCode >= 400) {
            curl_close($curl);
            $responseParsed = json_decode($response);
            throw new Exceptions\ConnectionException('HTTP Error ' . $httpCode .
                ' (' . $responseParsed->error->type . '): ' . $responseParsed->error->message);
        }

        curl_close($curl);

        return json_decode($response);
    }
}
