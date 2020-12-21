<?php
/**
 * classe abstrata para todos os tipos Cylix_SQL
 */
class Cylix_SQL_Abstract{

   public $_where;
   public $_join;
   public $_orderby;
   public $_limit;
   public $_count;

   public function  __construct() {}

   public function orderBy($criterios){
      $this->_orderby = " ORDER BY ".$this->anti_injection_valor($criterios);
      return $this;
   }
   /**
    * inclui para todos os metodos o limit padrão
    * @param int $limite
    * @return Cylix_SQL_Abstract
    */
   public function limit($limite=1){
      $limite = (int)$limite;
      $this->_limit = " LIMIT ".$this->anti_injection_valor($limite);
      return $this;
   }
   /*#############   metodos dinâmicos   #############*/
   /**
    * Especifica os blocos Where usando sempre o '?'<br/>
    * where("chave LIKE '%?%'","teste")<br/>
    * WHERE chave LIKE '%teste%'
    * @param <string> $condicao
    * @param <mixed> $valor
    * @return Cylix_SQL_Abstract
    */
   public function where($condicao,$valor=null,$tipoOR=false){


      //se vai ter o OR o AND ou ainda não possuir
      if(is_array($valor)){
         $valor = implode(',', $valor);
         $condicao = str_replace('?', $this->anti_injection_valor($valor),$condicao);
      }else{
         $valor = (string)$valor;
         $condicao = str_replace('?', "'".$this->anti_injection_valor($valor)."'",$condicao);//colocando aspas para valor único
      }
      

      if(count($this->_where) < 1){
         $this->_where[] = " ".$condicao;
      }else{
         if($tipoOR){
            $this->_where[] = " OR ".$condicao;
         }else{
            $this->_where[] = " AND ".$condicao;
         }
      }

      return $this;
   }
   
   /**
    * Bloco de JOIN's (sem anti-injection)<br/>
    * " {$tipo}JOIN $juncao ON $on"
    * @param string $juncao tabela
    * @param string $on [campo=campo]
    * @param string $tipo [null,INNER,LEFT,RIGHT]
    * @return Cylix_SQL_Abstract 
    */
   public function join($juncao,$on,$tipo=''){
       $tipo = (strlen($tipo) > 0) ? strtoupper($tipo).' ' : strtoupper($tipo);
       //JOIN categorias AS t2 ON t2.id = j1.categorias_id
      $this->_join[] = " {$tipo}JOIN $juncao ON $on";
      return $this;
   }
   
   /**
    * Trata alguns casos de SQL Injection
    */
   public function anti_injection_valor($valor){
      // remove palavras que contenham sintaxe sql
      $valor = (string)$valor;
      $valor = noQuotes($valor);//tirando aspas do valor
      //$valor = htmlspecialchars(stripslashes($valor));
      $valor = mysql_escape_string($valor);
      return $valor;
   }
   public function getSQL(){
      return $this->__toString();
   }
   
   public function setCount($v){
       $this->_count = $v;
   }
   public function getCount(){
       return $this->_count;
   }
}

?>
