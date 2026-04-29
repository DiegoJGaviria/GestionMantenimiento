DROP DATABASE IF EXISTS `sistema_arreglo_computadores`;
CREATE DATABASE `sistema_arreglo_computadores`;
USE `sistema_arreglo_computadores`;

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

-- Tabla Tipo de Dispositivo (NUEVA)
CREATE TABLE Tipo_Dispositivo (
  idTipoDispositivo INT NOT NULL AUTO_INCREMENT,
  Nombre_Tipo VARCHAR(100) NOT NULL,
  Descripcion VARCHAR(255),
  PRIMARY KEY (idTipoDispositivo)
);

-- Tabla Componente (para multiselect en diagnostico)
CREATE TABLE Componente (
  idComponente INT NOT NULL AUTO_INCREMENT,
  Nombre_Componente VARCHAR(100) NOT NULL,
  PRIMARY KEY (idComponente)
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

-- Tabla Arreglo (con Tipo_Dispositivo)
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

-- Tabla Diagnostico
CREATE TABLE Diagnostico (
  idDiagnostico INT NOT NULL AUTO_INCREMENT,
  Descripcion_Problema VARCHAR(500) NOT NULL,
  Valor_Estimado DECIMAL(10,2) NOT NULL, 
  Arreglo_idArreglo INT NOT NULL,
  PRIMARY KEY (idDiagnostico),
  CONSTRAINT fk_Diagnostico_Arreglo FOREIGN KEY (Arreglo_idArreglo) REFERENCES Arreglo(idArreglo) ON DELETE CASCADE
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

CREATE TABLE Factura (
  idFactura            INT NOT NULL AUTO_INCREMENT,
  Numero_Factura       VARCHAR(20) NOT NULL UNIQUE,
  Arreglo_idArreglo    INT NOT NULL,
  Fecha_Emision        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Subtotal_Componentes DECIMAL(10,2) NOT NULL DEFAULT 0,
  Mano_Obra            DECIMAL(10,2) NOT NULL DEFAULT 0,
  Subtotal             DECIMAL(10,2) NOT NULL DEFAULT 0,
  Iva_Porcentaje       DECIMAL(5,2)  NOT NULL DEFAULT 0,
  Iva_Valor            DECIMAL(10,2) NOT NULL DEFAULT 0,
  Total                DECIMAL(10,2) NOT NULL DEFAULT 0,
  Tecnico_Emisor       INT NOT NULL,
  Observaciones        TEXT NULL,
  PRIMARY KEY (idFactura),
  CONSTRAINT fk_Factura_Arreglo FOREIGN KEY (Arreglo_idArreglo) REFERENCES Arreglo(idArreglo),
  CONSTRAINT fk_Factura_Tecnico FOREIGN KEY (Tecnico_Emisor)    REFERENCES Tecnico(idTecnico)
);

-- 3. Detalle de la factura (snapshot historico)
CREATE TABLE Detalle_Factura (
  idDetalleFactura        INT NOT NULL AUTO_INCREMENT,
  Factura_idFactura       INT NOT NULL,
  Componente_idComponente INT NOT NULL,
  Nombre_Componente       VARCHAR(100) NOT NULL,
  Codigo_Producto         VARCHAR(50) NULL,
  Cantidad                INT NOT NULL,
  Precio_Unitario         DECIMAL(10,2) NOT NULL,
  Subtotal                DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (idDetalleFactura),
  CONSTRAINT fk_DetFact_Factura    FOREIGN KEY (Factura_idFactura)       REFERENCES Factura(idFactura) ON DELETE CASCADE,
  CONSTRAINT fk_DetFact_Componente FOREIGN KEY (Componente_idComponente) REFERENCES Componente(idComponente)
);
-- DATOS DE PRUEBA

-- Insertar datos en Tabla Rol
INSERT INTO `Rol` (`idRol`, `Nombre_Rol`) VALUES
(1, 'Administrador'),
(2, 'Tecnico');

-- Insertar datos en Tabla Marca
INSERT INTO `Marca` (`idMarca`, `Nombre_Marca`) VALUES
(1, 'Dell'), (2, 'HP'), (3, 'Lenovo'), (4, 'Acer'), (5, 'Apple'), 
(6, 'Asus'), (7, 'Samsung'), (8, 'Toshiba'), (9, 'Sony'), (10, 'MSI');

-- Insertar datos en Tabla Estado
INSERT INTO `Estado` (`idEstado`, `Nombre_Estado`) VALUES
(1, 'En diagnostico'),
(2, 'En reparacion'),
(3, 'Finalizado'),
(4, 'Entregado');

-- Insertar datos en Tabla Tipo_Dispositivo
INSERT INTO `Tipo_Dispositivo` (`idTipoDispositivo`, `Nombre_Tipo`, `Descripcion`) VALUES
(1, 'Laptop', 'Computador portatil'),
(2, 'PC de Escritorio', 'Computador de escritorio'),
(3, 'Tablet', 'Tableta electronica'),
(4, 'All-in-One', 'Computador todo en uno'),
(5, 'Monitor', 'Pantalla de computador'),
(6, 'Impresora', 'Impresora o multifuncional');

-- Insertar datos en Tabla Componente
INSERT INTO `Componente` (`idComponente`, `Nombre_Componente`) VALUES
(1, 'Pantalla'),
(2, 'Teclado'),
(3, 'Disco Duro / SSD'),
(4, 'Memoria RAM'),
(5, 'Placa Madre'),
(6, 'Procesador'),
(7, 'Tarjeta Grafica'),
(8, 'Bateria'),
(9, 'Fuente de Poder'),
(10, 'Puerto USB'),
(11, 'Puerto HDMI'),
(12, 'Camara Web'),
(13, 'Parlantes'),
(14, 'Ventilador / Cooler'),
(15, 'Conector de Carga');

-- Insertar datos en Tabla Tecnico
INSERT INTO `Tecnico` (`idTecnico`, `Nombre_Tecnico`, `Correo`, `Contraseña`, `Rol_idRol`) VALUES
(1, 'Juan Carlos Perez Lopez', 'juan.perez@gmail.com', '12345678', 1),
(2, 'Ana Maria Gomez Hernandez', 'ana.gomez@gmail.com', 'abcdef', 2),
(3, 'Luis Andres Morales Rojas', 'luis.morales@gmail.com', 'pass123', 2),
(4, 'Jesus Yeison Pencue Talaga', 'Yeison@gmail.com', '12345678', 1),
(5, 'Diego Jose Gaviria Ramirez', 'Diego@gmail.com', '12345678', 2);

-- Insertar datos en Tabla Cliente
INSERT INTO `Cliente` (`idCliente`, `Primer_Nombre`, `Primer_Apellido`, `Telefono`, `Direccion`) VALUES
(1, 'Luis', 'Martinez', '123456789', 'Calle 123'),
(2, 'Carla', 'Ramirez', '987654321', 'Avenida 456'),
(3, 'Pedro', 'Hernandez', '112233445', 'Calle Luna 10'),
(4, 'Ana', 'Salas', '998877665', 'Calle Sol 20'),
(5, 'Maria', 'Garcia', '223344556', 'Calle Estrella 30');

-- Insertar datos en Tabla Arreglo
INSERT INTO `Arreglo` 
(`idArreglo`, `Tipo_Dispositivo_idTipo`, `Nombre_Arreglo`, `Descripcion_Cliente`, `Valor_Pago`, `Fecha_Recibido`, `Fecha_Entrega`, `Marca_idMarca`, `Tecnico_idTecnico`, `Estado_idEstado`) VALUES
(1, 1, 'Cambio Pantalla', 'Pantalla rota', 120.00, '2024-11-01', '2024-11-05', 1, 2, 1),
(2, 2, 'Cambio Disco Duro', 'Disco dañado', 150.00, '2024-11-02', '2024-11-06', 2, 3, 2),
(3, 3, 'Reparacion Camara', 'Camara no funciona', 80.00, '2024-11-03', '2024-11-07', 3, 5, 3);

-- Insertar datos en Tabla Detalle Arreglo
INSERT INTO `Detalle_Arreglo` (`Cliente_idCliente`, `Arreglo_idArreglo`) VALUES
(1, 1), (2, 2), (3, 3);