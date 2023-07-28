<?php
namespace App\Application\Actions\Sensor;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Infrastructure\Persistence\SensorData\SqliteSensorDataRepository;


final class SensorDataCreateAction
{
    private $sensorDataRepository;

    public function __construct(SqliteSensorDataRepository $sensorDataRepository)
    {
        $this->sensorDataRepository = $sensorDataRepository;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array)$request->getParsedBody();
        if (!isset($data['timestamp'])) {
            $response->getBody()->write('timestamp is missing');
            return $response->withStatus(400);
        } else if (!is_int($data['timestamp'])) {
            if (is_string($data['timestamp'])) {
                $data['timestamp'] = (int)$data['timestamp'];
            } else {
                $response->getBody()->write('timestamp is not an integer');
                return $response->withStatus(400);
            }
        }
    
        if (!isset($data['face']) || !in_array($data['face'], ['south', 'east', 'north', 'west'])) {
            $response->getBody()->write('Invalid or missing face');
            return $response->withStatus(400);
        }
    
        if (!isset($data['temperature_value']) || !is_numeric($data['temperature_value'])) {
            $response->getBody()->write('Invalid or missing temperature_value');
            return $response->withStatus(400);
        }
    
        try {
            $this->sensorDataRepository->createSensorData($data);
        } catch (\Exception $e) {
            $response->getBody()->write('Failed to store sensor data: ' . $e->getMessage());
            return $response->withStatus(500);
        }
    
        $response->getBody()->write('Sensor data received successfully');
    
        return $response->withStatus(201); 
    }
    
}
