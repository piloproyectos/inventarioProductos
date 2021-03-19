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


INSERT INTO `productos` (`id`, `nombre`, `referencia`, `precio`, `peso`, `categoria`, `stock`, `fecha_creacion`,
 `fecha_ult_venta`) VALUES (NULL, 'Producto 1', '1234', '5000', '2', 'Categoria 1', '15', '2021-03-19 00:00:00', NULL),
  (NULL, 'Producto 2', '45678', '23000', '5', 'Categoria 2', '20', '2021-03-19 00:00:00', NULL);
  