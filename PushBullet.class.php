<?php

/**
 * Class PushBullet
 *
 * @version 2.7.0
 */
class PushBullet
{
    private $_apiKey;

    const URL_PUSHES         = 'https://api.pushbullet.com/v2/pushes';
    const URL_DEVICES        = 'https://api.pushbullet.com/v2/devices';
    const URL_CONTACTS       = 'https://api.pushbullet.com/v2/contacts';
    const URL_UPLOAD_REQUEST = 'https://api.pushbullet.com/v2/upload-request';
    const URL_USERS          = 'https://api.pushbullet.com/v2/users';
    const URL_SUBSCRIPTIONS  = 'https://api.pushbullet.com/v2/subscriptions';
    const URL_CHANNEL_INFO   = 'https://api.pushbullet.com/v2/channel-info';
    const URL_EPHEMERALS     = 'https://api.pushbullet.com/v2/ephemerals';

    /**
     * Pushbullet constructor.
     *
     * @param string $apiKey API key.
     *
     * @throws PushBulletException
     */
    public function __construct($apiKey)
    {
        $this->_apiKey = $apiKey;

        if (!function_exists('curl_init')) {
            throw new PushBulletException('cURL library is not loaded.');
        }
    }

    /**
     * Push a note.
     *
     * @param string $recipient Recipient. Can be device_iden, email or channel #tagname.
     * @param string $title     The note's title.
     * @param string $body      The note's message.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function pushNote($recipient, $title, $body = null)
    {
        return $this->_push($recipient, 'note', $title, $body);
    }

    /**
     * Push a link.
     *
     * @param string $recipient Recipient. Can be device_iden, email or channel #tagname.
     * @param string $title     The link's title.
     * @param string $url       The URL to open.
     * @param string $body      A message associated with the link.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function pushLink($recipient, $title, $url, $body = null)
    {
        return $this->_push($recipient, 'link', $title, $url, $body);
    }

    /**
     * Push an address.
     *
     * @param string $recipient Recipient. Can be device_iden, email or channel #tagname.
     * @param string $name      The place's name.
     * @param string $address   The place's address or a map search query.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function pushAddress($recipient, $name, $address)
    {
        return $this->_push($recipient, 'address', $name, $address);
    }

    /**
     * Push a checklist.
     *
     * @param string   $recipient Recipient. Can be device_iden, email or channel #tagname.
     * @param string   $title     The list's title.
     * @param string[] $items     The list items.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function pushList($recipient, $title, $items)
    {
        return $this->_push($recipient, 'list', $title, $items);
    }

    /**
     * Push a file.
     *
     * @param string $recipient Recipient. Can be device_iden, email or channel #tagname.
     * @param string $filePath  The path of the file to push.
     * @param string $mimeType  The MIME type of the file.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function pushFile($recipient, $filePath, $mimeType = null)
    {
        return $this->_push($recipient, 'file', $filePath, $mimeType);
    }

    /**
     * Get push history.
     *
     * @param int    $modifiedAfter Request pushes modified after this UNIX timestamp.
     * @param string $cursor        Request the next page via its cursor from a previous response. See the API
     *                              documentation (https://docs.pushbullet.com/http/) for a detailed description.
     * @param int    $limit         Maximum number of objects on each page.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function getPushHistory($modifiedAfter = 0, $cursor = null, $limit = null)
    {
        $data                   = array();
        $data['modified_after'] = $modifiedAfter;

        if ($cursor !== null) {
            $data['cursor'] = $cursor;
        }

        if ($limit !== null) {
            $data['limit'] = $limit;
        }

        return $this->_curlRequest(self::URL_PUSHES, 'GET', $data);
    }

    /**
     * Dismiss a push.
     *
     * @param string $pushIden push_iden of the push notification.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function dismissPush($pushIden)
    {
        return $this->_curlRequest(self::URL_PUSHES . '/' . $pushIden, 'POST', array('dismissed' => true));
    }

    /**
     * Delete a push.
     *
     * @param string $pushIden push_iden of the push notification.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function deletePush($pushIden)
    {
        return $this->_curlRequest(self::URL_PUSHES . '/' . $pushIden, 'DELETE');
    }

    /**
     * Get a list of available devices.
     *
     * @param int    $modifiedAfter Request devices modified after this UNIX timestamp.
     * @param string $cursor        Request the next page via its cursor from a previous response. See the API
     *                              documentation (https://docs.pushbullet.com/http/) for a detailed description.
     * @param int    $limit         Maximum number of objects on each page.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function getDevices($modifiedAfter = 0, $cursor = null, $limit = null)
    {
        $data                   = array();
        $data['modified_after'] = $modifiedAfter;

        if ($cursor !== null) {
            $data['cursor'] = $cursor;
        }

        if ($limit !== null) {
            $data['limit'] = $limit;
        }

        return $this->_curlRequest(self::URL_DEVICES, 'GET', $data);
    }

    /**
     * Delete a device.
     *
     * @param string $deviceIden device_iden of the device.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function deleteDevice($deviceIden)
    {
        return $this->_curlRequest(self::URL_DEVICES . '/' . $deviceIden, 'DELETE');
    }

    /**
     * Create a new contact.
     *
     * @param string $name  Name.
     * @param string $email Email address.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function createContact($name, $email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new PushBulletException('Create contact: Invalid email address.');
        }

        $queryData = array(
            'name'  => $name,
            'email' => $email
        );

        return $this->_curlRequest(self::URL_CONTACTS, 'POST', $queryData);
    }

    /**
     * Get a list of contacts.
     *
     * @param int    $modifiedAfter Request contacts modified after this UNIX timestamp.
     * @param string $cursor        Request the next page via its cursor from a previous response. See the API
     *                              documentation (https://docs.pushbullet.com/http/) for a detailed description.
     * @param int    $limit         Maximum number of objects on each page.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function getContacts($modifiedAfter = 0, $cursor = null, $limit = null)
    {
        $data                   = array();
        $data['modified_after'] = $modifiedAfter;

        if ($cursor !== null) {
            $data['cursor'] = $cursor;
        }

        if ($limit !== null) {
            $data['limit'] = $limit;
        }

        return $this->_curlRequest(self::URL_CONTACTS, 'GET', $data);
    }

    /**
     * Update a contact's name.
     *
     * @param string $contactIden contact_iden of the contact.
     * @param string $name        New name of the contact.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function updateContact($contactIden, $name)
    {
        return $this->_curlRequest(self::URL_CONTACTS . '/' . $contactIden, 'POST', array('name' => $name));
    }

    /**
     * Delete a contact.
     *
     * @param string $contactIden contact_iden of the contact.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function deleteContact($contactIden)
    {
        return $this->_curlRequest(self::URL_CONTACTS . '/' . $contactIden, 'DELETE');
    }

    /**
     * Get information about the current user.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function getUserInformation()
    {
        return $this->_curlRequest(self::URL_USERS . '/me', 'GET');
    }

    /**
     * Update preferences for the current user.
     *
     * @param array $preferences Preferences.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function updateUserPreferences($preferences)
    {
        return $this->_curlRequest(self::URL_USERS . '/me', 'POST', array('preferences' => $preferences));
    }

    /**
     * Subscribe to a channel.
     *
     * @param string $channelTag Channel tag.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function subscribeToChannel($channelTag)
    {
        return $this->_curlRequest(self::URL_SUBSCRIPTIONS, 'POST', array('channel_tag' => $channelTag));
    }

    /**
     * Get a list of the channels the current user is subscribed to.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function getSubscriptions()
    {
        return $this->_curlRequest(self::URL_SUBSCRIPTIONS, 'GET');
    }

    /**
     * Unsubscribe from a channel.
     *
     * @param string $channelIden channel_iden of the channel.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function unsubscribeFromChannel($channelIden)
    {
        return $this->_curlRequest(self::URL_SUBSCRIPTIONS . '/' . $channelIden, 'DELETE');
    }

    /**
     * Get information about a channel.
     *
     * @param string $channelTag Channel tag.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    public function getChannelInformation($channelTag)
    {
        return $this->_curlRequest(self::URL_CHANNEL_INFO, 'GET', array('tag' => $channelTag));
    }

    /**
     * Send an SMS message.
     *
     * @param string $fromDeviceIden device_iden of the device that should send the SMS message. Only devices which
     *                               have the 'has_sms' property set to true in their descriptions can send SMS
     *                               messages. Use {@link getDevices()} to check if they're capable to do so.
     * @param mixed  $toNumber       Phone number of the recipient.
     * @param string $message        Text of the message.
     *
     * @return object Response. Since this is an undocumented API endpoint, it doesn't return meaningful responses.
     * @throws PushBulletException
     */
    public function sendSms($fromDeviceIden, $toNumber, $message)
    {
        $data = array(
            'type' => 'push',
            'push' => array(
                'type'               => 'messaging_extension_reply',
                'package_name'       => 'com.pushbullet.android',
                'source_user_iden'   => $this->getUserInformation()->iden,
                'target_device_iden' => $fromDeviceIden,
                'conversation_iden'  => $toNumber,
                'message'            => $message
            ));

        return $this->_curlRequest(self::URL_EPHEMERALS, 'POST', $data, true, true);
    }

