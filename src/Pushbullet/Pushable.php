<?php

namespace Pushbullet;

/**
 * Target that can receive push notifications.
 *
 * @package Pushbullet
 */
trait Pushable
{
    private $recipientType;
    private $recipient;

    protected $apiKey;

    /**
     * @param string $recipientType Recipient type. Can be device/channel/email.
     * @param string $recipient     Recipient.
     *
     * @throws Exceptions\InvalidRecipientException
     */
    protected function setPushableRecipient($recipientType, $recipient)
    {
        if ($recipientType === "device") {
            $this->recipientType = "device_iden";
            $this->recipient = $recipient;
        } else if ($recipientType === "channel") {
            $this->recipientType = "channel_tag";
            $this->recipient = $recipient;
        } else if ($recipientType === "email") {
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL) !== false) {
                $this->recipientType = "email";
                $this->recipient = $recipient;
            } else {
                throw new Exceptions\InvalidRecipientException("Invalid email address.");
            }
        } else {
            throw new Exceptions\InvalidRecipientException("Unknown recipient type.");
        }
    }

    /**
     * Push a note.
     *
     * @param string $title The note's title.
     * @param string $body  The note's body.
     *
     * @return Push Push notification.
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\NotPushableException
     */
    public function pushNote($title, $body)
    {
        self::checkPushable();

        $data = [];
        $data[$this->recipientType] = $this->recipient;
        $data['type'] = 'note';
        $data['title'] = $title;
        $data['body'] = $body;

        return new Push(
            Connection::sendCurlRequest(Connection::URL_PUSHES, "POST", $data, true, $this->apiKey),
            $this->apiKey
        );
    }

    /**
     * Push a link.
     *
     * @param string $title The link's title.
     * @param string $url   The URL to open.
     * @param string $body  A message associated with the link.
     *
     * @return Push Push notification.
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\NotPushableException
     */
    public function pushLink($title, $url, $body)
    {
        self::checkPushable();

        $data = [];
        $data[$this->recipientType] = $this->recipient;
        $data['type'] = 'link';
        $data['title'] = $title;
        $data['url'] = $url;
        $data['body'] = $body;

        return new Push(
            Connection::sendCurlRequest(Connection::URL_PUSHES, "POST", $data, true, $this->apiKey),
            $this->apiKey
        );
    }

    /**
     * Push an address.
     *
     * @deprecated Pushing addresses has been deprecated in Pushbullet.
     *
     * @param string $name    The place's name.
     * @param string $address The place's address or a map search query.
     *
     * @throws Exceptions\DeprecatedException
     */
    public function pushAddress($name, $address)
    {
        throw new Exceptions\DeprecatedException("Pushing addresses has been deprecated in Pushbullet.");
    }

    /**
     * Push a list.
     *
     * @deprecated Pushing lists has been deprecated in Pushbullet.
     *
     * @param string   $title The list's title.
     * @param string[] $items The items in the list.
     *
     * @throws Exceptions\DeprecatedException
     */
    public function pushList($title, array $items)
    {
        throw new Exceptions\DeprecatedException("Pushing lists has been deprecated in Pushbullet.");
    }

    /**
     * Push a file.
     *
     * @param string $filePath    The path of the file to push.
     * @param string $mimeType    The MIME type of the file. If null, we'll try to guess it.
     * @param string $title       The title of the push notification.
     * @param string $body        The body of the push notification.
     * @param string $altFileName Alternative file name to use instead of the original one.
     *                            For example, you might want to push 'someFile.tmp' as 'image.jpg'.
     *
     * @return Push Push notification.
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\FilePushException
     * @throws Exceptions\NotFoundException Thrown if the file does not exist or is unreadable.
     * @throws Exceptions\NotPushableException
     */
    public function pushFile($filePath, $mimeType = null, $title = null, $body = null, $altFileName = null)
    {
        self::checkPushable();

        $fullFilePath = realpath($filePath);

        if (!is_readable($fullFilePath)) {
            throw new Exceptions\NotFoundException('File does not exist or is unreadable.');
        }

        if (filesize($fullFilePath) > 25 * 1024 * 1024) {
            throw new Exceptions\FilePushException('File size exceeds 25 MB.');
        }

        $data = [];

        $data['file_name'] = $altFileName === null ? basename($fullFilePath) : $altFileName;

        // Try to guess the MIME type if the argument is NULL
        $data['file_type'] = $mimeType === null ? mime_content_type($fullFilePath) : $mimeType;

        // Request authorization to upload the file
        $response = Connection::sendCurlRequest(Connection::URL_UPLOAD_REQUEST, 'GET', $data, true, $this->apiKey);
        $data['file_url'] = $response->file_url;

        if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
            $response->data->file = new \CURLFile($fullFilePath);
        } else {
            $response->data->file = '@' . $fullFilePath;
        }

        // Upload the file
        Connection::sendCurlRequest($response->upload_url, 'POST', $response->data, false, null);

        $data[$this->recipientType] = $this->recipient;
        $data['type'] = 'file';
        $data['title'] = $title;
        $data['body'] = $body;

        return new Push(
            Connection::sendCurlRequest(Connection::URL_PUSHES, 'POST', $data, true, $this->apiKey),
            $this->apiKey
        );
    }

    /**
     * Check if target is pushable.
     *
     * @throws Exceptions\NotPushableException
     */
    private function checkPushable()
    {
        if (empty($this->pushable)) {
            throw new Exceptions\NotPushableException("Cannot push to this target.");
        }
    }
}
