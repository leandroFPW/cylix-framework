<?php

class Cylix_SQL_Update extends Cylix_SQL_Abstract {

    private $_update;
    private $_set;

    public function __construct($tabela) {
        $this->_update = "UPDATE `" . $this->anti_injection_valor($tabela).'`';
        $this->_set = array();
        $this->_limit = '';
        $this->_orderby = '';
        $this->_where = array();
        $this->_join = array();
        return $this;
    }

    /**  seta os valores sem a necessidade de colocar ' '
     * @param array $arrayV array associativo ('coluna'=>'valor')
     * @return Cylix_SQL_Update
     * @example set(array('C1'=>'C1+1','C2'=>'2','C3'=>"'texto'||C2")) <-EQUIVALE-> SET C1=C1+1, C2=2, C3='texto'||C2
     */
    public function set($arrayV = array(), $seguro = true) {
        if (is_array($arrayV) && count($arrayV)) {
            $arrayVC = '';
            $i = 0;
            foreach ($arrayV as $chave => $valor) {
                if ($valor === null) {
                    if ($i == 0) {
                        $arrayVC = $chave . ' = NULL';
                    } else {
                        $arrayVC .= ', ' . $chave . ' = NULL';
                    }
                } else {
                    $valor = ($seguro) ? $this->anti_injection_valor($valor) : $valor;//noQuotes($valor);
                    if ($i == 0) {
                        $arrayVC = $chave . ' = \'' . $valor . "'";
                    } else {
                        $arrayVC .= ', ' . $chave . ' = \'' . $valor . "'";
                    }
                }
                $i++;
            }
            $this->_set[] = $arrayVC;
        }
        return $this;
    }

    /* #############   metodos extras   ############# */

    private function colar() {
        $str = $this->_update . " ";
        if (count($this->_set) >= 1) {
            $aux = implode(',', $this->_set);
            $str .= ' SET ' . $aux;
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
