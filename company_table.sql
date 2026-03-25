-- Crear tabla company
CREATE TABLE IF NOT EXISTS `company` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `logo` text,
  `description` text,
  `email` varchar(255),
  `phone` varchar(50),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insertar datos de ejemplo
INSERT INTO `company` (`id`, `name`, `logo`, `description`, `email`, `phone`) VALUES
(1, 'Mi Empresa', 'uploads/logo.png', 'Descripción de la empresa', 'info@miempresa.com', '+1234567890');