<?php

/*
 * Classe base para qualquer modelo
 * indica-se fazer um M_Abstract extendendo esta classe
 */

class Cylix_Model extends stdClass {

    public static $_instance;

    /**
     * Chave primária da tabela (id por padrão)
     * @var string
     */
    protected $_pk;
    protected $_table;
    public $_cfg_db;
    protected $_columns;
	protected $_is_ORM;

    /**
     * @example
     * array(
     *  'nome' => array(
     *      'required' => array(true,'Nome obrigatório'),
     *      'min' => array(3,'Nome precisa de ter no mínimo {{param}} letras')
     *      ),
     *  'cpf' => array(
     *      'required' => array(true,'Campo de name = {{name}} é obrigatório'),
     *      'c-p-f' => array(null,'CPF inválido')
     *      ),
     *  'sku' => array('regex'=>array('/[^0-9]/','SKU em formato incorreto.')),
	 *  'email' => array('uniq'=>array(true,'E-mail já cadastrado no sistema'))
     * )
     * @var array fields & type validation
     */
    protected $_validate;
    public $validation_msg;
    /**
     * todos os plugins
     * @var Cylix_Plugin 
     */
    public $plugin;

    /**
     * construir no modelo:
     * 
     * static function me() {
      $tabela = 'tabela';
      if(!isset(self::$_instance[$tabela])){
      self::$_instance[$tabela] = new self($tabela);
      }
      return self::$_instance[$tabela];
     * }
     */
    static function me() {
        
    }

	/**
	 *
	 * @param type $tabela table
	 * @param type $PKs primary key (id is default)
	 * @param type $cfg_db key from array in sql/connections
	 * @param type $is_orm this model is a ORM (Object-relational mapping)
	 */
    function __construct($tabela, $PKs = 'id', $cfg_db = 'default',$is_orm=true) {
		$this->view->plugin = $this->plugin = ($plugin) ? $plugin : new Cylix_Plugin();//invocador de plugins
		if($is_orm){
			//definfindo a chave primaria e a tabela
			$this->_table = $tabela;
			$this->_pk = $PKs;
			$this->_cfg_db = $cfg_db;
			$this->_columns = array();
			//buscando as colunas da tabela

			$colunas = Cylix_SQL::exec("SHOW COLUMNS FROM " . $this->_table . ';', 'abstract', $cfg_db);
			foreach ($colunas as $cada) {
				$this->_columns[] = $cada->Field;
			}
		}
    }

    /**
     * Instancia o início de um SELECT
     * @param string|array $colunas
     * @return Cylix_SQL_Select
     */
    function select($colunas = '*') {
        return Cylix_SQL::select($colunas)->from($this->_table);
    }

    /**
     * chama o delete para a tabela
     * Lembrar de colocar limit(1)
     * @return Cylix_SQL_Delete
     */
    function delete() {
        return Cylix_SQL::delete($this->_table);
    }

    /**
     * chamada do insert sem especificar campos
     * @return Cylix_SQL_Insert
     */
    function insert() {
        return Cylix_SQL::insert($this->_table);
    }

    /**
     * metodo de alterações update da tabela
     * @return Cylix_SQL_Update
     */
    function update() {
        return Cylix_SQL::update($this->_table);
    }

    /**
     * Busca rápida de todos os registros, podendo ordenar
     * @param string $orderBy colunas e nivelamento (titulo,id DESC)
     * @return array|boolean
     * @example Cylix_Modelo::eu()->tudo('id DESC')
     */
    function all($orderBy = null) {
        $sql = $this->select();
        if ($orderBy) {
            $sql->orderBy($orderBy);
        }
        return $sql;
    }

    /**
     * 'Busca r�pida pela chave prim�ria (uma chave somente)'
     * @param mixed $PK integer|array
     * @return Cylix_Model_Row|boolean
     */
    function find($PKs = 0) {
        $m_pk = $this->pkName();
        if (is_array($PKs)) {
            $sql = $this->select();
            foreach ($PKs as $i => $k) {
                $sql = $sql->where($m_pk[$i] . ' = ?', $k);
            }
        } else {
            $sql = $this->select()->where($m_pk . ' = ?', $PKs);
        }
        return $sql;
    }
	/**
     * Busca r��da por coluna especifica
     * @param array $key_value chave=>valor
     * @param boolean $return_first retorna row(true)|lista(false)
     * @return type
     */
	function findBy($key_value=array(),$return_first=true){
		$res=null;
		$sql = $this->select();
		foreach ($key_value as $k => $v) {
			$sql = $sql->where($k . ' = ?', $v);
		}
		$res = $this->exec($sql, Cylix_Model_Row::TYPE_SAVE_FIND);
		if(is_array($res) && $return_first)
			return $res[0];
		else
			return $res;
	}

