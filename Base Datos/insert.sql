USE `sistema_arreglo_computadores`;

-- 1. INSERTAR ROLES
INSERT INTO `Rol` (`idRol`, `Nombre_Rol`) VALUES
(1, 'Administrador'),
(2, 'Tecnico');

-- 2. INSERTAR MARCAS
INSERT INTO `Marca` (`idMarca`, `Nombre_Marca`) VALUES
(1, 'Dell'), (2, 'HP'), (3, 'Lenovo'), (4, 'Acer'), (5, 'Apple'), 
(6, 'Asus'), (7, 'Samsung'), (8, 'Toshiba'), (9, 'Sony'), (10, 'MSI');

-- 3. INSERTAR ESTADOS (Necesarios para la tabla Arreglo)
INSERT INTO `Estado` (`idEstado`, `Nombre_Estado`, `Descripcion_Estado`) VALUES
(1, 'Recibido', 'Equipo ingresado al sistema'),
(2, 'En Proceso', 'Técnico trabajando en el equipo'),
(3, 'Entregado', 'Equipo devuelto al cliente');

-- 4. INSERTAR TÉCNICOS (Antes llamados Usuarios)
INSERT INTO `Tecnico` (`idTecnico`, `Primer_Nombre`, `Segundo_Nombre`, `Primer_Apellido`, `Segundo_Apellido`, `Correo`, `Contraseña`, `Edad`, `Rol_idRol`) VALUES
(1, 'Juan', 'Carlos', 'Pérez', 'López', 'juan.perez@mail.com', '123456', 30, 1),
(2, 'Ana', 'María', 'Gómez', 'Hernández', 'ana.gomez@mail.com', 'abcdef', 25, 2),
(3, 'Luis', 'Andrés', 'Morales', 'Rojas', 'luis.morales@mail.com', 'pass123', 29, 2),
(4, 'Carla', 'Lucía', 'Ramírez', 'Torres', 'carla.ramirez@mail.com', 'password', 35, 1),
(5, 'Pedro', 'Enrique', 'Sánchez', 'Flores', 'pedro.sanchez@mail.com', 'qwerty', 40, 2),
(6, 'Sofía', 'Isabel', 'López', 'Guzmán', 'sofia.lopez@mail.com', 'asdfgh', 28, 1),
(7, 'María', 'Fernanda', 'Vargas', 'Cruz', 'maria.vargas@mail.com', 'zxcvbn', 31, 2),
(8, 'José', 'Luis', 'Hernández', 'Martínez', 'jose.hernandez@mail.com', 'hunter2', 34, 2),
(9, 'Laura', 'Andrea', 'Mendoza', 'Salinas', 'laura.mendoza@mail.com', 'opensesame', 22, 1),
(10, 'Daniel', 'Francisco', 'Reyes', 'Ortiz', 'daniel.reyes@mail.com', '123abc', 26, 2);

-- 5. INSERTAR CLIENTES
INSERT INTO `Cliente` (`idCliente`, `Primer_Nombre`, `Segundo_Nombre`, `Primer_Apellido`, `Segundo_Apellido`, `Telefono`, `Direccion`) VALUES
(1, 'Luis', 'Fernando', 'Martínez', 'Cruz', '123456789', 'Calle 123'),
(2, 'Carla', 'Sofía', 'Ramírez', 'Mendoza', '987654321', 'Avenida 456'),
(3, 'Pedro', 'José', 'Hernández', 'Gómez', '112233445', 'Calle Luna 10'),
(4, 'Ana', 'Beatriz', 'Salas', 'Torres', '998877665', 'Calle Sol 20'),
(5, 'María', 'Teresa', 'García', 'Pérez', '223344556', 'Calle Estrella 30'),
(6, 'Juan', 'Carlos', 'Ortiz', 'Lozano', '445566778', 'Calle Nube 40'),
(7, 'Laura', 'Andrea', 'Zamora', 'Molina', '556677889', 'Avenida Mar 50'),
(8, 'Javier', 'Antonio', 'Fuentes', 'López', '667788990', 'Avenida Río 60'),
(9, 'Rosa', 'Elena', 'Vázquez', 'Martínez', '778899001', 'Calle Bosque 70'),
(10, 'Gabriel', 'Alonso', 'Pérez', 'Sánchez', '889900112', 'Calle Lago 80');

