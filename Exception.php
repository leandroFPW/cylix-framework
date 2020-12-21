<?php
class Cylix_Exception extends Exception{
    
    static $receiver = 'leandro.fpw@gmail.com';

    public function  __construct($msg,$titulo='Erro') {
        $msg = nl2br(htmlentities($msg,ENT_NOQUOTES,'UTF-8'));
        $titulo = htmlentities($titulo,null,'UTF-8');
        $message = '<!-- Exception Content -->
        <div style="font-family: arial; font-size: 12px;">
            <div style="color: red; font-size: 14px;"><b>'.$titulo.'</b></div>
            <div><b>'.$msg.'</b></div>
            <div>'.$this->getFile().'['.$this->getLine().']<div></div></div>
			<div style="color: darkblue; font-size: 8px;">'."http" . (($_SERVER['SERVER_PORT']==443) ? "s://" : "://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'].'</div>
        </div>
        ';

        parent::__construct($message);
    }
    
    static function msg404(Cylix_Exception $e){
        return self::send($e, '404');
    }
    
    static function msg500(Cylix_Exception $e){
        return self::send($e);
    }
    
    static function send(Exception $e,$tipo='500'){
        $msg = $e->getMessage();
        $trace = $e->getTraceAsString();
        $code = date('YmdHis');
        $body = $msg.'<hr/><pre>'.$trace.'</pre>';
        if(ENV == 'online'){
			switch ($tipo){
				case '404': @header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found"); break;
				default : @header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error"); break;
			}
			if($tipo != '404'){
				//trace pra um arquivo txt
				$fp = fopen(SYS_PATH."tmp/log/$code.txt", "a");
				fwrite($fp, $trace);
				fclose($fp);
				//mensagem vai pro email
				$reponsavel = self::$receiver;
				if($reponsavel == null){
					$reponsavel = 'erros@ferramentasparaweb.com.br';
				}
				$flag = SYS_PATH . 'tmp/terminal/no-notify';//se existir este cara, não é para notificar
				if(!file_exists($flag)){
					Cylix_Mailer::sendFast($reponsavel, 'Application Error - '.$_SERVER['HTTP_HOST'], $body, 'sistema@'.$_SERVER['HTTP_HOST'], true);
				}
			}
            //template
            ob_start();
            include INDEX_PATH."$tipo.html";//var_dump($r);
            return(ob_get_clean());
        }else{
            return $body;
        }
    }
    
    static function show($e){
        if(!($e instanceof Cylix_Exception)){
            $e = new self($e->getMessage(),'Erro imprevisto');
        }
        die(self::send($e));
    }

}
