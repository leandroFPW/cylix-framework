<?php

/**
 * Invocador de Helpers (Classes Auxiliares)
 */
class Cylix_Helper {


    public function __call($name, $arguments) {
        $nome = Cylix_App::camelcase($name);
        $arq = SYS_PATH . 'helpers/H_' . $nome . '.php';
        if (file_exists($arq)) {
            require_once($arq);
            $classe = $nome . 'Helper'; //FormatarHelper
            $param = '';
            if(count($arguments) > 0){
                $param = array();
                for($i=0;$i<count($arguments);$i++){
                    $param[] = '$arguments['.$i.']';
                }
                $param = implode(',', $param);
            }
            eval("\$inst = new $classe($param);");
            return $inst; #instacia
        } else {
            throw new Cylix_Exception("Helper not found: " . $arq, 'Include error');
        }/**/
    }

}

?>
