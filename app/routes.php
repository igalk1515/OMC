<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Infrastructure\Persistence\SensorData\SqliteSensorDataRepository;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    $app->get('/testdb', function (Request $request, Response $response, $args) use ($app) {
        $repository = $app->getContainer()->get(SqliteSensorDataRepository::class);
        $data = $repository->fetchAllData();
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->post('/sensordata', \App\Application\Actions\Sensor\SensorDataCreateAction::class);

    // this endpoint is showing the malfunctioning sensors for the last hour in real life the interval should be much shorter
    $app->get('/malfunctioning', function (Request $request, Response $response, $args) use ($app) {
        $repository = $app->getContainer()->get(SqliteSensorDataRepository::class);
        $malfunctioningSensors = $repository->detectMalfunctioningSensors();
    
        $sensorData = [];
        foreach ($malfunctioningSensors as $sensorMessage) {
            preg_match("/Sensor (\d+) is malfunctioning. Average value: ([\d\.]+), Sensor value: ([\d\.]+)/", $sensorMessage, $matches);
    
            if (count($matches) == 4) {
                $sensorId = $matches[1];
                $averageValue = $matches[2];
                $sensorValue = $matches[3];
    
                $sensorData[] = [
                    'sensorId' => $sensorId,
                    'averageValue' => $averageValue,
                    'sensorValue' => $sensorValue
                ];
            }
        }
    
        usort($sensorData, function($a, $b) {
            return $a['sensorId'] <=> $b['sensorId'];
        });
    
        $output = '<table border="1">';
        $output .= '<tr><th>Sensor ID</th><th>Average Value</th><th>Sensor Value</th></tr>';
    
        foreach ($sensorData as $sensor) {
            $output .= "<tr><td>{$sensor['sensorId']}</td><td>{$sensor['averageValue']}</td><td>{$sensor['sensorValue']}</td></tr>";
        }
    
        $output .= '</table>';
    
        $response->getBody()->write($output);
        return $response->withHeader('Content-Type', 'text/html');
    });
    
    

    $app->get('/past-week', function (Request $request, Response $response, $args) use ($app) {
        $repository = $app->getContainer()->get(SqliteSensorDataRepository::class);
        $averages = $repository->getHourlyAveragesForPastWeek();
    
        $html = '<table>';
        $html .= '<tr><th>Hour</th><th>Face</th><th>Average Temp</th></tr>';
    
        foreach ($averages as $average) {
            $html .= '<tr>';
            $html .= '<td>' . $average['hour'] . '</td>';
            $html .= '<td>' . $average['face'] . '</td>';
            $html .= '<td>' . $average['average_temp'] . '</td>';
            $html .= '</tr>';
        }
    
        $html .= '</table>';
    
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });
    
    // generate data for the past week
    $app->get('/generatedata', function (Request $request, Response $response, $args) use ($app) {
        $repository = $app->getContainer()->get(SqliteSensorDataRepository::class);
        $repository->generateData();
        $response->getBody()->write('Data has been generated.');
        return $response->withHeader('Content-Type', 'text/plain');
    });

    // generate data for the past hour
    $app->get('/generatedatahour', function (Request $request, Response $response, $args) use ($app) {
        $repository = $app->getContainer()->get(SqliteSensorDataRepository::class);
        $repository->generateDataForPastHour();
        $response->getBody()->write('Data has been generated.');
        return $response->withHeader('Content-Type', 'text/plain');
    });

    $app->get('/sensordata/create', function (Request $request, Response $response, $args) {
        $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <title>Add Sensor Data</title>
                <script>
                    window.onload = function() {
                        var now = Math.floor(Date.now() / 1000);
                        var dateNow = new Date(now * 1000);
                        document.getElementById("timestamp").value = now;
                        document.getElementById("time_display").innerHTML = "Timestamp: " + dateNow;
                    }
                </script>
            </head>
            <body>
                <h1>Add Sensor Data</h1>
                <form action="/sensordata" method="post">
                    <label for="sensor_id">Sensor ID:</label><br>
                    <input type="number" id="sensor_id" name="sensor_id"><br>
                    <label for="face">Face:</label><br>
                    <select id="face" name="face">
                      <option value="north">North</option>
                      <option value="south">South</option>
                      <option value="east">East</option>
                      <option value="west">West</option>
                    </select><br>
                    <label for="temperature_value">Temperature Value:</label><br>
                    <input type="text" id="temperature_value" name="temperature_value"><br>
                    <input type="hidden" id="timestamp" name="timestamp">
                    <p id="time_display"></p>
                    <input type="submit" value="Submit">
                </form>
            </body>
            </html>
        ';
    
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });
    
    $app->delete('/sensors/{id}', \App\Application\Actions\Sensor\DeleteSensorAction::class);
};
