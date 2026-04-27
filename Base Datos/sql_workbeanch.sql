CREATE SCHEMA `sistema_arreglo_computadores`;
USE `sistema_arreglo_computadores`;

-- Tabla Marca
CREATE TABLE Marca (
  idMarca INT NOT NULL AUTO_INCREMENT,
  Nombre_Marca VARCHAR(45) NOT NULL,
  PRIMARY KEY (idMarca)
);

-- Tabla Rol
CREATE TABLE Rol (
  idRol INT NOT NULL AUTO_INCREMENT,
  Nombre_Rol VARCHAR(45) NOT NULL,
  PRIMARY KEY (idRol)
);

-- Tabla Tecnico
CREATE TABLE Tecnico (
  idTecnico INT NOT NULL AUTO_INCREMENT,
  Primer_Nombre VARCHAR(45) NOT NULL,
  Segundo_Nombre VARCHAR(45),
  Primer_Apellido VARCHAR(45) NOT NULL,
  Segundo_Apellido VARCHAR(45),
  Correo VARCHAR(100) NOT NULL,
  Contraseña VARCHAR(255) NOT NULL,
  Edad INT,
  Rol_idRol INT NOT NULL,
  PRIMARY KEY (idTecnico),
  CONSTRAINT fk_Tecnico_Rol FOREIGN KEY (Rol_idRol) REFERENCES Rol(idRol)
);

-- Tabla Cliente
CREATE TABLE Cliente (
  idCliente INT NOT NULL AUTO_INCREMENT,
  Primer_Nombre VARCHAR(45) NOT NULL,
  Segundo_Nombre VARCHAR(45),
  Primer_Apellido VARCHAR(45) NOT NULL,
  Segundo_Apellido VARCHAR(45),
  Telefono VARCHAR(45) NOT NULL,
  Direccion VARCHAR(100),
  PRIMARY KEY (idCliente)
);

-- Tabla Estado
CREATE TABLE Estado (
  idEstado INT NOT NULL AUTO_INCREMENT,
  Nombre_Estado VARCHAR(45) NOT NULL,
  Descripcion_Estado VARCHAR(200),
  PRIMARY KEY (idEstado)
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
  Cliente_idCliente INT NOT NULL,
  Estado_idEstado INT NOT NULL,
  PRIMARY KEY (idArreglo),
  CONSTRAINT fk_Arreglo_Marca FOREIGN KEY (Marca_idMarca) REFERENCES Marca(idMarca),
  CONSTRAINT fk_Arreglo_Tecnico FOREIGN KEY (Tecnico_idTecnico) REFERENCES Tecnico(idTecnico),
  CONSTRAINT fk_Arreglo_Cliente FOREIGN KEY (Cliente_idCliente) REFERENCES Cliente(idCliente),
  CONSTRAINT fk_Arreglo_Estado FOREIGN KEY (Estado_idEstado) REFERENCES Estado(idEstado)
);

-- Tabla Diagnostico
CREATE TABLE Diagnostico (
  idDiagnostico INT NOT NULL AUTO_INCREMENT,
  Componente VARCHAR(100) NOT NULL,
  Valor DECIMAL(10,2),
  Descripcion VARCHAR(500) NOT NULL,
  Arreglo_idArreglo INT NOT NULL,
  PRIMARY KEY (idDiagnostico),
  CONSTRAINT fk_Diagnostico_Arreglo FOREIGN KEY (Arreglo_idArreglo) REFERENCES Arreglo(idArreglo)
);

-- Tabla Detalle_Diagnostico (relación N:M entre Arreglo y Diagnostico)
CREATE TABLE Detalle_Diagnostico (
  idDetalleDiag INT NOT NULL AUTO_INCREMENT,
  Arreglo_idArreglo INT NOT NULL,
  Diagnostico_idDiagnostico INT NOT NULL,
  Cantidad INT,
  PRIMARY KEY (idDetalleDiag),
  CONSTRAINT fk_DetalleDiag_Arreglo FOREIGN KEY (Arreglo_idArreglo) REFERENCES Arreglo(idArreglo),
  CONSTRAINT fk_DetalleDiag_Diagnostico FOREIGN KEY (Diagnostico_idDiagnostico) REFERENCES Diagnostico(idDiagnostico)
);

-- Tabla Detalle_Arreglo (relación N:M entre Cliente y Arreglo)
CREATE TABLE Detalle_Arreglo (
  idDetalleArr INT NOT NULL AUTO_INCREMENT,
  Cliente_idCliente INT NOT NULL,
  Arreglo_idArreglo INT NOT NULL,
  Cantidad INT,
  PRIMARY KEY (idDetalleArr),
  CONSTRAINT fk_DetalleArr_Cliente FOREIGN KEY (Cliente_idCliente) REFERENCES Cliente(idCliente),
  CONSTRAINT fk_DetalleArr_Arreglo FOREIGN KEY (Arreglo_idArreglo) REFERENCES Arreglo(idArreglo)
);