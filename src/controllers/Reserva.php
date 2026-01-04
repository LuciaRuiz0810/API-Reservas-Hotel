<?php

/* Crear funciones para peticiones GET/PUT/POST/DELETE */
/* Validar datos de entrada */
/* Manejo de errores y excepciones */

include(__DIR__ . '/../config/config.php');
include(__DIR__ . '/../config/utils.php');
$conexion = connect($db);

//PETICIÓN GET  
if ($_SERVER['REQUEST_METHOD'] == 'GET'){

    try{
 //En caso de especificarse un id
    if(isset($_GET['id'])){
        //Ej --> http://localhost/API/src/controllers/Reserva.php?id=1 / http://localhost/API/Reserva/1
        $sql = $conexion->prepare('SELECT * FROM reservas where id=:id');
        $sql -> bindValue(':id' , $_GET['id']);
        $sql->execute();
        header("HTTP/1.1 200 OK");
        echo json_encode($sql -> fetch(PDO::FETCH_ASSOC));
        exit();

    //En caso de querer listar a todos las Reservas
    }else{

        $sql = $conexion->prepare('SELECT * FROM reservas');
        $sql->execute();
        header("HTTP/1.1 200 OK");
        echo json_encode($sql->fetchAll(PDO::FETCH_ASSOC));
        exit();
    }

    //En caso de que la petición GET falle
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        echo json_encode(['error' => 'Error al obtener la/las Reserva/s']);
        exit();
    }

   
}

//PETICIÓN DELETE
if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {

    try {
         //En caso de especificarse un id
        if (isset($_GET['id'])) {
            
            $sql = $conexion->prepare('DELETE FROM reservas WHERE id = :id');
            $sql->bindValue(':id', $_GET['id']);
            $sql->execute();

            header("HTTP/1.1 200 OK");
             echo json_encode(['La Reserva con el id ' . $_GET['id'] . ' ha sido eliminada']);
            exit();

        //En caso de querer borrar a TODAS las Reservas
        //Ej --> http://localhost/API/src/controllers/Reserva.php?all=true
        } elseif (isset($_GET['all']) && $_GET['all'] == 'true') {

            $sql = $conexion->prepare('DELETE FROM reservas');
            $sql->execute();

            header("HTTP/1.1 200 OK");
            echo json_encode(['TODAS las Reservas han sido eliminadas']);
            exit();

        } else {
            //Si no se especifica si son todas o una en concreto, se informará para evitar borrados por error
            //Ej --> http://localhost/API/src/controllers/Reserva.php
            header("HTTP/1.1 400 Bad Request");
            echo json_encode(['error' => 'Se requiere id o all=true para borrar']);
            exit();
        }
        //En caso de que falle la petición, se informará
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        echo json_encode(['error' => 'Error al eliminar la Reserva']);
        exit();
    }

}

// PETICION POST

/**
 * POST /reservas
 *
 * {
 *   "usuario": {
 *     "usuario": "admin",
 *     "contrasena": "2DawAp1"
 *   },
 *   "reserva": {
 *     "cliente_id": 1,
 *     "habitacion_id": 2,
 *     "fecha_entrada": "2026-01-10",
 *     "fecha_salida": "2026-01-15"
 *   }
 * }
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $datos = json_decode(file_get_contents("php://input"), true);

        //Autenticación (POST permitido)
        $usuario = autenticarUsuario($conexion, $datos, [1, 4, 6]);

        // Validar datos de la reserva
        if (
            !isset($datos['reserva']['cliente_id']) ||
            !isset($datos['reserva']['habitacion_id']) ||
            !isset($datos['reserva']['fecha_entrada']) ||
            !isset($datos['reserva']['fecha_salida'])
        ) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan datos de la reserva']);
            exit();
        }

        $reserva = $datos['reserva'];

        // Validar fechas
        $fechaEntrada = new DateTime($reserva['fecha_entrada']);
        $fechaSalida  = new DateTime($reserva['fecha_salida']);

        if ($fechaSalida <= $fechaEntrada) {
            http_response_code(400);
            echo json_encode([
                'error' => 'La fecha de salida debe ser posterior a la de entrada'
            ]);
            exit();
        }

        // Comprobar cliente
        $sqlCliente = $conexion->prepare(
            'SELECT id FROM clientes WHERE id = :id'
        );
        $sqlCliente->bindValue(':id', $reserva['cliente_id'], PDO::PARAM_INT);
        $sqlCliente->execute();

        if (!$sqlCliente->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Cliente no existe']);
            exit();
        }

        // Comprobar habitación y obtener precio
        $sqlHabitacion = $conexion->prepare(
            'SELECT precio FROM habitaciones WHERE id = :id'
        );
        $sqlHabitacion->bindValue(':id', $reserva['habitacion_id'], PDO::PARAM_INT);
        $sqlHabitacion->execute();

        $habitacion = $sqlHabitacion->fetch(PDO::FETCH_ASSOC);

        if (!$habitacion) {
            http_response_code(404);
            echo json_encode(['error' => 'Habitacion no existe']);
            exit();
        }

        // COMPROBAR DISPONIBILIDAD DE LA HABITACIÓN
        $sqlDisponibilidad = $conexion->prepare(
            'SELECT id FROM reservas
             WHERE habitacion_id = :habitacion
             AND estado = "activa"
             AND fecha_entrada < :fecha_salida
             AND fecha_salida > :fecha_entrada'
        );

        $sqlDisponibilidad->bindValue(':habitacion', $reserva['habitacion_id'], PDO::PARAM_INT);
        $sqlDisponibilidad->bindValue(':fecha_entrada', $reserva['fecha_entrada']);
        $sqlDisponibilidad->bindValue(':fecha_salida', $reserva['fecha_salida']);
        $sqlDisponibilidad->execute();

        if ($sqlDisponibilidad->fetch()) {
            http_response_code(409);
            echo json_encode([
                'error' => 'La habitacion no está disponible en esas fechas'
            ]);
            exit();
        }

        // Calcular número de días
        $dias = $fechaEntrada->diff($fechaSalida)->days;

        // Calcular precio total
        $precioTotal = $dias * $habitacion['precio'];

        // Insertar reserva
        $sql = $conexion->prepare(
            'INSERT INTO reservas 
            (cliente_id, habitacion_id, usuario_id, fecha_entrada, fecha_salida, precio_total)
            VALUES
            (:cliente, :habitacion, :usuario, :entrada, :salida, :precio)'
        );

        $sql->bindValue(':cliente', $reserva['cliente_id'], PDO::PARAM_INT);
        $sql->bindValue(':habitacion', $reserva['habitacion_id'], PDO::PARAM_INT);
        $sql->bindValue(':usuario', $usuario['id'], PDO::PARAM_INT);
        $sql->bindValue(':entrada', $reserva['fecha_entrada']);
        $sql->bindValue(':salida', $reserva['fecha_salida']);
        $sql->bindValue(':precio', $precioTotal);

        $sql->execute();

        http_response_code(201);
        echo json_encode([
            'mensaje' => 'Reserva creada correctamente',
            'dias' => $dias,
            'precio_total' => $precioTotal,
            'id' => $conexion->lastInsertId()
        ]);
        exit();

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error interno del servidor']);
        exit();
    }
}


?>