    /**
     * beforeRemove
     * @param int $PK id a ser removido
     */
    function beforeRemove($PK = 0) {
        
    }

    /**
     * Remove diretamente um registro  pela sua PK
     * @param int $PK
     * @return boolean
     */
    function remove($PK = 0) {
        $this->beforeRemove($PK);
        $ret = $this->delete()->where($this->pkName() . ' = ?', $PK)->limit(1);
        $this->afterRemove($ret);
        return $ret;
    }
    /**
     * afterRemove
     * @param boolean $return 
     */
    function afterRemove($return = null) {
        
    }
    /**
     * beforeCreate : M_Modelo::me()->create
     * @param array $valores
     */
    function beforeCreate(&$valores = null) {
        
    }

    /**
     * criação de registro por meio de um array associativo [array('coluna'=>'valor')]
     * @param array $valores
     * @return boolean
     * @example M_Modelo::eu()->criar(array('titulo'=>'teste','autor'=>'Cylix'))
     */
    function create($valores, $seguro = true) {
        $this->beforeCreate($valores);
        $campos = $values = array(); //coletor de colunas
        //procurar em $valores tds os possiveis campos da tabela
        foreach ($valores as $k => $v) {
            if (in_array($k, $this->_columns)) {
                $campos[] = $k;
                $v = noQuotes($v);
                $values[] = ($v == null) ? '' : $v;
            }
        }

        $sql = $this->insert()
                ->fields($campos)
                ->values($values, $seguro);

        return $sql;
    }

    /**
     * afterCreate
     * @param boolean $ret 
     */
    function afterCreate($ret = null) {
        
    }
    /**
     * beforeAlter $model->alter
     * @param int $PK numero/varchar
     * @param array $valores valores em array = ao criar()
     */
    function beforeAlter(&$PK = 0, &$valores = array()) {
        
    }

    /**
     * por meio de uma PK(definida no modelo) altera-se os valores
     * @param int $PK numero/varchar
     * @param array $valores valores em array = ao criar()
     * @param boolean $seguro flag para ativar/desativar o anti-sql-injection
     * @return boolean execução do script
     */
    function alter($PK = 0, $valores = array(), $seguro = true) {
        $this->beforeAlter($PK, $valores);
        $sets = array(); //coletor de colunas e valores
        //procurar em $valores tds os possiveis campos da tabela
        foreach ($valores as $k => $v) {
            if (in_array($k, $this->_columns)) {
                $sets[$k] = noQuotes($v);
            }
        }
        $sql = $this->update()
                ->set($sets, $seguro)
                ->where($this->_pk . ' = ?', $PK);
        //die($sql);
        return $sql;
    }
    /**
     * afterAlter
     * @param boolean $return 
     */
    function afterAlter($return = null) {
        
    }

    /**
     * novo objeto (linha)
     * @return Cylix_Model_Row Row 
     */
    function newRow() {
        $arq = getModelTableFile('abstract', true); //busca a row na camada model
        if (file_exists($arq)) {
            require_once $arq;
        }
        $arq = getModelTableFile($this->_table, true); //busca a row na camada model
        if (file_exists($arq)) {
            require_once $arq;
            $class = getModelTableName($this->_table, true);
        } else {
            $class = 'Cylix_Model_Row';
        }
		$pks = $this->_pk;
        eval("\$i = new {$class}('{$this->_table}',\$pks,'{$this->_cfg_db}',\$this->_columns);");
        
        return $i;
    }

    /**
     * verificação de valor único
     * @param string $col coluna
     * @param mixed $val
     * @param mixed $exc excessão(ões) (caso precise verificar todos os registros menos estes) direto ou array
     * @return int 
     */
    function uniq($col, $val, $exc = null) {
        $sql = $this->select($this->_pk)->where("$col = ?", $val)->limit(2);
        if ($exc) {
            if (is_array($exc)) {
                $sql->where($this->_pk . ' NOT IN (?)', $exc);
            } else {
                $sql->where($this->_pk . ' <> ?', $exc);
            }
        }
        return count($this->exec($sql)) >= 2;
    }

