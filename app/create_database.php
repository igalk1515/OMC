<?php
$db = new PDO('sqlite:./sensor.db');

$db->exec("CREATE TABLE IF NOT EXISTS sensor_data (
    id INTEGER PRIMARY KEY,
    sensor_id INTEGER,
    timestamp INTEGER,  
    face TEXT CHECK(face IN ('south', 'east', 'north', 'west')), 
    temperature_value REAL)");
