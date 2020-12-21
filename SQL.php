<?php
/**
 * Executa SQL de modo geral
 */
class Cylix_SQL {

    /**
     * SELECT passando inicialmente as colunas a serem buscadas
     * @param string $colunas
     * @return Cylix_SQL_Select
     */
    static function select($colunas='*') {
        return new Cylix_SQL_Select($colunas);
    }
    /**
     * update informando a tabela
     * @param string $tabela
     * @return Cylix_SQL_Update
     */
    static function update($tabela) {
        return new Cylix_SQL_Update($tabela);
    }
    /**
     * inserir em uma tabela
     * @param string $tabela
     * @return Cylix_SQL_Insert
     */
    static function insert($tabela) {
        return new Cylix_SQL_Insert($tabela);
    }
    /**
     * deleta registro de uma tabela
     * Lembrar de colocar limit(1)
     * @param string $tabela
     * @return Cylix_SQL_Delete
     */
    static function delete($tabela) {
        return new Cylix_SQL_Delete($tabela);
    }
    /**
     *  Executa a SQL passada em um determinado banco
     * @param Cylix_SQL_Abstract|string $sql
     * @param string $cfg_db qual banco será buscado (especificar no arquivo config-bd-local.php ou config-bd-online.php)
     * @return Cylix_Conexao_Linha|boolean
     */
    static function exec($sql,$table='abstract',$cfg_db='default') {
        $c = new Cylix_DataBase($cfg_db);
        if ($sql instanceof Cylix_SQL_Select || is_string($sql)) {
            $rt = $c->exec($sql,$table);
            $c->end();
            return $rt;
        } else {
            $rt = $c->exec($sql,$table, true); //sem resulset
            $c->end();
            return $rt;
        }
    }
	
	static function parseDate($date,$lang='pt'){
		$res = $date;
		if (strlen($date) > 0) {
            $aux_split = explode(' ', $date);
            $aux = $aux_split[0];
			if($lang=='pt'){
				$aux = explode('/', $aux);
				if (count($aux) == 3) {
					$res = $aux[2] . '-' . $aux[1] . '-' . $aux[0];
					$res = count($aux_split)==2 ? "$res ".$aux_split[1] : $res;
				}
			}
        }
		return $res;
	}
	
	static function parseFloat($number,$lang='pt'){
		$res = $number;
		if (strlen($number) > 0) {
			if($lang=='pt'){
				$search = array(' ','R$','$','.',',');
				$replace = array('','','','','.');
			}
			$res = str_replace($search, $replace, $number);
		}
		return $res;
	}

}