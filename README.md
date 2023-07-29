# OMC Atower Assignment

This project is a simple API built with Slim Framework and SQLite to demonstrate a system for gathering sensor data and identifying malfunctioning sensors.

## Environment

The project was developed and tested using:

- PHP 8.2
- SQLite
- Slim Framework
- Windows 10
- Postman

## Running the Project Locally

You can start the project locally by running the built-in PHP server:
php -S localhost:8000 -t public public/index.php

## Docker

A Dockerfile is also included. To build and run the project using Docker:
docker build -t omc .
docker run -p 80:80 omc

## API Endpoints

Here are the available API endpoints:

- GET `/testdb`: return all the database, this used for debug purpose
- POST `/sensordata`: Insert new sensor data.
- GET `/malfunctioning`: Identify malfunctioning sensors and display them.
- GET `/past-week`: Display hourly averages for the past week.
- GET `/generatedata`: Generate data for the past week.
- GET `/generatedatahour`: Generate data for the past hour.
- GET `/sensordata/create`: Display a form to create new sensor data.
- DELETE `/sensors/{id}`: Delete a specific sensor.

## Usage

Please refer to the source code for more details on the usage of these endpoints.
