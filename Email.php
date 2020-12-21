<?php
class Cylix_Email{

    const PLAIN = 'text/plain';
    const HTML = 'text/html';
    const CHARSET_ISO = 'iso-8859-1';
    const CHARSET_UTF8 = 'UTF-8';

    /**
     * destinatários
     * @var array
     */
    public $para;
    /**
     * Assunto
     * @var string
     */
    public $assunto;
    public $mensagem;
    public $de;
    public $responder_para;
    public $tipo;
    public $cc;
    public $charset;
    /**
     * arquivo para anexo
     * @var $_FILES
     */
    public $anexo;

    /**
     * Classe para envio de mensagem online ou offline
     * Mensagens offline, favor colocar o caminho da pasta (www/email por padrao)
     * @param string $assunto assunto sem precisar de acessar as variaveis
     * @param string $msg mensagem sem precisar de acessar as variaveis
     * @example
     * $mail = new Cylix_Email('email sem assunto','mensagem html contendo <html><body> ...');<br/>
     * $mail->para('contato@teste.com.br');<br/>
     * $mail->para('suporte@teste.com.br')<br/>
     * $mail->de('testador@teste.com.br','Testador Oficial');<br/>
     * $mail->assunto = 'mudei o assunto';<br/>
     * $mail->tipo = Cylix_Email::PLAIN;<br/>
     * $mail->mensagem = 'agora eh texto simples';<br/>
     * $mail->cc('testador@teste.com.br');<br/>
     * $mail->resposta('testador@teste.com.br','Testador Oficial');<br/>
     * $mail->charset = Cylix_Email::CHARSET_UTF8;<br/>
     * $mail->pasta_off = 'emails'; //pasta public_html/emails<br/>
     * $mail->enviar();
     */
    public function  __construct($assunto='email sem assunto',$msg='sem conteudo') {
        $this->para = array();
        $this->assunto = $assunto;
        $this->mensagem=$msg;
        $this->tipo = self::HTML;
        $this->de = array();
        $this->responder_para = array();
        $this->cc = array();
        $this->charset = self::CHARSET_UTF8;
        $this->pasta_off = 'email';
        $this->anexo = false;
    }

    function  to($email='excessao@ferramentasparaweb.com.br',$nome=null) {
        if($nome){
            $this->para[] = '"'.$nome.'" <'.$email.'>';
        }else{
            $this->para[] = $email;
        }
        return $this;
    }
    function  from($email='excessao@ferramentasparaweb.com.br',$nome=null) {
        if($nome){
            $this->de[] = '"'.$nome.'" <'.$email.'>';
        }else{
            $this->de[] = $email;
        }
        return $this;
    }
    function  replyTo($email='excessao@ferramentasparaweb.com.br',$nome=null) {
        if($nome){
            $this->responder_para[] = '"'.$nome.'" <'.$email.'>';
        }else{
            $this->responder_para[] = $email;
        }
        return $this;
    }
    function  cc($email='excessao@ferramentasparaweb.com.br',$nome=null) {
        if($nome){
            $this->cc[] = '"'.$nome.'" <'.$email.'>';
        }else{
            $this->cc[] = $email;
        }
        return $this;
    }
    /**
     * Arquivo $_FILES para anexo
     * @param $_FILES[] $arquivo
     */
    function file($file){
        $this->anexo = $file;
    }
    /**
     * monta o corpo da mensagem, seja ela html ou nao
     * @param type $msg 
     */
    private function msg($msg){
        $msg = ($this->charset == self::CHARSET_UTF8) ? $msg : utf8_decode($msg);
        if($this->tipo == self::HTML){
            $assunto = ($this->charset == self::CHARSET_UTF8) ? $this->assunto : utf8_decode($this->assunto);
            $this->mensagem = '<html>
<head>
<title>'.$assunto.'</title>
<meta http-equiv="Content-Type" content="text/html; charset='.$this->charset.'"/>
</head>
<body>'.$msg.'</body>
</html>';
        }else{
            $msg = str_replace("\n.", "\n..", $msg);
            $this->mensagem = str_replace("\n", "\r\n", $msg);
        }
    }
    /**
     * envia o correio já com os atributos preenchidos
     * @return boolean 
     */
    function send(){
        //destinatarios
        if(count($this->para)){
            $para = implode(', ', $this->para);
            if($this->anexo){
                $anexo = $this->anexo;
                $file = fopen($anexo['tmp_name'], "rb");
                $contents = fread($file, $anexo['size']);
                $encoded = chunk_split(base64_encode($contents));
                fclose($file);
            }
            #############
            // HEADERS //
            #############
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            if($this->anexo){
                $bound = "XYZ-" . date("dmYis") . "-ZYX";
                if (($fp = fopen($this->anexo['tmp_name'], "rb"))) {
                    $anexo = fread($fp, filesize($this->anexo['tmp_name']));
                    $anexo = base64_encode($anexo);
                    fclose($fp);
                    $anexo = chunk_split($anexo);
                }
                $headers .= "X-attachments: ".$this->anexo['name'] . "\r\n";
                $headers .= "Content-type: multipart/mixed; boundary=\"$bound\"" . "\r\n";
            }else{
                $headers .= 'Content-type: '.$this->tipo.'; charset=' . $this->charset . "\r\n";
            }
            
            // To
            $headers .= 'To: ' . $para . "\r\n";
            //From
            if(count($this->de)){
                $de = implode(', ', $this->de);
                $headers .= 'From: '. $de . "\r\n";
            }
            //Cc
            if(count($this->cc)){
                $cc = implode(', ', $this->cc);
                $headers .= 'Cc: '. $cc . "\r\n";
            }
            //Resposta
            if(count($this->responder_para)){
                $resp = implode(', ', $this->responder_para);
                $headers .= 'Reply-To: '. $resp . "\r\n";
            }
            //enviando das duas formas
            if(ENV == 'local'){
                //arquivo local com resposta
                $fdl = ($this->tipo == self::PLAIN) ? "\n" : '<br/>';
                //acrescentando mais informações
				$para = htmlentities($para);
				$de = htmlentities($de);
				$resp = htmlentities($resp);
                $msg = $this->mensagem."$fdl".
                        "Para: $para $fdl".
                        "De: $de $fdl".
                        "Assunto: {$this->assunto} $fdl".
                        "Resp: $resp $fdl";
                $this->msg($msg);
                //montando o buffering para soltar o arquivo
                $arquivo=@fopen($this->pasta_off.'/'.date('YmdHis').' - '.uniqid('email') .'.html','w');
                @fputs($arquivo,$this->mensagem,strlen($this->mensagem));
                @fclose($arquivo);
                $return = true;
            }else{
                //resposta pelo SMTP
                $this->msg($this->mensagem);
                if($this->anexo){
                    $corpo = $this->mensagem;
                    $this->mensagem = "--$bound\r\nContent-type: text/html\nContent-Transfer-Encoding: 7bit\r\n\r\n$corpo\r\n\r\n"
                                . "--$bound\r\nContent-type: ".$this->anexo['type']."\nContent-Disposition: attachment; filename=" . $this->anexo['name'] . "\nContent-Transfer-Encoding: base64\r\n\r\n$anexo\r\n"
                                . "--$bound\r\n";
                }
                $return = @mail($para, $this->assunto, $this->mensagem, $headers);
            }
        }else{
            $return = false;
        }
        return $return;
    }

}
?>