    /**
     * before Exec SQL
     * @param mixed $sql query string ou objeto SELECT, por exemplo
     * @param mixed $type tipo, tabela, etc
     */
    function beforeExec(&$sql, $type = null) {
        
    }

    /**
     * executa o objeto/string contendo a string, para o banco determinado e caso tenha retorno, retornará a row da tabela
     * @param mixed $sql
     * @param mixed $type tipo, tabela, etc
     * @return mixed 
     */
    function exec($sql, $type = null) {
        $this->beforeExec($sql, $type);
        $ret = Cylix_SQL::exec($sql, $this->_table, $this->_cfg_db);
        $this->afterExec($ret, $type);
        return $ret;
    }

    /**
     * after Exec SQL
     * @param boolean $return
     * @param mixed $type tipo, tabela, etc
     */
    function afterExec($return = null, $type = null) {}

    static function required($post, $chaves = array()) {
        $return = true;
        foreach ($chaves as $cada) {
            if (isset($post[$cada])) {
                $valor = trim($post[$cada]);
                if ($valor == null || strlen($valor) < 1) {
                    $return = false;
                }
            }
        }

        return $return;
    }

    /**
     * testa se é valido o post
     * @param array $post dados do fprmulario
     * @param array $array null: todos do modelo| true: array_keys do $post | array: especificos do modelo
     * @return boolean para exibir os erros, basta acessar getErros()
     */
    function isValid($post, $array = null) {
        $ret = true;
        if (is_array($array)) {
            //especificado
            $campos = $array;
        } elseif($array === true) {
            //procurar o modelo
            $campos = @array_keys($post);
        } else {
            //procurar o modelo
            $campos = @array_keys($this->_validate);
        }
        if (count($campos)) {
            //lets rock!
            $array_validation = $this->_validate;
            $c='';
            foreach ($campos as $key) {
                if (isset($array_validation[$key])) {
                    $validation = $array_validation[$key]; //contem um array cujas chaves serão os tipos e o valor terá parâmetro,mensagem
                    foreach ($validation as $tipo => $params) {
                        $tipo = Cylix_App::camelcase($tipo, false);
                        $valor = $post[$key];
                        eval("\$avaliacao = \$this->check{$tipo}(\$valor,\$params[0],\$key);");
                        if (!$avaliacao) {
                            $ret = false;
                            $msg = $params[1];
                            if (!$msg) {
                                $msg = "Campo {{name}} está inválido";
                            }
                            $msg = str_replace(array('{{name}}', '{{param}}'), array($key, $params[0]), $msg);
                            $this->validation_msg[] = Cylix_I18n::t($msg);
                        }
                    }
                }
            }
        }
        
        return $ret;
    }

    function getErrorValidation($bl = '<br/>') {
        return @implode($bl, $this->validation_msg);
    }

    ################
    #   check functions
    ################

    /**
     * Campo obrigatório
     * @param mixed $val valor avaliado
     * @param boolean $param retornar sem inverter o retorno?
     * @return boolean
     */
    function checkRequired($val, $param, $key = null) {
        $ret = ($val !== null && trim($val) != '');
        if ($param === false) {
            $ret = !$ret;
        }//flip
        return $ret;
    }

    /**
     * Mínimo de caracteres
     * @param mixed $val valor avaliado
     * @param int $param mínimo
     * @return boolean
     */
    function checkMin($val, $param, $key = null) {
        return strlen($val) >= ((int) $param);
    }

    /**
     * Validação RegEx - pelo menos 1 ocorrência, será válido
     * @param mixed $val valor avaliado
     * @param boolean $param pattern/expressão
     * @param null $key nulo
     * @return boolean 
     */
    function checkRegex($val, $param, $key = null) {
        return preg_match($param, $val) > 0;
    }

    /**
     * Valida se o valor deste campo é único na tabela
     * @param mixed $val valor avaliado
     * @param mixed $param true: usar a chave $key como campo| outro: campo especificado
     * @param type $key chave do array sob avaliação (post)
     * @return boolean 
     */
    function checkUniq($val, $param, $key = null) {
        $key = ($param == true && $key != null) ? $key : $param;
        return $this->uniq($key, $val) == 0;
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
	/**
	 * GET/SET primary key(s)
	 * @param null|string $name null is GET | string is SET
	 * @return mixed 
	 */
	function pkName($name=null){
		if($name){
			$this->_pk = $name;
			return $this;
		}else{
			return $this->_pk;
		}
	}

}
