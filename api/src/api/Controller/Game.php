<?php

namespace Robotjoosen\Lasertag\API\Controller;

use Exception;
use Robotjoosen\Lasertag\API;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Config
 * @package Pickup\Controller
 */
class Game extends API\Controller
{

    /**
     * Config constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $arguments
     * @return ResponseInterface
     */
    public function index(ServerRequestInterface $request, array $arguments): ResponseInterface
    {
        try {
            $response = [];

            // Get all send parameters
            $params = array_merge(
                $this->getParameters($request),
                $arguments
            );

            $response['success'] = 1;
            $response['games'] = [];
            $games = $this->database->select('game', ['id','starttime','endtime']);
            foreach($games as $game) {
                $response['games'][] = $game;
            }

            // Return response
            $this->response->getBody()->write(
                json_encode($response, JSON_PRETTY_PRINT)
            );
            return $this->response;

        } catch (Exception $exception) {
            return $this->pageNotFound(print_r($exception, 1));
        }

    }

}