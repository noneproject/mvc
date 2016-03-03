<?php

namespace Owl;

class View
{
	private static $vars 		= array();
	private static $theme 		= 'basic';
	private static $admin_theme = 'basic';

	public static function set($name, $value) {
		if (isset($name) && isset($value)) {
			self::$vars[$name] = $value;
			return true;
		}

		return false;
	}

	public static function get($name) {
		if (!empty($name)) {
			if (isset(self::$vars[$name])) {
				return self::$vars[$name];
			}
		}
		return NULL;
	}

	public static function showHeader() {

	}

	public static function showFooter() {
		
	}

	public static function showPage($controller, $mode) {
		include(__ROOT__.'/views/'.FRONTTYPE.'/'.self::$theme.'/templates/index/head.php');
		include(__ROOT__.'/views/'.FRONTTYPE.'/'.self::$theme.'/templates/'.$controller.'/'.$mode.'.php');
		include(__ROOT__.'/views/'.FRONTTYPE.'/'.self::$theme.'/templates/index/footer.php');
	}

	public static function showStatusNoPage() {
		header("HTTP/1.0 404 Not Found");
		fn_print_die('404 Not found');
	}

	public static function showStatusDeny() {
		header("HTTP/1.0 403 Forbidden");
		fn_print_die('403 Forbidden');
	}

	public static function show503() {
		header("HTTP/1.0 503 Service not available");
		fn_print_die('503 Service not available');
	}
}
