<?php

namespace Robotjoosen\Lasertag\API;

use Dotenv\Dotenv;
use Medoo\Medoo;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response;

/**
 * Class Controller
 * @package Pickup
 */
class Controller
{

    /**
     * @var Response
     */
    protected $response;

    public $database;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->response = new Response;
        $dotenv = Dotenv::createImmutable(dirname(dirname(__DIR__)));
        $dotenv->load();

        $pdo = new PDO('mysql:dbname='.$_ENV['MYSQL_DATABASE'].';host='.$_ENV['MYSQL_SERVER'], $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSWORD']);
        $this->database = new Medoo([
            'pdo' => $pdo,
            'database_type' => 'mysql',
            'prefix' => $_ENV['MYSQL_DATABASE_PREFIX']
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $arguments
     * @return ResponseInterface
     */
    public function index(ServerRequestInterface $request, array $arguments): ResponseInterface
    {
        $this->response->getBody()->write(
            json_encode([
                'code' => $this->response->getStatusCode()
            ])
        );
        return $this->response;
    }

    /**
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function getParameters(ServerRequestInterface &$request)
    {
        $parameters = [];
        switch($request->getMethod()) {
            case 'GET' :
                $parameters = $request->getQueryParams();
                unset($parameters['q']);
                break;
            case 'POST' :
                $parameters = json_decode($request->getBody()->getContents(), true);
                $parameters['files'] = array_merge_recursive(
                    $request->getParsedBody(),
                    $request->getUploadedFiles()
                );
                break;
            case 'PUT' :
            case 'DELETE' :
            case 'PATCH' :
                $parameters = json_decode($request->getBody()->getContents(), true);
                break;
        }
        return $parameters;
    }

    /**
     * Get request body parameters
     * @param ServerRequestInterface $request
     * @return mixed
     * @deprecated
     */
    protected function getRequestParams(ServerRequestInterface &$request)
    {
        parse_str($request->getBody()->getContents(), $params);
        return $params;
    }

    /**
     * Get Post Parameters
     * @param ServerRequestInterface $request
     * @return mixed
     * @deprecated
     */
    protected function getPostParams(ServerRequestInterface &$request)
    {
        return json_decode($request->getBody()->getContents(), true);
    }

    /**
     * Get Query Parameters
     * @param ServerRequestInterface $request
     * @return array
     * @deprecated
     */
    protected function getQueryParams(ServerRequestInterface &$request)
    {
        return $request->getQueryParams();
    }

    /**
     * @param $message string
     * @return Response
     */
    protected function unauthorizedRequest($message = null)
    {
        $this->response->getBody()->write(
            json_encode([
                'code' => 401,
                'message' => $message ?? "Unauthorized request"
            ])
        );
        return $this->response->withStatus(401);
    }

    /**
     * @param $message string
     * @return Response
     */
    protected function invalidRequest($message = null)
    {
        $this->response->getBody()->write(
            json_encode([
                'code' => 400,
                'message' => $message ?? "Invalid request"
            ])
        );
        return $this->response->withStatus(400);
    }

    /**
     * @param $message string
     * @return Response
     */
    protected function pageNotFound($message = null)
    {
        $this->response->getBody()->write(
            json_encode([
                'code' => 404,
                'message' => $message ?? "Page not found"
            ])
        );
        return $this->response->withStatus(404);
    }

}