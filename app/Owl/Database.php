<?php

namespace Owl;

class Database
{

	private static $conn;
	private static $stats;
	private static $emode;
	private static $exname;
	private static $prefix;

	private static $params = array(
		'host'      => 'localhost',
		'user'      => 'root',
		'pass'      => '',
		'db'        => 'test',
		'port'      => NULL,
		'socket'    => NULL,
		'pconnect'  => FALSE,
		'charset'   => 'utf8',
		'prefix'	=> 'owl_',
		'errmode'   => 'exception',
		'exception' => 'Exception',
	);

	const RESULT_ASSOC = MYSQLI_ASSOC;
	const RESULT_NUM   = MYSQLI_NUM;

	public static function setConfig($conf) {
		self::$params = array_merge(self::$params,$conf);
	}

	public static function connect() {

		self::$emode  = self::$params['errmode'];
		self::$exname = self::$params['exception'];

		self::$prefix = self::$params['prefix'];

		if (isset(self::$params['mysqli'])) {
			if (self::$params['mysqli'] instanceof mysqli) {
				self::$conn = self::$params['mysqli'];
				return;
			} else {
				self::error("mysqli option must be valid instance of mysqli class");
			}
		}

		if (self::$params['pconnect']) {
			self::$params['host'] = "p:".self::$params['host'];
		}

		@self::$conn = mysqli_connect(self::$params['host'], self::$params['user'], self::$params['pass'], self::$params['db'], self::$params['port'], self::$params['socket']);
		if ( !self::$conn ) {
			self::error(mysqli_connect_errno()." ".mysqli_connect_error());
		}

		mysqli_set_charset(self::$conn, self::$params['charset']) or self::error(mysqli_error(self::$conn));
	}

	/**
	 * Conventional function to run a query with placeholders. A mysqli_query wrapper with placeholders support
	 * 
	 * Examples:
	 * Database::query("DELETE FROM table WHERE id=?i", $id);
	 *
	 * @param string $query - an SQL query with placeholders
	 * @param mixed  $arg,... unlimited number of arguments to match placeholders in the query
	 * @return resource|FALSE whatever mysqli_query returns
	 */
	public static function query() {
		return self::rawQuery(self::prepareQuery(func_get_args()));
	}

	/**
	 * Conventional function to fetch single row. 
	 * 
	 * @param resource $result - myqli result
	 * @param int $mode - optional fetch mode, RESULT_ASSOC|RESULT_NUM, default RESULT_ASSOC
	 * @return array|FALSE whatever mysqli_fetch_array returns
	 */
	public static function fetch($result,$mode=self::RESULT_ASSOC) {
		return mysqli_fetch_array($result, $mode);
	}

	/**
	 * Conventional function to get number of affected rows. 
	 * 
	 * @return int whatever mysqli_affected_rows returns
	 */
	public static function affectedRows() {
		return mysqli_affected_rows (self::$conn);
	}

	/**
	 * Conventional function to get last insert id. 
	 * 
	 * @return int whatever mysqli_insert_id returns
	 */
	public static function insertId() {
		return mysqli_insert_id(self::$conn);
	}

	/**
	 * Conventional function to get number of rows in the resultset. 
	 * 
	 * @param resource $result - myqli result
	 * @return int whatever mysqli_num_rows returns
	 */
	public static function numRows($result) {
		return mysqli_num_rows($result);
	}

	/**
	 * Conventional function to free the resultset. 
	 */
	public static function free($result) {
		mysqli_free_result($result);
	}

	/**
	 * Helper function to get scalar value right out of query and optional arguments
	 * 
	 * Examples:
	 * $name = Database::getOne("SELECT name FROM table WHERE id=1");
	 * $name = Database::getOne("SELECT name FROM table WHERE id=?i", $id);
	 *
	 * @param string $query - an SQL query with placeholders
	 * @param mixed  $arg,... unlimited number of arguments to match placeholders in the query
	 * @return string|FALSE either first column of the first row of resultset or FALSE if none found
	 */
	public static function getOne() {
		$query = self::prepareQuery(func_get_args());
		if ($res = self::rawQuery($query)) {
			$row = self::fetch($res);
			if (is_array($row)) {
				return reset($row);
			}
			self::free($res);
		}
		return FALSE;
	}

