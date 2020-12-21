<?php
class Cylix_SQL_Delete extends Cylix_SQL_Abstract{

   private $_delete;

   public function __construct($tabela){
      $this->_delete = "DELETE FROM `".$this->anti_injection_valor($tabela).'`';
      $this->_limit='';
      $this->_orderby='';
      $this->_where=array();
      $this->_join=array();
      return $this;
   }
   
   /*#############   metodos extras   #############*/
   private function colar(){
       $str = $this->_delete . " ";
      
      if(count($this->_where) >= 1){
         $aux = implode('', $this->_where);
         $str .= "WHERE ".$aux;
      }

      $str .= $this->_limit.";";
      return $str;
   }
   public function  __toString() {
      return $this->colar();
   }
}
?>
