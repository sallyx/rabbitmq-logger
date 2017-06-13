# Rabbitmq Logger
Log error and exceptions int rabbitmq exchange.

[![Latest stable](https://img.shields.io/packagist/v/kdyby/rabbitmq.svg)](https://packagist.org/packages/sallyx/rabbitmq-logger)

Rabbitmq Logger provides two extensions for [Nette Framework](https://github.com/nette/nette).

- **RabbitMqLoggerExtension** for logging errors/exceptions into rabbitmq exange. You should use this extension on all projects where you want to log error messages to rabbitmq.
- **ConsumerExtension** for getting messages from queue and optionally save them into database using doctrine 2. It also provide grid to show the saved messages. You should use this extension with an internal application to manage saved messages.

## Installation

### Requirements

sallyx/rabbitmq-logger requires PHP 5.4 or higher.

- [Nette](https://github.com/nette/nette)
- [Kdyby/RabbitMq](https://github.com/Kdyby/RabbitMq)
- [php-amqplib](https://github.com/videlalvaro/php-amqplib)

### Suggests

If you want to use ConsumerExtension to save logs into database:

- [Kdyby/Doctrine](https://github.com/Kdyby/Doctrine)
- [Kdyby/Console](https://github.com/Kdyby/Console)

If you want to use ConsumerExtension to provide Grid to show saved messages:
- [ublaboo/datagrid](https://github.com/ublaboo/datagrid)

### Installation

The best way to install sallyx/rabbitmq-logger is using  [Composer](http://getcomposer.org/):

```sh
composer require sallyx/rabbitmq-logger
composer require kdyby/console
composer require kdyby/doctrine
composer require ublaboo/datagrid
```

### Configuration


First you need to configure [Kdyby/RabbitMq](https://github.com/Kdyby/RabbitMq).
A least the connection:

```yml
extensions:
    rabbitmq: Kdyby\RabbitMq\DI\RabbitMqExtension

rabbitmq:
    connection:
        host: localhost
        port: 5672
        user: 'guest'
        password: 'guest'
        #vhost: '/'
```

If you want to use RabbitMqLoggerExtension to log error messages, add and configure it like this:

```yml
extensions:
    rabbitmqLoggerExt: Sallyx\RabbitMqLogger\DI\RabbitMqLoggerExtension
```
This is the default configuration for RabbitMqLoggerExtension:

```yml
rabbitmqLoggerExt:
    rabbitmqExtensionName: rabbitmq
    guid: rabbitmq-logger
    producer:
        connection: default # Kdyby/RabbitMq default connection
        exchange:
            name: nette-log-exchange
            type: fanout
```
If you use **direct** exchange, the routing key is in form *priority*, where priority is *error* or *exception*.
If you use **topic** exchange, the routing key is in form *priority*.*guid*


If you want to use ConsumerExtension, you have to add [Kdyby/Console](https://github.com/Kdyby/Console)
and/or [Kdyby/Doctrine](https://github.com/Kdyby/Doctrine) extensions
and configure it (please have a look at the documentation for this extensions):

```yml
extensions:
    rabbitmqLoggerConsumer: Sallyx\RabbitMqLogger\DI\ConsumerExtension # must be first!
    console: Kdyby\Console\DI\ConsoleExtension
    events: Kdyby\Events\DI\EventsExtension
    annotations: Kdyby\Annotations\DI\AnnotationsExtension
    doctrine: Kdyby\Doctrine\DI\OrmExtension
    rabbitmqLoggerExt: Sallyx\RabbitMqLogger\DI\RabbitMqLoggerExtension # if you want to use it either
  
console:
    url: http://localhost/~petr/example-url/

doctrine:
    host: 127.0.0.1
    user: petr
    password: xxx
    dbname: databasename
    driver: pdo_pgsql # pdo_mysql
```
This is the default configuration for ConsumerExtension:

```yml
rabbitmqLoggerConsumer:
    consumerName: rabbitLogger
    consumer:
        connection: default               # Kdyby/RabbitMq default connection
        queue:
            name: nette-log-queue
        exchange:
            name: my-fancy-nette-log-exchange # the same as for RabbitMqLoggerExtension
            type: fanout                      # the same as for RabbitMqLoggerExtension
    manager: Sallyx\RabbitMqLogger\Model\Doctrine\Manager
```

If you want to get an easy acces to your logged exceptions, you can add this to RabbitMqLoggerExtension configuration:

```yml
rabbitmqLoggerExt:
    exceptionFileRoute:
        route: get-tracy-exception-file               
        secret: xxx
    ....
```
This create route in your web application, which provides access to your error messages saved in log in form
http://example.org/get-tracy-exception-file?secret=xxx&file=name_of_the_file

To use this in the grid, add this to ConsumerExtension configuration:

```yml
rabbitmqLoggerConsumer:
    exceptionFileRoute:
        route: get-tracy-exception-file
        secret: xxx
    ....
```
## Setup

You have to create exchange for RabbitMqLoggerExtension and queue for ConsumerExtension.
If you want to use ConsumerExtension to save messages to database, you also have to create the schema and table.

If you have installed  Kdyby/Console, you can use  *rabbitmq:setup-fabric* command from Kdyby/RabbitMq extension to setup exchange and queue:

```sh
php www/index.php  rabbitmq:setup-fabric
```

To create the database table, you can use [structure-psql.sql](src/setup/doctrine/structure-psql.sql) for Postgresql
and [structure-mysql.sql](src/setup/doctrine/structure-mysql.sql) for Mysql/MariaDB.

## Using RabbitMqLoggerExtension

No futher action needed. Your project should log messages into rabbitmq exhange as configured.

## Using ConsumerExtension

### Using Console

If you installed Kdyby/Console extension, you can use this commands:

- **rabbitmqLoggerConsumer:queue** to add or delete other queues to exchange.
- **rabbitmqLoggerConsumer:list** to list and remove messages from queue.
- **rabbitmqLoggerConsumer:save** to list, save messages to database and remove from queue (Kdyby/Doctine extension needed).

While *rabbitmqLoggerConsumer:save* takes messages from queue described in configuration, 
*rabbitmqLoggerConsumer:list* require queue name as argument to avoid accidentally removing message from queue.
You can use *rabbitmqLoggerConsumer:queue* to create new queue.

```sh
php www/index.php rabbitmqLoggerConsumer:queue spy-whats-going-on exception  # create queue for 'exception' routing key
php www/index.php rabbitmqLoggerConsumer:list spy-whats-going-on        # print message on console and remove if from queue
....
php www/index.php rabbitmqLoggerConsumer:queue spy-whats-going-on -d    # delete queue
```

### Using Grid

In a presenter:

```php
use Sallyx\RabbitMqLogger\Controls\Doctrine\GridFactory;
...

   public function __construct(GridFactory $factory) {
        $this->gridFactory = $factory;
   }

    public function createComponentGrid($name) {
        return $this->gridFactory->create();
    }
```
In latte:

```php
{control grid}
```

You need to add assets as described on https://ublaboo.org/datagrid/ page.
