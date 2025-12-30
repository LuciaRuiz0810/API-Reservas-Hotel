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





?>