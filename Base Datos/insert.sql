USE `sistema_arreglo_computadores`;

-- Insertar datos en Tabla Rol
INSERT INTO `Rol` (`idRol`, `Nombre_Rol`) VALUES
(1, 'Administrador'),
(2, 'Técnico');

-- Insertar datos en Tabla Marca
INSERT INTO `Marca` (`idMarca`, `Nombre_Marca`) VALUES
(1, 'Dell'), (2, 'HP'), (3, 'Lenovo'), (4, 'Acer'), (5, 'Apple'), 
(6, 'Asus'), (7, 'Samsung'), (8, 'Toshiba'), (9, 'Sony'), (10, 'MSI');

-- Insertar datos en Tabla Estado 

INSERT INTO `Estado` (`idEstado`, `Nombre_Estado`) VALUES
(1, 'En diagnóstico'),
(2, 'En reparación'),
(3, 'Finalizado'),
(4, 'Entregado');

-- Insertar datos en Tabla Técnico
INSERT INTO `Tecnico` (`idTecnico`, `Nombre_Tecnico`, `Correo`, `Contraseña`, `Rol_idRol`) VALUES
(1, 'Juan Carlos Pérez López', 'juan.perez@mail.com', '123456', 1),
(2, 'Ana María Gómez Hernández', 'ana.gomez@mail.com', 'abcdef', 2),
(3, 'Luis Andrés Morales Rojas', 'luis.morales@mail.com', 'pass123', 2),
(4, 'Carla Lucía Ramírez Torres', 'carla.ramirez@mail.com', 'password', 1),
(5, 'Pedro Enrique Sánchez Flores', 'pedro.sanchez@mail.com', 'qwerty', 2),
(6, 'Sofía Isabel López Guzmán', 'sofia.lopez@mail.com', 'asdfgh', 1),
(7, 'María Fernanda Vargas Cruz', 'maria.vargas@mail.com', 'zxcvbn', 2),
(8, 'José Luis Hernández Martínez', 'jose.hernandez@mail.com', 'hunter2', 2),
(9, 'Laura Andrea Mendoza Salinas', 'laura.mendoza@mail.com', 'opensesame', 1),
(10, 'Daniel Francisco Reyes Ortiz', 'daniel.reyes@mail.com', '123abc', 2);

-- Insertar datos en Tabla Cliente
INSERT INTO `Cliente` (`idCliente`, `Primer_Nombre`, `Primer_Apellido`, `Telefono`, `Direccion`) VALUES
(1, 'Luis', 'Martínez', '123456789', 'Calle 123'),
(2, 'Carla', 'Ramírez', '987654321', 'Avenida 456'),
(3, 'Pedro', 'Hernández', '112233445', 'Calle Luna 10'),
(4, 'Ana', 'Salas', '998877665', 'Calle Sol 20'),
(5, 'María', 'García', '223344556', 'Calle Estrella 30'),
(6, 'Juan', 'Ortiz', '445566778', 'Calle Nube 40'),
(7, 'Laura', 'Zamora', '556677889', 'Avenida Mar 50'),
(8, 'Javier', 'Fuentes', '667788990', 'Avenida Río 60'),
(9, 'Rosa', 'Vázquez', '778899001', 'Calle Bosque 70'),
(10, 'Gabriel', 'Pérez', '889900112', 'Calle Lago 80');

-- Insertar datos en Tabla Arreglo
INSERT INTO `Arreglo` 
(`idArreglo`, `Tipo_Arreglo`, `Nombre_Arreglo`, `Descripcion_Cliente`, `Valor_Pago`, `Fecha_Recibido`, `Fecha_Entrega`, `Marca_idMarca`, `Tecnico_idTecnico`, `Estado_idEstado`) VALUES
(1, 'Laptop', 'Cambio Pantalla', 'Pantalla rota', 120.00, '2024-11-01', '2024-11-05', 1, 1, 4),
(2, 'PC', 'Cambio Disco Duro', 'Disco dañado', 150.00, '2024-11-02', '2024-11-06', 2, 2, 4),
(3, 'Tablet', 'Reparación Cámara', 'Cámara no funciona', 80.00, '2024-11-03', '2024-11-07', 3, 3, 4),
(4, 'Laptop', 'Limpieza Interna', 'Mucho polvo', 50.00, '2024-11-04', '2024-11-08', 4, 4, 4),
(5, 'PC', 'Actualización RAM', 'Agregar más RAM', 70.00, '2024-11-05', '2024-11-09', 5, 5, 4),
(6, 'Laptop', 'Cambio Teclado', 'Teclado no responde', 100.00, '2024-11-06', '2024-11-10', 6, 6, 4),
(7, 'PC', 'Cambio Fuente Poder', 'Fuente dañada', 130.00, '2024-11-07', '2024-11-11', 7, 7, 4),
(8, 'Laptop', 'Instalación SO', 'Instalar sistema operativo', 40.00, '2024-11-08', '2024-11-12', 8, 8, 4),
(9, 'PC', 'Cambio Ventilador', 'Ventilador ruidoso', 60.00, '2024-11-09', '2024-11-13', 9, 9, 4),
(10, 'Tablet', 'Cambio Batería', 'Batería agotada', 90.00, '2024-11-10', '2024-11-14', 10, 10, 4);

-- Insertar datos en Tabla Diagnóstico
INSERT INTO `Diagnostico` (`idDiagnostico`, `Componente`, `Valor`, `Descripcion`) VALUES
(1, 'Pantalla', 100.00, 'Pantalla dañada'),
(2, 'Disco Duro', 80.00, 'Sectores dañados'),
(3, 'Teclado', 60.00, 'Teclas no responden'),
(4, 'Cámara', 40.00, 'Cámara sin funcionamiento'),
(5, 'RAM', 50.00, 'Error de lectura'),
(6, 'Placa Base', 200.00, 'Cortocircuito'),
(7, 'Fuente Poder', 90.00, 'Sin energía'),
(8, 'Batería', 70.00, 'No carga'),
(9, 'Sistema Operativo', 30.00, 'Error en instalación'),
(10, 'Ventilador', 20.00, 'Ruido excesivo');

-- Insertar datos en Tabla Detalle Diagnóstico
INSERT INTO `Detalle_Diagnostico` (`Arreglo_idArreglo`, `Diagnostico_idDiagnostico`) VALUES
(1, 1), (2, 2), (3, 4), (4, 5), (5, 3), 
(6, 1), (7, 2), (8, 6), (9, 3), (10, 7);

-- Insertar datos en Tabla Detalle Arreglo
INSERT INTO `Detalle_Arreglo` (`Cliente_idCliente`, `Arreglo_idArreglo`) VALUES
(1, 1), (2, 2), (3, 3), (4, 4), (5, 5),
(6, 6), (7, 7), (8, 8), (9, 9), (10, 10);