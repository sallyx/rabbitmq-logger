<?php

namespace Sallyx\RabbitMqLogger\Model;

use Sallyx\RabbitMqLogger\Model\Manager;
use Sallyx\RabbitMqLogger\Model\Message;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumerCallback {

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager) {
	$this->manager = $manager;
    }

    /**
     * @param AMQPMessage $message
     * @return void
     */
    public function save(AMQPMessage $message) {
	$this->manager->save(new Message($message));
    }

}
