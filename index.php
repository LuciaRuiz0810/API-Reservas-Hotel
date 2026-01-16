<?php

//Este archivo reescribe las urls para que sean más legibles y limpias
//Si no existiera este archivo las URL se verían --> http://localhost/API/src/controllers/Cliente.php?id=1
//Con esta configuración y .htaccess se pueden escribir asi  --> http://localhost/API/clientes/1

header('Content-Type: application/json');

//Obtener la ruta solicitada
$peticion = $_SERVER['REQUEST_URI']; /*Guarda la ruta */
$peticion = str_replace('/API', '', $peticion); /*Elimina /API de la ruta interna*/
$peticion = parse_url($peticion, PHP_URL_PATH);  /*Elimina los parametros */

//Dividir la ruta en segmentos
$peticion = explode('/', trim($peticion, '/'));

//Esta estructura analiza la URL enviada por Thunder Client y se ejecuta en el archivo correspondiente (cliente, habitaciones o reservas)


if($peticion[0] == ''){
    //Carga un archivo basico
    include(__DIR__ . '/src/controllers/inicio.php');

    //Si la URL empieza con 'clientes'
} elseif ($peticion[0] === 'clientes') {

     // Si hay un número después de /clientes/ lo guardará en $_GET['id']
    if (isset($peticion[1]) && is_numeric($peticion[1])) {
        $_GET['id'] = $peticion[1];
    }

     //Carga el archivo que maneja las peticiones de clientes
    include(__DIR__ . '/src/controllers/Cliente.php');


    //Si la URL empieza con 'habitaciones'
} elseif ($peticion[0] === 'habitaciones') {

     // Si hay un número después de /habitaciones/ lo guardará en $_GET['id']
    if (isset($peticion[1]) && is_numeric($peticion[1])) {
        $_GET['id'] = $peticion[1];
    }
 //Carga el archivo que maneja las peticiones de las habitaciones
    include(__DIR__ . '/src/controllers/Habitacion.php');


    //Si la URL empieza con 'reservas'
} elseif ($peticion[0] === 'reservas') {

     // Si hay un número después de /reservas/ lo guardará en $_GET['id']
    if (isset($peticion[1]) && is_numeric($peticion[1])) {
        $_GET['id'] = $peticion[1];
    }

    //Carga el archivo que maneja las peticiones de las reservas
    include(__DIR__ . '/src/controllers/Reserva.php');

} else {
    http_response_code(404);
    echo json_encode(['error' => 'Ruta no encontrada']);
}