	/**
	 * Helper function to get single row right out of query and optional arguments
	 * 
	 * Examples:
	 * $data = Database::getRow("SELECT * FROM table WHERE id=1");
	 * $data = Database::getOne("SELECT * FROM table WHERE id=?i", $id);
	 *
	 * @param string $query - an SQL query with placeholders
	 * @param mixed  $arg,... unlimited number of arguments to match placeholders in the query
	 * @return array|FALSE either associative array contains first row of resultset or FALSE if none found
	 */
	public static function getRow() {
		$query = self::prepareQuery(func_get_args());
		if ($res = self::rawQuery($query)) {
			$ret = self::fetch($res);
			self::free($res);
			return $ret;
		}
		return FALSE;
	}

	/**
	 * Helper function to get single column right out of query and optional arguments
	 * 
	 * Examples:
	 * $ids = Database::getCol("SELECT id FROM table WHERE cat=1");
	 * $ids = Database::getCol("SELECT id FROM tags WHERE tagname = ?s", $tag);
	 *
	 * @param string $query - an SQL query with placeholders
	 * @param mixed  $arg,... unlimited number of arguments to match placeholders in the query
	 * @return array|FALSE either enumerated array of first fields of all rows of resultset or FALSE if none found
	 */
	public static function getCol() {
		$ret   = array();
		$query = self::prepareQuery(func_get_args());
		if ( $res = self::rawQuery($query) ) {
			while($row = self::fetch($res)) {
				$ret[] = reset($row);
			}
			self::free($res);
		}
		return $ret;
	}

	/**
	 * Helper function to get all the rows of resultset right out of query and optional arguments
	 * 
	 * Examples:
	 * $data = Database::getAll("SELECT * FROM table");
	 * $data = Database::getAll("SELECT * FROM table LIMIT ?i,?i", $start, $rows);
	 *
	 * @param string $query - an SQL query with placeholders
	 * @param mixed  $arg,... unlimited number of arguments to match placeholders in the query
	 * @return array enumerated 2d array contains the resultset. Empty if no rows found. 
	 */
	public static function getAll() {
		$ret   = array();
		$query = self::prepareQuery(func_get_args());
		if ( $res = self::rawQuery($query) ) {
			while($row = self::fetch($res)) {
				$ret[] = $row;
			}
			self::free($res);
		}
		return $ret;
	}

	/**
	 * Helper function to get all the rows of resultset into indexed array right out of query and optional arguments
	 * 
	 * Examples:
	 * $data = Database::getInd("id", "SELECT * FROM table");
	 * $data = Database::getInd("id", "SELECT * FROM table LIMIT ?i,?i", $start, $rows);
	 *
	 * @param string $index - name of the field which value is used to index resulting array
	 * @param string $query - an SQL query with placeholders
	 * @param mixed  $arg,... unlimited number of arguments to match placeholders in the query
	 * @return array - associative 2d array contains the resultset. Empty if no rows found. 
	 */
	public static function getInd() {
		$args  = func_get_args();
		$index = array_shift($args);
		$query = self::prepareQuery($args);

		$ret = array();
		if ( $res = self::rawQuery($query) ) {
			while($row = self::fetch($res)) {
				$ret[$row[$index]] = $row;
			}
			self::free($res);
		}
		return $ret;
	}

	/**
	 * Helper function to get a dictionary-style array right out of query and optional arguments
	 * 
	 * Examples:
	 * $data = Database::getIndCol("name", "SELECT name, id FROM cities");
	 *
	 * @param string $index - name of the field which value is used to index resulting array
	 * @param string $query - an SQL query with placeholders
	 * @param mixed  $arg,... unlimited number of arguments to match placeholders in the query
	 * @return array - associative array contains key=value pairs out of resultset. Empty if no rows found. 
	 */
	public static function getIndCol() {
		$args  = func_get_args();
		$index = array_shift($args);
		$query = self::prepareQuery($args);

		$ret = array();
		if ( $res = self::rawQuery($query) ) {
			while($row = self::fetch($res)) {
				$key = $row[$index];
				unset($row[$index]);
				$ret[$key] = reset($row);
			}
			self::free($res);
		}
		return $ret;
	}

