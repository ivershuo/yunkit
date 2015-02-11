<?php
class Modules_Response{
	protected $def_cb  = 'cb';
	protected $headers = array();

	public static $messages = array(
		// Informational 1xx
		100 => 'Continue',
		101 => 'Switching Protocols',
		// Success 2xx
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		// Redirection 3xx
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found', // 1.1
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		// 306 is deprecated but reserved
		307 => 'Temporary Redirect',
		// Client Error 4xx
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		// Server Error 5xx
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		509 => 'Bandwidth Limit Exceeded'
	);

	public function __construct(){
	}

	public function add_header($k, $v){
		$this->headers[$k] = $v;
	}

	public function add_status_header($code=200){
		if(array_key_exists($code, Modules_Response::$messages)){
			$msg = Modules_Response::$messages[$code];
		}
		$msg = isset($msg) ? $msg : 'Unknow';
		$this->add_header('Status', $code . ' ' . $msg);
	}

	public function send($body=''){
		if(empty($this->headers['Status'])){
			$this->add_status_header(200);
		}
		App::write($body, $this->headers);
		unset($this->headers);
		unset($body);
	}	

	public function json($data){
		$this->add_header('Content-Type', 'application/json');
		$this->send(json_encode($data));
		unset($data);
	}

	public function jsonp($data, $cb=''){
		$cb = preg_replace('/[^a-zA-Z0-9_.]/', '', $cb);
		if(empty($cb)){
			return $this->json($data);
		}
		$this->add_header('Content-Type', 'application/x-javascript');
		$data = $cb . '(' . json_encode($data) . ')';
		$this->send($data);
		unset($data);
	}

	public function close($code = 500, $msg = '', $body = ''){
		if(empty($msg)){
			$this->add_status_header($code);
		} else {
			$this->add_header('Status', $code . ' ' . $msg);
		}	
		$this->send($body ? $body : $msg);
		unset($body);
	}
}