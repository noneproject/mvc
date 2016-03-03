<?php

use Owl\View;

if ($this->run_mode == 'index') {
	View::set('name', 'John');
}