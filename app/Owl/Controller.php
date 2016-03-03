<?php

namespace Owl;

class Controller
{
	public $run_controller 	= 'index';
	public $run_mode 		= 'index';
	private $request 		= array();

	const DIR = '/app/controllers/';

	function __construct() {
		if (!empty($_GET['mode'])) {
			@list($controller, $mode) = explode('.', $_GET['mode']);
		}

		//$this->request = $this->

		$this->run_controller = empty($controller) ? 'index' : $controller;
		$this->run_mode = empty($mode) ? 'index' : $mode;

		$this->runInit();

		$res = $this->runController($this->run_controller, $this->run_mode);
		if (is_array($res)) {
			if ($res[0] == STATUS_NO_PAGE) {
				View::showStatusNoPage();
			} elseif ($res[0] == STATUS_REDIRECT) {
				$url = empty($res[1]) ? '' : $res[1];
				header("Location: ".$url);
			} elseif ($res[0] == STATUS_DENY) {
				View::showStatusDeny();
			}
		}
	}

	function runController($controller, $mode) {
		$c = $this->CheckController($controller);
		if ($c) {
			return include(__ROOT__.self::DIR.FRONTTYPE.'/'.$controller.'.php');
		} else {
			View::showStatusNoPage();
		}
	}

	function checkController($controller) {
		$res = false;
		if (is_file(__ROOT__.self::DIR.FRONTTYPE.'/'.$controller.'.php')) {
			$res = true;
		}

		return $res;
	}

	function runInit() {
		if (is_file(__ROOT__.self::DIR.FRONTTYPE.'/init.php')) {
			$mode = $this->run_mode;
			include(__ROOT__.self::DIR.FRONTTYPE.'/init.php');
		} else {
			View::show503();
		}
	}

}