<?php
if(!defined('APP_PATH')){
	define('APP_PATH', dirname(getcwd()));
}
if(!defined('LIB_PATH')){
	define('LIB_PATH', __DIR__);
}
if(!defined('CLASSES_DIR')){
	define('CLASSES_DIR', APP_PATH . '/class');
}
if(!defined('VIEWS_DIR')){
	define('VIEWS_DIR', APP_PATH . '/view');
}
if(!defined('DEBUG')){
	define('DEBUG', false);
}

include LIB_PATH . '/class/log.php';
include LIB_PATH . '/class/yunkit_exception.php';
include LIB_PATH . '/functions.php';
if(LIB_PATH != APP_PATH && file_exists(APP_PATH . '/functions.php')){
	include APP_PATH . '/functions.php';
}

class CTX {
	private static $data=array();

	public static function set_data($key, $value=true){
		self::$data[$key] = $value;
	}

	public static function get_data($key){
		return isset(self::$data[$key]) ? self::$data[$key] : null;
	}
}

class App{
	const VERSION = '0.0.2';

	const controller = 'home';
	const action     = 'index';

	private static $controller;
	private static $action;

	private static $inited = false;
	private static $pause = false;

	private static function init(){
		if(!defined('APP_LOG_LEVEL')){
			if(DEBUG){
				define('APP_LOG_LEVEL', Log::DEBUG);
			} else {
				define('APP_LOG_LEVEL', Log::INFO);
			}
		}
		if(!defined('APP_LOG_DIR')){
			define('APP_LOG_DIR', APP_PATH . '/logs');
		}
		Log::instance(APP_LOG_LEVEL, APP_LOG_DIR);

		set_exception_handler('exception_handler');
		set_error_handler('error_handler');
		spl_autoload_register('auto_load');

		self::$inited = true;
	}

	public static function set_controller($controller){
		self::$controller = $controller;
	}

	public static function set_action($action){
		self::$action = $action;
	}

	private static function set_ac_from_pathdata(){
		$R = explode('/', preg_replace('/\/$/', '', $_SERVER['PATH_INFO']));
		array_shift($R);
		if(empty($R)){
			$R = array(self::controller, self::action);
		} elseif(count($R) === 1){
			array_push($R, self::action);
		}
		$action = array_pop($R);
		if(preg_match('/^\_/', $action)){
			throw new Yunkit_Exception(404, "Not a action '$action'");
		}
		$controller = join($R, '_');

		self::set_controller($controller);
		self::set_action($action);
		unset($R);
		unset($action);
		unset($controller);
	}

	public static function get_classname($controller){
		return 'Controller_' . preg_replace_callback('/(^|\_)\w/', function($x){
			return strtoupper($x[0]);
		}, $controller);
	}	

	public static function forward($controller, $action){
		self::$pause = true;
		self::set_controller($controller);
		self::set_action($action);
		self::$pause = false;
		self::run();
	}

	public static function write($body='', $headers=array()){
		foreach ($headers as $key => $value) {
			header($key . ': ' . $value);
		}
		unset($headers);
		if(!empty($body)){
			echo $body;
			unset($body);
		}
		exit();
	}

	private static function _runapp(){
		$class_name = self::get_classname(self::$controller);

		if(!class_exists($class_name)){
			throw new Yunkit_Exception(404, "Class '$class_name' not exists");
		}
		$action = self::$action;

		$class = new $class_name;
		$class->action = $action;
		$class->controller = $class_name;

		if(!is_callable(array($class, $action))){
			throw new Yunkit_Exception(404, "Method '$class_name->$action' not exists");
		}

		if(method_exists($class, '_before')){
			call_user_func(array($class, '_before'));
		}
		if(defined('SMARTY_DIR')){
			call_user_func(array($class, 'open_smarty'));
		}
		$r = call_user_func(array($class, $action));
		if($class->auto_render && !self::$pause){
			if(!$class->tpl_file){
				$tpl_file_path = strtolower(join(explode('_', self::$controller), '/')) . '/' . $action;
				call_user_func(array($class, 'set_tpl'), $tpl_file_path);
			}
			call_user_func(array($class, 'render'));
		}
	}

	public static function run(){
		if(!self::$inited){
			self::init();
		}

		if(!self::$controller || !self::$action){
			self::set_ac_from_pathdata();
		}

		self::_runapp();
	}
}