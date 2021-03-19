CREATE TABLE `productos` ( 
`id` INT(10) NOT NULL AUTO_INCREMENT , 
`nombre` VARCHAR(100) NOT NULL ,
`referencia` VARCHAR(20) NOT NULL ,
`precio` INT(10) NOT NULL , 
`peso` INT(10) NOT NULL , 
`categoria` VARCHAR(30) NOT NULL , 
`stock` INT(10) NOT NULL , 
`fecha_creacion` DATETIME NOT NULL ,
`fecha_ult_venta` DATETIME NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;
