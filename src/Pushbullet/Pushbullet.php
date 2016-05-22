<?php

namespace Pushbullet;

/**
 * Pushbullet
 *
 * @package Pushbullet
 */
class Pushbullet
{
    private $apiKey;
    private $devices;
    private $channels;
    private $myChannels;
    private $contacts;

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
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\InvalidTokenException
     */
    public function getPushes($modifiedAfter = 0, $cursor = null, $limit = null)
    {
        $data = self::initData($modifiedAfter, $cursor, $limit);

        $pushes = Connection::sendCurlRequest(Connection::URL_PUSHES, 'GET', $data, false, $this->apiKey)->pushes;

        $objPushes = [];

        foreach ($pushes as $p) {
            if (!empty($p->active)) {
                $objPushes[] = new Push($p, $this->apiKey);
            }
        }

        return $objPushes;
    }

    /**
     * Returns a Push object with the specified iden.
     *
     * @param string $iden Iden of push notification.
     * @return Push Push notification object.
     * 
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\NotFoundException If there's no push notification with the specified iden
     */
    public function push($iden)
    {
        $response = Connection::sendCurlRequest(Connection::URL_PUSHES . '/' . $iden, 'GET', null, false, $this->apiKey);

        return new Push($response, $this->apiKey);
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
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\InvalidTokenException
     */
    public function getDevices($modifiedAfter = 0, $cursor = null, $limit = null)
    {
        $data = self::initData($modifiedAfter, $cursor, $limit);

        $devices = Connection::sendCurlRequest(Connection::URL_DEVICES, 'GET', $data, true, $this->apiKey)->devices;

        $objDevices = [];

        foreach ($devices as $d) {
            if (!empty($d->active)) {
                $objDevices[] = new Device($d, $this->apiKey);
            }
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
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\InvalidTokenException
     * @throws Exceptions\NotFoundException
     */
    public function device($idenOrNickname)
    {
        if ($this->devices === null) {
            $this->getDevices();
        }

        foreach ($this->devices as $d) {
            if ($d->iden == $idenOrNickname || $d->nickname == $idenOrNickname) {
                return $d;
            }
        }

        throw new Exceptions\NotFoundException("Device not found.");
    }

    /**
     * Target all devices for pushing. This method returns a pseudo-device object that can only be pushed to. It
     * does not support SMS, has no phonebook, and cannot be deleted.
     *
     * @return Device A pseudo-device that targets all available devices for pushing.
     */
    public function allDevices() {
        return new Device([
            "iden" => "",
            "pushable" => true,
            "has_sms" => false
        ], $this->apiKey);
    }

    /**
     * Create a new contact.
     *
     * @param string $name  Name.
     * @param string $email Email address.
     *
     * @return Contact The newly created contact.
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\InvalidRecipientException Thrown if the email address is invalid.
     * @throws Exceptions\InvalidTokenException
     */
    public function createContact($name, $email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new Exceptions\InvalidRecipientException('Invalid email address.');
        }

        $data = [
            'name'  => $name,
            'email' => $email
        ];

        return new Contact(
            Connection::sendCurlRequest(Connection::URL_CONTACTS, 'POST', $data, true, $this->apiKey),
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
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\InvalidTokenException
     */
    public function getContacts($modifiedAfter = 0, $cursor = null, $limit = null)
    {
        $data = self::initData($modifiedAfter, $cursor, $limit);

        $contacts = Connection::sendCurlRequest(Connection::URL_CONTACTS, 'GET', $data, false, $this->apiKey)->contacts;

        $objContacts = [];

        foreach ($contacts as $c) {
            if (!empty($c->active)) {
                $objContacts[] = new Contact($c, $this->apiKey);
            }
        }

        $this->contacts = $objContacts;

        return $objContacts;
    }

    /**
     * Target a contact by its name or email.
     *
     * @param string $nameOrEmail Name or email of the contact.
     *
     * @return Contact The contact.
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\InvalidTokenException
     * @throws Exceptions\NotFoundException
     */
    public function contact($nameOrEmail)
    {
        if ($this->contacts === null) {
            $this->getContacts();
        }

        foreach ($this->contacts as $c) {
            if ($c->name == $nameOrEmail || $c->email == $nameOrEmail) {
                return $c;
            }
        }

        throw new Exceptions\NotFoundException("Contact not found.");
    }

    /**
     * Get information about the current user.
     *
     * @return object Response.
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\InvalidTokenException
     */
    public function getUserInformation()
    {
        return Connection::sendCurlRequest(Connection::URL_USERS . '/me', 'GET', null, false, $this->apiKey);
    }

    /**
     * Update preferences for the current user.
     *
     * @param array $preferences Preferences.
     *
     * @return object Response.
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\InvalidTokenException
     */
    public function updateUserPreferences($preferences)
    {
        return Connection::sendCurlRequest(Connection::URL_USERS . '/me', 'POST', ['preferences' => $preferences], true,
            $this->apiKey);
    }

    /**
     * Target a channel to create, subscribe to, unsubscribe from, or get information.
     *
     * @param string $tag Channel tag.
     *
     * @return Channel Channel or a subscription to a channel.
     *                 If you need information about a channel, and not a subscription to one, it is
     *                 recommended to use the <code>getChannelInformation()</code> method and access the properties
     *                 of the object it returns.
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\InvalidTokenException
     */
    public function channel($tag)
    {
        if ($this->channels === null) {
            $this->getChannelSubscriptions();
        }

        if ($this->myChannels === null) {
            $this->getMyChannels();
        }

        foreach ($this->myChannels as $c) {
            if ($tag == $c->tag) {
                $c->myChannel = true;

                return $c;
            }
        }

        foreach ($this->channels as $c) {
            if ($tag == $c->channel->tag) {
                return $c;
            }
        }

        return new Channel(["tag" => $tag], $this->apiKey);
    }

    /**
     * Get a list of the channels the current user is subscribed to.
     *
     * @param int    $modifiedAfter Request contacts modified after this UNIX timestamp.
     * @param string $cursor        Request the next page via its cursor from a previous response. See the API
     *                              documentation (https://docs.pushbullet.com/http/) for a detailed description.
     * @param int    $limit         Maximum number of objects on each page.
     *
     * @return Channel[] Channels.
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\InvalidTokenException
     * @throws Exceptions\NotFoundException
     */
    public function getChannelSubscriptions($modifiedAfter = 0, $cursor = null, $limit = null)
    {
        $data = self::initData($modifiedAfter, $cursor, $limit);

        $subscriptions = Connection::sendCurlRequest(Connection::URL_SUBSCRIPTIONS, 'GET', $data, false,
            $this->apiKey)->subscriptions;

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
     * @param int    $modifiedAfter Request contacts modified after this UNIX timestamp.
     * @param string $cursor        Request the next page via its cursor from a previous response. See the API
     *                              documentation (https://docs.pushbullet.com/http/) for a detailed description.
     * @param int    $limit         Maximum number of objects on each page.
     *
     * @return Channel[] Channels.
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\InvalidTokenException
     * @throws Exceptions\NotFoundException
     */
    public function getMyChannels($modifiedAfter = 0, $cursor = null, $limit = null)
    {
        $data = self::initData($modifiedAfter, $cursor, $limit);

        $myChannels = Connection::sendCurlRequest(Connection::URL_CHANNELS, 'GET', $data, false,
            $this->apiKey)->channels;

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
     * Initialize data to be sent.
     *
     * @param int    $modifiedAfter Request contacts modified after this UNIX timestamp.
     * @param string $cursor        Request the next page via its cursor from a previous response. See the API
     *                              documentation (https://docs.pushbullet.com/http/) for a detailed description.
     * @param int    $limit         Maximum number of objects on each page.
     *
     * @return array Data.
     */
    private static function initData($modifiedAfter, $cursor, $limit)
    {
        $data = [];
        $data['modified_after'] = $modifiedAfter;
        $data['cursor'] = $cursor;
        $data['limit'] = $limit;

        return $data;
    }
}
