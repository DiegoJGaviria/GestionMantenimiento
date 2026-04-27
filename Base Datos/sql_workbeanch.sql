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