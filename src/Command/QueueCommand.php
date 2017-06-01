<?php

namespace Sallyx\RabbitMqLogger\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Kdyby\RabbitMq\Connection;

class QueueCommand extends Command {

    /** @var Connection */
    private $connection;

    /** @var string */
    protected $prefix;

    /** @var array */
    protected $config;

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
	$this->setName($this->prefix . ':queue')
		->setDescription('Add or delete a queue.')
		->setHelp("Create a queue and bind it to exchange or delete a queue.")
		->addArgument('queue', InputArgument::REQUIRED, 'Queue name. (e.g. ' . $qn . ')')
		->addArgument('bindingKey', InputArgument::OPTIONAL, 'Binding key.')
		->addOption('delete', 'd', InputOption::VALUE_NONE, 'Delete queue instead of create.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
	$delete = $input->getOption('delete');
	$bindingKey = $input->getArgument('bindingKey') ? : '';
	$queue = $input->getArgument('queue');
	$exchangeName = $this->config['exchange']['name'];
	try {
	    $channel = $this->connection->channel();
	    if (!$delete) {
		$channel->queue_declare($queue, false, true, false, false);
		$channel->queue_bind($queue, $exchangeName, $bindingKey, true);
		$channel->close();
		$this->connection->close();
		return 0;
	    }
	    $channel->queue_delete($queue);
	} catch (\Exception $e) {
	    $output->writeLn('<error>' . $e->getMessage() . '</error>');
	    return 1;
	}
    }

}
