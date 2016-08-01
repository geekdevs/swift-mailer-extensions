# Swift Mailer Extensions

## Overview

This project is intended to extend capabilities of [SwiftMailer](https://github.com/swiftmailer/swiftmailer).

## Transport

### File transport

For dev environment it is usually desirable to prevent sending out actual emails and store them into files instead for testing.

**FileTransport** does exactly that - it stores emails into eml format which can then be opened with most email applications (e.g. Outlook or Thunderbird). It accepts event dispatcher as first argument and path to the folder where email files will be stored (you should have write access in it).

Usage example:

```
// Initialize file transport
$eventDispatcher = new \Swift_Events_SimpleEventDispatcher()
$transport = new FileTransport($eventDispatcher, 'path/to/folder');

// Create the Mailer using your created Transport
$mailer = Swift_Mailer::newInstance($transport);

// Create a message
$message = Swift_Message::newInstance('Wonderful Subject')
  ->setFrom(array('john@doe.com' => 'John Doe'))
  ->setTo(array('receiver@domain.org', 'other@domain.org' => 'A name'))
  ->setBody('Here is the message itself')
  ;

// Send the message
$result = $mailer->send($message);
```