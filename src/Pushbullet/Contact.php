<?php

namespace Pushbullet;

/**
 * Contact
 *
 * @package Pushbullet
 */
class Contact
{
    use Pushable;

    public function __construct($properties, $apiKey)
    {
        foreach ($properties as $k => $v) {
            $this->$k = $v ?: null;
        }

        $this->apiKey = $apiKey;

        if (isset($this->email)) {
            $this->setPushableRecipient("email", $this->email);
            $this->pushable = true;
        } else {
            $this->pushable = false;
        }
    }

    /**
     * Change the contact's name.
     *
     * @param string $name New name.
     *
     * @return Contact The same contact with a different name.
     * @throws Exceptions\ConnectionException
     */
    public function changeName($name)
    {
        return new Contact(
            Connection::sendCurlRequest(Connection::URL_CONTACTS . '/' . $this->iden, 'POST', ['name' => $name], true,
                $this->apiKey), $this->apiKey
        );
    }

    /**
     * Delete the contact.
     *
     * @throws Exceptions\ConnectionException
     */
    public function delete()
    {
        Connection::sendCurlRequest(Connection::URL_CONTACTS . '/' . $this->iden, 'DELETE', null, false,
            $this->apiKey);
    }
}
