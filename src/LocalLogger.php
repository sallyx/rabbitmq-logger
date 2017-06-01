<?php

namespace Sallyx\RabbitMqLogger;

use Tracy\Logger as TLogger;

class LocalLogger extends TLogger {

    /**
     * @var string
     */
    private $lastExceptionFile;

    /**
     * @param mixed $value
     * @param string $priority
     * @return void
     */
    public function log($value, $priority = self::INFO) {
	$this->lastExceptionFile = null;
	parent::log($value, $priority);
    }

    /**
     * @param  \Exception|\Throwable
     * @return string
     */
    public function getExceptionFile($exception) {
	$this->lastExceptionFile = parent::getExceptionFile($exception);
	return $this->lastExceptionFile;
    }

    /**
     * @return string
     */
    public function getLastExceptionFile() {
	return \basename($this->lastExceptionFile);
    }

}
