/*
SQLyog Community v13.2.1 (64 bit)
MySQL - 10.4.21-MariaDB : Database - mapos_paoficinas
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `veiculos` */

DROP TABLE IF EXISTS `veiculos`;

CREATE TABLE `veiculos` (
  `idVeiculos` int(11) NOT NULL AUTO_INCREMENT,
  `placa` varchar(10) CHARACTER SET latin1 NOT NULL DEFAULT '0',
  `marca` varchar(50) CHARACTER SET latin1 NOT NULL DEFAULT '0',
  `modelo` varchar(50) CHARACTER SET latin1 NOT NULL DEFAULT '0',
  `cor` varchar(50) CHARACTER SET latin1 NOT NULL DEFAULT '0',
  `ano` varchar(10) CHARACTER SET latin1 NOT NULL DEFAULT '0',
  `combustivel` varchar(50) CHARACTER SET latin1 NOT NULL DEFAULT '0',
  PRIMARY KEY (`idVeiculos`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

/*Table structure for table `veiculos_clientes` */

DROP TABLE IF EXISTS `veiculos_clientes`;

CREATE TABLE `veiculos_clientes` (
  `idVeiculos_clientes` int(11) NOT NULL AUTO_INCREMENT,
  `veiculo_id` int(11) NOT NULL DEFAULT 0,
  `cliente_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`idVeiculos_clientes`),
  KEY `veiculo_id_cliente` (`veiculo_id`),
  KEY `cliente_id_veiculo` (`cliente_id`),
  CONSTRAINT `cliente_id_veiculo` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`idClientes`),
  CONSTRAINT `veiculo_id_cliente` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`idVeiculos`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
