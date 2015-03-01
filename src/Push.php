<?php

namespace Pushbullet;


class Push
{
    private $apiKey;

    public function __construct($pushProperties, $apiKey)
    {
        foreach ($pushProperties as $k => $v) {
            $this->$k = $v ?: null;
        }

        $this->apiKey = $apiKey;
    }

    /**
     * Dismiss the push.
     *
     * @return Push The same push, now dismissed.
     * @throws Exceptions\ConnectionException
     */
    public function dismiss()
    {
        return new Push(Pushbullet::sendCurlRequest(Pushbullet::URL_PUSHES . '/' . $this->iden, 'POST',
            ['dismissed' => true], true, $this->apiKey), $this->apiKey);
    }

    /**
     * Delete the push.
     *
     * @throws Exceptions\ConnectionException
     */
    public function delete()
    {
        Pushbullet::sendCurlRequest(Pushbullet::URL_PUSHES . '/' . $this->iden, 'DELETE', null, null, $this->apiKey);
    }
}