	/**
	 * Function to parse placeholders either in the full query or a query part
	 * unlike native prepared statements, allows ANY query part to be parsed
	 * 
	 * useful for debug
	 * and EXTREMELY useful for conditional query building
	 * like adding various query parts using loops, conditions, etc.
	 * already parsed parts have to be added via ?p placeholder
	 * 
	 * Examples:
	 * $query = Database::parse("SELECT * FROM table WHERE foo=?s AND bar=?s", $foo, $bar);
	 * echo $query;
	 * 
	 * if ($foo) {
	 *     $qpart = Database::parse(" AND foo=?s", $foo);
	 * }
	 * $data = Database::getAll("SELECT * FROM table WHERE bar=?s ?p", $bar, $qpart);
	 *
	 * @param string $query - whatever expression contains placeholders
	 * @param mixed  $arg,... unlimited number of arguments to match placeholders in the expression
	 * @return string - initial expression with placeholders substituted with data. 
	 */
	public static function parse() {
		return self::prepareQuery(func_get_args());
	}

	/**
	 * function to implement whitelisting feature
	 * sometimes we can't allow a non-validated user-supplied data to the query even through placeholder
	 * especially if it comes down to SQL OPERATORS
	 * 
	 * Example:
	 *
	 * $order = Database::whiteList($_GET['order'], array('name','price'));
	 * $dir   = Database::whiteList($_GET['dir'],   array('ASC','DESC'));
	 * if (!$order || !dir) {
	 *     throw new http404(); //non-expected values should cause 404 or similar response
	 * }
	 * $sql  = "SELECT * FROM table ORDER BY ?p ?p LIMIT ?i,?i"
	 * $data = Database::getArr($sql, $order, $dir, $start, $per_page);
	 * 
	 * @param string $iinput   - field name to test
	 * @param  array  $allowed - an array with allowed variants
	 * @param  string $default - optional variable to set if no match found. Default to false.
	 * @return string|FALSE    - either sanitized value or FALSE
	 */
	public static function whiteList($input,$allowed,$default=FALSE) {
		$found = array_search($input,$allowed);
		return ($found === FALSE) ? $default : $allowed[$found];
	}

	/**
	 * function to filter out arrays, for the whitelisting purposes
	 * useful to pass entire superglobal to the INSERT or UPDATE query
	 * OUGHT to be used for this purpose, 
	 * as there could be fields to which user should have no access to.
	 * 
	 * Example:
	 * $allowed = array('title','url','body','rating','term','type');
	 * $data    = Database::filterArray($_POST,$allowed);
	 * $sql     = "INSERT INTO ?n SET ?u";
	 * Database::query($sql,$table,$data);
	 * 
	 * @param  array $input   - source array
	 * @param  array $allowed - an array with allowed field names
	 * @return array filtered out source array
	 */
	public static function filterArray($input,$allowed) {
		foreach(array_keys($input) as $key ) {
			if ( !in_array($key,$allowed) ) {
				unset($input[$key]);
			}
		}
		return $input;
	}

	/**
	 * Function to get last executed query. 
	 * 
	 * @return string|NULL either last executed query or NULL if were none
	 */
	public static function lastQuery() {
		$last = end(self::$stats);
		return $last['query'];
	}

	/**
	 * Function to get all query statistics. 
	 * 
	 * @return array contains all executed queries with timings and errors
	 */
	public static function getStats() {
		return self::$stats;
	}

	/**
	 * private function which actually runs a query against Mysql server.
	 * also logs some stats like profiling info and error message
	 * 
	 * @param string $query - a regular SQL query
	 * @return mysqli result resource or FALSE on error
	 */
	private function rawQuery($query) {
		$start = microtime(TRUE);
		$res   = mysqli_query(self::$conn, $query);
		$timer = microtime(TRUE) - $start;

		self::$stats[] = array(
			'query' => $query,
			'start' => $start,
			'timer' => $timer,
		);
		if (!$res) {
			$error = mysqli_error(self::$conn);
			
			end(self::$stats);
			$key = key(self::$stats);
			self::$stats[$key]['error'] = $error;
			self::cutStats();
			
			self::error("$error. Full query: [$query]");
		}
		self::cutStats();
		return $res;
	}

