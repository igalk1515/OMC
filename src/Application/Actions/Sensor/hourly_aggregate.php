<?php

$current_time = time();

$one_hour_ago = $current_time - 3600;

$db = new PDO('sqlite:./sensor.db');

$query = $db->prepare("
    SELECT 
        face, 
        AVG(temperature_value) as avg_temperature 
    FROM 
        sensor_data 
    WHERE 
        timestamp >= :one_hour_ago AND 
        timestamp < :current_time 
    GROUP BY 
        face
");

$query->execute([
    'one_hour_ago' => $one_hour_ago,
    'current_time' => $current_time,
]);

$results = $query->fetchAll(PDO::FETCH_ASSOC);
print_r($results);
