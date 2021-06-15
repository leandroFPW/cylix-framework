<?php
class Cylix_Request{
    /**
     * parametros vindos da requisição
     * @param string $p
     * @return mixed 
     */
    public function getParam($p) {
        if (isset($_GET[$p])) {
            return $_GET[$p];
        } elseif (isset($_POST[$p])) {
            return $_POST[$p];
        } elseif (isset($_FILES[$p])) {
            return $_FILES[$p];
        } else {
            return null;
        }
    }
    /**
     * retorna todos os valores de $_POST
     * @return array com chave => valor
     */
    public function getPost(){
        $array=array();
        foreach ($_POST as $k => $v){
            $array[$k] = $v;
        }
        if(isset($_FILES)){
            foreach ($_FILES as $k => $v){
                $array[$k] = $v;
            }
        }

        return $array;
    }
}