	private function prepareQuery($args) {
		$query = '';
		$raw   = array_shift($args);
		$raw = str_replace('?:', self::$prefix, $raw);
		$array = preg_split('~(\?[ndsiuap])~u',$raw,null,PREG_SPLIT_DELIM_CAPTURE);
		$anum  = count($args);
		$pnum  = floor(count($array) / 2);
		if ( $pnum != $anum ) {
			self::error("Number of args ($anum) doesn't match number of placeholders ($pnum) in [$raw]");
		}

		foreach ($array as $i => $part) {
			if ( ($i % 2) == 0 ) {
				$query .= $part;
				continue;
			}

			$value = array_shift($args);
			switch ($part) {
				case '?n':
					$part = self::escapeIdent($value);
					break;
				case '?s':
					$part = self::escapeString($value);
					break;
				case '?i':
					$part = self::escapeInt($value);
					break;
				case '?a':
					$part = self::createIN($value);
					break;
				case '?u':
					$part = self::createSET($value);
					break;
				case '?p':
					$part = $value;
					break;
				case '?d':
					$part = self::escapeDec($value);
			}
			$query .= $part;
		}
		self::connect(self::$params);
		return $query;
	}

	private function escapeInt($value) {
		if ($value == INF) {
			$value = PHP_INT_MAX;
		}

		return $value + 0;
	}

	private function escapeDec($value) {
		if ($value == INF) {
			$value = PHP_INT_MAX;
		}

		$value = (float) $value;
		$value = round($value, 2);

		return $value;
	}

	private function escapeString($value) {
		if ($value === NULL) {
			return 'NULL';
		}

		return	"'".mysqli_real_escape_string(self::$conn,$value)."'";
	}

	private function escapeIdent($value) {
		if ($value) {
			return "`".str_replace("`","``",$value)."`";
		} else {
			self::error("Empty value for identifier (?n) placeholder");
		}
	}

	private function createIN($data) {
		if (!is_array($data)) {
			self::error("Value for IN (?a) placeholder should be array");
			return;
		}

		if (!$data) {
			return 'NULL';
		}

		$query = $comma = '';
		foreach ($data as $value) {
			$query .= $comma.self::escapeString($value);
			$comma  = ",";
		}

		return $query;
	}

	private function createSET($data)
	{
		if (!is_array($data))
		{
			self::error("SET (?u) placeholder expects array, ".gettype($data)." given");
			return;
		}
		if (!$data)
		{
			self::error("Empty array for SET (?u) placeholder");
			return;
		}
		$query = $comma = '';
		foreach ($data as $key => $value)
		{
			$query .= $comma.self::escapeIdent($key).'='.self::escapeString($value);
			$comma  = ",";
		}
		return $query;
	}

	private function error($err)
	{
		$err  = __CLASS__.": ".$err;

		if ( self::$emode == 'error' )
		{
			$err .= ". Error initiated in ".self::scaller().", thrown";
			trigger_error($err,E_USER_ERROR);
		} else {
			throw new self::$exname($err);
		}
	}

	private function caller()
	{
		$trace  = debug_backtrace();
		$caller = '';
		foreach ($trace as $t)
		{
			if ( isset($t['class']) && $t['class'] == __CLASS__ )
			{
				$caller = $t['file']." on line ".$t['line'];
			} else {
				break;
			}
		}
		return $caller;
	}

	/**
	 * On a long run we can eat up too much memory with mere statsistics
	 * Let's keep it at reasonable size, leaving only last 100 entries.
	 */
	private function cutStats()
	{
		if ( count(self::$stats) > 100 )
		{
			reset(self::$stats);
			$first = key(self::$stats);
			unset(self::$stats[$first]);
		}
	}
}
