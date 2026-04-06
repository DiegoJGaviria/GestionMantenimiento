CREATE SCHEMA `sistema_arreglo_computadores`;
USE `sistema_arreglo_computadores` ;

CREATE TABLE  `sistema_arreglo_computadores`.`Marca` (
  `idMarca` INT NOT NULL,
  `Nombre_Marca` VARCHAR(45) NULL,
  PRIMARY KEY (`idMarca`)
);

CREATE TABLE  `sistema_arreglo_computadores`.`Rol` (
  `idRol` INT NOT NULL,
  `Nombre_Rol` VARCHAR(45) NULL,
  PRIMARY KEY (`idRol`)
);

CREATE TABLE  `sistema_arreglo_computadores`.`Empleado` (
  `idEmpleado` INT NOT NULL AUTO_INCREMENT,
  `Primer_Nombre` VARCHAR(45) NULL,
  `Segundo_Nombre` VARCHAR(45) NULL,
  `Primer_Apellido` VARCHAR(45) NULL,
  `Segundo_Apellido` VARCHAR(45) NULL,
  `Correo` VARCHAR(45) NULL,
  `Contraseña` VARCHAR(255) NULL,
  `Edad` INT NULL,
  `Rol_idRol` INT NOT NULL,
  PRIMARY KEY (`idEmpleado`, `Rol_idRol`),
  CONSTRAINT `fk_Empleadoo_Rol1`FOREIGN KEY (`Rol_idRol`)REFERENCES `sistema_arreglo_computadores`.`Rol` (`idRol`))
;
CREATE TABLE  `sistema_arreglo_computadores`.`Admin` (
  `idAdmin` INT NOT NULL AUTO_INCREMENT,
  `Primer_Nombre_Admin` VARCHAR(45) NULL,
  `Segundo_Nombre_Admim` VARCHAR(45) NULL,
  `Primer_Apellido_Admin` VARCHAR(45) NULL,
  `Segundo_Apellido_Admin` VARCHAR(45) NULL,
  `Correo_Admin` VARCHAR(45) NULL,
  `Contraseña_Admin` VARCHAR(255) NULL,
  `Edad_Admin` INT NULL,
  `Rol_idRol` INT NOT NULL,
  PRIMARY KEY (`idAdmin`, `Rol_idRol`),
  CONSTRAINT `fk_Admin_Rol1`FOREIGN KEY (`Rol_idRol`)REFERENCES `sistema_arreglo_computadores`.`Rol` (`idRol`))
;

CREATE TABLE  `sistema_arreglo_computadores`.`Arreglo` (
  `idArreglo` INT NOT NULL AUTO_INCREMENT,
  `Tipo_Arreglo` VARCHAR(45) NULL,
  `Nombre_Arreglo` VARCHAR(45) NULL,
  `Descripcion_Cliente` VARCHAR(45) NULL,
  `Valor_Pago` DECIMAL NULL,
  `Fecha_Recibido` DATE NULL,
  `Fecha_Entrega` DATE NULL,
  `Marca_idMarca` INT NOT NULL,
  `Empleado_idEmpleado` INT NOT NULL,
  PRIMARY KEY (`idArreglo`, `Marca_idMarca`, `Empleado_idEmpleado`),
  INDEX `fk_Arreglo_Marca_idx` (`Marca_idMarca` ASC) ,
  INDEX `fk_Arreglo_Empleado1_idx` (`Empleado_idEmpleado` ASC) ,
  CONSTRAINT `fk_Arreglo_Marca`FOREIGN KEY (`Marca_idMarca`)REFERENCES `sistema_arreglo_computadores`.`Marca` (`idMarca`),
  CONSTRAINT `fk_Arreglo_Empleado1`FOREIGN KEY (`Empleado_idEmpleado`)REFERENCES `sistema_arreglo_computadores`.`Empleado` (`idEmpleado`)
);

