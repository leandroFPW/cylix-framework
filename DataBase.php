<?php

class Cylix_DataBase {

    private $_server;
    private $_username;
    private $_password;
    private $_database;
    private $_link;
    private $_adapter;
    private $_port;
	private $_charset;
	private static $_CONFIG_BD;
    static $ID;
    static $LastError;

    /**
     * Realiza todas as conexões por meio do tipo de configuração
     * o default é 'padrao' mas podendo expecificar outros bancos
     * caso for local basta configurar em config-bd-local.php
     * @filesource
     * @param string $cfg_db chave do array vinda de $_CONFIG_BD
     */
    public function __construct($cfg_db='default') {

        //obtêm dados do banco de acordo com o tipo de ambiente
        $file = SYS_PATH . '/db/connections/' . ENV . '.php';
        if (file_exists($file)) {
            include $file;
        } else {
            throw new Cylix_Exception($file . ' not found.', 'connections');
        }

        $this->_server = $_CONFIG_BD[$cfg_db]['server'];

        $this->_username = $_CONFIG_BD[$cfg_db]['user'];
        $this->_password = $_CONFIG_BD[$cfg_db]['pass'];
        $this->_database = $_CONFIG_BD[$cfg_db]['db'];
        $this->_port = $_CONFIG_BD[$cfg_db]['port'];
		$this->_charset = strtolower($_CONFIG_BD[$cfg_db]['charset']);
        
        $adapter = (isset($_CONFIG_BD[$cfg_db]['adapter'])) ? $_CONFIG_BD[$cfg_db]['adapter'] : Cylix_DataBaseAdapter::MYSQL;
        $adapter = $this->_adapter = new Cylix_DataBaseAdapter($adapter);
		
		self::$_CONFIG_BD = $_CONFIG_BD;
		
        try{
            $this->_link = $this->_adapter->connect($this->_server, $this->_username, $this->_password,$this->_database,$this->_port,$this->_charset);
        }  catch (Exception $e){
            throw new Cylix_Exception("database {$this->_database} error in {$this->_server}. Favor conferir o array de configuração<br/>".$e->getMessage(), 'Database Error(check the config array)');
        }
		if(!$this->_link){
			throw new Cylix_Exception("Base {$this->_database} error in {$this->_server}<br/>".  mysql_error(), 'Database connection is null');
		}
		
    }

    /**
     * Executa SQL informada por uma classe Cylix_SQL_Abstract
     * @param string tabela para puxar a row (geralmente eh o nome da tabela ou junção)
     * @param Cylix_SQL_Abstract $sql
     * @param boolean $semRes semRes eh usado quando nao precisa de linhas de resultado, soh executar
     * @return Cylix_Conexao_Linha|boolean
     */
    public function exec(&$sql, $tabela = 'abstract', $semRes=false) {/* semRes eh usado quando nao precisa de linhas de resultado, soh executar */

        if (is_object($sql)) {
            $query = $sql->__toString();
        } else {
            $query = (string) $sql;
        }

        $r = array();
        $adapt = $this->_adapter;
        $result = $adapt->query($query, $this->_link);
        self::$ID = $adapt->insertId($this->_link);
        self::$LastError = $erro = $adapt->error($this->_link);
        if(strlen($erro)){
            //trace pra um arquivo txt
            $code = date('Ymd_His');
            $fp = fopen(SYS_PATH."tmp/log/query_$code.log", "a");
            fwrite($fp, "----\n$query\nErro para $tabela: $erro\n----\n");
            fclose($fp);
        }
        
        if ($result) {
			$arq = getModelTableFile('abstract', true); //busca a row na camada model
			if (file_exists($arq)) {
				require_once $arq;
			}
			$arquivo = getModelTableFile($tabela); //table
			$fw_model=true;//vai usar model padrao do framework
			if (file_exists($arquivo) && $tabela != 'abstract') {
				require_once $arquivo; //GaleriasModel::me()->newRow()
				$classe = getModelTableName($tabela); //table
				$fw_model=false;
			}
			if(is_bool($result) || $semRes){
				//sem resultset
                $a_r = $adapt->affectedRows($this->_link);
				if (!$fw_model) {
					eval("\$linha = {$classe}::me()->newRow();");
				} else {
					$linha = new Cylix_Model_Row($tabela);
				}
				$linha->setAffectedRows($a_r);//linhas afetadas
				$r = $linha;//linha em branco
			}else{
				//com resultset
				while ($cada = $adapt->fetchArray($result, 1)) {//Cylix_DataBaseAdapter::RS_ASSOC
					if (!$fw_model) {
						eval("\$linha = {$classe}::me()->newRow();");
						if($linha===null){
							$linha = new Cylix_Model_Row($tabela);
						}
					} else {
						$linha = new Cylix_Model_Row($tabela);
					}
					
					foreach ($cada as $chave => $valor) {
						$linha->setInArray($chave, $valor,true);
					}
					$r[] = $linha;
				}
			}
			
        } else {
            throw new Cylix_Exception($adapt->error($this->_link) . "\n" . $query, "Ocorreu um erro na execução de sua SQL (SQL excecution error)");
        }

        return $r;
    }

    /**
     * START TRANSACTION 
     * @return void
     */
    public function start() {
        $this->_adapter->startTransaction($this->_link);
    }

    public function commit() {
        $this->_adapter->query('COMMIT;', $this->_link);
        $this->_adapter->close($this->_link);
    }

    public function rollback() {
        $this->_adapter->query('ROLLBACK;', $this->_link);
        $this->_adapter->close($this->_link);
    }

    public function end() {
        @$this->_adapter->close($this->_link);
        unset($this->_link);
    }
	
	static function getConfigDB(){
		return self::$_CONFIG_BD;
	}

}
