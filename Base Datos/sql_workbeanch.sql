-- =============================================================
-- ESQUEMA COMPLETO V2: Sistema de Arreglo de Computadores
-- Esquema centrado en la gestión de arreglos, tipos de arreglo y reingresos
-- =============================================================

DROP DATABASE IF EXISTS `sistema_arreglo_computadores`;
CREATE DATABASE `sistema_arreglo_computadores`;
USE `sistema_arreglo_computadores`;

-- =============================================================
-- TABLAS BASE
-- =============================================================

-- Tabla Rol
CREATE TABLE Rol (
  idRol INT NOT NULL AUTO_INCREMENT,
  Nombre_Rol VARCHAR(45) NOT NULL,
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
  Nombre_Estado VARCHAR(45) NOT NULL,
  PRIMARY KEY (idEstado)
);

-- Tabla Tipo de Dispositivo
CREATE TABLE Tipo_Dispositivo (
  idTipoDispositivo INT NOT NULL AUTO_INCREMENT,
  Nombre_Tipo VARCHAR(100) NOT NULL,
  Descripcion VARCHAR(255),
  PRIMARY KEY (idTipoDispositivo)
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

-- =============================================================
-- TABLA TIPO_ARREGLO
-- =============================================================

CREATE TABLE Tipo_Arreglo (
  id INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  PRIMARY KEY (id)
);

-- =============================================================
-- TABLA ARREGLO
-- =============================================================

CREATE TABLE Arreglo (
  idArreglo INT NOT NULL AUTO_INCREMENT,
  Tipo_Dispositivo_idTipo INT NOT NULL,
  tipo_arreglo_id INT NOT NULL,
  Nombre_Arreglo VARCHAR(45) NOT NULL,
  Descripcion_Cliente VARCHAR(255) NOT NULL,
  comentario_estado TEXT DEFAULT NULL,
  Valor_Pago DECIMAL(10,2), 
  Fecha_Recibido DATE NOT NULL,
  Fecha_Entrega DATE,
  Fecha_Cambio_Estado DATETIME DEFAULT NULL,
  Marca_idMarca INT NOT NULL, 
  Tecnico_idTecnico INT NOT NULL,
  Estado_idEstado INT NOT NULL DEFAULT 1,
  PRIMARY KEY (idArreglo),
  CONSTRAINT fk_Arreglo_TipoDisp FOREIGN KEY (Tipo_Dispositivo_idTipo) REFERENCES Tipo_Dispositivo(idTipoDispositivo),
  CONSTRAINT fk_Arreglo_TipoArreglo FOREIGN KEY (tipo_arreglo_id) REFERENCES Tipo_Arreglo(id),
  CONSTRAINT fk_Arreglo_Marca FOREIGN KEY (Marca_idMarca) REFERENCES Marca(idMarca),
  CONSTRAINT fk_Arreglo_Tecnico FOREIGN KEY (Tecnico_idTecnico) REFERENCES Tecnico(idTecnico),
  CONSTRAINT fk_Arreglo_Estado FOREIGN KEY (Estado_idEstado) REFERENCES Estado(idEstado)
);

-- =============================================================
-- TABLA REINGRESO_ARREGLO
-- =============================================================

CREATE TABLE Reingreso_Arreglo (
  idReingreso INT NOT NULL AUTO_INCREMENT,
  Arreglo_idArreglo INT NOT NULL,
  Fecha_Reingreso DATE NOT NULL,
  Motivo_Reingreso TEXT NOT NULL,
  Fecha_Registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (idReingreso),
  CONSTRAINT fk_Reingreso_Arreglo FOREIGN KEY (Arreglo_idArreglo) REFERENCES Arreglo(idArreglo) ON DELETE CASCADE
);

-- Tabla Detalle Arreglo (relacion cliente-arreglo)
CREATE TABLE Detalle_Arreglo (
  idDetalleArr INT NOT NULL AUTO_INCREMENT,
  Cliente_idCliente INT NOT NULL,
  Arreglo_idArreglo INT NOT NULL,
  PRIMARY KEY (idDetalleArr),
  CONSTRAINT fk_DetalleArr_Cliente FOREIGN KEY (Cliente_idCliente) REFERENCES Cliente(idCliente) ON DELETE CASCADE,
  CONSTRAINT fk_DetalleArr_Arreglo FOREIGN KEY (Arreglo_idArreglo) REFERENCES Arreglo(idArreglo) ON DELETE CASCADE 
);

-- =============================================================
-- INDICES PARA OPTIMIZACION
-- =============================================================

CREATE INDEX idx_arreglo_estado ON Arreglo(Estado_idEstado);
CREATE INDEX idx_arreglo_tecnico ON Arreglo(Tecnico_idTecnico);
CREATE INDEX idx_reingreso_arreglo ON Reingreso_Arreglo(Arreglo_idArreglo);

-- =============================================================
-- DATOS DE PRUEBA
-- =============================================================

-- Roles
INSERT INTO Rol (idRol, Nombre_Rol) VALUES
(1, 'Administrador'),
(2, 'Tecnico');

-- Marcas
INSERT INTO Marca (idMarca, Nombre_Marca) VALUES
(1, 'Dell'), (2, 'HP'), (3, 'Lenovo'), (4, 'Acer'), (5, 'Apple'), 
(6, 'Asus'), (7, 'Samsung'), (8, 'Toshiba'), (9, 'Sony'), (10, 'MSI');

-- Estados
INSERT INTO Estado (idEstado, Nombre_Estado) VALUES
(1, 'En diagnostico'),
(2, 'En reparacion'),
(3, 'Finalizado'),
(4, 'Entregado');

-- Tipos de Dispositivo
INSERT INTO Tipo_Dispositivo (idTipoDispositivo, Nombre_Tipo, Descripcion) VALUES
(1, 'Laptop', 'Computador portatil'),
(2, 'PC de Escritorio', 'Computador de escritorio'),
(3, 'Tablet', 'Tableta electronica'),
(4, 'All-in-One', 'Computador todo en uno'),
(5, 'Monitor', 'Pantalla de computador'),
(6, 'Impresora', 'Impresora o multifuncional');

-- Tecnicos
INSERT INTO Tecnico (idTecnico, Nombre_Tecnico, Correo, Contraseña, Rol_idRol) VALUES
(1, 'Juan Carlos Perez Lopez', 'juan.perez@gmail.com', '12345678', 1),
(2, 'Ana Maria Gomez Hernandez', 'ana.gomez@gmail.com', 'abcdef', 2),
(3, 'Luis Andres Morales Rojas', 'luis.morales@gmail.com', 'pass123', 2),
(4, 'Jesus Yeison Pencue Talaga', 'yeison@gmail.com', '12345678', 2),
(5, 'Diego Jose Gaviria Ramirez', 'diego@gmail.com', '12345678', 1);

-- Clientes
INSERT INTO Cliente (idCliente, Primer_Nombre, Primer_Apellido, Telefono, Direccion) VALUES
(1, 'Luis', 'Martinez', '123456789', 'Calle 123'),
(2, 'Carla', 'Ramirez', '987654321', 'Avenida 456'),
(3, 'Pedro', 'Hernandez', '112233445', 'Calle Luna 10'),
(4, 'Ana', 'Salas', '998877665', 'Calle Sol 20'),
(5, 'Maria', 'Garcia', '223344556', 'Calle Estrella 30');

-- Tipos de Arreglo
INSERT INTO Tipo_Arreglo (id, nombre) VALUES
(1, 'Reparación'),
(2, 'Mantenimiento'),
(3, 'Actualización'),
(4, 'Instalación'),
(5, 'Limpieza');

-- Arreglos de prueba
INSERT INTO Arreglo 
(idArreglo, Tipo_Dispositivo_idTipo, tipo_arreglo_id, Nombre_Arreglo, Descripcion_Cliente, Valor_Pago, Fecha_Recibido, Fecha_Entrega, Marca_idMarca, Tecnico_idTecnico, Estado_idEstado) VALUES
(1, 1, 1, 'Cambio Pantalla', 'Pantalla rota', 120000, '2024-11-01', '2024-11-05', 1, 2, 3),
(2, 2, 1, 'Cambio Disco Duro', 'Disco dañado', 150000, '2024-11-02', '2024-11-06', 2, 3, 3),
(3, 3, 1, 'Reparacion Camara', 'Camara no funciona', 80000, '2024-11-03', '2024-11-07', 3, 5, 3),
(4, 1, 5, 'Cambio de Bateria', 'No enciende sin cargador', 65000, '2024-11-10', NULL, 5, 2, 3),
(5, 2, 2, 'Limpieza y Mantenimiento', 'Muy lento y se calienta', 50000, '2024-11-12', NULL, 4, 3, 3);

-- Detalle Arreglo (relacion con clientes)
INSERT INTO Detalle_Arreglo (Cliente_idCliente, Arreglo_idArreglo) VALUES
(1, 1), (1, 4),
(2, 2), (2, 5),
(3, 3);
