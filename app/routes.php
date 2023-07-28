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

    $app->get('/', function (Request $request, Response $response, $args) {
        $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <title>Hello World</title>
            </head>
            <body>
                <h1>Hello World!</h1>
            </body>
            </html>
        ';
    
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
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

    $app->get('/malfunctioning', function (Request $request, Response $response, $args) use ($app) {
        $repository = $app->getContainer()->get(SqliteSensorDataRepository::class);
        $repository->detectMalfunctioningSensors();
        $response->getBody()->write('Malfunctioning sensors have been logged.');
        return $response->withHeader('Content-Type', 'text/plain');
    });

    $app->get('/past-week', function (Request $request, Response $response, $args) use ($app) {
        $repository = $app->getContainer()->get(SqliteSensorDataRepository::class);
        $averages = $repository->getHourlyAveragesForPastWeek();
        $response->getBody()->write(json_encode($averages));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    $app->get('/generatedata', function (Request $request, Response $response, $args) use ($app) {
        $repository = $app->getContainer()->get(SqliteSensorDataRepository::class);
        $repository->generateData();
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
    
    
    
    
    
    
};