    /**
     * Send a push.
     *
     * @param string $recipient Recipient of the push.
     * @param mixed  $type      Type of the push notification.
     * @param mixed  $arg1      Property of the push notification.
     * @param mixed  $arg2      Property of the push notification.
     * @param mixed  $arg3      Property of the push notification.
     *
     * @return object Response.
     * @throws PushBulletException
     */
    private function _push($recipient, $type, $arg1, $arg2 = null, $arg3 = null)
    {
        $queryData = array();

        if (!empty($recipient)) {
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL) !== false) {
                $queryData['email'] = $recipient;
            } else {
                if (substr($recipient, 0, 1) == "#") {
                    $queryData['channel_tag'] = substr($recipient, 1);
                } else {
                    $queryData['device_iden'] = $recipient;
                }
            }
        }

        $queryData['type'] = $type;

        switch ($type) {
            case 'note':
                $queryData['title'] = $arg1;
                $queryData['body']  = $arg2;
                break;


            case 'link':
                $queryData['title'] = $arg1;
                $queryData['url']   = $arg2;

                if ($arg3 !== null) {
                    $queryData['body'] = $arg3;
                }
                break;


            case 'address':
                $queryData['name']    = $arg1;
                $queryData['address'] = $arg2;
                break;


            case 'list':
                $queryData['title'] = $arg1;
                $queryData['items'] = $arg2;
                break;


            case 'file':
                $fullFilePath = realpath($arg1);

                if (!is_readable($fullFilePath)) {
                    throw new PushBulletException('File: File does not exist or is unreadable.');
                }

                if (filesize($fullFilePath) > 25 * 1024 * 1024) {
                    throw new PushBulletException('File: File size exceeds 25 MB.');
                }

                $queryData['file_name'] = basename($fullFilePath);

                // Try to guess the MIME type if the argument is NULL
                if ($arg2 === null) {
                    $queryData['file_type'] = mime_content_type($fullFilePath);
                } else {
                    $queryData['file_type'] = $arg2;
                }

                // Request authorization to upload a file
                $response              = $this->_curlRequest(self::URL_UPLOAD_REQUEST, 'GET', $queryData);
                $queryData['file_url'] = $response->file_url;

                // Upload the file
                $response->data->file = '@' . $fullFilePath;
                $this->_curlRequest($response->upload_url, 'POST', $response->data, false, false);
                break;

            default:
                throw new PushBulletException('Unknown push type.');
        }

        return $this->_curlRequest(self::URL_PUSHES, 'POST', $queryData);
    }


    /**
     * Send a request to a remote server using cURL.
     *
     * @param string $url        URL to send the request to.
     * @param string $method     HTTP method.
     * @param array  $data       Query data.
     * @param bool   $sendAsJSON Send the request as JSON.
     * @param bool   $auth       Use the API key to authenticate
     *
     * @return object Response.
     * @throws PushBulletException
     */
    private function _curlRequest($url, $method, $data = null, $sendAsJSON = true, $auth = true)
    {
        $curl = curl_init();

        if ($method == 'GET' && $data !== null) {
            $url .= '?' . http_build_query($data);
        }

        curl_setopt($curl, CURLOPT_URL, $url);

        if ($auth) {
            curl_setopt($curl, CURLOPT_USERPWD, $this->_apiKey);
        }

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        if ($method == 'POST' && $data !== null) {
            if ($sendAsJSON) {
                $data = json_encode($data);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data)
                ));
            }

            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);

        $response = curl_exec($curl);

        if ($response === false) {
            $curlError = curl_error($curl);
            curl_close($curl);
            throw new PushBulletException('cURL Error: ' . $curlError);
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($httpCode >= 400) {
            curl_close($curl);
            throw new PushBulletException('HTTP Error ' . $httpCode);
        }

        curl_close($curl);

        return json_decode($response);
    }
}

/**
 * Class PushBulletException
 */
class PushBulletException extends Exception
{
    // Exception thrown by PushBullet
}
