<?php
class Modules_View{
	public function __construct(){
		$this->data = array();
	}

	public function assign($key, $value=true){
		$this->data[$key] = $value;
	}

	public function getData(){
		return $this->data;
	}
}