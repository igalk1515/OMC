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

    // calculate average temperature side for the last hour
    public function calculateAverageForSide($side)
    {
        $stmt = $this->connection->prepare(
            'SELECT AVG(temperature_value) FROM sensor_data WHERE face = :face AND timestamp >= datetime("now", "-1 hour")'
        );
        $stmt->bindParam(':face', $side, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function getSensorsForSide($side)
    {
        $stmt = $this->connection->prepare(
            'SELECT * FROM sensor_data WHERE face = :face AND timestamp >= datetime("now", "-1 hour")'
        );
        $stmt->bindParam(':face', $side, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function detectMalfunctioningSensors()
    {
        $logFilePath = __DIR__ . '/../../../../logs/sensor_malfunction.log';
        $sides = ['north', 'south', 'east', 'west'];
        $malfunctioningSensors = [];

        foreach ($sides as $side) {
            $average = $this->calculateAverageForSide($side);
            $sensors = $this->getSensorsForSide($side);
            foreach ($sensors as $sensor) {
                $deviation = abs($sensor['temperature_value'] - $average) / $average;
                if ($deviation > 0.2) {
                    $message = "Sensor {$sensor['id']} is malfunctioning. Average value: {$average}, Sensor value: {$sensor['temperature_value']}";
                    $malfunctioningSensors[] = $message;
                    file_put_contents($logFilePath, $message.PHP_EOL, FILE_APPEND);
                }
            }
        }
        return $malfunctioningSensors;
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

    // generate data for past week
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

    //generate data for past hour
    public function generateDataForPastHour() {
        $faces = ['south', 'east', 'north', 'west'];

        for($i=1; $i<=100; $i++){
            $face = $faces[array_rand($faces)];
            $temperature_value = rand(15, 25) + lcg_value() - 0.5;
            $timestamp = date('Y-m-d H:i:s', strtotime('-' . rand(0, 60) . ' minutes')); 

            if($i === 100){
                $temperature_value = 50;
            }

            $stmt = $this->connection->prepare('INSERT INTO sensor_data (sensor_id, timestamp, face, temperature_value) VALUES (:sensor_id, :timestamp, :face, :temperature_value)');
            $stmt->bindParam(':sensor_id', $i, PDO::PARAM_INT);
            $stmt->bindParam(':timestamp', $timestamp, PDO::PARAM_STR);
            $stmt->bindParam(':face', $face, PDO::PARAM_STR);
            $stmt->bindParam(':temperature_value', $temperature_value, PDO::PARAM_STR);
            $stmt->execute();
        }
    }


    public function deleteSensor(int $sensorId): void {
        $stmt = $this->connection->prepare('DELETE FROM sensor_data WHERE sensor_id = :sensorId');
        $stmt->bindParam(':sensorId', $sensorId);
        $stmt->execute();
    }
}
