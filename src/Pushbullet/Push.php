<?php

namespace Pushbullet;

/**
 * Push Notification
 *
 * @package Pushbullet
 */
class Push
{
    private $apiKey;

    public function __construct($properties, $apiKey)
    {
        foreach ($properties as $k => $v) {
            $this->$k = $v ?: null;
        }

        $this->apiKey = $apiKey;
    }

    /**
     * Dismiss the push notification.
     *
     * @return Push The same push notification, now dismissed.
     * @throws Exceptions\ConnectionException
     */
    public function dismiss()
    {
        return new Push(Connection::sendCurlRequest(Connection::URL_PUSHES . '/' . $this->iden, 'POST',
            ['dismissed' => true], true, $this->apiKey), $this->apiKey);
    }

    /**
     * Delete the push notification.
     *
     * @throws Exceptions\ConnectionException
     */
    public function delete()
    {
        Connection::sendCurlRequest(Connection::URL_PUSHES . '/' . $this->iden, 'DELETE', null, false, $this->apiKey);
    }
}
