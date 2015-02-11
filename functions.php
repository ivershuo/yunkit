<?php
function get_clean_path($file){
	if (strpos($file, APP_PATH) === 0) {
			$file = 'APP_PATH' . ': ' . substr($file, strlen(APP_PATH));
	}
	elseif (strpos($file, LIB_PATH) === 0) {
		$file = 'LIB_PATH' . ': ' . substr($file, strlen(LIB_PATH));
	}
	return $file;
}

function context_to_string($context) {
	$export = '';
	foreach ($context as $key => $value) {
		$export .= "{$key}: ";
		$export .= preg_replace(array(
			'/=>\s+([a-zA-Z])/im',
			'/array\(\s+\)/im',
			'/^  |\G  /m',
		), array(
			'=> $1',
			'array()',
			'    ',
		), str_replace('array (', 'array(', var_export($value, true)));
		$export .= PHP_EOL;
	}
	return str_replace(array('\\\\', '\\\''), array('\\', '\''), rtrim($export));
}

function get_ip(){
	$trusted_proxies = array('127.0.0.1', 'localhost', 'localhost.localdomain');
	if(defined('APP_PROXY_IP') && isset($_SERVER[APP_PROXY_IP])){
		$ip = $_SERVER[APP_PROXY_IP];
	} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], $trusted_proxies)) {
		$ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		$ip = array_shift($ips);
	} elseif (isset($_SERVER['HTTP_CLIENT_IP']) && isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], $trusted_proxies)) {
		$ips = explode(',', $_SERVER['HTTP_CLIENT_IP']);
		$ip = array_shift($ips);
	} elseif (isset($_SERVER['REMOTE_ADDR'])) {
		$ip = $_SERVER['REMOTE_ADDR'];
	} else {
		$ip = '0.0.0.0';
	}
	return $ip;
}

function auto_load($class){
	$class_name = strtolower(str_replace('_', '/', $class));
	$class_file_inlib = LIB_PATH . '/class/' . $class_name . '.php';
	$class_file_inapp = APP_PATH . '/class/' . $class_name . '.php';

	if(file_exists($class_file_inapp)){
		$file = $class_file_inapp;
	} elseif(file_exists($class_file_inlib)){
		$file = $class_file_inlib;
	} elseif(substr($class_name, 0, 11) == 'controller/') {
		throw new Yunkit_Exception(404, "Load class '$class_name' error");
	}

	try{
		if(isset($file)){
			include_once $file;
			return true;
		}
	} catch(Exception $e){
		throw new Yunkit_Exception(500, $e->getMessage());
	}
}

function exception_handler($exception){
	$msg   = $exception->getMessage();	
	$code  = (int) $exception->getCode();
	$file  = get_clean_path($exception->getFile());
	$line  = $exception->getLine();
	$trace = $exception->getTrace();
	$trace_str     = $exception->getTraceAsString();
	$pre_exception = $exception->getPrevious();

	$err_str = "[".  $msg . "] \n=>file: " . $file . "; line: " . $line ."\n" . $trace_str;
	CTX::set_data('error_str', $err_str);
	Log::error($err_str);

	if('Yunkit_Exception' === get_class($exception) && $code >= 100 && $code <= 999){
		$error_action = 'error_' . $code;
	} else {
		$error_action = 'error_500';
		$code = 500;
	}
	CTX::set_data('forward_error_code', $code);

	$app_error_controller = defined('APP_ERROR_CONTROLLER') ? APP_ERROR_CONTROLLER : 'error';
	$Class = App::get_classname($app_error_controller);
	try{
		if(class_exists($Class)){
			$class = new $Class;
			if(method_exists($class, $error_action)){
				App::forward('error', $error_action);
				return;
			}
		}
	} catch (Exception $e){
		Log::debug('no app error controller! ' . $e->getMessage());
	}
	App::forward('yunkiterror', $error_action);
}

function error_handler($errno, $errstr, $errfile, $errline){
	$level = error_reporting();
	if ($level & $errno) {
		$exit = false;
		switch ($errno) {
			case E_USER_ERROR:
				$type = 'Fatal Error';
			break;
			case E_USER_WARNING:
			case E_WARNING:
				$type = 'Warning';
			break;
			case E_USER_NOTICE:
			case E_NOTICE:
			case @E_STRICT:
				$type = 'Notice';
			break;
			case @E_RECOVERABLE_ERROR:
				$type = 'Catchable';
			break;
			default:
				$type = 'Unknown Error';
			break;
		}
		
		throw new Yunkit_Exception(500, $type.': '.$errstr);
	}
	return false;
}