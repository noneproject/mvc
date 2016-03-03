<?php

use Owl\Database;
use Owl\View;
use Owl\Controller;

function fn_init() {
	session_start();
	require_once(__ROOT__.'/config.php');
	fn_load_all();
	Database::setConfig($db_conf);
	$controller = new Controller();
	View::showPage($controller->run_controller, $controller->run_mode);
	/*$result = ob_get_clean();
	file_put_contents(__ROOT__.'/log.html', $result);*/
}

function fn_print_r() {
	$vars = func_get_args();
	if (!empty($vars)) {
		echo '<ul style="background: #ccc;">';
		foreach ($vars as $var) {
			echo '<li><pre>';
			print_r($var);
			echo '</pre></li>';
		}
		echo '</ul>';
	}
}

function fn_print_die() {
	call_user_func_array('fn_print_r', func_get_args());
	exit;
}

function fn_load_by_dir($dir) {
	$files = scandir($dir);

	foreach ($files as $file) {
		if (is_file($dir.'/'.$file)) {
			require_once($dir.'/'.$file);
		}
	}
}

function fn_load_owl_classes() {
	fn_load_by_dir(__ROOT__.'/app/Owl');
}

function fn_load_default_functions() {
	fn_load_by_dir(__ROOT__.'/app/functions');
}

function fn_load_all() {
	fn_load_owl_classes();
	fn_load_default_functions();
}

function fn_echo($var, $nofilter = false) {
	if (isset($var)) {
		echo (!$nofilter) ? htmlspecialchars($var) : $var;
	} else {
		echo 'херь';
		return false;
	}
}