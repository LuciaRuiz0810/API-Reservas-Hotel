<?php

//Este archivo reescribe las urls para que sean más legibles y limpias
//Si no existiera este archivo las URL serían asi --> http://localhost/API/src/controllers/Cliente.php?id=1
//Con esta configuración se pueden escribir asi  --> http://localhost/API/clientes/1

header('Content-Type: application/json');

//Obtener la ruta solicitada
$peticion = $_SERVER['REQUEST_URI'];
$peticion = str_replace('/API', '', $peticion);
$peticion = parse_url($peticion, PHP_URL_PATH);

//Dividir la ruta en segmentos
$peticion = explode('/', trim($peticion, '/'));

//Router básico
if ($peticion[0] === 'clientes') {

    if (isset($peticion[1]) && is_numeric($peticion[1])) {
        $_GET['id'] = $peticion[1];
    }

    include(__DIR__ . '/src/controllers/Cliente.php');
} elseif ($peticion[0] === 'habitaciones') {

    if (isset($peticion[1]) && is_numeric($peticion[1])) {
        $_GET['id'] = $peticion[1];
    }

    include(__DIR__ . '/src/controllers/Habitacion.php');
} elseif ($peticion[0] === 'Reserva') {

    if (isset($peticion[1]) && is_numeric($peticion[1])) {
        $_GET['id'] = $peticion[1];
    }

    include(__DIR__ . '/src/controllers/Reserva.php');
} elseif ($peticion[0] === 'usuarios') {

    if (isset($peticion[1]) && is_numeric($peticion[1])) {
        $_GET['id'] = $peticion[1];
    }

    include(__DIR__ . '/src/controllers/Usuarios.php');
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Ruta no encontrada']);
}
