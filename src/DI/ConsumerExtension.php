<?php

namespace Sallyx\RabbitMqLogger\DI;

use Nette\DI\CompilerExtension;
use Kdyby\RabbitMq\DI\IConsumersProvider;

class ConsumerExtension extends CompilerExtension implements IConsumersProvider {

    const DEFAULT_MANAGER = 'Sallyx\RabbitMqLogger\Model\Doctrine\Manager';
    const DEFAULT_GRID_FACTORY = 'Sallyx\RabbitMqLogger\Controls\Doctrine\GridFactory';

    /**
     * @var array
     */
    private $defaults = array(
	'rabbitmqExtensionName' => 'rabbitmq',
	'consumerName' => 'rabbitLogger',
	'consumer' => array(
	    'connection' => 'default',
	    'contentType' => 'application/json',
	    'queue' => array(
		'name' => 'nette-log-queue'
	    ),
	    'exchange' => array(
		'name' => 'nette-log-exchange',
		'type' => 'fanout',
	    ),
	    'callback' => array(
		'@Sallyx\RabbitMqLogger\Model\ConsumerCallback',
		'save'
	    )
	),
	'exceptionFileRoute' => array(
	    'route' => 'get-tracy-exception-file',
	    'secret' => null
	),
	'manager' => self::DEFAULT_MANAGER
    );

    /**
     * @return array
     */
    public function getRabbitConsumers() {
	$config = $this->getConfig($this->defaults);
	return [$config['consumerName'] => $config['consumer']];
    }

    /**
     * @throws \Nette\Utils\AssertionException
     * @return void
     */
    public function loadConfiguration() {
	$config = $this->getConfig($this->defaults);
	if ($config['manager'] === self::DEFAULT_MANAGER) {
	    $ext = $this->compiler->getExtensions('Kdyby\Doctrine\DI\OrmExtension');
	    if (empty($ext)) {
		throw new Nette\Utils\AssertionException('You should register \'Kdyby\Doctrine\DI\OrmExtension\' before \'' . get_class($this) . '\'.');
	    }
	    $orm = \array_pop($ext);
	    $ns = 'Sallyx\RabbitMqLogger\Model\Doctrine';
	    $dir = __DIR__ . '/../model/doctrine/entity/';
	    if (!empty($orm->config['metadata'])) {
		$orm->config['metadata'][$ns] = $dir;
	    } else {
		$orm->config['metadata'] = [$ns => $dir];
	    }
	    $builder = $this->getContainerBuilder();
	    $builder->addDefinition($this->prefix('manager'))
		    ->setClass(self::DEFAULT_MANAGER);
	    $builder->addDefinition($this->prefix('consumerCallback'))
		    ->setClass(trim($config['consumer']['callback'][0], '@'));

	    $gfClass = $builder->addDefinition($this->prefix('gridFactory'))
		    ->setClass(self::DEFAULT_GRID_FACTORY, ['exConfig' => $config['exceptionFileRoute']]);
	}
	$this->loadConsole();
    }

    /**
     * @return void
     */
    private function loadConsole() {
	if (!class_exists('Kdyby\Console\DI\ConsoleExtension') || PHP_SAPI !== 'cli') {
	    return;
	}

	$config = $this->getConfig($this->defaults);
	$builder = $this->getContainerBuilder();
	$connectionLink = '@' . $config['rabbitmqExtensionName'] . '.' . $config['consumer']['connection'] . '.connection';
	$managerLink = '@' . $builder->getByType($config['manager']);
	//ListMessagesCommand
	$class1 = 'Sallyx\RabbitMqLogger\Command\ListMessagesCommand';
	$builder->addDefinition($this->prefix('console.list'))
		->setClass($class1, [$connectionLink, $this->prefix(''), $config['consumer'], $managerLink])
		->addTag(\Kdyby\Console\DI\ConsoleExtension::COMMAND_TAG);
	//QueueCommand
	$class2 = 'Sallyx\RabbitMqLogger\Command\QueueCommand';
	$builder->addDefinition($this->prefix('console.queue'))
		->setClass($class2, [$connectionLink, $this->prefix(''), $config['consumer'], $managerLink])
		->addTag(\Kdyby\Console\DI\ConsoleExtension::COMMAND_TAG);
	//ConsumerCommand
	if ($config['manager'] === self::DEFAULT_MANAGER) {
	    $class3 = 'Sallyx\RabbitMqLogger\Command\ConsumerCommand';
	    $builder->addDefinition($this->prefix('console.save'))
		    ->setClass($class3, [$connectionLink, $this->prefix(''), $config['consumer'], $managerLink])
		    ->addTag(\Kdyby\Console\DI\ConsoleExtension::COMMAND_TAG);
	}
    }

}
