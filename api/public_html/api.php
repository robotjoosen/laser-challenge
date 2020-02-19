<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use League\Route\Router;
use League\Route\RouteGroup;
use Laminas\HttpHandlerRunner\Emitter;
use Laminas\Diactoros\ServerRequestFactory;
use Robotjoosen\Lasertag\API\Controller;
use Psr\Http\Message\ResponseInterface;

/** @var Router $router */
$router = new Router;

/** Devices */
$router->group('api/device', function (RouteGroup $router) {
    $router->map('GET', '/', [Controller\Device::class, 'index']);
    $router->map('GET', '{alias}/register/{ip}', [Controller\Device::class, 'register']);
    $router->map('GET', '{alias}/hit', [Controller\Device::class, 'add']);
});

$router->group('api/game', function (RouteGroup $router) {
    $router->map('GET', '/', [Controller\Game::class, 'index']);
});

/**
 * Process
 */
try {

    $request = ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
    $sapiStreamEmitter = new Emitter\SapiStreamEmitter();
    $conditionalEmitter = new class ($sapiStreamEmitter) implements Emitter\EmitterInterface {

        /** @var Emitter\EmitterInterface */
        private $emitter;

        /**
         *  constructor.
         * @param Emitter\EmitterInterface $emitter
         */
        public function __construct(Emitter\EmitterInterface $emitter)
        {
            $this->emitter = $emitter;
        }

        /**
         * @param ResponseInterface $response
         * @return bool
         */
        public function emit(ResponseInterface $response): bool
        {
            if (!$response->hasHeader('Content-Disposition')
                && !$response->hasHeader('Content-Range')
            ) {
                return false;
            }
            return $this->emitter->emit($response);
        }
    };
    $stack = new Emitter\EmitterStack();
    $stack->push(new Emitter\SapiEmitter());
    $stack->push($conditionalEmitter);
    $stack->emit($router->dispatch($request));

} catch (Exception $e) {
    http_response_code(404);
    die(json_encode([
        'code' => $e->getCode(),
        'message' => $e->getMessage()
    ]));
}