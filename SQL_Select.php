<?php

/**
 * Classe auxiliar para SELECT's
 */
class Cylix_SQL_Select extends Cylix_SQL_Abstract {

    protected $_select;
    public $_from;
    protected $_group;

    /**
     * Instancia o início de um SELECT
     * @param string $colunas
     * @return Cylix_SQL_Select
     */
    public function __construct($colunas='*') {
        if (is_array($colunas)) {
            $colunas = implode(',', $colunas);
        }else{
            $colunas = $colunas ? $colunas : '*';
            $colunas = trim((string)$colunas);
        }
        $this->_select = "SELECT $colunas"; //. $this->anti_injection_valor($colunas);
        $this->_from = '';
        $this->_limit = '';
        $this->_orderby = '';
        $this->_where = array();
        $this->_join = array();
        $this->_group = array();
        return $this;
    }
    /**
     * chama o limit paginado somente para o select, para os demais será sem paginação
     * @param int $limite
     * @param int $pagina
     * @return Cylix_SQL_Select
     */
    public function limit($limite=1, $pagina=0) {
        //limit diferenciado somente para select
        $limite = (int) $limite;
        $pagina = (int) $pagina;
        $this->_limit = " LIMIT " . $this->anti_injection_valor($pagina) . "," . $this->anti_injection_valor($limite);
        return $this;
    }

    /* #############   metodos simples   ############# */

    /**
     * Utilizar em modelos e evitar outros fins
     * @param string $tabela
     * @return Cylix_SQL_Select
     */
    public function from($tabela) {
        $this->_from = $this->anti_injection_valor($tabela);
        return $this;
    }
    
    public function groupBy($field){
        $this->_group[] = $field;
        return $this;
    }

    /* #############   metodos extras   ############# */

    private function colar() {
        $str = $this->_select . " FROM " . $this->_from;
        if (count($this->_join) >= 1) {
            $aux = implode('', $this->_join);
            $str .= $aux;
        }
        if (count($this->_where) >= 1) {
            $aux = implode('', $this->_where);
            $str .= " WHERE " . $aux;
        }
        if (count($this->_group) >= 1) {
            $aux = implode(',', $this->_group);
            $str .= " GROUP BY " . $aux;
        }

        $str .= $this->_orderby . $this->_limit . ";";
        return $str;
    }

    public function __toString() {
        return $this->colar();
    }

    public function  getSQL() {
        if(strpos($this->_select, 'FROM') !== false){//nao encontrado
            return $this->_select;
        }else{
            $select = $this->_select . " FROM " . $this->_from;
            if (count($this->_join) >= 1) {
                $aux = implode('', $this->_join);
                $select .= $aux;
            }
            if (count($this->_where) >= 1) {
                $aux = implode('', $this->_where);
                $select .= " WHERE " . $aux;
            }
            if (count($this->_group) >= 1) {
                $aux = implode(',', $this->_group);
                $select .= " GROUP BY " . $aux;
            }

            $select .= $this->_orderby . $this->_limit . ";";
            return $select;
        }
    }

}

?>
