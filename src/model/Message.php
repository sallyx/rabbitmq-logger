<?php

namespace Sallyx\RabbitMqLogger\Model;

use PhpAmqpLib\Message\AMQPMessage;

class Message {

    use \Nette\SmartObject;

    /** @var string */
    private $guid;

    /** @var int */
    private $timestamp;

    /** @var string */
    private $priority;

    /** @var string */
    private $location;

    /** @var string */
    private $exceptionFile;

    /** @var string */
    private $errorText;

    public function __construct(AMQPMessage $msg) {
	$message = \json_decode($msg->body);
	$this->priority = $message->priority;
	$this->timestamp = $message->timestamp;
	$this->guid = $message->guid;
	$this->exceptionFile = $message->exceptionFile;
	$this->errorText = $message->errorText;
	$this->location = $message->location;
    }

    /** @return string */
    public function getPriority() {
	return $this->priority;
    }

    /** @return int */
    public function getTimestamp() {
	return $this->timestamp;
    }

    /** @return \DateTime */
    public function getTimestampAsDateTime() {
	return new \DateTime(date('c', $this->getTimestamp()));
    }

    /** @return string */
    public function getGuid() {
	return $this->guid;
    }

    /** @return string */
    public function getExceptionFile() {
	return $this->exceptionFile;
    }

    /** @return string */
    public function getErrorText() {
	return $this->errorText;
    }

    /** @return string */
    public function getLocation() {
	return $this->location;
    }

}
