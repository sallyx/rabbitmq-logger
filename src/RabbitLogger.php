<?php

namespace Sallyx\RabbitMqLogger;

use Tracy\ILogger;
use Tracy\Debugger;
use Kdyby\RabbitMq\Producer;
use Nette\Http\IRequest;

class RabbitLogger implements ILogger {

    use \Nette\SmartObject;

    /**
     * @var Producer
     */
    private $producer;

    /**
     * @var ILogger
     */
    private $logger;

    /**
     * Global identifier of this message logger/producer (i.e. server name)
     * Used as part of the routing key
     * @var string
     */
    private $guid;

    /**
     * Log messages by rabbitmq $producer?
     * @var boolean
     */
    private $rabbitEnabled;

    /**
     * Log messages by $logger?
     *  @var boolean
     */
    private $localEnabled;

    /**
     * @var \Nette\Http\Url
     */
    private $url;

    /**
     * @param IRequest $request
     * @param Producer $producer
     * @param string $guid
     * @param bool $rabbitEnabled
     * @param bool $localEnabled
     */
    public function __construct(IRequest $request, Producer $producer, $guid = 'rabbit-logger', $rabbitEnabled = TRUE, $localEnabled = TRUE) {
	$this->url = $request->getUrl();
	$this->producer = $producer;
	$this->guid = $guid;
	$this->rabbitEnabled = $rabbitEnabled;
	$this->localEnabled = $localEnabled;
    }

    /**
     * @param ILogger $logger
     * @return void
     */
    public function setLogger(ILogger $logger) {
	$this->logger = $logger;
    }

    /**
     * @return ILogger
     */
    private function getLogger() {
	if (!$this->logger) {
	    $this->logger = new LocalLogger(Debugger::$logDirectory, Debugger::$email, Debugger::getBlueScreen());
	    $this->logger->directory = & Debugger::$logDirectory; // back compatiblity
	    $this->logger->email = & Debugger::$email;
	}
	return $this->logger;
    }

    /**
     * @param mixed $value
     * @param string $priority
     * @return void
     */
    public function log($value, $priority = self::INFO) {
	$exceptionFile = null;
	if ($this->localEnabled) {
	    $logger = $this->getLogger();
	    $logger->log($value, $priority);
	    if ($logger instanceof LocalLogger) {
		$exceptionFile = $logger->getLastExceptionFile();
	    }
	}

	if ($this->rabbitEnabled) {
	    $message = $value instanceof \Throwable ? $value->getMessage() : $value;
	    if (!\is_string($message)) {
		$message = \json_encode($message);
	    }
	    $this->publishLog($priority, $message, $exceptionFile);
	}
    }

    /**
     * @param string $priority
     * @param string $errorText
     * @param string $exceptionFile
     * @return void
     */
    private function publishLog($priority, $errorText, $exceptionFile = null) {
	$url = $this->url;
	$location = $url->getScheme() . '://' . $url->getAuthority() . $url->getBasePath();
	$message = [
	    'guid' => $this->guid,
	    'timestamp' => time(),
	    'priority' => $priority,
	    'errorText' => $errorText,
	    'location' => $location,
	    'exceptionFile' => $exceptionFile
	];
	$routingKey = $priority;
	$exOpt = $this->producer->getExchangeOptions();
	if ($exOpt['type'] === 'topic') {
	    $routingKey .= "." . $this->guid;
	}

	$this->producer->publish(json_encode($message), $routingKey);
    }

}
