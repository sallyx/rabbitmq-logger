<?php

namespace Sallyx\RabbitMqLogger\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Kdyby\RabbitMq\Connection;
use Kdyby\RabbitMq\Channel;
use PhpAmqpLib\Message\AMQPMessage;

class ListMessagesCommand extends Command {

    const DEFAULT_LIMIT = 1;

    /** @var Connection */
    private $connection;

    /** @var string */
    protected $prefix;

    /** @var array */
    protected $config;

    /** @var boolean */
    private $isSilenced = FALSE;

    /** @var int */
    private $consoleWidth = 80;

    /**
     * @param Connection $connection
     * @param string $prefix
     * @param array $config
     */
    public function __construct(Connection $connection, $prefix, array $config) {
	$this->prefix = rtrim($prefix, '.');
	$this->config = $config;
	parent::__construct();
	$this->connection = $connection;
    }

    /**
     * @return void
     */
    protected function configure() {
	$qn = $this->config['queue']['name'];
	$this->setName($this->prefix . ':list')
		->setDescription('List and consume messages from a queue.')
		->setHelp("List message(s) from a queue. Listeted messages are deleted from the queue.")
		->addArgument('queue', InputArgument::REQUIRED, 'Queue name. (e.g. ' . $qn . ')')
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
	$message = \json_decode($msg->body);
	if (!$this->isSilenced) {
	    $len = $this->consoleWidth - 51;
	    $ms = sprintf(
		    "%9.9s|%14s|%-25.25s|<info>%.{$len}s</info>", $message->priority, \date('y/m/d H:i', $message->timestamp), $message->guid, $message->errorText
	    );
	    $output->writeLn($ms);
	}
	$channel->basic_ack($msg->delivery_info['delivery_tag']);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
	$limit = $input->getOption('limit');
	if ($limit === NULL) {
	    $limit = self::DEFAULT_LIMIT;
	}
	$limit = \intval($limit);
	$this->isSilenced = $input->getOption('quiet');
	$cw = \intval($input->getOption('width'));
	if ($cw > 0) {
	    $this->consoleWidth = $cw;
	}
	$queue = $input->getArgument('queue') ? : $this->config['queue']['name'];
	$console = $this;
	$channel = $this->connection->channel();
	$callback = function($msg) use ($output, $console, $channel) {
	    $console->outputMessage($output, $channel, $msg);
	};
	try {
	    $c = $this->config;
	    if ($limit <= 0) {
		$channel->basic_consume($queue, '', false, false, false, false, $callback);
		while (\count($channel->callbacks)) {
		    $channel->wait();
		}
	    } else {
		$channel->basic_qos(null, 1, null);
		do {
		    $msg = $channel->basic_get($queue, false);
		    if ($msg == NULL) {
			break;
		    }
		    $this->outputMessage($output, $channel, $msg);
		} while (--$limit > 0);
	    }
	    $channel->close();
	    $this->connection->close();
	    return 0;
	} catch (\Exception $e) {
	    $output->writeLn('<error>' . $e->getMessage() . '</error>');
	    return 1;
	}
    }

}
