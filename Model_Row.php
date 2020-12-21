<?php
/* pasta: sys/models/row/M_Abstract_Row.php */
/**
 * Classe basica caso nao tenha nenhum modelo associado
 */
class Cylix_Model_Row extends stdClass {

    protected $_array;
    protected $_table;
    protected $_pk;
    protected $_cfg_db;
    protected $_post;
	protected $_affected_rows;
	protected $_columns;
	/**
	 * auto convert to put value using convertTo method
	 * @example $_convert = array( 'datetime' => array('date_to','date_from'), 
	 *								'int' => array('status'), 
	 *								'null' => array('test')
	 *							);
	 * @var array 
	 */
	protected $_convert;

	const TYPE_SAVE_UPDATE = 'update';
    const TYPE_SAVE_INSERT = 'insert';
    const TYPE_SAVE_FIND = 'find';
    /**
     * todos os plugins
     * @var Cylix_Plugin 
     */
    public $plugin;
    
    public function __construct($table='',$PK='id',$cfg_db='default',$cols=array()) {
        $this->_table = $table;
        $this->_pk = $PK;
        $this->_cfg_db = $cfg_db;
        $this->_post = array();
		$this->_columns = $cols;
        if(count($cols) > 0){
            foreach($cols as $col){
                $this->_array[$col] = null;
            }
        }else{
            $this->_array = array();
        }
		$this->view->plugin = $this->plugin = ($plugin) ? $plugin : new Cylix_Plugin();//invocador de plugins
    }

    public function __toString() {
        return 'Cylix_Model_Row possui varios campos (has many fields)';
    }
    public function setInArray($chave,$valor,$include=false){
        $this->_array[$chave] = $valor;
		if($include && !in_array($chave,$this->_columns)){
			//include in allowed column
			$this->_columns[] = $chave;
		}
    }
    
    public function get($coluna){
        return $this->_array[$coluna];
    }
    public function __get($name) {
        if(!in_array($name, array('_array','_table','_pk','_cfg_db'))){
            //se nao for os privados pode seguir o get da classe
            return $this->get($name);
        }else{
            return parent::__get($name);
        }
        
    }
    public function __set($name, $value) {
        if(!in_array($name, array('_array','_table','_pk','_cfg_db'))){
            $this->setInArray($name, $value);
        }else{
            parent::__set($name, $value);
        }
    }
	
	function setAffectedRows($int){
		$this->_affected_rows = $int;
	}
	function getAffectedRows(){
		return $this->_affected_rows;
	}
    
	/**
	 * @deprecated since 1.5.1
	 * @return string 
	 */
    public function getTable(){
        return $this->tableName();
    }
	/**
	 * @deprecated since 1.5.1
	 * @return string 
	 */
    public function getPK(){
        return $this->pkName();
    }
	
	public function toArray(){
		return $this->_array;
	}
    /**
     * returns model table
     * @return Cylix_Model 
     */
    public function getModelTable(){
        $model = getModelTableName(str_replace('_', '-', $this->_table));
        eval("\$model = $model::me();");
        return $model;
    }
    /**
     * beforeSave
     * @param string $type TYPE_SAVE_UPDATE, TYPE_SAVE_INSERT, etc
     * @param string $model model name
     * @param array $post current post from the past put()
     * @param Cylix_Model $model objeto
     */
    public function beforeSave($type=null,$model=null,$post=array()){}
    /**
     * afterSave
     * @param string $pk 'id', 'chave','my_id', etc
     * @param mixed $return retorno de exec($sql)
     * @param string $type TYPE_SAVE_UPDATE, TYPE_SAVE_INSERT, etc
     * @param array $c_values current values
     * @param array $post current post from the past put()
     */
    public function afterSave($pk=null,$return=null,$type=null,$c_values=array(),$post=array()){}

