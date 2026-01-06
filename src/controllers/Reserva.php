<?php

/* Crear funciones para peticiones GET/PUT/POST/DELETE */
/* Validar datos de entrada */
/* Manejo de errores y excepciones */

include(__DIR__ . '/../config/config.php');
include(__DIR__ . '/../config/utils.php');
$conexion = connect($db);

//PETICIÓN GET  
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    try {

        //En caso de especificarse un id
        if (isset($_GET['id'])) {
            //Ej --> http://localhost/API/src/controllers/Reserva.php?id=1 / http://localhost/API/reservas/1
            $sql = $conexion->prepare('SELECT * FROM reservas WHERE id = :id');
            $sql->bindValue(':id', $_GET['id']);
            $sql->execute();
            $resultado = $sql->fetch(PDO::FETCH_ASSOC);

            //Si no se encuentra la reserva con ese id
            if (!$resultado) {
                http_response_code(404);
                echo json_encode(['error' => 'Reserva no existente']);
                exit();
            }

            http_response_code(200);
            echo json_encode($resultado);
            exit();
        }

        //En caso de que la petición GET falle
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error interno del servidor']);
        exit();
    }
}

//PETICIÓN DELETE
//EJ --> 
/*

http://localhost/API/reservas/2 //Petición con id y credenciales del admin para poder eliminar

{
  "usuario": {
    "usuario": "admin",
    "contrasena": "2DawAp1"
  }
}
*/
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    try {

        $datos = json_decode(file_get_contents("php://input"), true);

        // Validar credenciales
        autenticarUsuario($conexion, $datos, [1, 4, 6]);

        //En caso de especificarse un id
        if (isset($_GET['id'])) {

            //Verificar si la reserva existe antes de eliminarla
            $verificar = $conexion->prepare('SELECT id FROM reservas WHERE id = :id');
            $verificar->bindValue(':id', $_GET['id']);
            $verificar->execute();

            if (!$verificar->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Reserva no existente']);
                exit();
            }

            $sql = $conexion->prepare('DELETE FROM reservas WHERE id = :id');
            $sql->bindValue(':id', $_GET['id']);
            $sql->execute();

            http_response_code(200);
            echo json_encode(['mensaje' => 'La Reserva con el id ' . $_GET['id'] . ' ha sido eliminada']);
            exit();

            //En caso de querer borrar a TODAS las Reservas
            //Ej --> http://localhost/API/src/controllers/Reserva.php?all=true
        } elseif (isset($_GET['all']) && $_GET['all'] === 'true') {

            $sql = $conexion->prepare('DELETE FROM reservas');
            $sql->execute();

            http_response_code(200);
            echo json_encode(['mensaje' => 'TODAS las Reservas han sido eliminadas']);
            exit();
        } else {
            //Si no se especifica si son todas o una en concreto, se informará para evitar borrados por error
            //Ej --> http://localhost/API/src/controllers/Reserva.php
            http_response_code(400);
            echo json_encode(['error' => 'Se requiere id o all=true para borrar']);
            exit();
        }
        //En caso de que falle la petición, se informará
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error interno del servidor']);
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

//PETICIÓN PUT
/**
 * PUT /reservas?id=1
 *
 * Opción 1: Actualizar datos completos de la reserva
 * {
 *   "usuario": {
 *     "usuario": "admin",
 *     "contrasena": "2DawAp1"
 *   },
 *   "reserva": {
 *     "cliente_id": 1,
 *     "habitacion_id": 3,
 *     "fecha_entrada": "2026-01-12",
 *     "fecha_salida": "2026-01-18",
 *     "estado": "activa"
 *   }
 * }
 *
 * Opción 2: Solo cancelar la reserva
 * {
 *   "usuario": {
 *     "usuario": "admin",
 *     "contrasena": "2DawAp1"
 *   },
 *   "accion": "cancelar"
 * }
 */

 if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

    try {

        $datos = json_decode(file_get_contents("php://input"), true);

        //Autenticación
        $usuario = autenticarUsuario($conexion, $datos, [1, 4, 6]);

        //Verificar que se especifique un id
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Se requiere un id para actualizar']);
            exit();
        }

        //Verificar si la reserva existe
        $verificar = $conexion->prepare('SELECT * FROM reservas WHERE id = :id');
        $verificar->bindValue(':id', $_GET['id']);
        $verificar->execute();
        $reservaExistente = $verificar->fetch(PDO::FETCH_ASSOC);

        if (!$reservaExistente) {
            http_response_code(404);
            echo json_encode(['error' => 'Reserva no existente']);
            exit();
        }

        // OPCIÓN 1: CANCELAR RESERVA (acción rápida)
        if (isset($datos['accion']) && $datos['accion'] === 'cancelar') {
            
            // Verificar que la reserva no esté ya cancelada
            if ($reservaExistente['estado'] === 'cancelada') {
                http_response_code(400);
                echo json_encode(['error' => 'La reserva ya está cancelada']);
                exit();
            }

            $sql = $conexion->prepare('UPDATE reservas SET estado = "cancelada" WHERE id = :id');
            $sql->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
            $sql->execute();

            http_response_code(200);
            echo json_encode([
                'mensaje' => 'Reserva cancelada correctamente',
                'id' => $_GET['id'],
                'estado' => 'cancelada'
            ]);
            exit();
        }

        // OPCIÓN 2: ACTUALIZAR DATOS DE LA RESERVA (completo)
        // Obtener datos actualizados o mantener los existentes
        $reserva = $datos['reserva'] ?? [];
        $cliente_id = $reserva['cliente_id'] ?? $reservaExistente['cliente_id'];
        $habitacion_id = $reserva['habitacion_id'] ?? $reservaExistente['habitacion_id'];
        $fecha_entrada = $reserva['fecha_entrada'] ?? $reservaExistente['fecha_entrada'];
        $fecha_salida = $reserva['fecha_salida'] ?? $reservaExistente['fecha_salida'];
        $estado = $reserva['estado'] ?? $reservaExistente['estado'];

        // Validar que el estado sea válido
        $estadosValidos = ['activa', 'cancelada', 'completada'];
        if (!in_array($estado, $estadosValidos)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Estado inválido. Debe ser: activa, cancelada o completada'
            ]);
            exit();
        }

        // Validar fechas
        $fechaEntrada = new DateTime($fecha_entrada);
        $fechaSalida = new DateTime($fecha_salida);

        if ($fechaSalida <= $fechaEntrada) {
            http_response_code(400);
            echo json_encode([
                'error' => 'La fecha de salida debe ser posterior a la de entrada'
            ]);
            exit();
        }

        // Comprobar cliente
        $sqlCliente = $conexion->prepare('SELECT id FROM clientes WHERE id = :id');
        $sqlCliente->bindValue(':id', $cliente_id, PDO::PARAM_INT);
        $sqlCliente->execute();

        if (!$sqlCliente->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Cliente no existe']);
            exit();
        }

        // Comprobar habitación y obtener precio
        $sqlHabitacion = $conexion->prepare('SELECT precio FROM habitaciones WHERE id = :id');
        $sqlHabitacion->bindValue(':id', $habitacion_id, PDO::PARAM_INT);
        $sqlHabitacion->execute();

        $habitacion = $sqlHabitacion->fetch(PDO::FETCH_ASSOC);

        if (!$habitacion) {
            http_response_code(404);
            echo json_encode(['error' => 'Habitacion no existe']);
            exit();
        }

        // Comprobar disponibilidad solo si el estado es "activa"
        if ($estado === 'activa') {
            $sqlDisponibilidad = $conexion->prepare(
                'SELECT id FROM reservas
                 WHERE habitacion_id = :habitacion
                 AND estado = "activa"
                 AND id != :reserva_id
                 AND fecha_entrada < :fecha_salida
                 AND fecha_salida > :fecha_entrada'
            );

            $sqlDisponibilidad->bindValue(':habitacion', $habitacion_id, PDO::PARAM_INT);
            $sqlDisponibilidad->bindValue(':reserva_id', $_GET['id'], PDO::PARAM_INT);
            $sqlDisponibilidad->bindValue(':fecha_entrada', $fecha_entrada);
            $sqlDisponibilidad->bindValue(':fecha_salida', $fecha_salida);
            $sqlDisponibilidad->execute();

            if ($sqlDisponibilidad->fetch()) {
                http_response_code(409);
                echo json_encode([
                    'error' => 'La habitacion no está disponible en esas fechas'
                ]);
                exit();
            }
        }

        // Calcular número de días y precio total
        $dias = $fechaEntrada->diff($fechaSalida)->days;
        $precioTotal = $dias * $habitacion['precio'];

        // Actualizar reserva
        $sql = $conexion->prepare(
            'UPDATE reservas 
            SET cliente_id = :cliente,
                habitacion_id = :habitacion,
                usuario_id = :usuario,
                fecha_entrada = :entrada,
                fecha_salida = :salida,
                precio_total = :precio,
                estado = :estado
            WHERE id = :id'
        );

        $sql->bindValue(':cliente', $cliente_id, PDO::PARAM_INT);
        $sql->bindValue(':habitacion', $habitacion_id, PDO::PARAM_INT);
        $sql->bindValue(':usuario', $usuario['id'], PDO::PARAM_INT);
        $sql->bindValue(':entrada', $fecha_entrada);
        $sql->bindValue(':salida', $fecha_salida);
        $sql->bindValue(':precio', $precioTotal);
        $sql->bindValue(':estado', $estado);
        $sql->bindValue(':id', $_GET['id'], PDO::PARAM_INT);

        $sql->execute();

        http_response_code(200);
        echo json_encode([
            'mensaje' => 'Reserva actualizada correctamente',
            'id' => $_GET['id'],
            'dias' => $dias,
            'precio_total' => $precioTotal,
            'estado' => $estado
        ]);
        exit();

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error interno del servidor']);
        exit();
    }
}