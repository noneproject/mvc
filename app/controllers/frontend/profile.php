<?php

use Owl\View;

if ($mode == 'login') {
	View::set('q', 'logo<script>alert(123)</script>');
}