    /**
     * Salva o objeto
     * @return boolean retorno do sql->exec
     */
    public function save($safe=true){
        $pk = $this->_pk;
        //getting model
        $model = $this->getModelTable();
		
        if($this->{$pk} > 0 || (is_string($this->{$pk}) && strlen($this->{$pk}) > 0)){
            //update
            $this->beforeSave(self::TYPE_SAVE_UPDATE,$model,$this->_post);
            $values = array();
            foreach($this->_array as $k=>$v){
                if($v!==null && $k != $pk && in_array($k, $this->_columns)){
                    $values[$k] = strtolower(trim($v)) === '(null)' ? null : $v;
					$this->_array[$k] = strtolower(trim($v)) === '(null)' ? '' : $v;
                }
            }
            //update
            $sql = $model->update($this->_table)->set($values, $safe)->where($pk.' = ?',$this->{$pk});
            $r = $model->exec($sql);
            if(is_int($r) && $r >=0){
                $r =true;
            }
            $this->afterSave($this->{$pk},$r,self::TYPE_SAVE_UPDATE,$this->_array,$this->_post);
        }else{
            //insert
            $this->beforeSave(self::TYPE_SAVE_INSERT,$model,$this->_post);
            $c_v = $this->_array;
            foreach($c_v as $c => $v){
				if(in_array($c, $this->_columns)){//somente os permitidos - v1.5.1
					$array_campos[] = $c;
					$array_valores[] = strtolower(trim($v)) === '(null)' ? null : $v;
					$this->_array[$k] = strtolower(trim($v)) === '(null)' ? '' : $v;
				}
            }
            //insert
            $sql = $model->insert($this->_table)->fields($array_campos)->values($array_valores,$safe);
            $r = $model->exec($sql);
            if(is_int($r) && $r >=0){
                $r =true;
            }
            //se inseriu, entao teve primary key gerada
            $this->{$pk} = Cylix_DataBase::$ID;
            $this->afterSave($this->{$pk},$r,self::TYPE_SAVE_INSERT,$this->_array,$this->_post);
        }
        return $r;
    }
    /**
     * configura chave/valor no array interno<br>
     * put the values in internal array<br>
     * @example $row->put($post);
     * @param array $values 
     * @param array $ignore array de chaves a serem ignoradas (keys ignored)
     */
    public function put($values=array(),$ignore=array()){
        $this->_post = $values;
		if(is_array($this->_convert)){
			foreach($this->_convert as $type => $fields){
				$name = Cylix_App::camelcase($type, true);
				eval("\$this->_convertTo$name(\$values,\$fields);");//passagem por referencia
			}
		}
        foreach($values as $k => $v){
            if(array_key_exists($k, $this->_array) && (is_numeric($v) || is_string($v) || $v === null)){
                if(!in_array($k, $ignore)){
                    $this->setInArray($k, $v);
                }
            }
        }
    }
    /**
     * delete row from table
     * @return boolean
     */
    public function destroy(){
        $ret = false;
        if($this->{$this->_pk} > 0){
            $m = $this->getModelTable();
            $sql = $m->delete()->where($this->_pk.' = ?',$this->{$this->_pk});
            if($m->exec($sql)){
                $ret = true;
            }
        }
        return $ret;
    }
    /**
     * retorna as linhas de uma associação N-N (has_and_belongs_to_many) seguindo a conveção <br>
     * join1_join2 - onde a ordem deve respeitar a ordem alfabética<br>
     * Ex.: areas_paginas, areas_categorias, categorias_paginas, etc.
     * @param Cylix_Model_Row $row_t1 
     * @param Cylix_Model $t2 
     * @param string $fields campos da t1
     * @return Cylix_Model_Row array de rows
     * @example 
       function categorias(){<br>
        return parent::assocNtoN($this,  CategoriasModel::me());<br>
       }
     */
    public function assocNtoN(Cylix_Model_Row $row_t1,Cylix_Model $t2,$fields=null,$where='',$order='',$limit=null){
        $pk2 = $t2->pkName();
        $cfg_db = $t2->_cfg_db;
        $pk1 = $row_t1->pkName();
        $t1 = $row_t1->tableName();
        $t2 = $t2->tableName();
        $j1 = array($t1,$t2);
        sort($j1);//convensão: areas_categs, testes_saveds
        $j1 = implode('_', $j1);
        if($fields==null){
            $fields = $t2.'.*';
        }
        $where = (strlen($where) > 0) ? ' '.$where : '';
        //SELECT t2.* FROM areas_categorias AS j1 JOIN categorias AS t2 ON t2.id = j1.categorias_id WHERE j1.areas_id = $row_t1->{$pk1}
        $sql = Cylix_SQL::select($fields)->from($j1)->join($t2, $t2.'.'.$pk2.' = '.$j1.".{$t2}_$pk2")->where($j1.'.'.$t1.'_'.$pk1.' = '.$row_t1->{$pk1}.$where);
        if(strlen($order)){
            $sql->orderBy($order);
        }
		if($limit){
            $sql = $sql->limit($limit);
        }
        return Cylix_SQL::exec($sql, $t2, $cfg_db);
    }
    /**
     * retorna as linhas de uma associação 1-N (has_many)<br>
     * ex.: 1 galeria (t1) -> N fotos (t2)<br>
     * convenções: areas_id, categorias_id, etc.
     * @param Cylix_Model_Row $row_t1
     * @param Cylix_Model $t2
     * @param string $fields campos da t2
     * @param string $where complemento depois do where
     * @return Cylix_Model_Row array de rows
     */
    function assoc1toN(Cylix_Model_Row $row_t1,Cylix_Model $t2,$fields=null,$where='',$order=null,$limit=null){
        $pk2 = $t2->pkName();
        $cfg_db = $t2->_cfg_db;
        $pk1 = $row_t1->pkName();
        $t1 = $row_t1->tableName();
        $t2 = $t2->tableName();
        if($fields==null){
            $fields = $t2.'.*';
        }
		if(strlen($where) > 0){
			$where = ' '.$where;
		}else{
			$where='';
		}
        //SELECT t2.* FROM t2 WHERE t2.t1_{$pk2} = $row_t1->{$pk1}
        $sql = Cylix_SQL::select($fields)->from($t2)->where($t2.'.'.$t1.'_'.$pk2.' = '.$row_t1->{$pk1}.$where);
        if($order){
            $sql = $sql->orderBy($order);
        }
		if($limit){
            $sql = $sql->limit($limit);
        }
        return Cylix_SQL::exec($sql, $t2, $cfg_db);
    }
    /**
     * retorna a linha de uma associação N-1 (belongs to)<br>
     * ex.: N fotos (t1) -> 1 galeria (t2)<br>
     * convenções: areas_id, categorias_id, etc.
     * @param Cylix_Model_Row $row_t1
     * @param Cylix_Model $t2
     * @param string $fields campos da t2
     * @param string $where complemento depois do where
     * @param string $t1_alias alias para t1
     * @param string $t2_alias alias para t2
     * @return Cylix_Model_Row 
     */
    function assocNto1(Cylix_Model_Row $row_t1,Cylix_Model $t2,$fields=null,$where='',$order=null,$limit=null,$t1_alias='',$t2_alias=''){
        //SELECT galerias.* FROM fotos JOIN galerias ON galerias.id = fotos.galerias_id WHERE fotos.id = 2;
        $pk2 = $t2->pkName();
        $cfg_db = $t2->_cfg_db;
        $pk1 = $row_t1->pkName();
        $t1 = $row_t1->tableName();
        $t2 = $t2->tableName();
        if($fields==null){
            $fields = $t2.'.*';
        }
		if(strlen($t1_alias)){
			$t1_aux = ' '.$t1_alias;
		}else{
			$t1_alias = $t1;
		}
		if(strlen($t2_alias)){
			$t2_aux = ' '.$t2_alias;
		}else{
			$t2_alias = $t2;
		}
        $where = (strlen($where) > 0) ? ' '.$where : '';
        //SELECT t2.* FROM t1 JOIN t2 ON t2.$pk2 = t1.t2_$pk2 WHERE t1.$pk1 = $row_t1->{$pk1};
        $sql = Cylix_SQL::select($fields)->from($t1.$t1_aux)->join($t2.$t2_aux, $t2_alias.'.'.$pk2.' = '.$t1_alias.'.'.$t2.'_'.$pk2)->where($t1_alias.'.'.$pk1.' = '.$row_t1->{$pk1}.$where);
		
		if($order){
            $sql = $sql->orderBy($order);
        }
		if($limit){
            $sql = $sql->limit($limit);
        }
		
		//return (string)$sql;
        $rows = Cylix_SQL::exec($sql, $t2, $cfg_db);
        return $rows[0];
    }
    //added after 1.5.1
	function tableName($name=null){
		if($name){
			$this->_table = $name;
			return $this;
		}else{
			return $this->_table;
		}
	}
	
	function pkName($name=null){
		if($name){
			$this->_pk = $name;
			return $this;
		}else{
			return $this->_pk;
		}
	}
	//added after 1.7.0
	
	protected function _convertToNull(&$arrayValues=array(),$columns=null){
		if(is_array($arrayValues)){
			if(is_array($columns)){
				foreach($columns as $c){
					$arrayValues[$c] = ($arrayValues[$c]=='') ? '(null)' : $arrayValues[$c];
				}
			}else{
				$arrayValues[$columns] = ($arrayValues[$columns]=='') ? '(null)' : $arrayValues[$columns];
			}
		}
	}
	
	protected function _convertToDatetime(&$arrayValues=array(),$columns=null){
		if(is_array($arrayValues)){
			dateToSQL((array)$columns,$arrayValues);
		}
	}
	
	protected function _convertToFloat(&$arrayValues=array(),$columns=null){
		if(is_array($arrayValues)){
			if(is_array($arrayValues)){
				moneyToSQL((array)$columns,$arrayValues);
			}
		}
	}

}