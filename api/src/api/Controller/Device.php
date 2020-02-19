<?php

namespace Robotjoosen\Lasertag\API\Controller;

use Exception;
use PDOStatement;
use Robotjoosen\Lasertag\API;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Config
 * @package Pickup\Controller
 */
class Device extends API\Controller
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
            $response['success'] = 1;
            $response['devices'] = [];
            $devices = $this->database->select('device', ['id', 'name', 'ip', 'createdon', 'updatedon']);
            foreach ($devices as $device) {
                $response['devices'][$device['id']] = [
                    'name' => $device['name'],
                    'ip' => $device['ip'],
                    'createdon' => $device['createdon'],
                    'updatedon' => $device['updatedon'],
                    'stats' => []
                ];

                $stats = $this->database->select('device_log', ['value', 'createdon'], ['device' => $device['id']]);
                foreach ($stats as $stat) {
                    $response['devices'][$device['id']]['stats'][] = $stat;
                }
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

    /**
     * Register Device
     * @param ServerRequestInterface $request
     * @param array $arguments
     * @return ResponseInterface
     */
    public function register(ServerRequestInterface $request, array $arguments): ResponseInterface
    {
        try {
            $response = ['success' => 0];

            // Get all send parameters
            $params = array_merge(
                $this->getParameters($request),
                $arguments
            );

            $devices = $this->database->select('device', ['id', 'name', 'ip'], ['name' => $params['alias']]);
            if (empty($devices)) {
                $response['device'] = [
                    'name' => $params['alias'],
                    'ip' => $params['ip'] //get ip from parameters because docker wont give me a proper ip address
                ];
                /** @var PDOStatement $device */
                $device = $this->database->insert('device', $response['device']);
                $response['device']['id'] = $this->database->id();
                $response['success'] = 1;
            } else {
                $response['success'] = 0;
                $response['message'] = 'Device is already registered';
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

    /**
     * @param ServerRequestInterface $request
     * @param array $arguments
     * @return ResponseInterface
     */
    public function add(ServerRequestInterface $request, array $arguments): ResponseInterface
    {
        try {
            $response = [];

            // Get all send parameters
            $params = array_merge(
                $this->getParameters($request),
                $arguments
            );

            $devices = $this->database->select('device', ['id', 'name', 'ip'], ["name" => $params['alias']]);
            if (!empty($devices)) {

                // set device parameters
                $response['device'] = $devices[0];
                $response['event'] = [
                    'device' => $devices[0]['id'],
                    'value' => 'hit'
                ];

                // add event to database
                if ($this->database->insert('device_log', $response['event'])) {
                    $response['event']['id'] = $this->database->id();
                    $response['success'] = 1;
                }
            } else {
                $response['success'] = 0;
                $response['message'] = 'Device does not exist';
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