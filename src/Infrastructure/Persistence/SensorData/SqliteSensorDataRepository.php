<?php

namespace App\Infrastructure\Persistence\SensorData;

use App\Domain\SensorData\SensorData;
use App\Domain\SensorData\SensorDataNotFoundException;
use PDO;

class SqliteSensorDataRepository 
{
    /**
     * @var PDO
     */
    private $connection;

    /**
     * SqliteSensorDataRepository constructor.
     * @param PDO $connection
     */
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return SensorData[]
     */
    public function findAll(): array
    {
        $stmt = $this->connection->prepare('SELECT * FROM sensor_data');
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // Convert each result into a SensorData object
        return array_map([$this, 'convertRowToSensorData'], $rows);
    }

    private function convertRowToSensorData(array $row): SensorData
    {
        return new SensorData($row['sensor_id'], $row['timestamp'], $row['face'], $row['temperature_value']);
    }
    

    public function fetchAllData(): array 
    {
        $stmt = $this->connection->query('SELECT * FROM sensor_data');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createSensorData(array $data)
    {
        $stmt = $this->connection->prepare('INSERT INTO sensor_data (sensor_id, timestamp, face, temperature_value) VALUES (:sensor_id, :timestamp, :face, :temperature_value)');
        $stmt->bindParam(':sensor_id', $data['sensor_id'], PDO::PARAM_INT);
        $stmt->bindParam(':timestamp', $data['timestamp'], PDO::PARAM_STR);
        $stmt->bindParam(':face', $data['face'], PDO::PARAM_STR);
        $stmt->bindParam(':temperature_value', $data['temperature_value'], PDO::PARAM_STR);
        $stmt->execute();
    }
    
    public function calculateAverageForSide($side)
    {
        $stmt = $this->connection->prepare('SELECT AVG(temperature_value) FROM sensor_data WHERE face = :face');  
        $stmt->bindParam(':face', $side, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function getSensorsForSide($side)
    {
        $stmt = $this->connection->prepare('SELECT * FROM sensor_data WHERE face = :face');
        $stmt->bindParam(':face', $side, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function detectMalfunctioningSensors()
    {
        $logFilePath = __DIR__ . '/../../../../logs/sensor_malfunction.log';
        
        $sides = ['north', 'south', 'east', 'west'];
        foreach ($sides as $side) {
            $average = $this->calculateAverageForSide($side);
            $sensors = $this->getSensorsForSide($side);
            foreach ($sensors as $sensor) {
                $deviation = abs($sensor['temperature_value'] - $average) / $average;
                if ($deviation > 0.2) {
                    $message = "Sensor {$sensor['id']} is malfunctioning. Average value: {$average}, Sensor value: {$sensor['temperature_value']}";
                    file_put_contents($logFilePath, $message.PHP_EOL, FILE_APPEND);
                }
            }
        }
    }

    public function getHourlyAveragesForPastWeek()
    {
        $stmt = $this->connection->prepare("
            SELECT 
                strftime('%Y-%m-%d %H:00:00', datetime(timestamp, 'unixepoch')) as hour, 
                face, 
                AVG(temperature_value) as average_temp
            FROM sensor_data
            WHERE datetime(timestamp, 'unixepoch') >= datetime('now', '-7 days')
            GROUP BY hour, face
            ORDER BY hour DESC, face ASC
        ");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generateData() {
        for($i = 0; $i < 168; $i++) { // 168 hours = 1 week
            foreach(['north', 'south', 'east', 'west'] as $side) {
                $temperature = mt_rand(15 * 10, 25 * 10) / 10; 
                $timestamp = time() - $i * 3600;
                $sensor_id = mt_rand(1, 10); 
    
                $stmt = $this->connection->prepare('INSERT INTO sensor_data (sensor_id, timestamp, face, temperature_value) VALUES (:sensor_id, :timestamp, :face, :temperature_value)');
                $stmt->bindParam(':sensor_id', $sensor_id, PDO::PARAM_INT);
                $stmt->bindParam(':timestamp', $timestamp, PDO::PARAM_INT);
                $stmt->bindParam(':face', $side, PDO::PARAM_STR);
                $stmt->bindParam(':temperature_value', $temperature, PDO::PARAM_STR);
                $stmt->execute();
            }
        }
    }
    
}
