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
        //Ej --> http://localhost/API/src/controllers/Habitacion.php?id=1 / http://localhost/API/habitaciones/1
        $sql = $conexion->prepare('SELECT * FROM habitaciones where id=:id');
        $sql -> bindValue(':id' , $_GET['id']);
        $sql->execute();
        header("HTTP/1.1 200 OK");
        echo json_encode($sql -> fetch(PDO::FETCH_ASSOC));
        exit();

    //En caso de querer listar a todos las habitaciones
    }else{

        $sql = $conexion->prepare('SELECT * FROM habitaciones');
        $sql->execute();
        header("HTTP/1.1 200 OK");
        echo json_encode($sql->fetchAll(PDO::FETCH_ASSOC));
        exit();
    }

    //En caso de que la petición GET falle
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        echo json_encode(['error' => 'Error al obtener la/las habitacion/es']);
        exit();
    }

   
}

//PETICIÓN DELETE
if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {

    try {
         //En caso de especificarse un id
        if (isset($_GET['id'])) {
            
            $sql = $conexion->prepare('DELETE FROM habitaciones WHERE id = :id');
            $sql->bindValue(':id', $_GET['id']);
            $sql->execute();

            header("HTTP/1.1 200 OK");
             echo json_encode(['La habitacion con el id ' . $_GET['id'] . ' ha sido eliminada']);
            exit();

        //En caso de querer borrar a TODAS las Habitaciones
        //Ej --> http://localhost/API/src/controllers/Habitaciones.php?all=true
        } elseif (isset($_GET['all']) && $_GET['all'] == 'true') {

            $sql = $conexion->prepare('DELETE FROM habitaciones');
            $sql->execute();

            header("HTTP/1.1 200 OK");
            echo json_encode(['TODAS las habitaciones han sido eliminadas']);
            exit();

        } else {
            //Si no se especifica si son todas o una en concreto, se informará para evitar borrados por error
            //Ej --> http://localhost/API/src/controllers/Habitaciones.php
            header("HTTP/1.1 400 Bad Request");
            echo json_encode(['error' => 'Se requiere id o all=true para borrar']);
            exit();
        }
        //En caso de que falle la petición, se informará
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        echo json_encode(['error' => 'Error al eliminar la habitacion']);
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

        autenticarUsuario($conexion, $datos, [1,4,6]);

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

            for ($i=$habitacion['numero']; $i < ($habitacion['n_habitaciones']+$habitacion['numero']) ; $i++) { 
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




?>