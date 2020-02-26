<?php

namespace Robotjoosen\Lasertag\API\Controller;

use DateTime;
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
            $games = $this->database->select('game', ['id', 'name', 'starttime', 'endtime']);
            foreach ($games as $game) {

                // get device logs
                $hits = [
                    'devices' => [],
                    'hits' => [],
                    'raw' => []
                ];
                $logs = $this->database->select('device_log', ['id', 'device', 'createdon'], [
                    'createdon[<>]' => [$game['starttime'], $game['endtime']]
                ]);
                foreach ($logs as $log) {
                    $hits['devices'][] = $log['device'];
                    $hits['hits'][] = $log['id'];
                    $hits['raw'][] = $log;
                }

                // create datetimes
                $end_time = new DateTime($game['endtime']);
                $start_time = new DateTime($game['starttime']);

                // setup up response
                $response['games'][] = [
                    'id' => $game['id'],
                    'name' => $game['name'],
                    'start_time' => $game['starttime'],
                    'end_time' => $game['endtime'],
                    'total_time' => abs($end_time->format('U.u') - $start_time->format('U.u')),
                    'raw' => $hits['raw'],
                    'devices' => count(array_unique($hits['devices'])),
                    'hits' => count(array_unique($hits['hits']))
                ];

            }
            $response['games'] = array_reverse($response['games']);

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
    public function addGame(ServerRequestInterface $request, array $arguments): ResponseInterface
    {
        try {

            // get start time
            $t = microtime(true);
            $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
            $d = new DateTime(date('Y-m-d H:i:s.' . $micro, $t));

            // add game to database
            $response = [
                'name' => md5(time()),
                'starttime' => $d->format("Y-m-d H:i:s.u")
            ];
            if ($this->database->insert('game', $response)) {
                $response['id'] = $this->database->id();
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
    public function updateGame(ServerRequestInterface $request, array $arguments): ResponseInterface
    {
        try {

            $response = [
                'success' => 0
            ];

            // Get all send parameters
            $params = array_merge(
                $this->getParameters($request),
                $arguments
            );

            // update
            if ($this->database->update('game', ['endtime' => $params['endtime']], ['id' => $params['alias']])) {
                $response['success'] = 1;
                $response['endtime'] = $params['endtime'];
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
    public function getStatus(ServerRequestInterface $request, array $arguments): ResponseInterface
    {
        try {

            // Get all send parameters
            $params = array_merge(
                $this->getParameters($request),
                $arguments
            );

            if ($game = $this->database->select('game', ['id', 'starttime'], ['id' => $params['alias']])) {
                $response = [
                    'success' => 1,
                    'game_id' => intval($game[0]['id']),
                    'starttime' => $game[0]['starttime'],
                    'hits' => [],
                    'device' => [],
                    'stats' => [
                        'devices_hit' => 0,
                        'last_hit' => 0.,
                        'total_time' => 0
                    ]
                ];

                $devices = [];
                foreach ($this->database->select('device', ['id', 'name']) as $device) {
                    $devices[$device['id']] = $device['name'];
                }

                $devices_hit = [];
                foreach ($this->database->select('device_log', ['id', 'device', 'value', 'createdon'], ['value' => 'hit', 'createdon[>]' => $game[0]['starttime']]) as $hit) {
                    $devices_hit[] = $hit['device'];
                    $response['hits'][] = $hit;
                    $response['stats']['last_hit'] = $hit['createdon'];
                    $key = $this->searchForId($devices[$hit['device']], 'name', $response['device']);
                    if (!is_null($key)) {
                        $response['device'][$key]['hit']++;
                    } else {
                        $response['device'][] = [
                            'id' => intval($hit['device']),
                            'name' => $devices[$hit['device']],
                            'hit' => 1
                        ];
                    }
                }

                // devices hit
                $response['stats']['devices_hit'] = count(array_unique($devices_hit));

                // calculate total time
                if(gettype($response['stats']['last_hit']) !== 'double') {
                    $last_hit = new DateTime($response['stats']['last_hit']);
                    $start_time = new DateTime($game[0]['starttime']);
                    $response['stats']['total_time'] = abs($last_hit->format('U.u') - $start_time->format('U.u'));
                } else {
                    $response['stats']['total_time'] = 0;
                }
            } else {
                $response = $params;
                $response['success'] = 0;
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
     * @param int $id
     * @param string $key
     * @param array $array
     * @return int|string|null
     */
    public function searchForId($id, $key, $array)
    {
        foreach ($array as $idx => $val) {
            if ($val[$key] === $id) {
                return $idx;
            }
        }
        return null;
    }

}