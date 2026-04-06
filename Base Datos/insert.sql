INSERT INTO `sistema_arreglo_computadores`.`Rol` (`idRol`, `Nombre_Rol`) VALUES
(1, 'Administrador'),
(2, 'Empleado');

INSERT INTO `sistema_arreglo_computadores`.`Marca` (`idMarca`, `Nombre_Marca`) VALUES
(1, 'Dell'),
(2, 'HP'),
(3, 'Lenovo'),
(4, 'Acer'),
(5, 'Apple'),
(6, 'Asus'),
(7, 'Samsung'),
(8, 'Toshiba'),
(9, 'Sony'),
(10, 'MSI');

INSERT INTO `sistema_arreglo_computadores`.`Usuario` (`Primer_Nombre`, `Segundo_Nombre`, `Primer_Apellido`, `Segundo_Apellido`, `Correo`, `Contraseña`, `Edad`, `Rol_idRol`) VALUES
('Juan', 'Carlos', 'Pérez', 'López', 'juan.perez@mail.com', '123456', 30, 1),
('Ana', 'María', 'Gómez', 'Hernández', 'ana.gomez@mail.com', 'abcdef', 25, 2),
('Luis', 'Andrés', 'Morales', 'Rojas', 'luis.morales@mail.com', 'pass123', 29, 2),
('Carla', 'Lucía', 'Ramírez', 'Torres', 'carla.ramirez@mail.com', 'password', 35, 1),
('Pedro', 'Enrique', 'Sánchez', 'Flores', 'pedro.sanchez@mail.com', 'qwerty', 40, 2),
('Sofía', 'Isabel', 'López', 'Guzmán', 'sofia.lopez@mail.com', 'asdfgh', 28, 1),
('María', 'Fernanda', 'Vargas', 'Cruz', 'maria.vargas@mail.com', 'zxcvbn', 31, 2),
('José', 'Luis', 'Hernández', 'Martínez', 'jose.hernandez@mail.com', 'hunter2', 34, 2),
('Laura', 'Andrea', 'Mendoza', 'Salinas', 'laura.mendoza@mail.com', 'opensesame', 22, 1),
('Daniel', 'Francisco', 'Reyes', 'Ortiz', 'daniel.reyes@mail.com', '123abc', 26, 2);

INSERT INTO `sistema_arreglo_computadores`.`Cliente` (`Primer_Nombre`, `Segundo_Nombre`, `Primer_Apellido`, `Segundo_Apellido`, `Telefono`, `Direccion`) VALUES
('Luis', 'Fernando', 'Martínez', 'Cruz', '123456789', 'Calle 123'),
('Carla', 'Sofía', 'Ramírez', 'Mendoza', '987654321', 'Avenida 456'),
('Pedro', 'José', 'Hernández', 'Gómez', '112233445', 'Calle Luna 10'),
('Ana', 'Beatriz', 'Salas', 'Torres', '998877665', 'Calle Sol 20'),
('María', 'Teresa', 'García', 'Pérez', '223344556', 'Calle Estrella 30'),
('Juan', 'Carlos', 'Ortiz', 'Lozano', '445566778', 'Calle Nube 40'),
('Laura', 'Andrea', 'Zamora', 'Molina', '556677889', 'Avenida Mar 50'),
('Javier', 'Antonio', 'Fuentes', 'López', '667788990', 'Avenida Río 60'),
('Rosa', 'Elena', 'Vázquez', 'Martínez', '778899001', 'Calle Bosque 70'),
('Gabriel', 'Alonso', 'Pérez', 'Sánchez', '889900112', 'Calle Lago 80');

INSERT INTO `sistema_arreglo_computadores`.`Diagnostico` (`Componente`, `Valor`, `Descripcion`) VALUES
('Pantalla', 100.00, 'Pantalla dañada'),
('Disco Duro', 80.00, 'Sectores dañados'),
('Teclado', 60.00, 'Teclas no responden'),
('Cámara', 40.00, 'Cámara sin funcionamiento'),
('RAM', 50.00, 'Error de lectura'),
('Placa Base', 200.00, 'Cortocircuito'),
('Fuente Poder', 90.00, 'Sin energía'),
('Batería', 70.00, 'No carga'),
('Sistema Operativo', 30.00, 'Error en instalación'),
('Ventilador', 20.00, 'Ruido excesivo');

