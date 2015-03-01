<?php

namespace Pushbullet;

class Device
{
    use Pushable;

    private $apiKey;
    private $currentUserCallback;

    public function __construct($properties, $apiKey, callable $currentUserCallback)
    {
        foreach ($properties as $k => $v) {
            $this->$k = $v ?: null;
        }

        $this->apiKey = $apiKey;
        $this->currentUserCallback = $currentUserCallback;

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
     * @throws Exceptions\NoSmsException
     */
    public function sendSms($toNumber, $message)
    {
        if (empty($this->has_sms)) {
            throw new Exceptions\NoSmsException("Device cannot send SMS messages.");
        }

        $currentUserCallback = $this->currentUserCallback;

        $data = [
            'type' => 'push',
            'push' => [
                'type'               => 'messaging_extension_reply',
                'package_name'       => 'com.pushbullet.android',
                'source_user_iden'   => $currentUserCallback()->iden,
                'target_device_iden' => $this->iden,
                'conversation_iden'  => $toNumber,
                'message'            => $message
            ]
        ];

        return new Push(
            Pushbullet::sendCurlRequest(Pushbullet::URL_EPHEMERALS, 'POST', $data, true, $this->apiKey),
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
        $entries = Pushbullet::sendCurlRequest(Pushbullet::URL_PHONEBOOK . '_' . $this->iden, 'GET', null, false,
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
     * @return object
     * @throws Exceptions\ConnectionException
     */
    public function delete()
    {
        if (isset($this->active) && $this->active == 1) {
            Pushbullet::sendCurlRequest(Pushbullet::URL_DEVICES . '/' . $this->iden, 'DELETE', null, true,
                $this->apiKey);
        }
    }
}
