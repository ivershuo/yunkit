<?php
/**
* Just for use exception code.
* 10-99 : for lib core
* 100-599 : for http
* 1000-9999 : for app
*/
class Yunkit_Exception extends Exception {
	public function __construct($code = 0, $message = '', Exception $previous = NULL) {
		if(is_array($message)){
			$message = context_to_string($message);
		}
		parent::__construct($message, (int) $code, $previous);
	}
}