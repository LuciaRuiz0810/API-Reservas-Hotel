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
        //Ej --> http://localhost/API/src/controllers/Usuarios.php?id=1 / http://localhost/API/Usuarios/1
        $sql = $conexion->prepare('SELECT * FROM usuarios where id=:id');
        $sql -> bindValue(':id' , $_GET['id']);
        $sql->execute();
        header("HTTP/1.1 200 OK");
        echo json_encode($sql -> fetch(PDO::FETCH_ASSOC));
        exit();

    //En caso de querer listar a todos las usuarios
    }else{

        $sql = $conexion->prepare('SELECT * FROM usuarios');
        $sql->execute();
        header("HTTP/1.1 200 OK");
        echo json_encode($sql->fetchAll(PDO::FETCH_ASSOC));
        exit();
    }

    //En caso de que la petición GET falle
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        echo json_encode(['error' => 'Error al obtener el/los usuario/s']);
        exit();
    }

}

//PETICIÓN DELETE
if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {

    try {
         //En caso de especificarse un id
        if (isset($_GET['id'])) {
            
            $sql = $conexion->prepare('DELETE FROM usuarios WHERE id = :id');
            $sql->bindValue(':id', $_GET['id']);
            $sql->execute();

            header("HTTP/1.1 200 OK");
             echo json_encode(['El usuario con el correo ' . $_GET['id'] . ' ha sido eliminado']);
            exit();

        //En caso de querer borrar a TODOS los Usuarios
        //Ej --> http://localhost/API/src/controllers/Usuarios.php?all=true
        } elseif (isset($_GET['all']) && $_GET['all'] == 'true') {

            $sql = $conexion->prepare('DELETE FROM usuarios');
            $sql->execute();

            header("HTTP/1.1 200 OK");
            echo json_encode(['TODOS los usuarios han sido eliminados']);
            exit();

        } else {
            //Si no se especifica si son todos o uno en concreto, se informará para evitar borrados por error
            //Ej --> http://localhost/API/src/controllers/Usuarios.php
            header("HTTP/1.1 400 Bad Request");
            echo json_encode(['error' => 'Se requiere id o all=true para borrar']);
            exit();
        }
        //En caso de que falle la petición, se informará
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        echo json_encode(['error' => 'Error al eliminar el Usuario']);
        exit();
    }

}





?>