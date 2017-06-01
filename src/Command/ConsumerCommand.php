<?php

namespace Sallyx\RabbitMqLogger\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Kdyby\RabbitMq\Connection;
use Kdyby\RabbitMq\Channel;
use PhpAmqpLib\Message\AMQPMessage;
use Sallyx\RabbitMqLogger\Model\Manager;
use Sallyx\RabbitMqLogger\Model\Message;

class ConsumerCommand extends ListMessagesCommand {

    /** @var Manager */
    private $manager;

    public function __construct(Connection $connection, $prefix, array $config, Manager $manager) {
	parent::__construct($connection, $prefix, $config);
	$this->manager = $manager;
    }

    protected function configure() {
	$qn = $this->config['queue']['name'];
	$this->setName($this->prefix . ':save')
		->setDescription('List, save and consume messages from a queue.')
		->setHelp("List message(s) from a queue. " .
			"Save them using \Sallyx\RabbitMqLogger\Model\Manager.\n" .
			"Listeted and saved messages are deleted from the queue.")
		->addArgument('queue', InputArgument::OPTIONAL, 'Queue name. (e.g. ' . $qn . ')')
		->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit of consumed messages. Default ' . self::DEFAULT_LIMIT . '. (0 means no limit.)')
		->addOption('width', 'w', InputOption::VALUE_REQUIRED, 'Console width. (Default 80)');
    }

    /**
     * @param OutputInterface $output
     * @param Channel $channel
     * @param AMQPMessage $msg
     * @return void
     */
    protected function outputMessage(OutputInterface $output, Channel $channel, AMQPMessage $msg) {
	try {
	    $this->manager->save(new Message($msg));
	} catch (\Exception $e) {
	    $output->writeLn('<error>' . $e->getMessage() . '</error>');
	    throw $e;
	}
	parent::outputMessage($output, $channel, $msg);
    }

}
