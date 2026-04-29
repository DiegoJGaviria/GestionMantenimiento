-- =============================================================
-- ESQUEMA COMPLETO V2: Sistema de Arreglo de Computadores
-- Incluye: Flujo Diagnostico->Tecnico, Facturacion Agrupada, Inventario
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

-- =============================================================
-- TABLA COMPONENTE (INVENTARIO EXTENDIDO)
-- =============================================================

CREATE TABLE Componente (
  idComponente INT NOT NULL AUTO_INCREMENT,
  Nombre_Componente VARCHAR(100) NOT NULL,
  Codigo_Producto VARCHAR(50) DEFAULT NULL,
  Codigo_Barras VARCHAR(100) DEFAULT NULL,
  Marca_idMarca INT DEFAULT NULL,
  Cantidad INT NOT NULL DEFAULT 0,
  Precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  Activo TINYINT(1) NOT NULL DEFAULT 1,
  Fecha_Ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
  Fecha_Modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (idComponente),
  CONSTRAINT fk_Componente_Marca FOREIGN KEY (Marca_idMarca) REFERENCES Marca(idMarca) ON DELETE SET NULL
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
-- TABLA ARREGLO
-- =============================================================

CREATE TABLE Arreglo (
  idArreglo INT NOT NULL AUTO_INCREMENT,
  Tipo_Dispositivo_idTipo INT NOT NULL,
  Nombre_Arreglo VARCHAR(45) NOT NULL,
  Descripcion_Cliente VARCHAR(255) NOT NULL,
  Valor_Pago DECIMAL(10,2), 
  Fecha_Recibido DATE NOT NULL,
  Fecha_Entrega DATE,
  Fecha_Cambio_Estado DATETIME DEFAULT NULL,
  Marca_idMarca INT NOT NULL, 
  Tecnico_idTecnico INT NOT NULL,
  Estado_idEstado INT NOT NULL DEFAULT 1,
  PRIMARY KEY (idArreglo),
  CONSTRAINT fk_Arreglo_TipoDisp FOREIGN KEY (Tipo_Dispositivo_idTipo) REFERENCES Tipo_Dispositivo(idTipoDispositivo),
  CONSTRAINT fk_Arreglo_Marca FOREIGN KEY (Marca_idMarca) REFERENCES Marca(idMarca),
  CONSTRAINT fk_Arreglo_Tecnico FOREIGN KEY (Tecnico_idTecnico) REFERENCES Tecnico(idTecnico),
  CONSTRAINT fk_Arreglo_Estado FOREIGN KEY (Estado_idEstado) REFERENCES Estado(idEstado)
);

-- =============================================================
-- TABLA DIAGNOSTICO (CON CAMPOS DE ASIGNACION)
-- =============================================================

CREATE TABLE Diagnostico (
  idDiagnostico INT NOT NULL AUTO_INCREMENT,
  Descripcion_Problema VARCHAR(500) NOT NULL,
  Valor_Estimado DECIMAL(10,2) NOT NULL, 
  Arreglo_idArreglo INT NOT NULL,
  Asignado_Tecnico TINYINT(1) NOT NULL DEFAULT 0,
  Tecnico_Asignado INT DEFAULT NULL,
  Fecha_Asignacion DATETIME DEFAULT NULL,
  PRIMARY KEY (idDiagnostico),
  CONSTRAINT fk_Diagnostico_Arreglo FOREIGN KEY (Arreglo_idArreglo) REFERENCES Arreglo(idArreglo) ON DELETE CASCADE,
  CONSTRAINT fk_Diagnostico_TecnicoAsignado FOREIGN KEY (Tecnico_Asignado) REFERENCES Tecnico(idTecnico) ON DELETE SET NULL
);

-- Tabla Detalle Diagnostico Componente (relacion muchos a muchos)
CREATE TABLE Detalle_Diagnostico_Componente (
  idDetalle INT NOT NULL AUTO_INCREMENT,
  Diagnostico_idDiagnostico INT NOT NULL,
  Componente_idComponente INT NOT NULL,
  PRIMARY KEY (idDetalle),
  CONSTRAINT fk_DetalleDC_Diagnostico FOREIGN KEY (Diagnostico_idDiagnostico) REFERENCES Diagnostico(idDiagnostico) ON DELETE CASCADE,
  CONSTRAINT fk_DetalleDC_Componente FOREIGN KEY (Componente_idComponente) REFERENCES Componente(idComponente)
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
-- TABLA INSUMO_ARREGLO (DESCUENTO DE STOCK)
-- =============================================================

CREATE TABLE Insumo_Arreglo (
  idInsumoArreglo INT NOT NULL AUTO_INCREMENT,
  Arreglo_idArreglo INT NOT NULL,
  Componente_idComponente INT NOT NULL,
  Cantidad_Usada INT NOT NULL DEFAULT 1,
  Precio_Unitario DECIMAL(10,2) NOT NULL,
  Subtotal DECIMAL(10,2) NOT NULL,
  Fecha_Registro DATETIME DEFAULT CURRENT_TIMESTAMP,
  Notas VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (idInsumoArreglo),
  CONSTRAINT fk_InsumoArreglo_Arreglo FOREIGN KEY (Arreglo_idArreglo) 
    REFERENCES Arreglo(idArreglo) ON DELETE CASCADE,
  CONSTRAINT fk_InsumoArreglo_Componente FOREIGN KEY (Componente_idComponente) 
    REFERENCES Componente(idComponente) ON DELETE RESTRICT
);

-- =============================================================
-- TABLA FACTURA (SOPORTA INDIVIDUAL Y AGRUPADA)
-- =============================================================

CREATE TABLE Factura (
  idFactura INT NOT NULL AUTO_INCREMENT,
  Numero_Factura VARCHAR(20) NOT NULL UNIQUE,
  Arreglo_idArreglo INT DEFAULT NULL,
  Cliente_idCliente INT DEFAULT NULL,
  Tipo_Factura ENUM('individual', 'agrupada') DEFAULT 'individual',
  Fecha_Emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Subtotal_Componentes DECIMAL(10,2) NOT NULL DEFAULT 0,
  Mano_Obra DECIMAL(10,2) NOT NULL DEFAULT 0,
  Subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  Iva_Porcentaje DECIMAL(5,2) NOT NULL DEFAULT 0,
  Iva_Valor DECIMAL(10,2) NOT NULL DEFAULT 0,
  Total DECIMAL(10,2) NOT NULL DEFAULT 0,
  Tecnico_Emisor INT NOT NULL,
  Observaciones TEXT NULL,
  PRIMARY KEY (idFactura),
  CONSTRAINT fk_Factura_Arreglo FOREIGN KEY (Arreglo_idArreglo) REFERENCES Arreglo(idArreglo),
  CONSTRAINT fk_Factura_Cliente FOREIGN KEY (Cliente_idCliente) REFERENCES Cliente(idCliente),
  CONSTRAINT fk_Factura_Tecnico FOREIGN KEY (Tecnico_Emisor) REFERENCES Tecnico(idTecnico)
);

-- =============================================================
-- TABLA DETALLE_FACTURA_ARREGLO (PARA FACTURAS AGRUPADAS)
-- =============================================================

CREATE TABLE Detalle_Factura_Arreglo (
  idDetalleFacturaArreglo INT NOT NULL AUTO_INCREMENT,
  Factura_idFactura INT NOT NULL,
  Arreglo_idArreglo INT NOT NULL,
  Mano_Obra DECIMAL(10,2) NOT NULL DEFAULT 0,
  Subtotal_Componentes DECIMAL(10,2) NOT NULL DEFAULT 0,
  Subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (idDetalleFacturaArreglo),
  CONSTRAINT fk_DetFactArr_Factura FOREIGN KEY (Factura_idFactura) 
    REFERENCES Factura(idFactura) ON DELETE CASCADE,
  CONSTRAINT fk_DetFactArr_Arreglo FOREIGN KEY (Arreglo_idArreglo) 
    REFERENCES Arreglo(idArreglo) ON DELETE RESTRICT
);

-- Detalle de la factura (snapshot historico de componentes)
CREATE TABLE Detalle_Factura (
  idDetalleFactura INT NOT NULL AUTO_INCREMENT,
  Factura_idFactura INT NOT NULL,
  Componente_idComponente INT NOT NULL,
  Nombre_Componente VARCHAR(100) NOT NULL,
  Codigo_Producto VARCHAR(50) NULL,
  Cantidad INT NOT NULL,
  Precio_Unitario DECIMAL(10,2) NOT NULL,
  Subtotal DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (idDetalleFactura),
  CONSTRAINT fk_DetFact_Factura FOREIGN KEY (Factura_idFactura) REFERENCES Factura(idFactura) ON DELETE CASCADE,
  CONSTRAINT fk_DetFact_Componente FOREIGN KEY (Componente_idComponente) REFERENCES Componente(idComponente)
);

-- =============================================================
-- INDICES PARA OPTIMIZACION
-- =============================================================

CREATE INDEX idx_arreglo_estado ON Arreglo(Estado_idEstado);
CREATE INDEX idx_arreglo_tecnico ON Arreglo(Tecnico_idTecnico);
CREATE INDEX idx_factura_cliente ON Factura(Cliente_idCliente);
CREATE INDEX idx_factura_arreglo ON Factura(Arreglo_idArreglo);
CREATE INDEX idx_insumo_arreglo ON Insumo_Arreglo(Arreglo_idArreglo);
CREATE INDEX idx_componente_activo ON Componente(Activo);
CREATE INDEX idx_diagnostico_arreglo ON Diagnostico(Arreglo_idArreglo);

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

-- Componentes (con campos de inventario)
INSERT INTO Componente (idComponente, Nombre_Componente, Codigo_Producto, Cantidad, Precio, Activo) VALUES
(1, 'Pantalla', 'PANT-001', 15, 150000, 1),
(2, 'Teclado', 'TECL-001', 25, 45000, 1),
(3, 'Disco Duro / SSD', 'SSD-001', 20, 120000, 1),
(4, 'Memoria RAM', 'RAM-001', 30, 80000, 1),
(5, 'Placa Madre', 'MB-001', 10, 250000, 1),
(6, 'Procesador', 'CPU-001', 8, 350000, 1),
(7, 'Tarjeta Grafica', 'GPU-001', 5, 450000, 1),
(8, 'Bateria', 'BAT-001', 20, 65000, 1),
(9, 'Fuente de Poder', 'PSU-001', 12, 85000, 1),
(10, 'Puerto USB', 'USB-001', 40, 15000, 1),
(11, 'Puerto HDMI', 'HDMI-001', 30, 18000, 1),
(12, 'Camara Web', 'CAM-001', 15, 35000, 1),
(13, 'Parlantes', 'SPK-001', 20, 25000, 1),
(14, 'Ventilador / Cooler', 'FAN-001', 25, 22000, 1),
(15, 'Conector de Carga', 'CHG-001', 35, 28000, 1);

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

-- Arreglos de prueba
INSERT INTO Arreglo 
(idArreglo, Tipo_Dispositivo_idTipo, Nombre_Arreglo, Descripcion_Cliente, Valor_Pago, Fecha_Recibido, Fecha_Entrega, Marca_idMarca, Tecnico_idTecnico, Estado_idEstado) VALUES
(1, 1, 'Cambio Pantalla', 'Pantalla rota', 120000, '2024-11-01', '2024-11-05', 1, 2, 3),
(2, 2, 'Cambio Disco Duro', 'Disco dañado', 150000, '2024-11-02', '2024-11-06', 2, 3, 3),
(3, 3, 'Reparacion Camara', 'Camara no funciona', 80000, '2024-11-03', '2024-11-07', 3, 5, 3),
(4, 1, 'Cambio de Bateria', 'No enciende sin cargador', 65000, '2024-11-10', NULL, 5, 2, 3),
(5, 2, 'Limpieza y Mantenimiento', 'Muy lento y se calienta', 50000, '2024-11-12', NULL, 4, 3, 3);

-- Detalle Arreglo (relacion con clientes)
INSERT INTO Detalle_Arreglo (Cliente_idCliente, Arreglo_idArreglo) VALUES
(1, 1), (1, 4),
(2, 2), (2, 5),
(3, 3);

-- Diagnosticos con asignacion
INSERT INTO Diagnostico (idDiagnostico, Arreglo_idArreglo, Descripcion_Problema, Valor_Estimado, Asignado_Tecnico, Tecnico_Asignado, Fecha_Asignacion) VALUES
(1, 1, 'Pantalla LCD dañada, requiere reemplazo completo', 120000, 1, 2, '2024-11-01 10:00:00'),
(2, 2, 'Disco duro con sectores dañados, no reconoce arranque', 150000, 1, 3, '2024-11-02 11:00:00'),
(3, 3, 'Camara frontal no detectada, posible daño en flex', 80000, 1, 5, '2024-11-03 09:00:00'),
(4, 4, 'Bateria no carga, celulas dañadas', 65000, 1, 2, '2024-11-10 14:00:00'),
(5, 5, 'Acumulacion de polvo, pasta termica seca', 50000, 1, 3, '2024-11-12 16:00:00');

-- Detalle Diagnostico Componente
INSERT INTO Detalle_Diagnostico_Componente (Diagnostico_idDiagnostico, Componente_idComponente) VALUES
(1, 1),
(2, 3),
(3, 12),
(4, 8),
(5, 14);