-- 6. INSERTAR ARREGLOS
INSERT INTO `Arreglo` 
(`idArreglo`, `Tipo_Arreglo`, `Nombre_Arreglo`, `Descripcion_Cliente`, `Valor_Pago`, `Fecha_Recibido`, `Fecha_Entrega`, `Marca_idMarca`, `Tecnico_idTecnico`, `Cliente_idCliente`, `Estado_idEstado`) VALUES
(1, 'Laptop', 'Cambio Pantalla', 'Pantalla rota', 120.00, '2024-11-01', '2024-11-05', 1, 1, 1, 3),
(2, 'PC', 'Cambio Disco Duro', 'Disco dañado', 150.00, '2024-11-02', '2024-11-06', 2, 2, 2, 3),
(3, 'Tablet', 'Reparación Cámara', 'Cámara no funciona', 80.00, '2024-11-03', '2024-11-07', 3, 3, 3, 3),
(4, 'Laptop', 'Limpieza Interna', 'Mucho polvo', 50.00, '2024-11-04', '2024-11-08', 4, 4, 4, 3),
(5, 'PC', 'Actualización RAM', 'Agregar más RAM', 70.00, '2024-11-05', '2024-11-09', 5, 5, 5, 3),
(6, 'Laptop', 'Cambio Teclado', 'Teclado no responde', 100.00, '2024-11-06', '2024-11-10', 6, 6, 6, 3),
(7, 'PC', 'Cambio Fuente Poder', 'Fuente dañada', 130.00, '2024-11-07', '2024-11-11', 7, 7, 7, 3),
(8, 'Laptop', 'Instalación SO', 'Instalar sistema operativo', 40.00, '2024-11-08', '2024-11-12', 8, 8, 8, 3),
(9, 'PC', 'Cambio Ventilador', 'Ventilador ruidoso', 60.00, '2024-11-09', '2024-11-13', 9, 9, 9, 3),
(10, 'Tablet', 'Cambio Batería', 'Batería agotada', 90.00, '2024-11-10', '2024-11-14', 10, 10, 10, 3);

-- 7. INSERTAR DIAGNÓSTICOS (Asignados a un arreglo específico)
INSERT INTO `Diagnostico` (`idDiagnostico`, `Componente`, `Valor`, `Descripcion`, `Arreglo_idArreglo`) VALUES
(1, 'Pantalla', 100.00, 'Pantalla dañada', 1),
(2, 'Disco Duro', 80.00, 'Sectores dañados', 2),
(3, 'Teclado', 60.00, 'Teclas no responden', 6),
(4, 'Cámara', 40.00, 'Cámara sin funcionamiento', 3),
(5, 'RAM', 50.00, 'Error de lectura', 5),
(6, 'Placa Base', 200.00, 'Cortocircuito', 2),
(7, 'Fuente Poder', 90.00, 'Sin energía', 7),
(8, 'Batería', 70.00, 'No carga', 10),
(9, 'Sistema Operativo', 30.00, 'Error en instalación', 8),
(10, 'Ventilador', 20.00, 'Ruido excesivo', 9);

-- 8. DETALLE DIAGNÓSTICO (Relación N:M)
INSERT INTO `Detalle_Diagnostico` (`Arreglo_idArreglo`, `Diagnostico_idDiagnostico`, `Cantidad`) VALUES
(1, 1, 1), (2, 2, 1), (3, 4, 1), (4, 5, 2), (5, 3, 1), 
(6, 1, 1), (7, 2, 1), (8, 6, 2), (9, 3, 1), (10, 7, 2);

-- 9. DETALLE ARREGLO (Relación N:M)
INSERT INTO `Detalle_Arreglo` (`Cliente_idCliente`, `Arreglo_idArreglo`, `Cantidad`) VALUES
(1, 1, 1), (2, 2, 1), (3, 3, 2), (4, 4, 1), (5, 5, 1),
(6, 6, 1), (7, 7, 1), (8, 8, 1), (9, 9, 1), (10, 10, 1);