<?php
// Init
$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'attribute_carrier_config` (
  `id_attribute_carrier_config` int(11) NOT NULL AUTO_INCREMENT,
  `price` decimal(20,6) NOT NULL,
  `date_add` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL,
  PRIMARY KEY (`id_attribute_carrier_config`)
) ENGINE='._MYSQL_ENGINE_.'  DEFAULT CHARSET=utf8;';
$sql[] = 'TRUNCATE `'._DB_PREFIX_.'attribute_carrier_config`;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'attribute_carrier_config_combination` (
  `id_attribute_carrier_config` int(11) NOT NULL,
  `id_attribute` int(11) NOT NULL
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
$sql[] = 'TRUNCATE `'._DB_PREFIX_.'attribute_carrier_config_combination`;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'attribute_carrier_config_lang` (
  `id_attribute_carrier_config` int(11) NOT NULL,
  `id_lang` int(11) NOT NULL
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
$sql[] = 'TRUNCATE `'._DB_PREFIX_.'attribute_carrier_config_lang`;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'attribute_carrier_config_shop` (
  `id_attribute_carrier_config` int(11) NOT NULL,
  `id_shop` int(11) NOT NULL,
  `price` decimal(20,0) NOT NULL,
  `date_add` datetime DEFAULT NULL,
  `date_upd` datetime DEFAULT NULL
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
$sql[] = 'TRUNCATE `'._DB_PREFIX_.'attribute_carrier_config_shop`;';