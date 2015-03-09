<?php

namespace Pushbullet;

/**
 * Phonebook Entry
 *
 * @package Pushbullet
 */
class PhonebookEntry
{
    private $deviceParent;

    public function __construct($properties, Device $parent)
    {
        foreach ($properties as $k => $v) {
            $this->$k = $v ?: null;
        }

        $this->deviceParent = $parent;
    }

    /**
     * Send an SMS message to the contact.
     *
     * @param string $message Message.
     *
     * @return Push
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\InvalidRecipientException Thrown if the contact doesn't have a number.
     * @throws Exceptions\NoSmsException Thrown if the device cannot send SMS messages.
     */
    public function sendSms($message)
    {
        if (empty($this->phone)) {
            throw new Exceptions\InvalidRecipientException("Phonebook entry doesn't have a phone number.");
        }

        return $this->deviceParent->sendSms($this->phone, $message);
    }
}
