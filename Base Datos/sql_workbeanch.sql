DROP DATABASE IF EXISTS `sistema_arreglo_computadores`;
CREATE DATABASE `sistema_arreglo_computadores`;
USE `sistema_arreglo_computadores`;

-- Tabla Rol
CREATE TABLE Rol (
  idRol INT NOT NULL AUTO_INCREMENT,
  Nombre_Rol VARCHAR(45) NOT NULL, -- Administrador / Técnico [cite: 2]
  PRIMARY KEY (idRol)
);
-- Tabla Marca
CREATE TABLE Marca (
  idMarca INT NOT NULL AUTO_INCREMENT,
  Nombre_Marca VARCHAR(45) NOT NULL, 
  PRIMARY KEY (idMarca)
);

-- Tabla Estados
CREATE TABLE Estado (
  idEstado INT NOT NULL AUTO_INCREMENT,
  Nombre_Estado VARCHAR(45) NOT NULL, -- "En diagnóstico", "En reparación", "Finalizado", "Entregado" [cite: 7]
  PRIMARY KEY (idEstado)
);

-- Tabla Tecnico
CREATE TABLE Tecnico (
  idTecnico INT NOT NULL AUTO_INCREMENT,
  Nombre_Tecnico VARCHAR(100) NOT NULL, 
  Correo VARCHAR(100) NOT NULL UNIQUE, 
  Contraseña VARCHAR(255) NOT NULL, 
  Rol_idRol INT NOT NULL, 
  PRIMARY KEY (idTecnico),
  CONSTRAINT fk_Tecnico_Rol FOREIGN KEY (Rol_idRol) REFERENCES Rol(idRol)
);

-- Tabla Cliente
CREATE TABLE Cliente (
  idCliente INT NOT NULL AUTO_INCREMENT,
  Primer_Nombre VARCHAR(45) NOT NULL, 
  Primer_Apellido VARCHAR(45) NOT NULL, 
  Telefono VARCHAR(45) NOT NULL, 
  Direccion VARCHAR(100) NOT NULL,
  PRIMARY KEY (idCliente)
);

-- Tabla Arreglo
CREATE TABLE Arreglo (
  idArreglo INT NOT NULL AUTO_INCREMENT,
  Tipo_Arreglo VARCHAR(45) NOT NULL,
  Nombre_Arreglo VARCHAR(45) NOT NULL,
  Descripcion_Cliente VARCHAR(255) NOT NULL,
  Valor_Pago DECIMAL(10,2), 
  Fecha_Recibido DATE NOT NULL,
  Fecha_Entrega DATE,
  Marca_idMarca INT NOT NULL, 
  Tecnico_idTecnico INT NOT NULL,
  Estado_idEstado INT NOT NULL, 
  PRIMARY KEY (idArreglo),
  CONSTRAINT fk_Arreglo_Marca FOREIGN KEY (Marca_idMarca) REFERENCES Marca(idMarca),
  CONSTRAINT fk_Arreglo_Tecnico FOREIGN KEY (Tecnico_idTecnico) REFERENCES Tecnico(idTecnico),
  CONSTRAINT fk_Arreglo_Estado FOREIGN KEY (Estado_idEstado) REFERENCES Estado(idEstado)
);

-- Tabla Diagnostico
CREATE TABLE Diagnostico (
  idDiagnostico INT NOT NULL AUTO_INCREMENT,
  Componente VARCHAR(100) NOT NULL,
  Valor DECIMAL(10,2) NOT NULL, 
  Descripcion VARCHAR(500) NOT NULL, 
  PRIMARY KEY (idDiagnostico)
);

-- Tabla Detalle Diagnostico
CREATE TABLE Detalle_Diagnostico (
  idDetalleDiag INT NOT NULL AUTO_INCREMENT,
  Arreglo_idArreglo INT NOT NULL,
  Diagnostico_idDiagnostico INT NOT NULL,
  PRIMARY KEY (idDetalleDiag),
  CONSTRAINT fk_DetalleDiag_Arreglo FOREIGN KEY (Arreglo_idArreglo) REFERENCES Arreglo(idArreglo) ON DELETE CASCADE,
  CONSTRAINT fk_DetalleDiag_Diagnostico FOREIGN KEY (Diagnostico_idDiagnostico) REFERENCES Diagnostico(idDiagnostico)
);
-- Tabla Detalle Arreglo
CREATE TABLE Detalle_Arreglo (
  idDetalleArr INT NOT NULL AUTO_INCREMENT,
  Cliente_idCliente INT NOT NULL,
  Arreglo_idArreglo INT NOT NULL,
  PRIMARY KEY (idDetalleArr),
  CONSTRAINT fk_DetalleArr_Cliente FOREIGN KEY (Cliente_idCliente) REFERENCES Cliente(idCliente) ON DELETE CASCADE,
  CONSTRAINT fk_DetalleArr_Arreglo FOREIGN KEY (Arreglo_idArreglo) REFERENCES Arreglo(idArreglo) ON DELETE CASCADE 
);

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
(1, 'Juan Carlos Pérez López', 'juan.perez@gmail.com', '12345678', 1),
(2, 'Ana María Gómez Hernández', 'ana.gomez@gmail.com', 'abcdef', 2),
(3, 'Luis Andrés Morales Rojas', 'luis.morales@gmail.com', 'pass123', 2),
(4, 'Jesus Yeison Pencue Talaga', 'Yeison@gmail.com', '12345678', 1),
(5, 'Diego Jose Gaviria Ramirez', 'Diego@gmail.com', '12345678', 1),
(6, 'Sofía Isabel López Guzmán', 'sofia.lopez@gmail.com', 'asdfgh', 1),
(7, 'María Fernanda Vargas Cruz', 'maria.vargas@gmail.com', 'zxcvbn', 2),
(8, 'José Luis Hernández Martínez', 'jose.hernandez@gmail.com', 'hunter2', 2),
(9, 'Laura Andrea Mendoza Salinas', 'laura.mendoza@gmail.com', 'opensesame', 1),
(10, 'Daniel Francisco Reyes Ortiz', 'daniel.reyes@gmail.com', '123abc', 2);

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

SELECT * FROM tecnico;