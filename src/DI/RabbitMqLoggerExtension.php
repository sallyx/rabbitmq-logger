<?php

namespace Sallyx\RabbitMqLogger\DI;

use Nette\DI\CompilerExtension;
use Kdyby\RabbitMq\DI\IProducersProvider;

class RabbitMqLoggerExtension extends CompilerExtension implements IProducersProvider {

    /**
     * @var array
     */
    private $defaults = array(
	'rabbitmqExtensionName' => 'rabbitmq',
	'producerName' => 'rabbitLogger',
	'guid' => 'rabbitmq-logger',
	'exceptionFileRoute' => array(
	    'route' => 'get-tracy-exception-file',
	    'secret' => null
	),
	'producer' => array(
	    'connection' => 'default',
	    'contentType' => 'application/json',
	    'exchange' => array(
		'name' => 'nette-log-exchange',
		'type' => 'fanout'
	    )
	)
    );

    /**
     * @return array
     */
    public function getRabbitProducers() {
	$config = $this->getConfig($this->defaults);
	return [$config['producerName'] => $config['producer']];
    }

    /**
     * @return void
     */
    public function loadConfiguration() {
	$config = $this->getConfig($this->defaults);
	$builder = $this->getContainerBuilder();
	if ($builder->hasDefinition('tracy.logger')) {
	    $builder->removeDefinition('tracy.logger');
	}
	$producerLink = '@' . $config['rabbitmqExtensionName'] . '.producer.' . $config['producerName'];
	$builder->addDefinition('tracy.logger')
		->setClass('Sallyx\RabbitMqLogger\RabbitLogger', ['@http.request', $producerLink, $config['guid']]);
    }

    /**
     * @return void
     */
    public function beforeCompile() {
	$config = $this->getConfig($this->defaults);
	$exceptionFileRoute = $config['exceptionFileRoute'];
	if (empty($exceptionFileRoute['secret'])) {
	    return;
	}
	$builder = $this->getContainerBuilder();
	$builder->getDefinition($builder->getByType('Nette\Application\IRouter') ? : 'router')
		->addSetup('Sallyx\RabbitMqLogger\ExceptionLogPresenter::addRoutes', ['@router', $exceptionFileRoute]);
    }

}
