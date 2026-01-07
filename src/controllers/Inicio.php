<?php

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'api' => 'API Hotel',
    'version' => '1.0',
    'descripcion' => 'API REST para la gestión de reservas de un hotel',
    'nota' => 'Las peticiones POST, PUT y DELETE requieren autenticación',

    'endpoints' => [

        'clientes' => [
            'GET' => [
                'descripcion' => 'Obtener un cliente por ID o listar todos',
                'autenticacion' => false,
                'ejemplo' => '/clientes?id=1 o /clientes'
            ],
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
            ],
            'PUT' => [
                'descripcion' => 'Actualizar datos de un cliente',
                'autenticacion' => true,
                'ejemplo' => '/clientes?id=1',
                'body' => [
                    'usuario' => [
                        'usuario' => 'admin',
                        'contrasena' => '2DawAp1'
                    ],
                    'cliente' => [
                        'nombre' => 'Laura',
                        'apellidos' => 'Martín García',
                        'dni' => '77889900A',
                        'correo' => 'laura.nueva@email.com',
                        'telefono' => '622334455'
                    ]
                ]
            ],
            'DELETE' => [
                'descripcion' => 'Eliminar un cliente o todos',
                'autenticacion' => true,
                'ejemplo' => '/clientes?id=1 o /clientes?all=true'
            ]
        ],

        'habitaciones' => [
            'GET' => [
                'descripcion' => 'Obtener una habitación por ID o listar todas',
                'autenticacion' => false,
                'ejemplo' => '/habitaciones?id=1 o /habitaciones'
            ],
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
            ],
            'PUT' => [
                'descripcion' => 'Actualizar habitación completa o cambiar solo disponibilidad',
                'autenticacion' => true,
                'ejemplo' => '/habitaciones?id=1',
                'opciones' => [
                    '1' => 'Actualizar todos los datos',
                    '2' => 'Solo cambiar disponibilidad'
                ],
                'body_opcion_1' => [
                    'usuario' => [
                        'usuario' => 'admin',
                        'contrasena' => '2DawAp1'
                    ],
                    'habitacion' => [
                        'numero' => 101,
                        'planta' => 1,
                        'tipo' => 'Doble',
                        'precio' => 150,
                        'suite' => 0,
                        'num_personas' => 2,
                        'disponible' => 1
                    ]
                ],
                'body_opcion_2' => [
                    'usuario' => [
                        'usuario' => 'admin',
                        'contrasena' => '2DawAp1'
                    ],
                    'accion' => 'cambiar_disponibilidad',
                    'disponible' => 0
                ]
            ],
            'DELETE' => [
                'descripcion' => 'Eliminar una habitación o todas',
                'autenticacion' => true,
                'ejemplo' => '/habitaciones?id=1 o /habitaciones?all=true'
            ]
        ],

        'reservas' => [
            'GET' => [
                'descripcion' => 'Obtener una reserva por ID o listar todas',
                'autenticacion' => false,
                'ejemplo' => '/reservas?id=1 o /reservas'
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
            'PUT' => [
                'descripcion' => 'Actualizar reserva completa o cambiar estado',
                'autenticacion' => true,
                'ejemplo' => '/reservas?id=1',
                'opciones' => [
                    '1' => 'Actualizar datos completos',
                    '2' => 'Cancelar reserva',
                    '3' => 'Completar reserva'
                ],
                'body_opcion_1' => [
                    'usuario' => [
                        'usuario' => 'admin',
                        'contrasena' => '2DawAp1'
                    ],
                    'reserva' => [
                        'cliente_id' => 1,
                        'habitacion_id' => 3,
                        'fecha_entrada' => '2026-01-12',
                        'fecha_salida' => '2026-01-18',
                        'estado' => 'activa'
                    ]
                ],
                'body_opcion_2' => [
                    'usuario' => [
                        'usuario' => 'admin',
                        'contrasena' => '2DawAp1'
                    ],
                    'accion' => 'cancelar'
                ],
                'body_opcion_3' => [
                    'usuario' => [
                        'usuario' => 'admin',
                        'contrasena' => '2DawAp1'
                    ],
                    'accion' => 'completar'
                ]
            ],
            'DELETE' => [
                'descripcion' => 'Eliminar una reserva o todas',
                'autenticacion' => true,
                'ejemplo' => '/reservas?id=2 o /reservas?all=true'
            ]
        ]
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);