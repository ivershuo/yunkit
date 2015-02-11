<?php
$config = include(APP_PATH . '/config/config.php');

Class Modules_Conf{
	static function load($key){
		global $config;
		return $config[$key];
	}

	static function __callStatic($func, $argv){
		return self::load($func);
	}
}