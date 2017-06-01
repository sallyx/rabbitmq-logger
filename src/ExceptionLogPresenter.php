<?php

namespace Sallyx\RabbitMqLogger;

use Nette\Application\IRouter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\InvalidArgumentException;
use Nette\Application\Responses\CallbackResponse;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Application\ForbiddenRequestException;
use Tracy\Debugger;

class ExceptionLogPresenter {

    use \Nette\SmartObject;

    /**
     * @param IRouter $router
     * @param array $config
     * @return void
     * @throws \InvalidArgumentException
     */
    public static function addRoutes(IRouter $router, array $config) {
	if (!$router instanceof RouteList || $router->getModule()) {
	    throw new InvalidArgumentException(
	    'If you want to use Sallyx\RabbitMqLogger\ExceptionLogPresenter then your main router ' .
	    'must be an instance of Nette\Application\Routers\RouteList without module'
	    );
	}

	$router[] = new Route('tmp');
	$lastKey = count($router) - 1;
	foreach ($router as $i => $route) {
	    if ($i === $lastKey) {
		break;
	    }
	    $router[$i + 1] = $route;
	}

	$callback = self::getRouteCAllback($config);
	$router[0] = new Route($config['route'], $callback);
    }

    /**
     * @return callback
     */
    private static function getRouteCallback($config) {
	return function() use ($config) {
	    return new CallbackResponse(function(IRequest $request, IResponse $response) use ($config) {
		$secret = $request->getQuery('secret');
		if ($secret !== $config['secret']) {
		    throw new ForbiddenRequestException('Bad secret');
		}
		$file = \basename($request->getQuery('file'));
		$fullpath = Debugger::$logDirectory . DIRECTORY_SEPARATOR . $file;
		if (!\preg_match('/\A[a-z0-9.-]+\Z/', $file) or ! \is_readable($fullpath)) {
		    die("File '" . htmlspecialchars($fullpath) . "' not found.");
		}
		\readfile($fullpath);
	    });
	};
    }

}
