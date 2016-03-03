<?php

function db_get_array() {
	return call_user_func_array(array('\\Owl\\Database', 'getAll'), func_get_args());
}

function db_get_row() {
	return call_user_func_array(array('\\Owl\\Database', 'getRow'), func_get_args());
}

function db_get_field() {
	return call_user_func_array(array('\\Owl\\Database', 'getOne'), func_get_args());
}

function db_get_fields() {
	return call_user_func_array(array('\\Owl\\Database', 'getCol'), func_get_args());
}

function db_query() {
	return call_user_func_array(array('\\Owl\\Database', 'query'), func_get_args());
}

function db_quote() {
	return call_user_func_array(array('\\Owl\\Database', 'parse'), func_get_args());
}
