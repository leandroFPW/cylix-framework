<?php

class Cylix_SQL_Insert extends Cylix_SQL_Abstract {

    private $_into;
    private $_campos;
    private $_values;

    public function __construct($tabela) {
        $this->_into = "INSERT INTO `" . $this->anti_injection_valor($tabela).'`';
        $this->_campos = array();
        $this->_values = array();
        $this->_limit = '';
        $this->_orderby = '';
        $this->_where = array();
        $this->_join = array();
        return $this;
    }

    /**
     * inclui campos depois de INTO 'tabela'
     * @param array $array_campos
     */
    public function fields($array_campos) {
        if(is_array($array_campos)){
            $aux = array();
            foreach($array_campos as $k => $v){
                $aux[$k] = $this->anti_injection_valor($v);
            }
            $campos = implode(',', $aux);
            $this->_campos[] = "($campos)";
        }elseif(is_string($array_campos)){
            $this->_campos[] = "($campos)";
        }
        return $this;
    }

    /**
     * passa os valores
     * Atenção! Não esqueça de chamar function campos($array_campos)
     * @param array $array_valores
     * @param boolean $safe usar anti_sql_injection
     * @return Cylix_SQL_Insert
     */
    public function values($array_valores, $safe=true) {
        $valores = array();
        foreach ($array_valores as $v) {
            if($v===null || strtolower(trim($valor)) === '(null)'){
                $valores[] = "NULL";
            }else{
                $v = ($safe) ? $this->anti_injection_valor($v) : noQuotes($v);
                $valores[] = "'" . $v . "'";
            }
        }
        $valores = implode(',', $valores);
        $this->_values[] = "($valores)";
        return $this;
    }

    /* #############   metodos extras   ############# */

    private function colar() {
        $str = $this->_into . " ";
        
        if (count($this->_campos) >= 1) {
            $aux = implode(',', $this->_campos);
            $str .= $aux;
        }
        if (count($this->_values) >= 1) {
            $aux = implode(',', $this->_values);
            $str .= " VALUES " . $aux;
        }
        if (count($this->_where) >= 1) {
            $aux = implode('', $this->_where);
            $str .= " WHERE " . $aux;
        }

        $str .= $this->_orderby . $this->_limit . ";";
        return $str;
    }

    public function __toString() {
        return $this->colar();
    }

}

?>
