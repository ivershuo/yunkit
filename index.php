<?php
echo '<pre>';

/*初始化*/
require('init.php');

/*简单路由*/
if(!preg_match("/^\/(\w+)\/?(\w*)\/?$/", $_SERVER['REQUEST_URI'], $R)){
	echo 'error';
	exit();
}
/*默认action*/
$R[2] = $R[2] ? $R[2] : DEFAULT_ACTION;

/*加载action*/
$class_name = 'Controller_' . ucfirst($R[1]);
try{
	$C = new $class_name;
	call_user_func(array($C, 'action_' . $R[2]));
} catch(Exception $e){
	echo 'error';
	exit();
}