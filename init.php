<?php

define(DEFAULT_ACTION, 'index');
define(CLASSES_DIR, 'class/');

function auto_load($class){
	$class_name = strtolower(str_replace("_", "/", $class));
	$class_file = CLASSES_DIR . $class_name . '.php';

	if(!file_exists($class_file)){
		return false;
	}	
	try{
		require_once $class_file;
		return true;
	} catch(Exception $e){
		return false;
	}
}

/**
 * 注册类自动加载器
 */
spl_autoload_register('auto_load');
