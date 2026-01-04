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

//Comprobar el usuario
function autenticarUsuario($conexion, $datos, $rangosPermitidos) { //Se le pasa la conexion a la base de datos, el json que recibimos y un array con el rango que esta permitido (Ver script sql, para ver tipos de rangos)
    if (
        //comprobamos si nos dan usuario y contraseña
        empty($datos['usuario']['usuario']) ||
        empty($datos['usuario']['contrasena'])
    ) {
        http_response_code(401);
        echo json_encode(['error' => 'Credenciales obligatorias']);
        exit();
    }

    $sql = $conexion->prepare(
        'SELECT id, contrasena_usuario, rango 
         FROM usuarios 
         WHERE nombre_usuario = :usuario'
    );

    $sql->bindValue(':usuario', $datos['usuario']['usuario']);
    $sql->execute();

    $usuario = $sql->fetch(PDO::FETCH_ASSOC);

    if (
        !$usuario ||
        !password_verify(
            $datos['usuario']['contrasena'],
            $usuario['contrasena_usuario']
        )
    ) {
        http_response_code(401);
        echo json_encode(['error' => 'Usuario o contraseña incorrectos']);
        exit();
    }

    if (!in_array($usuario['rango'], $rangosPermitidos)) {
        http_response_code(403);
        echo json_encode(['error' => 'Permisos insuficientes']);
        exit();
    }

    return $usuario;
}

?>