INSERT INTO `sistema_arreglo_computadores`.`Arreglo` 
(`idArreglo`, `Tipo_Arreglo`, `Nombre_Arreglo`, `Descripcion_Cliente`, `Valor_Pago`, `Fecha_Recibido`, `Fecha_Entrega`, `Marca_idMarca`, `Usuario_idUsuario`) 
VALUES
(1, 'Laptop', 'Cambio Pantalla', 'Pantalla rota', 120.00, '2024-11-01', '2024-11-05', 1, 1),
(2, 'PC', 'Cambio Disco Duro', 'Disco dañado', 150.00, '2024-11-02', '2024-11-06', 2, 2),
(3, 'Tablet', 'Reparación Cámara', 'Cámara no funciona', 80.00, '2024-11-03', '2024-11-07', 3, 3),
(4, 'Laptop', 'Limpieza Interna', 'Mucho polvo', 50.00, '2024-11-04', '2024-11-08', 4, 4),
(5, 'PC', 'Actualización RAM', 'Agregar más RAM', 70.00, '2024-11-05', '2024-11-09', 5, 5),
(6, 'Laptop', 'Cambio Teclado', 'Teclado no responde', 100.00, '2024-11-06', '2024-11-10', 6, 6),
(7, 'PC', 'Cambio Fuente Poder', 'Fuente dañada', 130.00, '2024-11-07', '2024-11-11', 7, 7),
(8, 'Laptop', 'Instalación SO', 'Instalar sistema operativo', 40.00, '2024-11-08', '2024-11-12', 8, 8),
(9, 'PC', 'Cambio Ventilador', 'Ventilador ruidoso', 60.00, '2024-11-09', '2024-11-13', 9, 9),
(10, 'Tablet', 'Cambio Batería', 'Batería agotada', 90.00, '2024-11-10', '2024-11-14', 10, 10);

INSERT INTO `sistema_arreglo_computadores`.`Detalle_Diagnostico` (`Arreglo_idArreglo`, `Arreglo_Marca_idMarca`, `Arreglo_Usuario_idUsuario`, `Diagnostico_idDiagnostico`, `Cantidad`) VALUES
(1, 1, 1, 1, 1),
(1, 1, 1, 5, 2),
(2, 2, 2, 2, 1),
(2, 2, 2, 3, 1),
(3, 3, 3, 4, 1),
(3, 3, 3, 6, 1),
(4, 4, 4, 5, 2),
(4, 4, 4, 7, 1),
(5, 5, 5, 3, 1),
(5, 5, 5, 8, 1),
(6, 6, 6, 1, 1),
(6, 6, 6, 9, 2),
(7, 7, 7, 2, 1),
(7, 7, 7, 10, 1),
(8, 8, 8, 6, 2),
(8, 8, 8, 5, 1),
(9, 9, 9, 3, 1),
(9, 9, 9, 8, 1),
(10, 10, 10, 7, 2),
(10, 10, 10, 4, 1),
(1, 1, 1, 9, 1),
(2, 2, 2, 6, 1),
(3, 3, 3, 1, 1),
(4, 4, 4, 2, 2),
(5, 5, 5, 4, 1),
(6, 6, 6, 10, 1),
(7, 7, 7, 8, 1),
(8, 8, 8, 3, 1),
(9, 9, 9, 9, 2),
(10, 10, 10, 6, 1);

INSERT INTO `sistema_arreglo_computadores`.`Detalle_Arreglo` 
(`Cliente_idCliente`, `Arreglo_idArreglo`, `Arreglo_Marca_idMarca`, `Arreglo_Usuario_idUsuario`, `Cantidad`) 
VALUES
(1, 1, 1, 1, 1),
(2, 2, 2, 2, 1),
(3, 3, 3, 3, 2),
(4, 4, 4, 4, 1),
(5, 5, 5, 5, 1),
(6, 6, 6, 6, 1),
(7, 7, 7, 7, 1),
(8, 8, 8, 8, 1),
(9, 9, 9, 9, 1),
(10, 10, 10, 10, 1);