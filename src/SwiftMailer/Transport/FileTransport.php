<?php
namespace Geekdevs\SwiftMailer\Transport;

use Swift_Events_EventListener;
use Swift_Mime_SimpleMessage as Swift_Mime_Message;
use Swift_Transport;
use Swift_IoException;

/**
 * Class FileTransport
 * @package AppBundle\SwiftMailer\Transport
 */
class FileTransport implements Swift_Transport
{
    /**
     * @var string
     */
    protected $path;

    /**
     * File WriteRetry Limit.
     *
     * @var int
     */
    private $retryLimit = 10;

    /**
     * The event dispatcher from the plugin API
     */
    private $eventDispatcher;

    /**
     * FileTransport constructor.
     * @param \Swift_Events_EventDispatcher $eventDispatcher
     * @param string                        $path
     * @throws Swift_IoException
     */
    public function __construct(\Swift_Events_EventDispatcher $eventDispatcher, $path)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->path = $path;

        if (!file_exists($this->path)) {
            if (!mkdir($this->path, 0777, true)) {
                throw new Swift_IoException(sprintf('Unable to create path "%s".', $this->path));
            }
        }
    }

    /**
     * @param Swift_Events_EventListener $plugin
     * @return void
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        $this->eventDispatcher->bindEventListener($plugin);
    }

    /**
     * @return bool
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * @return void
     */
    public function start()
    {
    }

    /**
     * @return void
     */
    public function stop()
    {
    }

    /**
     * Sends the given message.
     *
     * @param Swift_Mime_Message $message
     * @param string[]           $failedRecipients An array of failures by-reference
     *
     * @return int The number of sent emails
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        if ($evt = $this->eventDispatcher->createSendEvent($this, $message)) {
            $this->eventDispatcher->dispatchEvent($evt, 'beforeSendPerformed');
            if ($evt->bubbleCancelled()) {
                return 0;
            }
        }

        $this->doSend($message, $failedRecipients);

        if ($evt) {
            $evt->setResult(\Swift_Events_SendEvent::RESULT_SUCCESS);
            $this->eventDispatcher->dispatchEvent($evt, 'sendPerformed');
        }

        $count = (
            count((array) $message->getTo())
            + count((array) $message->getCc())
            + count((array) $message->getBcc())
        );

        return $count;
    }

    /**
     * @param Swift_Mime_Message $message
     * @param null               $failedRecipients
     * @return bool
     * @throws Swift_IoException
     */
    protected function doSend(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $body = $message->toString();
        $fileName = $this->path.'/'.date('Y-m-d H_i_s');

        for ($i = 0; $i < $this->retryLimit; ++$i) {
            /* We try an exclusive creation of the file. This is an atomic operation, it avoid locking mechanism */
            $fp = @fopen($fileName.'.eml', 'x');
            if (false !== $fp) {
                if (false === fwrite($fp, $body)) {
                    return false;
                }

                return fclose($fp);
            } else {
                /* The file already exists, we try a longer fileName */
                if ($i === 0) {
                    $fileName .= '_';
                }
                $fileName .= $this->getRandomString(1);
            }
        }


        throw new Swift_IoException(sprintf('Unable to create a file for enqueuing Message in "%s".', $this->path));
    }

    /**
     * Returns a random string needed to generate a fileName for the queue.
     *
     * @param int $count
     *
     * @return string
     */
    protected function getRandomString($count)
    {
        // This string MUST stay FS safe, avoid special chars
        $base = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
        $ret = '';
        $strlen = strlen($base);
        for ($i = 0; $i < $count; ++$i) {
            $ret .= $base[((int) rand(0, $strlen - 1))];
        }

        return $ret;
    }

	/**
	 * Check if this Transport mechanism is alive.
	 *
	 * If a Transport mechanism session is no longer functional, the method
	 * returns FALSE. It is the responsibility of the developer to handle this
	 * case and restart the Transport mechanism manually.
	 *
	 * @example
	 *
	 *   if (!$transport->ping()) {
	 *      $transport->stop();
	 *      $transport->start();
	 *   }
	 *
	 * The Transport mechanism will be started, if it is not already.
	 *
	 * It is undefined if the Transport mechanism attempts to restart as long as
	 * the return value reflects whether the mechanism is now functional.
	 *
	 * @return bool TRUE if the transport is alive
	 */
	public function ping()
	{
		return true;
	}
}
