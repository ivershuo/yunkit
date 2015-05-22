<?php
class Modules_View{
	public function __construct(){
		$this->data = array();
		$this->var_cache = array();
	}

	public function assign($key, $value=true, $nocache=false){
		$this->data[$key] = $value;
		$this->var_cache[$key] = $nocache;
	}

	public function getData(){
		return $this->data;
	}

	public function getVarCacheSets(){
		return $this->var_cache;
	}
}