CREATE DATABASE IF NOT EXISTS GestionMantenimientoCom;
USE GestionMantenimientoCom;

-- Tabla Roles
CREATE TABLE IF NOT EXISTS Roles (
    id_rol INT NOT NULL AUTO_INCREMENT,
    nombre_rol VARCHAR(40) NOT NULL,
    PRIMARY KEY (id_rol)
);

-- Tabla Empleados
CREATE TABLE IF NOT EXISTS Empleados (
    id_empleado INT NOT NULL AUTO_INCREMENT,
    nombre_usuario VARCHAR(100) NOT NULL,
    apeliido_usuario VARCHAR(100) NOT NULL,
    contraseña_usuario VARCHAR(255) NOT NULL,
    correo_usuario VARCHAR(100) NOT NULL UNIQUE,
    telefono_usuario VARCHAR(15),
    direccion_usuario VARCHAR(100),
    id_rol INT NOT NULL,
    PRIMARY KEY (id_empleado),
    CONSTRAINT fk_empleado_rol FOREIGN KEY (id_rol) REFERENCES Roles(id_rol)
);

-- Tabla Clientes
CREATE TABLE IF NOT EXISTS Clientes (
    id_cliente INT NOT NULL AUTO_INCREMENT,
    nombre_cliente VARCHAR(100) NOT NULL,
    apeliido_cliente VARCHAR(100) NOT NULL,
    correo_cliente VARCHAR(100) NOT NULL UNIQUE,
    telefono_cliente VARCHAR(15),
    direccion_cliente VARCHAR(100),
    PRIMARY KEY (id_cliente)
);

-- Tabla Equipos
CREATE TABLE IF NOT EXISTS Equipos (
    id_equipo INT NOT NULL AUTO_INCREMENT,
    id_cliente INT NOT NULL,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    numero_serial VARCHAR(100) NOT NULL UNIQUE,
    fecha_registro DATE NOT NULL,
    PRIMARY KEY (id_equipo),
    CONSTRAINT fk_equipo_cliente FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente)
);

-- Tabla Detalles del Equipo
CREATE TABLE IF NOT EXISTS Detalles_Equipo (
    id_detalle INT NOT NULL AUTO_INCREMENT,
    id_equipo INT NOT NULL,
    estado ENUM('recibido', 'en reparación', 'listo para entrega', 'entregado') NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_actualizacion DATE NOT NULL,
    id_empleado INT NOT NULL,
    PRIMARY KEY (id_detalle),
    CONSTRAINT fk_detalles_equipo FOREIGN KEY (id_equipo) REFERENCES Equipos(id_equipo),
    CONSTRAINT fk_detalle_empleado FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado)
);

-- Tabla Recepción de Equipos
CREATE TABLE IF NOT EXISTS Recepcion_Equipos (
    id_recepcion INT NOT NULL AUTO_INCREMENT,
    id_equipo INT NOT NULL,
    id_empleado INT NOT NULL,
    fecha_recepcion DATE NOT NULL,
    observaciones TEXT,
    PRIMARY KEY (id_recepcion),
    CONSTRAINT fk_recepcion_equipo FOREIGN KEY (id_equipo) REFERENCES Equipos(id_equipo),
    CONSTRAINT fk_recepcion_empleado FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado)
);

-- Tabla Diagnóstico
CREATE TABLE IF NOT EXISTS Diagnostico (
    id_diagnostico INT NOT NULL AUTO_INCREMENT,
    id_equipo INT NOT NULL,
    tipo_diagnostico ENUM('Preventivo', 'Correctivo', 'Inicial') NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_diagnostico DATE NOT NULL,
    PRIMARY KEY (id_diagnostico),
    CONSTRAINT fk_diagnostico_equipo FOREIGN KEY (id_equipo) REFERENCES Equipos(id_equipo)
);

-- Tabla Procedimientos
CREATE TABLE IF NOT EXISTS Procedimientos (
    id_procedimiento INT NOT NULL AUTO_INCREMENT,
    nombre_procedimiento VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    PRIMARY KEY (id_procedimiento)
);

-- Tabla Tarifas
CREATE TABLE IF NOT EXISTS Tarifas (
    id_tarifa INT NOT NULL AUTO_INCREMENT,
    id_procedimiento INT NOT NULL,
    costo DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (id_tarifa),
    CONSTRAINT fk_tarifa_procedimiento FOREIGN KEY (id_procedimiento) REFERENCES Procedimientos(id_procedimiento)
);

-- Relación entre Diagnósticos y Procedimientos
CREATE TABLE IF NOT EXISTS Diagnostico_Procedimientos (
    id_diagnostico INT NOT NULL,
    id_procedimiento INT NOT NULL,
    PRIMARY KEY (id_diagnostico, id_procedimiento),
    CONSTRAINT fk_diag_proc_diagnostico FOREIGN KEY (id_diagnostico) REFERENCES Diagnostico(id_diagnostico),
    CONSTRAINT fk_diag_proc_procedimiento FOREIGN KEY (id_procedimiento) REFERENCES Procedimientos(id_procedimiento)
);

-- Tabla Arreglos (Gestión de reparaciones)
CREATE TABLE IF NOT EXISTS Arreglos (
    id_arreglo INT NOT NULL AUTO_INCREMENT,
    id_cliente INT NOT NULL,
    id_empleado INT NOT NULL,
    fecha_ingreso DATE NOT NULL,
    fecha_entrega DATE,
    PRIMARY KEY (id_arreglo),
    CONSTRAINT fk_arreglo_cliente FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente),
    CONSTRAINT fk_arreglo_empleado FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado),
    CONSTRAINT chk_fecha_entrega CHECK (fecha_entrega >= fecha_ingreso OR fecha_entrega IS NULL)
);

-- Tabla Detalles del Arreglo
CREATE TABLE IF NOT EXISTS Detalles_Arreglo (
    id_detalle_arreglo INT NOT NULL AUTO_INCREMENT,
    id_equipo INT NOT NULL,
    id_arreglo INT NOT NULL,
    descripcion TEXT NOT NULL,
    cantidad INT NOT NULL,
    PRIMARY KEY (id_detalle_arreglo),
    CONSTRAINT fk_detalle_arreglo_equipo FOREIGN KEY (id_equipo) REFERENCES Equipos(id_equipo),
    CONSTRAINT fk_detalle_arreglo_arreglo FOREIGN KEY (id_arreglo) REFERENCES Arreglos(id_arreglo)
);

-- Tabla Facturas
CREATE TABLE IF NOT EXISTS Facturas (
    id_factura INT NOT NULL AUTO_INCREMENT,
    id_cliente INT NOT NULL,
    id_arreglo INT NOT NULL,
    fecha_emision DATE NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (id_factura),
    CONSTRAINT fk_factura_cliente FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente),
    CONSTRAINT fk_factura_arreglo FOREIGN KEY (id_arreglo) REFERENCES Arreglos(id_arreglo)
);

-- Insertar roles (necesario para empleados)
INSERT INTO Roles (nombre_rol) 
VALUES ('Administrador'), ('Técnico');

-- Insertar un empleado administrador para poder iniciar sesión
INSERT INTO Empleados (nombre_usuario, apeliido_usuario, contraseña_usuario, correo_usuario, telefono_usuario, direccion_usuario, id_rol) 
VALUES ('Admin', 'Sistema', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@sistema.com', '1234567890', 'Dirección del Sistema', 1);

-- La contraseña del administrador es 'password'

