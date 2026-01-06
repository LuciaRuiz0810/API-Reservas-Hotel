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
            //Ej --> http://localhost/API/src/controllers/Habitacion.php?id=1 / http://localhost/API/habitaciones/1
            $sql = $conexion->prepare('SELECT * FROM habitaciones WHERE id = :id');
            $sql->bindValue(':id', $_GET['id']);
            $sql->execute();
            $resultado = $sql->fetch(PDO::FETCH_ASSOC);

            //Si no se encuentra la habitación con ese id
            if (!$resultado) {
                http_response_code(404);
                echo json_encode(['error' => 'Habitación no existente']);
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

http://localhost/API/habitaciones/1 //Petición con id y credenciales del admin para poder eliminar

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

            //Verificar si la habitación existe antes de eliminarla
            $verificar = $conexion->prepare('SELECT id FROM habitaciones WHERE id = :id');
            $verificar->bindValue(':id', $_GET['id']);
            $verificar->execute();

            if (!$verificar->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Habitación no existente']);
                exit();
            }

            $sql = $conexion->prepare('DELETE FROM habitaciones WHERE id = :id');
            $sql->bindValue(':id', $_GET['id']);
            $sql->execute();

            http_response_code(200);
            echo json_encode(['mensaje' => 'La habitacion con el id ' . $_GET['id'] . ' ha sido eliminada']);
            exit();

            //En caso de querer borrar a TODAS las Habitaciones
            //Ej --> http://localhost/API/src/controllers/Habitaciones.php?all=true
        } elseif (isset($_GET['all']) && $_GET['all'] === 'true') {

            $sql = $conexion->prepare('DELETE FROM habitaciones');
            $sql->execute();

            http_response_code(200);
            echo json_encode(['mensaje' => 'TODAS las habitaciones han sido eliminadas']);
            exit();
        } else {
            //Si no se especifica si son todas o una en concreto, se informará para evitar borrados por error
            //Ej --> http://localhost/API/src/controllers/Habitaciones.php
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


//PETICION POST
/* 
Opcion 1
{
  "usuario": {
    "usuario": "admin",
    "contrasena": "2DawAp1"
  },
  "habitacion": {
    "numero": "400",
    "planta": "4",
    "tipo": "Familiar",
    "precio": "400",
    "suite": "1",
    "num_personas": "4"
  },
  "opcion": {
    "n": 1
  }
}

Opcion 2
{
  "usuario": {
    "usuario": "admin",
    "contrasena": "2DawAp1"
  },
  "habitacion": {
    "numero": "400",
    "planta": "4",
    "tipo": "Familiar",
    "precio": "400",
    "suite": "1",
    "num_personas": "4",
    "n_habitaciones": "20"
  },
  "opcion": {
    "n": 2
  }
}

*/

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $datos = json_decode(file_get_contents("php://input"), true);

        autenticarUsuario($conexion, $datos, [1, 4, 6]);

        // Validar datos del cliente
        if (
            empty($datos['habitacion']['numero']) ||
            empty($datos['habitacion']['planta']) ||
            empty($datos['habitacion']['tipo']) ||
            empty($datos['habitacion']['precio']) ||
            empty($datos['habitacion']['suite']) ||
            empty($datos['habitacion']['num_personas']) ||
            empty($datos['opcion']['n'])
        ) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan datos de la habitacion']);
            exit();
        }

        $habitacion = $datos['habitacion'];

        //Comprobamos si quiere ir añadiendo las habitaciones de una en una o por plantas de manera generica
        if ($datos['opcion']['n'] == 1) {
            //insertar valores manera individual
            $sql = $conexion->prepare(
                'INSERT INTO habitaciones (numero, planta, tipo, precio, suite, num_personas)
                VALUES (:numero, :planta, :tipo, :precio, :suite, :num_personas)'
            );

            $sql->bindValue(':numero', $habitacion['numero']);
            $sql->bindValue(':planta', $habitacion['planta']);
            $sql->bindValue(':tipo', $habitacion['tipo']);
            $sql->bindValue(':precio', $habitacion['precio']);
            $sql->bindValue(':suite', $habitacion['suite'] ?? null);
            $sql->bindValue(':num_personas', $habitacion['num_personas'] ?? null);

            $sql->execute();

            http_response_code(201);
            echo json_encode([
                'mensaje' => 'Habitacion creada correctamente',
                'id' => $conexion->lastInsertId()
            ]);
            exit();
        } else {
            if (
                empty($datos['habitacion']['n_habitaciones'])
            ) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan datos de la habitacion']);
                exit();
            }

            //insertar valores en bloque
            $sql = $conexion->prepare(
                'INSERT INTO habitaciones (numero, planta, tipo, precio, suite, num_personas)
                VALUES (:numero, :planta, :tipo, :precio, :suite, :num_personas)'
            );

            for ($i = $habitacion['numero']; $i < ($habitacion['n_habitaciones'] + $habitacion['numero']); $i++) {
                $sql->bindValue(':numero', $i);
                $sql->bindValue(':planta', $habitacion['planta']);
                $sql->bindValue(':tipo', $habitacion['tipo']);
                $sql->bindValue(':precio', $habitacion['precio']);
                $sql->bindValue(':suite', $habitacion['suite'] ?? null);
                $sql->bindValue(':num_personas', $habitacion['num_personas'] ?? null);

                $sql->execute();
            }

            http_response_code(201);
            echo json_encode([
                'mensaje' => 'Habitaciones creadas correctamente',
                'total' => $habitacion['n_habitaciones']
            ]);
            exit();
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error interno del servidor']);
        exit();
    }
}
//PETICIÓN PUT
/**
 * PUT /habitaciones?id=1
 *
 * Opción 1: Actualizar datos de la habitación
 * {
 *   "usuario": {
 *     "usuario": "admin",
 *     "contrasena": "2DawAp1"
 *   },
 *   "habitacion": {
 *     "numero": "101",
 *     "planta": "1",
 *     "tipo": "Doble",
 *     "precio": "150",
 *     "suite": "0",
 *     "num_personas": "2",
 *     "disponible": "1"
 *   }
 * }
 *
 * Opción 2: Solo cambiar disponibilidad
 * {
 *   "usuario": {
 *     "usuario": "admin",
 *     "contrasena": "2DawAp1"
 *   },
 *   "accion": "cambiar_disponibilidad",
 *   "disponible": "0"
 * }
 */

 if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

    try {

        $datos = json_decode(file_get_contents("php://input"), true);

        //Autenticación
        autenticarUsuario($conexion, $datos, [1, 4, 6]);

        //Verificar que se especifique un id
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Se requiere un id para actualizar']);
            exit();
        }

        //Verificar si la habitación existe
        $verificar = $conexion->prepare('SELECT * FROM habitaciones WHERE id = :id');
        $verificar->bindValue(':id', $_GET['id']);
        $verificar->execute();
        $habitacionExistente = $verificar->fetch(PDO::FETCH_ASSOC);

        if (!$habitacionExistente) {
            http_response_code(404);
            echo json_encode(['error' => 'Habitación no existente']);
            exit();
        }

        // OPCIÓN 1: CAMBIAR SOLO DISPONIBILIDAD (acción rápida)
        if (isset($datos['accion']) && $datos['accion'] === 'cambiar_disponibilidad') {
            
            if (!isset($datos['disponible'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Se requiere el campo disponible (0 o 1)']);
                exit();
            }

            $disponible = $datos['disponible'];

            // Validar que sea 0 o 1
            if ($disponible !== '0' && $disponible !== '1' && $disponible !== 0 && $disponible !== 1) {
                http_response_code(400);
                echo json_encode(['error' => 'El campo disponible debe ser 0 o 1']);
                exit();
            }

            $sql = $conexion->prepare('UPDATE habitaciones SET disponible = :disponible WHERE id = :id');
            $sql->bindValue(':disponible', $disponible);
            $sql->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
            $sql->execute();

            http_response_code(200);
            echo json_encode([
                'mensaje' => 'Disponibilidad actualizada correctamente',
                'id' => $_GET['id'],
                'disponible' => $disponible == 1 ? 'Sí' : 'No'
            ]);
            exit();
        }

        // OPCIÓN 2: ACTUALIZAR TODOS LOS DATOS DE LA HABITACIÓN
        // Obtener datos actualizados o mantener los existentes
        $habitacion = $datos['habitacion'] ?? [];
        $numero = $habitacion['numero'] ?? $habitacionExistente['numero'];
        $planta = $habitacion['planta'] ?? $habitacionExistente['planta'];
        $tipo = $habitacion['tipo'] ?? $habitacionExistente['tipo'];
        $precio = $habitacion['precio'] ?? $habitacionExistente['precio'];
        $suite = $habitacion['suite'] ?? $habitacionExistente['suite'];
        $num_personas = $habitacion['num_personas'] ?? $habitacionExistente['num_personas'];
        $disponible = $habitacion['disponible'] ?? $habitacionExistente['disponible'];

        // Validar tipos de habitación válidos (opcional)
        $tiposValidos = ['Individual', 'Doble', 'Triple', 'Familiar', 'Suite'];
        if (!in_array($tipo, $tiposValidos)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Tipo de habitación inválido. Debe ser: Individual, Doble, Triple, Familiar o Suite'
            ]);
            exit();
        }

        // Validar que el precio sea positivo
        if ($precio <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'El precio debe ser mayor que 0']);
            exit();
        }

        // Verificar que el número de habitación no esté duplicado (si se está cambiando)
        if ($numero !== $habitacionExistente['numero']) {
            $checkNumero = $conexion->prepare('SELECT id FROM habitaciones WHERE numero = :numero');
            $checkNumero->bindValue(':numero', $numero);
            $checkNumero->execute();

            if ($checkNumero->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'El número de habitación ya existe']);
                exit();
            }
        }

        // Actualizar habitación
        $sql = $conexion->prepare(
            'UPDATE habitaciones 
            SET numero = :numero,
                planta = :planta,
                tipo = :tipo,
                precio = :precio,
                suite = :suite,
                num_personas = :num_personas,
                disponible = :disponible
            WHERE id = :id'
        );

        $sql->bindValue(':numero', $numero);
        $sql->bindValue(':planta', $planta);
        $sql->bindValue(':tipo', $tipo);
        $sql->bindValue(':precio', $precio);
        $sql->bindValue(':suite', $suite);
        $sql->bindValue(':num_personas', $num_personas);
        $sql->bindValue(':disponible', $disponible);
        $sql->bindValue(':id', $_GET['id'], PDO::PARAM_INT);

        $sql->execute();

        http_response_code(200);
        echo json_encode([
            'mensaje' => 'Habitación actualizada correctamente',
            'id' => $_GET['id'],
            'disponible' => $disponible == 1 ? 'Sí' : 'No'
        ]);
        exit();

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error interno del servidor']);
        exit();
    }
}