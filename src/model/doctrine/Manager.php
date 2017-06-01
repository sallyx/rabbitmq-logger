<?php

namespace Sallyx\RabbitMqLogger\Model\Doctrine;

use Sallyx\RabbitMqLogger\Model\Manager as IManager;
use Sallyx\RabbitMqLogger\Model\Message;
use Kdyby\Doctrine\EntityManager;

class Manager implements IManager {

    use \Nette\SmartObject;

    /** @var EntityManager */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em) {
	$this->em = $em;
    }

    /**
     * @param Message
     * @return void
     * @throws \Exception
     */
    public function save(Message $message) {
	$nel = new Entity\NetteExceptionsLog();
	$nel->guid = $message->getGuid();
	$nel->timestamp = $message->getTimestampAsDateTime();
	$nel->priority = $message->getPriority();
	$nel->location = $message->getLocation();
	$nel->exceptionFile = $message->getExceptionFile();
	$nel->errorText = $message->getErrorText();
	$this->em->persist($nel);
	$this->em->flush();
    }

}
