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
            //Ej --> http://localhost/API/src/controllers/Cliente.php?id=1 / http://localhost/API/clientes/1
            $sql = $conexion->prepare('SELECT * FROM clientes WHERE id = :id');
            $sql->bindValue(':id', $_GET['id']);
            $sql->execute();

            $resultado = $sql->fetch(PDO::FETCH_ASSOC);

            //Si no se encuentra el cliente con ese id
            if (!$resultado) {
                http_response_code(404);
                echo json_encode(['error' => 'Cliente no existente']);
                exit();
            }

            http_response_code(200);
            echo json_encode($resultado);
            exit();

            //En caso de querer listar a todos los clientes
             //Ej --> http://localhost/API/src/controllers/Cliente.php?all=true
        } else {

            $sql = $conexion->prepare('SELECT * FROM clientes');
            $sql->execute();
            http_response_code(200);
            echo json_encode($sql->fetchAll(PDO::FETCH_ASSOC));
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

http://localhost/API/clientes/1 //Petición con id y credenciales del admin para poder eliminar

{
  "usuario": {
    "usuario": "admin",
    "contrasena": "2DawAp1"
  }
}
*/
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    try {

        //Array de credenciales de sesión para verificar que el usuario tiene permisos
        $datos = json_decode(file_get_contents("php://input"), true);

        //Validar credenciales (rangos con privilegios -> 1,4,6)
        autenticarUsuario($conexion, $datos, [1, 4, 6]);

        //En caso de especificarse un id
        //Ej --> http://localhost/API/clientes/1
        if (isset($_GET['id'])) {

            //Verificar si el cliente existe antes de eliminarlo
            $verificar = $conexion->prepare('SELECT id FROM clientes WHERE id = :id');
            $verificar->bindValue(':id', $_GET['id']);
            $verificar->execute();

            if (!$verificar->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Cliente no existente']);
                exit();
            }

            $sql = $conexion->prepare('DELETE FROM clientes WHERE id = :id');
            $sql->bindValue(':id', $_GET['id']);
            $sql->execute();

            http_response_code(200);
            echo json_encode(['mensaje' => 'El cliente con el id ' . $_GET['id'] . ' ha sido eliminado']);
            exit();

            //En caso de querer borrar a TODOS los clientes
            //Ej --> http://localhost/API/src/controllers/Cliente.php?all=true
        } elseif (isset($_GET['all']) && $_GET['all'] === 'true') {

            $sql = $conexion->prepare('DELETE FROM clientes');
            $sql->execute();

            http_response_code(200);
            echo json_encode(['mensaje' => 'TODOS los clientes han sido eliminados']);
            exit();
        } else {
            //Si no se especifica si son todos o uno en concreto, se informará para evitar borrados por error
            //Ej --> http://localhost/API/src/controllers/Cliente.php
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

//Peticion POST

/*
{
  "usuario": {
    "usuario": "admin",
    "contrasena": "2DawAp1"
  },
  "cliente": {
    "nombre": "Laura",
    "apellidos": "Martín Pérez",
    "dni": "77889900A",
    "correo": "laura@email.com",
    "telefono": "622334455"
  }
}
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $datos = json_decode(file_get_contents("php://input"), true);

        // Validar credenciales
        autenticarUsuario($conexion, $datos, [1, 4, 6]);

        // Validar datos del cliente
        if (
            empty($datos['cliente']['nombre']) ||
            empty($datos['cliente']['apellidos']) ||
            empty($datos['cliente']['dni']) ||
            empty($datos['cliente']['correo'])
        ) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan datos del cliente']);
            exit();
        }

        $cliente = $datos['cliente'];

        // DNI duplicado
        $checkDni = $conexion->prepare(
            'SELECT id FROM clientes WHERE dni = :dni'
        );
        $checkDni->bindValue(':dni', $cliente['dni']);
        $checkDni->execute();

        if ($checkDni->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'El DNI ya existe']);
            exit();
        }

        // Correo duplicado
        $checkCorreo = $conexion->prepare(
            'SELECT id FROM clientes WHERE correo = :correo'
        );
        $checkCorreo->bindValue(':correo', $cliente['correo']);
        $checkCorreo->execute();

        if ($checkCorreo->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'El correo ya existe']);
            exit();
        }

        // Insertar cliente
        $sql = $conexion->prepare(
            'INSERT INTO clientes (nombre, apellidos, dni, correo, telefono)
             VALUES (:nombre, :apellidos, :dni, :correo, :telefono)'
        );

        $sql->bindValue(':nombre', $cliente['nombre']);
        $sql->bindValue(':apellidos', $cliente['apellidos']);
        $sql->bindValue(':dni', $cliente['dni']);
        $sql->bindValue(':correo', $cliente['correo']);
        $sql->bindValue(':telefono', $cliente['telefono'] ?? null);

        $sql->execute();

        http_response_code(201);
        echo json_encode([
            'mensaje' => 'Cliente creado correctamente',
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
 * PUT /clientes?id=1
 *
 * {
 *   "usuario": {
 *     "usuario": "admin",
 *     "contrasena": "2DawAp1"
 *   },
 *   "cliente": {
 *     "nombre": "Laura",
 *     "apellidos": "Martín García",
 *     "dni": "77889900A",
 *     "correo": "laura.nueva@email.com",
 *     "telefono": "622334455"
 *   }
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

        //Verificar si el cliente existe
        $verificar = $conexion->prepare('SELECT * FROM clientes WHERE id = :id');
        $verificar->bindValue(':id', $_GET['id']);
        $verificar->execute();
        $clienteExistente = $verificar->fetch(PDO::FETCH_ASSOC);

        if (!$clienteExistente) {
            http_response_code(404);
            echo json_encode(['error' => 'Cliente no existente']);
            exit();
        }

        // Obtener datos actualizados o mantener los existentes
        $cliente = $datos['cliente'] ?? [];
        $nombre = $cliente['nombre'] ?? $clienteExistente['nombre'];
        $apellidos = $cliente['apellidos'] ?? $clienteExistente['apellidos'];
        $dni = $cliente['dni'] ?? $clienteExistente['dni'];
        $correo = $cliente['correo'] ?? $clienteExistente['correo'];
        $telefono = $cliente['telefono'] ?? $clienteExistente['telefono'];

        // Validar que los campos obligatorios no estén vacíos
        if (empty($nombre) || empty($apellidos) || empty($dni) || empty($correo)) {
            http_response_code(400);
            echo json_encode(['error' => 'Los campos nombre, apellidos, dni y correo son obligatorios']);
            exit();
        }

        // Validar formato de DNI (8 números + 1 letra)
        if (!preg_match('/^[0-9]{8}[A-Za-z]$/', $dni)) {
            http_response_code(400);
            echo json_encode(['error' => 'Formato de DNI inválido. Debe ser 8 números seguidos de una letra']);
            exit();
        }

        // Validar formato de correo electrónico
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Formato de correo electrónico inválido']);
            exit();
        }

        // Validar formato de teléfono (opcional, 9 dígitos)
        if (!empty($telefono) && !preg_match('/^[0-9]{9}$/', $telefono)) {
            http_response_code(400);
            echo json_encode(['error' => 'Formato de teléfono inválido. Debe tener 9 dígitos']);
            exit();
        }

        // Verificar DNI duplicado (si se está cambiando)
        if ($dni !== $clienteExistente['dni']) {
            $checkDni = $conexion->prepare('SELECT id FROM clientes WHERE dni = :dni');
            $checkDni->bindValue(':dni', $dni);
            $checkDni->execute();

            if ($checkDni->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'El DNI ya existe']);
                exit();
            }
        }

        // Verificar correo duplicado (si se está cambiando)
        if ($correo !== $clienteExistente['correo']) {
            $checkCorreo = $conexion->prepare('SELECT id FROM clientes WHERE correo = :correo');
            $checkCorreo->bindValue(':correo', $correo);
            $checkCorreo->execute();

            if ($checkCorreo->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'El correo ya existe']);
                exit();
            }
        }

        // Actualizar cliente
        $sql = $conexion->prepare(
            'UPDATE clientes 
            SET nombre = :nombre,
                apellidos = :apellidos,
                dni = :dni,
                correo = :correo,
                telefono = :telefono
            WHERE id = :id'
        );

        $sql->bindValue(':nombre', $nombre);
        $sql->bindValue(':apellidos', $apellidos);
        $sql->bindValue(':dni', $dni);
        $sql->bindValue(':correo', $correo);
        $sql->bindValue(':telefono', $telefono);
        $sql->bindValue(':id', $_GET['id'], PDO::PARAM_INT);

        $sql->execute();

        http_response_code(200);
        echo json_encode([
            'mensaje' => 'Cliente actualizado correctamente',
            'id' => $_GET['id'],
            'nombre_completo' => $nombre . ' ' . $apellidos
        ]);
        exit();

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error interno del servidor']);
        exit();
    }
}