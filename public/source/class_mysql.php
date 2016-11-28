<?php
/*
	[UCenter Home] (C) 2007-2008 Comsenz Inc.
	$Id: class_mysql.php 10484 2008-12-05 05:46:59Z liguode $
*/

if(!defined('IN_UCHOME')) {
	exit('Access Denied');
}

class dbstuff {
	var $querynum = 0;
	var $link;
	var $charset='utf-8';
	function connect($dbhost, $dbuser, $dbpw, $dbname = '', $pconnect = 0, $halt = TRUE) {

		if(!$this->link = mysqli_connect($dbhost, $dbuser, $dbpw)) {
			$halt && $this->halt('Can not connect to MySQL server');
		}
		
		if($this->version() > '4.1') {
			if($this->charset) {
				mysqli_query($this->link, "SET character_set_connection=$this->charset, character_set_results=$this->charset, character_set_client=binary");
			}
			if($this->version() > '5.0.1') {
				mysqli_query($this->link, "SET sql_mode=''");
			}
		}
		if($dbname) {
			mysqli_select_db($this->link, $dbname);
		}
	}

	function escape_string($query) {
		return mysqli_escape_string($this->link, $query);
	}
	
	function select_db($dbname) {
		return mysqli_select_db($this->link, $dbname);
	}

	function fetch_array($query, $result_type = MYSQLI_ASSOC) {
		return mysqli_fetch_array($query, $result_type);
	}

	function query($sql, $type = '') {
		if(D_BUG) {
			global $_SGLOBAL;
			$sqlstarttime = $sqlendttime = 0;
			$mtime = explode(' ', microtime());
			$sqlstarttime = number_format(($mtime[1] + $mtime[0] - $_SGLOBAL['supe_starttime']), 6) * 1000;
		}
		$resultmode = $type == 'UNBUFFERED' ? MYSQLI_USE_RESULT : MYSQLI_STORE_RESULT;
		if(!($query = mysqli_query($this->link, $sql, $resultmode)) && $type != 'SILENT') {
			$this->halt('MySQL Query Error', $sql);
		}
		if(D_BUG) {
			$mtime = explode(' ', microtime());
			$sqlendttime = number_format(($mtime[1] + $mtime[0] - $_SGLOBAL['supe_starttime']), 6) * 1000;
			$sqltime = round(($sqlendttime - $sqlstarttime), 3);

			$explain = array();
			$info = mysqli_info($this->link);
			if($query && preg_match("/^(select )/i", $sql)) {
				$explain = mysqli_fetch_assoc(mysqli_query($this->link, 'EXPLAIN '.$sql));
			}
			$_SGLOBAL['debug_query'][] = array('sql'=>$sql, 'time'=>$sqltime, 'info'=>$info, 'explain'=>$explain);
		}
		$this->querynum++;
		return $query;
	}

	function affected_rows() {
		return mysqli_affected_rows($this->link);
	}

	function error() {
		return (empty($this->link) ?'mysqli_link_empty': mysqli_error($this->link) );
	}

	function errno() {
		return empty($this->link) ?0: intval(mysqli_errno ($this->link) );
	}

	function result($query, $row) {
		mysqli_data_seek($query, $row);
		$f = mysqli_fetch_array( $query );
		return $f[0];
	}

	function num_rows($query) {
		$query = mysqli_num_rows($query);
		return $query;
	}

	function num_fields($query) {
		return mysqli_num_fields($query);
	}

	function free_result($query) {
		return mysqli_free_result($query);
	}

	function insert_id() {
		return ($id = mysqli_insert_id($this->link)) >= 0 ? $id : $this->result($this->query("SELECT last_insert_id()"), 0);
	}

	function fetch_row($query) {
		$query = mysqli_fetch_row($query);
		return $query;
	}

	function fetch_fields($query) {
		return mysqli_fetch_field($query);
	}

	function version() {
		return mysqli_get_server_info($this->link);
	}

	function close() {
		return mysqli_close($this->link);
	}

	function halt($message = '', $sql = '') {
		$dberror = $this->error();
		$dberrno = $this->errno();
		$help_link = "http://faq.comsenz.com/?type=mysql&dberrno=".rawurlencode($dberrno)."&dberror=".rawurlencode($dberror);
		echo "<div style=\"position:absolute;font-size:11px;font-family:verdana,arial;background:#EBEBEB;padding:0.5em;\">
				<b>MySQL Error</b><br>
				<b>Message</b>: $message<br>
				<b>SQL</b>: $sql<br>
				<b>Error</b>: $dberror<br>
				<b>Errno.</b>: $dberrno<br>
				<a href=\"$help_link\" target=\"_blank\">Click here to seek help.</a>
				</div>";
		exit();
	}
}

?>