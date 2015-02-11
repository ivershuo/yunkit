<?php
function auto_smarty_sys_cls($class){
	if(!preg_match('/^smarty/i', $class)){
		return;
	}
	$file = SMARTY_DIR . 'sysplugins/' . strtolower($class) . '.php';

	try{
		include_once $file;
		return true;
	} catch(Exception $e){
		header('http/1.1 500 Internal server error');
		exit('500');
	}
}
spl_autoload_register('auto_smarty_sys_cls');

class Modules_Smarty {
	public $smarty = null;

	public function __construct($config=array()){
		if(!defined('SMARTY_DIR')){
			return null;
		}
		require_once SMARTY_DIR . 'Smarty.class.php';
		$smarty = new Smarty;
		if(!DEBUG){
			$smarty->debugging = false;
			$smarty->caching = isset($config['caching']) ? $config['caching'] : true;
			$smarty->cache_lifetime = 600;
		}else{
			$smarty->caching = false;
			$smarty->debugging_ctrl = 'URL';
		}
		$smarty->inheritance_merge_compiled_includes = false; 

		$smarty->template_dir = VIEWS_DIR;
		$smarty->compile_dir  = VIEWS_DIR . '/tpl_c';
		$smarty->addPluginsDir(VIEWS_DIR . '/smarty_plug');
		$smarty->config_dir   = VIEWS_DIR . '/smarty_config';
		$smarty->cache_dir    = '/tmp/smarty';

		foreach ($config as $key => $value) {
			$smarty->$key = $value;
		}
		$this->smarty = $smarty;
		return $smarty;
	}

	public function assign($key, $value=true, $smarty_nocache=false){
		$this->smarty->assign($key, $value, $smarty_nocache);
	}

	public function display($file, $data=array()){
		foreach ($data as $key => $value) {
			$this->assign($key, $value);
		}
		$this->smarty->display($file);
	}

	public function fetch($file, $data=array()){
		foreach ($data as $key => $value) {
			$this->assign($key, $value);
		}
		return $this->smarty->fetch($file);
	}
}