CREATE TABLE `sistema_arreglo_computadores`.`Cliente` (
  `idCliente` INT NOT NULL AUTO_INCREMENT,
  `Primer_Nombre` VARCHAR(45) NULL,
  `Segundo_Nombre` VARCHAR(45) NULL,
  `Primer_Apellido` VARCHAR(45) NULL,
  `Segundo_Apellido` VARCHAR(45) NULL,
  `Telefono` VARCHAR(45) NULL,
  `Direccion` VARCHAR(45) NULL,
  PRIMARY KEY (`idCliente`)
);

CREATE TABLE  `sistema_arreglo_computadores`.`Diagnostico` (
  `idDiagnostico` INT NOT NULL AUTO_INCREMENT,
  `Componente` VARCHAR(45) NULL,
  `Valor` DECIMAL NULL,
  `Descripcion` VARCHAR(45) NULL,
  PRIMARY KEY (`idDiagnostico`))
;

CREATE TABLE  `sistema_arreglo_computadores`.`Detalle_Diagnostico` (
  `Arreglo_idArreglo` INT NOT NULL,
  `Arreglo_Marca_idMarca` INT NOT NULL,
  `Arreglo_Empleado_idEmpleado` INT NOT NULL,
  `Diagnostico_idDiagnostico` INT NOT NULL,
  `Cantidad` INT NULL,
  PRIMARY KEY (`Arreglo_idArreglo`, `Arreglo_Marca_idMarca`, `Arreglo_Empleado_idEmpleado`, `Diagnostico_idDiagnostico`),
  INDEX `fk_Arreglo_has_Diagnostico_Diagnostico1_idx` (`Diagnostico_idDiagnostico` ASC) ,
  INDEX `fk_Arreglo_has_Diagnostico_Arreglo1_idx` (`Arreglo_idArreglo` ASC, `Arreglo_Marca_idMarca` ASC, `Arreglo_Empleado_idEmpleado` ASC) ,
  CONSTRAINT `fk_Arreglo_has_Diagnostico_Arreglo1`FOREIGN KEY (`Arreglo_idArreglo` , `Arreglo_Marca_idMarca` , `Arreglo_Empleado_idEmpleado`)REFERENCES `sistema_arreglo_computadores`.`Arreglo` (`idArreglo` , `Marca_idMarca` , `Empleado_idEmpleado`),
  CONSTRAINT `fk_Arreglo_has_Diagnostico_Diagnostico1`FOREIGN KEY (`Diagnostico_idDiagnostico`)REFERENCES `sistema_arreglo_computadores`.`Diagnostico` (`idDiagnostico`)
);

CREATE TABLE  `sistema_arreglo_computadores`.`Detalle_Arreglo` (
  `Cliente_idCliente` INT NOT NULL,
  `Arreglo_idArreglo` INT NOT NULL,
  `Arreglo_Marca_idMarca` INT NOT NULL,
  `Arreglo_Empleado_idEmpleado` INT NOT NULL,
  `Cantidad` INT NULL,
  PRIMARY KEY (`Cliente_idCliente`, `Arreglo_idArreglo`, `Arreglo_Marca_idMarca`, `Arreglo_Empleado_idEmpleado`),
  INDEX `fk_Cliente_has_Arreglo_Arreglo1_idx` (`Arreglo_idArreglo` ASC, `Arreglo_Marca_idMarca` ASC, `Arreglo_Empleado_idEmpleado` ASC) ,
  INDEX `fk_Cliente_has_Arreglo_Cliente1_idx` (`Cliente_idCliente` ASC) ,
  CONSTRAINT `fk_Cliente_has_Arreglo_Cliente1`FOREIGN KEY (`Cliente_idCliente`)REFERENCES `sistema_arreglo_computadores`.`Cliente` (`idCliente`),
  CONSTRAINT `fk_Cliente_has_Arreglo_Arreglo1`FOREIGN KEY (`Arreglo_idArreglo` , `Arreglo_Marca_idMarca` , `Arreglo_Empleado_idEmpleado`)REFERENCES `sistema_arreglo_computadores`.`Arreglo` (`idArreglo` , `Marca_idMarca` , `Empleado_idEmpleado`)
);
