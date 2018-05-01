<?php
namespace Geekdevs\SwiftMailer\Plugin;

/**
 * Class CopyPlugin
 * @package Geekdevs\SwiftMailer\Transport
 */
class CopyPlugin implements \Swift_Events_SendListener
{
    /**
     * @var string
     */
    protected $recipient;

    /**
     * CopyPlugin constructor.
     * @param string $recipient
     */
    public function __construct($recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * @param \Swift_Events_SendEvent $evt
     */
    public function beforeSendPerformed(\Swift_Events_SendEvent $evt)
    {
        /**
         * @var \Swift_Message $message
         */
        $message = $evt->getMessage();
        $recipient = $this->recipient;
        $headers = $message->getHeaders();

        if ($headers->has('to')) {
            $headers->addMailboxHeader('X-Swift-To', $message->getTo());
        }

        if ($headers->has('cc')) {
            $headers->addMailboxHeader('X-Swift-Cc', $message->getCc());
        }

        $message->addBcc($recipient);
    }

    /**
     * @param \Swift_Events_SendEvent $evt
     */
    public function sendPerformed(\Swift_Events_SendEvent $evt)
    {
    }
}
