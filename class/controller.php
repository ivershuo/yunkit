<?php
class Controller{
	public $auto_render = true;
	public $tpl_file = false;

	function __construct(){
		$this->request = new Modules_Request();
		$this->response = new Modules_Response();
		$this->view = new Modules_View();

		foreach($this->request as $key => $value) {
			$this->$key = $this->request->$key;
		}
	}

	function _before(){
		if($this->get_ctx_data('forward_error_code')){
			$this->assign('error_data', $this->get_ctx_data('error_data'));
			if(DEBUG){
				$this->assign('error_debug_data', $this->get_ctx_data('error_str'));
			}
			$this->response->add_status_header($this->get_ctx_data('forward_error_code'));
			$this->smarty_config = array(
				'caching' => false,
			);
		}
	}

	function _after(){
	}

	public function open_smarty(){
		$smarty_config = isset($this->smarty_config) ? $this->smarty_config : array();
		$this->smarty = new Modules_Smarty($smarty_config);
	}

	protected function params($key=null, $default=''){
		return $this->request->params($key, $default);
	}

	protected function post($key){
		$post_data = $this->request->params()->post();
		return isset($post_data[$key]) ? $post_data[$key] : '';
	}

	protected function get($key){
		$get_data = $this->request->params()->get();
		return isset($get_data[$key]) ? $get_data[$key] : '';
	}

	protected function assign($key, $value=true){
		$this->view->assign($key, $value);
	}

	protected function add_ctx_data($key, $data=true){
		return CTX::set_data($key, $data);
	}

	protected function get_ctx_data($key){
		return CTX::get_data($key);
	}

	public function end($code=500, $data=''){
		if($code >= 400 && $code <= 599){
			$this->add_ctx_data('error_data', $data);
			throw new Yunkit_Exception($code, $data);
		} else {
			$this->response->close($code);
		}
	}

	public function err($data=null, $err=-1, $cb=null){
		if($this->is_ajax){
			$cb = null;
		}
		$cb = $cb ? $cb : $this->get('_cb');
		if(is_string($data)){
			$data = array(
				'msg' => $data,
			);
		}
		$this->response->jsonp(array(
			'err'  => $err,
			'data' => $data, 
		), $cb);
	}

	public function ok($data=null, $cb=null){
		$this->err($data, 0, $cb);
	}

	public function redirect($url, $code=302){
		$this->response->add_header('Location', $url);
		$this->end($code);
	}

	public function forward($controller='', $action='index'){
		if(!$controller){
			$class_name = get_class($this);
			$controller = strtolower(preg_replace('/^Controller_/', '', $class_name));
		}
		App::forward($controller, $action);
	}

	public function set_tpl($file){
		if($this->smarty){
			$tpl_file = VIEWS_DIR . '/' . $file . '.tpl';
		} else {
			$tpl_file = VIEWS_DIR . '/' . $file . '.html';
		}
		if(file_exists($tpl_file)){
			$this->tpl_file = $tpl_file;
		}
	}

	public function render(){
		call_user_func(array($this, '_after'));
		if(!$this->tpl_file){
			$this->response->send();
		}
		$data = $this->view->getData();
		if($this->smarty){
			$resp_data = $this->smarty->fetch($this->tpl_file, $data);
			$this->response->send($resp_data);
		}
		ob_start();
		try {
			extract($data);
			include($this->tpl_file);
		} catch (Exception $e) {
			ob_end_clean();
			throw $e;
		}
		$this->response->send(ob_get_clean());
	}
}