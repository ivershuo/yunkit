<?php
class Log {
	const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

	private static $_instance;

	private $log_levels = array(
		Log::EMERGENCY => LOG_EMERG,
		Log::ALERT     => LOG_ALERT,
		Log::CRITICAL  => LOG_CRIT,
		Log::ERROR     => LOG_ERR,
		Log::WARNING   => LOG_WARNING,
		Log::NOTICE    => LOG_NOTICE,
		Log::INFO      => LOG_INFO,
		Log::DEBUG     => LOG_DEBUG,
	);

	public $default_permissions = 0777;
	public $date_format = 'Y-m-d G:i:s.u';

	public function __construct($level_base, $log_dir) {
		$this->level_base = $level_base;
		$log_dir = rtrim($log_dir, '\\/');
		if (!file_exists($log_dir)) {
			mkdir($log_dir, $this->default_permissions, true);
		}
		$this->log_file = $log_dir . DIRECTORY_SEPARATOR . 'log_' . date('Y-m-d') . '.txt';
		if (file_exists($this->log_file) && !is_writable($this->log_file)) {
			throw new RuntimeException('日志文件不可写：' . $this->log_file);
		}
		$this->_f = fopen($this->log_file, 'a');
		if (!$this->_f) {
			throw new RuntimeException('日志文件打开读写失败：' . $this->log_file);
		}
	}

	public function __destruct() {
		if ($this->_f) {
			fclose($this->_f);
		}
	}

	public static function instance($level_base = Log::DEBUG, $log_dir = '') {
		if (Log::$_instance === NULL) {
			if(!$log_dir){
				$log_dir = APP_PATH . '/logs';
			}
			Log::$_instance = new Log($level_base, $log_dir);
		}
		return Log::$_instance;
	}

	public function log($level, $message, array $context = array()) {
		if ($this->log_levels[$this->level_base] < $this->log_levels[$level]) {
			return;
		}
		$message = $this->format_message($level, $message, $context);        
		$this->write($message);
	}

	public function write($message) {
		if (!is_null($this->_f)) {
			if (fwrite($this->_f, $message) === false) {
				throw new RuntimeException('写日志失败：' . $this->log_file);
			}
		}
	}

	private function format_message($level, $message, $context) {
		$level = strtoupper($level);
		$ip = get_ip();
		if(is_array($message) && empty($context)){
			$context = $message;
			$message = '';
		}
		if (!empty($context)) {
			$message .= PHP_EOL.$this->indent(context_to_string($context));
		}
		return "[{$this->getTimestamp()}] [{$ip}] [{$level}] {$message}".PHP_EOL;
	}

	private function indent($string, $indent = '    ') {
		return $indent . str_replace("\n", "\n" . $indent, $string);
	}

	private function getTimestamp() {
		$originalTime = microtime(true);
		$micro = sprintf("%06d", ($originalTime - floor($originalTime)) * 1000000);
		$date = new DateTime(date('Y-m-d H:i:s.'.$micro, $originalTime));

		return $date->format($this->date_format);
	}

	public static function debug($message, array $context = array()){
		Log::$_instance->log(Log::DEBUG, $message, $context);
	}

	public static function info($message, array $context = array()){
		Log::$_instance->log(Log::INFO, $message, $context);
	}

	public static function notice($message, array $context = array()){
		Log::$_instance->log(Log::NOTICE, $message, $context);
	}

	public static function warning($message, array $context = array()){
		Log::$_instance->log(Log::WARNING, $message, $context);
	}

	public static function error($message, array $context = array()){
		Log::$_instance->log(Log::ERROR, $message, $context);
	}

	public static function critical($message, array $context = array()){
		Log::$_instance->log(Log::CRITICAL, $message, $context);
	}

	public static function alert($message, array $context = array()){
		Log::$_instance->log(Log::ALERT, $message, $context);
	}

	public static function emergency($message, array $context = array()){
		Log::$_instance->log(Log::EMERGENCY, $message, $context);
	}
}