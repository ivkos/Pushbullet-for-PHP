<?php

namespace Pushbullet;

/**
 * Channel
 *
 * This class abstracts some of the differences between a subscription to a channel, and a channel itself.
 *
 * @package Pushbullet
 */
class Channel
{
    use Pushable;

    private $channelTag;
    private $type;

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

        if (!empty($this->myChannel)) {
            $this->pushable = true;
        }

        $this->apiKey = $apiKey;

        $this->setPushableRecipient("channel", $this->channelTag);
    }

    /**
     * Subscribe to the channel.
     *
     * @return Channel Subscription.
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ChannelException
     */
    public function subscribe()
    {
        if ($this->type == "subscription") {
            throw new Exceptions\ChannelException("Already subscribed to this channel.");
        }

        if (!empty($this->myChannel)) {
            throw new Exceptions\ChannelException("Cannot subscribe to own channel.");
        }

        try {
            return new Channel(
                Connection::sendCurlRequest(Connection::URL_SUBSCRIPTIONS, 'POST', ['channel_tag' => $this->channelTag],
                    true, $this->apiKey), $this->apiKey
            );
        } catch (Exceptions\ConnectionException $e) {
            if ($e->getCode() === 400) {
                throw new Exceptions\NotFoundException("Channel does not exist.");
            } else {
                throw $e;
            }
        }
    }

    /**
     * Unsubscribe from the channel.
     *
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\ChannelException Thrown if the user is not subscribed to the channel.
     */
    public function unsubscribe()
    {
        if ($this->type != "subscription") {
            throw new Exceptions\ChannelException("The current user is not subscribed to this channel.");
        }

        Connection::sendCurlRequest(Connection::URL_SUBSCRIPTIONS . '/' . $this->iden, 'DELETE', null, false,
            $this->apiKey);
    }

    /**
     * Get information about the channel.
     *
     * Guaranteed to always return channel information, whether or not the user is subscribed to it. It is
     * recommended to access the properties returned by this method, since they are consistent and always refer to
     * a channel, and not a subscription.
     *
     * @return Channel Channel object with information about a particular channel.
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\NotFoundException
     */
    public function getChannelInformation()
    {
        try {
            return new Channel(
                Connection::sendCurlRequest(Connection::URL_CHANNEL_INFO, 'GET', ['tag' => $this->channelTag], false,
                    $this->apiKey),
                $this->apiKey
            );
        } catch (Exceptions\ConnectionException $e) {
            if ($e->getCode() === 400) {
                throw new Exceptions\NotFoundException("Channel does not exist.");
            } else {
                throw $e;
            }
        }
    }

    /**
     * Create the channel if it does not exist already.
     *
     * @deprecated Channels can only be created from Pushbullet's website.
     *
     * @param string $title       Channel name.
     * @param string $description Channel description.
     *
     * @throws Exceptions\DeprecatedException
     */
    public function create($title, $description)
    {
        throw new Exceptions\DeprecatedException("Channels can only be created from Pushbullet's website.");
    }

    /**
     * Delete the channel if it is owned by the current user.
     *
     * @deprecated Channels can only be deleted from Pushbullet's website.
     *
     * @throws Exceptions\DeprecatedException
     */
    public function delete()
    {
        throw new Exceptions\DeprecatedException("Channels can only be deleted from Pushbullet's website.");
    }
}
