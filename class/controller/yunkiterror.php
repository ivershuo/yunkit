<?php
class Controller_Yunkiterror extends Controller{
	public function _before(){
		parent::_before();
		$this->smarty = null;
		$this->auto_render = false;
	}

	public function error_500(){
		$body = $this->_html('Internal Server Error', $this->get_ctx_data('error_str'));
		$this->response->close(500, '', $body);
	}

	public function error_404(){
		$body = $this->_html('Not Found', $this->get_ctx_data('error_str'));
		$this->response->close(404, '', $body);
	}

	public function __call($method, $arguments){
		$valid = preg_match('/^error_(\d+)$/i', $method, $ret);
		if($valid){
			$code = (int) $ret[1];
			if(array_key_exists($code, Modules_Response::$messages)){
				$title = Modules_Response::$messages[$code];
			}
		}
		if(isset($title) && $title){
			$body = $this->_html($title, $this->get_ctx_data('error_str'));
			$this->response->close($code, '', $body);
		} else {
			$this->error_500();
		}
	}

	private function _html($title='Error', $body=''){
		ob_start();
		$body = DEBUG && $body ? '<div><pre>' . $body . '</pre></div><hr>' : '';
		$version = App::VERSION;
echo <<<EOF
			<!doctype html>
			<html>
				<head>
					<meta charset="utf-8">
					<title>$title</title>
				</head>
				<body>
					<h1 style="text-align:center">$title</h1>
					<hr>
					$body
					<p style="text-align:center">By: Yunkit $version</p>
				</body>
			</html>
EOF;
		return ob_get_clean();
	}
}