<?php
class Params {
	public function all(){
		$allParams = array_merge($this->get(), $this->post());
		return $allParams;
	}
	public function __call($method, $argvs){
		if(!($method == 'get' || $method == 'post')){
			return;
		}
		$ret   = array();
		$data  = $method == 'post' ? $_POST : $_GET;
		$param = isset($argvs[0]) ? $argvs[0] : null;
		foreach ($data as $key => $value) {
			$ret[$key] = trim($value);
		}
		if($param){
			$defaultValue = isset($argvs[1]) ? $argvs[1] : '';
			return isset($ret[$param]) ? $ret[$param] : $defaultValue;
		}
		return $ret;
	}
}

class Modules_Request{
	protected function get_method(){
		$req_method = strtolower($_SERVER['REQUEST_METHOD']);
		foreach (array('is_get', 'is_post', 'is_head', 'is_put', 'is_options', 'is_delete', 'is_trace', 'is_connect') as $value) {
			$this->$value = false;
		}
		$m = 'is_' . $req_method;
		$this->$m = true;
	}

	protected function get_ua(){
		$this->ua = strtolower($_SERVER['HTTP_USER_AGENT']);
	}

	protected function get_ip(){
		$this->ip = get_ip();
	}

	protected function get_detect_ajax(){
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'){
			$this->is_ajax = true;
		} else {
			$this->is_ajax = false;
		}
	}

	function __construct(){
		$this->get_ua();
		$this->get_ip();
		$this->get_method();
		$this->get_detect_ajax();
	}

	public function params($key=null, $default=''){
		$params = new Params;
		if($key){
			$all = $params->all();
			return isset($all[$key]) ? $all[$key] : $default;
		}
		return $params;
	}
}