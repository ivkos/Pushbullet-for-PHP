<?php

namespace Pushbullet;

/**
 * Device
 *
 * @package Pushbullet
 */
class Device
{
    use Pushable;

    public function __construct($properties, $apiKey)
    {
        foreach ($properties as $k => $v) {
            $this->$k = $v ?: null;
        }

        $this->apiKey = $apiKey;

        $this->setPushableRecipient("device", $this->iden);
    }

    /**
     * Send an SMS message from the device.
     *
     * @param string $toNumber Phone number of the recipient.
     * @param string $message  Text of the message.
     *
     * @return Push
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\NoSmsException Thrown if the device cannot send SMS messages.
     */
    public function sendSms($toNumber, $message)
    {
        if (empty($this->has_sms)) {
            throw new Exceptions\NoSmsException("Device cannot send SMS messages.");
        }

        $data = [
            'type' => 'push',
            'push' => [
                'type'               => 'messaging_extension_reply',
                'package_name'       => 'com.pushbullet.android',
                'source_user_iden'   => (new Pushbullet($this->apiKey))->getUserInformation()->iden,
                'target_device_iden' => $this->iden,
                'conversation_iden'  => $toNumber,
                'message'            => $message
            ]
        ];

        return new Push(
            Connection::sendCurlRequest(Connection::URL_EPHEMERALS, 'POST', $data, true, $this->apiKey),
            $this->apiKey
        );
    }

    /**
     * Get the device's phonebook.
     *
     * @return PhonebookEntry[] Phonebook entries.
     * @throws Exceptions\ConnectionException
     */
    public function getPhonebook()
    {
        $entries = Connection::sendCurlRequest(Connection::URL_PHONEBOOK . '_' . $this->iden, 'GET', null, false,
            $this->apiKey)->phonebook;

        $objEntries = [];
        foreach ($entries as $e) {
            $objEntries[] = new PhonebookEntry($e, $this);
        }

        return $objEntries;
    }

    /**
     * Delete the device.
     *
     * @throws Exceptions\ConnectionException
     */
    public function delete()
    {
        Connection::sendCurlRequest(Connection::URL_DEVICES . '/' . $this->iden, 'DELETE', null, false,
            $this->apiKey);
    }
}
