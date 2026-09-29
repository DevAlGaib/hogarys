-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema hogary's
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema hogary's
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `hogary's` DEFAULT CHARACTER SET utf8 ;
USE `hogary's` ;

-- -----------------------------------------------------
-- Table `hogary's`.`usuarios`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hogary's`.`usuarios` (
  `id_usuario` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `correo` VARCHAR(100) NOT NULL,
  `telefono` VARCHAR(15) NOT NULL,
  `descripcion` TEXT NULL,
  `contrasena_hash` VARCHAR(255) NOT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE INDEX `id_usuarios_UNIQUE` (`id_usuario` ASC) VISIBLE,
  UNIQUE INDEX `correo_UNIQUE` (`correo` ASC) VISIBLE,
  UNIQUE INDEX `contrasena_hash_UNIQUE` (`contrasena_hash` ASC) VISIBLE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hogary's`.`propiedades`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hogary's`.`propiedades` (
  `id_propiedad` INT NOT NULL AUTO_INCREMENT,
  `id_usuario` INT NOT NULL,
  `operacion` ENUM('Venta', 'Renta') NOT NULL,
  `tipo` ENUM('Casa', 'Departamento', 'Terreno') NOT NULL,
  `precio` DECIMAL(12,2) NOT NULL,
  `recamaras` INT NULL,
  `banios` INT NULL,
  `metros_cuadrados` INT NULL,
  `descripcion` TEXT NULL,
  `estatus` ENUM('Disponible', 'Vendida') NOT NULL DEFAULT 'Disponible',
  `fecha_publicacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_propiedad`),
  INDEX `fk_propiedades_usuarios_idx` (`id_usuario` ASC) VISIBLE,
  CONSTRAINT `fk_propiedades_usuarios`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `hogary's`.`usuarios` (`id_usuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hogary's`.`imagenes_propiedad`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hogary's`.`imagenes_propiedad` (
  `id_imagen_propiedad` INT NOT NULL AUTO_INCREMENT,
  `id_propiedad` INT NOT NULL,
  `url_imagen_propiedad` VARCHAR(500) NOT NULL,
  PRIMARY KEY (`id_imagen_propiedad`),
  INDEX `fk_imagenes_propiedad_propiedades1_idx` (`id_propiedad` ASC) VISIBLE,
  CONSTRAINT `fk_imagenes_propiedad_propiedades1`
    FOREIGN KEY (`id_propiedad`)
    REFERENCES `hogary's`.`propiedades` (`id_propiedad`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hogary's`.`staff`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hogary's`.`staff` (
  `id_staff` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `lugar_trabajo` VARCHAR(100) NOT NULL,
  `correo` VARCHAR(100) NOT NULL,
  `telefono` VARCHAR(15) NOT NULL,
  `url_imagen_staff` VARCHAR(500) NOT NULL,
  PRIMARY KEY (`id_staff`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hogary's`.`horarios_staff`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hogary's`.`horarios_staff` (
  `id_horario_staff` INT NOT NULL,
  `id_staff` INT NOT NULL,
  `dias_semana` ENUM('Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo') NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_fin` TIME NOT NULL,
  PRIMARY KEY (`id_horario_staff`),
  INDEX `fk_horario_staff_staff1_idx` (`id_staff` ASC) VISIBLE,
  CONSTRAINT `fk_horario_staff_staff1`
    FOREIGN KEY (`id_staff`)
    REFERENCES `hogary's`.`staff` (`id_staff`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hogary's`.`agendas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hogary's`.`agendas` (
  `id_agenda` INT NOT NULL AUTO_INCREMENT,
  `id_usuario` INT NOT NULL,
  `id_staff` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `hora` TIME NOT NULL,
  `mensaje` TEXT NULL,
  PRIMARY KEY (`id_agenda`),
  INDEX `fk_agendas_usuarios1_idx` (`id_usuario` ASC) VISIBLE,
  INDEX `fk_agendas_staff1_idx` (`id_staff` ASC) VISIBLE,
  CONSTRAINT `fk_agendas_usuarios1`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `hogary's`.`usuarios` (`id_usuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_agendas_staff1`
    FOREIGN KEY (`id_staff`)
    REFERENCES `hogary's`.`staff` (`id_staff`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hogary's`.`resenias`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hogary's`.`resenias` (
  `id_resenia` INT NOT NULL AUTO_INCREMENT,
  `id_usuario` INT NOT NULL,
  `id_staff` INT NOT NULL,
  `calificacion` TINYINT NOT NULL,
  `mensaje` TEXT NULL,
  PRIMARY KEY (`id_resenia`),
  INDEX `fk_resenias_usuarios1_idx` (`id_usuario` ASC) VISIBLE,
  INDEX `fk_resenias_staff1_idx` (`id_staff` ASC) VISIBLE,
  CONSTRAINT `fk_resenias_usuarios1`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `hogary's`.`usuarios` (`id_usuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_resenias_staff1`
    FOREIGN KEY (`id_staff`)
    REFERENCES `hogary's`.`staff` (`id_staff`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hogary's`.`direcciones_propiedad`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hogary's`.`direcciones_propiedad` (
  `id_direccion_propiedad` INT NOT NULL AUTO_INCREMENT,
  `id_propiedad` INT NOT NULL,
  `ciudad` VARCHAR(100) NOT NULL,
  `colonia` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_direccion_propiedad`),
  INDEX `fk_direcciones_propiedad_propiedades1_idx` (`id_propiedad` ASC) VISIBLE,
  CONSTRAINT `fk_direcciones_propiedad_propiedades1`
    FOREIGN KEY (`id_propiedad`)
    REFERENCES `hogary's`.`propiedades` (`id_propiedad`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hogary's`.`favoritos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `hogary's`.`favoritos` (
  `id_usuario` INT NOT NULL,
  `id_propiedad` INT NOT NULL,
  `fecha_agregado` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`, `id_propiedad`),
  INDEX `fk_usuarios_has_propiedades_propiedades1_idx` (`id_propiedad` ASC) VISIBLE,
  INDEX `fk_usuarios_has_propiedades_usuarios1_idx` (`id_usuario` ASC) VISIBLE,
  CONSTRAINT `fk_usuarios_has_propiedades_usuarios1`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `hogary's`.`usuarios` (`id_usuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_usuarios_has_propiedades_propiedades1`
    FOREIGN KEY (`id_propiedad`)
    REFERENCES `hogary's`.`propiedades` (`id_propiedad`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
