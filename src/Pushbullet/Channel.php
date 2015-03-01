<?php

namespace Pushbullet;

class Channel
{
    use Pushable;

    private $channelTag;
    private $type;

    private $tag;
    private $iden;

    public $myChannel = false;

    public function __construct($properties, $apiKey)
    {
        foreach ($properties as $k => $v) {
            $this->$k = $v ?: null;
        }

        if (isset($properties->channel)) {
            $this->type = "subscription";
            $this->channelTag = $properties->channel->tag;
        } else {
            $this->type = "channel";
            $this->channelTag = $this->tag;
        }

        $this->apiKey = $apiKey;

        $this->setPushableRecipient("channel", $this->channelTag);
    }

    /**
     * Subscribe to the channel.
     *
     * @return Channel Subscription.
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\PushbulletException
     */
    public function subscribe()
    {
        if ($this->type == "subscription") {
            throw new Exceptions\PushbulletException("Already subscribed to this channel.");
        } else if (!empty($this->myChannel)) {
            throw new Exceptions\PushbulletException("Cannot subscribe to own channel.");
        }

        return new Channel(
            Pushbullet::sendCurlRequest(Pushbullet::URL_SUBSCRIPTIONS, 'POST', ['channel_tag' => $this->channelTag],
                true, $this->apiKey), $this->apiKey
        );
    }

    /**
     * Unsubscribe from the channel.
     *
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\PushbulletException
     */
    public function unsubscribe()
    {
        if ($this->type != "subscription") {
            throw new Exceptions\PushbulletException("Not subscribed to this channel.");
        }

        Pushbullet::sendCurlRequest(Pushbullet::URL_SUBSCRIPTIONS . '/' . $this->iden, 'DELETE', null, false,
            $this->apiKey);
    }

    /**
     * Get channel information.
     *
     * @return Channel
     * @throws Exceptions\ConnectionException
     */
    public function getInformation()
    {
        return new Channel(
            Pushbullet::sendCurlRequest(Pushbullet::URL_CHANNEL_INFO, 'GET', ['tag' => $this->channelTag], false,
                $this->apiKey),
            $this->apiKey
        );
    }

    /**
     * Create the channel if it does not exist already.
     *
     * @param string $title       Channel name.
     * @param string $description Channel description.
     *
     * @return Channel The newly created channel.
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\PushbulletException
     */
    public function create($title, $description)
    {
        if ($this->type == "subscription" || !empty($this->myChannel)) {
            throw new Exceptions\PushbulletException("Channel already exists.");
        }

        // TODO Ability to add a picture for the channel.

        return new Channel(
            Pushbullet::sendCurlRequest(Pushbullet::URL_CHANNELS, 'POST', [
                'name'        => $title,
                'description' => $description,
                'tag'         => $this->channelTag
            ], true, $this->apiKey),
            $this->apiKey
        );
    }

    /**
     * Delete the channel if it was created by you.
     *
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\PushbulletException
     */
    public function delete()
    {
        if (empty($this->myChannel)) {
            throw new Exceptions\PushbulletException("Cannot delete not owned channels.");
        }

        Pushbullet::sendCurlRequest(Pushbullet::URL_CHANNELS . '/' . $this->iden, 'DELETE', null, false, $this->apiKey);
    }
}
