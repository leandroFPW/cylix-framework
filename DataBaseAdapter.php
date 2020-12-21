<?php
class Cylix_DataBaseAdapter {
    
    const MYSQL = 'mysql';
    const POSTGRESQL = 'pg';
	const CHARSET_LATIN1 = 'latin1';
	const CHARSET_UTF8 = 'utf8';
	
	const RS_ASSOC = 1;
	const RS_NUMERIC = 2;
	const RS_BOTH = 0;
    
    private $_tipo;
    
    function __construct($type='mysql') {
        $this->_tipo = $type;
    }
    
    function connect($server, $username, $password, $database,$port=null,$charset=null){
        $link = null;
        if($this->_tipo == self::POSTGRESQL){
            $aux = "host=$server dbname=$database user=$username password=$password";
            if($port){
                $aux .= " port=$port";
            }
			$charset = ($charset)?strtoupper($charset):'UTF8';
            $link = pg_connect("$aux options='--client_encoding=$charset'");
        }else{
            if($port){
                $server .= ":$port";
            }
            $link = mysql_connect($server, $username, $password);
            mysql_select_db($database, $link);
			$charset = ($charset)?$charset:'utf8';
            mysql_set_charset($charset, $link);
        }
        return $link;
    }
    
    function query($query,$link=null){
        $result = null;
        if($this->_tipo == self::POSTGRESQL){
            $result = pg_query($link,$query);
        }else{
            $result = mysql_query($query, $link);
        }
        return $result;
    }
    
    function insertId($link=null){
        $id = 0;
        if($this->_tipo == self::POSTGRESQL){
            $id = pg_last_oid($link);
        }else{
            $id = mysql_insert_id($link);
        }
        return $id;
    }
	
	function fetchArray($result=null,$result_type=0){
		if($this->_tipo == self::POSTGRESQL){
            switch ($result_type){
				case self::RS_ASSOC: $type=PGSQL_ASSOC;
					break;
				case self::RS_NUMERIC: $type=PGSQL_NUM;
					break;
				default : $type=PGSQL_BOTH;
					break;
			}
            $array = pg_fetch_array($result, $type);
        }else{
			switch ($result_type){
				case self::RS_ASSOC: $type=MYSQL_ASSOC;
					break;
				case self::RS_NUMERIC: $type=MYSQL_NUM;
					break;
				default : $type=MYSQL_BOTH;
					break;
			}
            $array = mysql_fetch_array($result, $type);
        }
        return $array;
	}
	
	function fetchObject($result=null){
		if($this->_tipo == self::POSTGRESQL){
            $obj = pg_fetch_object($result);
        }else{
            $obj = mysql_fetch_object($result);
        }
        return $obj;
	}
    
    function affectedRows($link=null){
        $n_rows = 0;
        if($this->_tipo == self::POSTGRESQL){
            $n_rows = pg_affected_rows($link);
        }else{
            $n_rows = mysql_affected_rows($link);
        }
        return $n_rows;
    }
    
    function error($link=null){
        if($this->_tipo == self::POSTGRESQL){
            $erro = pg_errormessage($link);
        }else{
            $erro = mysql_error($link);
        }
        return $erro;
    }
    
    function close($link=null){
        $ret = 0;
        if($this->_tipo == self::POSTGRESQL){
            $ret = pg_close($link);
        }else{
            $ret = mysql_close($link);
        }
        return $ret;
    }
    
    function startTransaction(&$link=null){
        if($this->_tipo == self::POSTGRESQL){
            pg_query($link,'BEGIN;');
        }else{
            mysql_query('SET autocommit = 0;', $link);
            mysql_query('START TRANSACTION;', $link);
        }
    }
}
?>
