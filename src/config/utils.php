<?php

//Conexión a la base de datos
function connect($db){
        try{

            $conexion = new PDO("mysql:host={$db['host']};dbname={$db['db']}", $db['username'], $db['password']); 
            $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conexion;
        
           }catch (PDOException $e){
                exit($e -> getMessage());
           }
    }

?>