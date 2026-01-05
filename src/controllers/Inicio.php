<?php

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'api' => 'API Hotel',
    'version' => '1.0',
    'descripcion' => 'API REST para la gestión de reservas de un hotel',
    'nota' => 'Las peticiones POST, PUT y DELETE requieren autenticación',

    'endpoints' => [

        'clientes' => [
            'POST' => [
                'descripcion' => 'Crear un nuevo cliente',
                'autenticacion' => true,
                'body' => [
                    'usuario' => [
                        'usuario' => 'usuario',
                        'contrasena' => 'contraseña'
                    ],
                    'cliente' => [
                        'nombre' => 'Laura',
                        'apellidos' => 'Martín Pérez',
                        'dni' => '77889900A',
                        'correo' => 'laura@email.com',
                        'telefono' => '622334455'
                    ]
                ]
            ]
        ],

        'habitaciones' => [
            'POST' => [
                'descripcion' => 'Crear una o varias habitaciones',
                'autenticacion' => true,
                'opciones' => [
                    '1' => 'Crear una habitación',
                    '2' => 'Crear varias habitaciones'
                ],
                'body_opcion_1' => [
                    'usuario' => [
                        'usuario' => 'usuario',
                        'contrasena' => 'contraseña'
                    ],
                    'habitacion' => [
                        'numero' => 400,
                        'planta' => 4,
                        'tipo' => 'Familiar',
                        'precio' => 400,
                        'suite' => 1,
                        'num_personas' => 4
                    ],
                    'opcion' => [
                        'n' => 1
                    ]
                ],
                'body_opcion_2' => [
                    'usuario' => [
                        'usuario' => 'usuario',
                        'contrasena' => 'contraseña'
                    ],
                    'habitacion' => [
                        'numero' => 400,
                        'planta' => 4,
                        'tipo' => 'Familiar',
                        'precio' => 400,
                        'suite' => 1,
                        'num_personas' => 4,
                        'n_habitaciones' => 20
                    ],
                    'opcion' => [
                        'n' => 2
                    ]
                ]
            ]
        ],

        'reservas' => [
            'GET' => [
                'descripcion' => 'Obtener una reserva por ID',
                'autenticacion' => false,
                'ejemplo' => '/reservas?id=1'
            ],
            'POST' => [
                'descripcion' => 'Crear una reserva (precio calculado automáticamente)',
                'autenticacion' => true,
                'body' => [
                    'usuario' => [
                        'usuario' => 'usuario',
                        'contrasena' => 'contraseña'
                    ],
                    'reserva' => [
                        'cliente_id' => 1,
                        'habitacion_id' => 2,
                        'fecha_entrada' => '2026-01-10',
                        'fecha_salida' => '2026-01-15'
                    ]
                ]
            ],
            'DELETE' => [
                'descripcion' => 'Eliminar una reserva',
                'autenticacion' => true,
                'ejemplo' => '/reservas?id=2'
            ]
        ]
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
