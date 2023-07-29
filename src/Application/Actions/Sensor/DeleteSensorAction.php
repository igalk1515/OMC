<?php

namespace App\Application\Actions\Sensor;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Infrastructure\Persistence\SensorData\SqliteSensorDataRepository;

class DeleteSensorAction
{
    private $sensorRepository;

    public function __construct(SqliteSensorDataRepository $sensorRepository)
    {
        $this->sensorRepository = $sensorRepository;
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $sensorId = (int) $args['id'];
        $this->sensorRepository->deleteSensor($sensorId);

        $response->getBody()->write(json_encode(['status' => 'Sensor deleted successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
