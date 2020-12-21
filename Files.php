<?php

class Cylix_Files {

    protected $_files;
    protected $_nome;
    protected $_pasta;

    /**
     * cria instancia e guarda o FILES e um nome uniqid()
     * @param $_FILES $files
     */
    public function __construct($files) {
        $this->_files = $files;
        $this->_nome = uniqid();
    }

    function getOriginalName() {
        return $this->_files['name'];
    }

    /**
     * nome do arquivo sem a extensão
     * @return string
     */
    function getName() {
        return $this->_nome;
    }

    function getType() {
        return $this->_files['type'];
    }

    function getTmp() {
        return $this->_files['tmp_name'];
    }

    function getSize() {
        return $this->_files['size'];
    }

    function getError() {
        return $this->_files['error'];
    }

    /**
     * retorna a extensão
     * @return strin 
     */
    function getExt() {
        $arq = explode('.', $this->getOriginalName());
        $ext = strtolower(end($arq));
        $ext = str_replace('jpeg', 'jpg', $ext);
        return $ext;
    }

    function setName($nome) {
        $this->_nome = $nome;
    }

    /**
     * Caminho de destino sem a barra no final ('/')
     * @param string $caminho 
     */
    function setFolder($caminho) {
        $this->_pasta = $caminho;
        if (!is_dir($caminho)) {
            @mkdir($caminho, 0777);
        }
    }

    /**
     * move o arquivo para a pasta
     * @param string $pasta
     * @return boolean
     */
    function sendTo($pasta) {

        if ($this->getOriginalName() == "" || $pasta == '') {
            return false;
        } else {
            if (is_writable($pasta)) {
                //copia para apasta
                @chmod($pasta, 0777);
                return move_uploaded_file($this->getTmp(), $pasta . '/' . $this->_nome . '.' . $this->getExt());
            } else {
                throw new Cylix_Exception("Pasta $pasta não possui permissões de escrita!", 'Permission to write');
            }
        }
    }

    /**
     * copia o arquivo para a pasta
     * @param string $pasta
     * @return boolean
     */
    function copy() {
        if ($this->getOriginalName() == "" || $this->_pasta == '') {
            return false;
        } else {
            //copia para apasta
            return copy($this->getTmp(), $this->_pasta . '/' . $this->_nome . '.' . $this->getExt()); //
        }
    }

    function getFile() {
        return $this->_pasta . '/' . $this->_nome . '.' . $this->getExt();
    }

    /**
     * Redimensiona imagens do tipo JPEG, PNG ou GIF, mas sobrescrevendo o mesmo
     * @param int $prop será Int x Int com proporção automática
     * @param string $nome nome do arquivo (sem extensão)
     * @param int $qualidade
     * @param string $pasta
     * @return string
     */
    function resizeMe($prop=1024, $nome='', $qualidade=80, $pasta=null) {
        $pasta = ($pasta == null) ? $this->_pasta : $pasta;
        $nome = ($nome != null && strlen($nome) > 0) ? $nome : $this->_nome;
        try {
            $arquivo = $pasta . '/' . $nome . '.' . $this->getExt();
            //copia para apasta
            if (copy($this->getTmp(), $arquivo)) {
                //chamando o resizer
                $resizer = new Cylix_Resizer($arquivo);
                if($resizer->getWidth() > $prop || $resizer->getHeight() > $prop){
                    $prop2 = $prop;
                }else{
                    $prop = $resizer->getWidth();
                    $prop2 = $resizer->getHeight();
                }
                $resizer->resizeImage($prop, $prop2); //usando auto proporcao
                //sobrescrevendo
                $resizer->saveImage($arquivo, $qualidade);
                $n_foto = $arquivo;
            }
        } catch (Exception $e) {
            $n_foto = "erro";
        }

        return $n_foto;
    }

    /**
     * Expostar arquivo XLS (somente para office 2003 ou inferior)
     * @param array $dados tabela de dados
     * @param string $nome
     * @param boolean $header_first first line is header
     * @example
      $dados = array(<br>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;array('Nome','Email'),<br>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;array('Teste','teste@teste.com.br')<br>
      &nbsp;&nbsp;&nbsp;);<br>
      Cylix_Arquivos::exportarLista($dados,'news');
     *
     */
    static function exportXLS($dados=array(), $nome='exportado',$header_first=false,$border=0,$validate_num=false) {
        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $nome . '.xls"');
        print ('<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:x="urn:schemas-microsoft-com:office:excel"
        xmlns="http://www.w3.org/TR/REC-html40">
        <div id="Classeur1_16681" align=center x:publishsource="Excel">
        <table x:str border="'.(int)$border.'" cellpadding="0" cellspacing="0" width="100%">');
        for ($i = 0; $i < count($dados); $i++) {
            print ('<tr>');
            $array = array();
            foreach ($dados[$i] as $c) {
				$c = utf8_decode($c);
                $class = '';
                if($validate_num && is_numeric($c)){
                    $class = ' style="mso-number-format:\'0\'"';
                }
                $array[] = ($header_first && $i==0) ? "<td{$class}><b>{$c}</b></td>" : "<td{$class}>{$c}</td>";
            }
            print implode("\n", $array);
            print ('</tr>');
        }
        echo '
        </div>
        </body>
        </html>';
        die;
    }

    /**
     * Expostar arquivo CSV
     * @param array $dados tabela de dados
     * @param string $nome
     * @example
      $dados = array(<br>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;array('Nome','Email'),<br>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;array('Teste','teste@teste.com.br')<br>
      &nbsp;&nbsp;&nbsp;);<br>
      Cylix_Arquivos::exportarLista($dados,'news');
     *
     */
    static function exportCSV($dados=array(), $nome='exportado') {
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="' . $nome . '.csv"');
        for ($i = 0; $i < count($dados); $i++) {
            echo implode(";", $dados[$i]);
            print ("\n");
        }
        die;
    }

    /**
     * Realiza download do arquivo
     * @param string $arquivo caminho publico até o arquivo
     */
    static function download($arquivo) {
        $fullPath = $arquivo;
        // Must be fresh start
        if (headers_sent())
            die('Headers Sent');

        // Required for some browsers
        if (ini_get('zlib.output_compression'))
            ini_set('zlib.output_compression', 'Off');

        // File Exists?
        if (file_exists($fullPath)) {

            // Parse Info / Get Extension
            $fsize = filesize($fullPath);
            $path_parts = pathinfo($fullPath);
            $ext = strtolower($path_parts["extension"]);

            // Determine Content Type
            switch ($ext) {
                case "pdf": $ctype = "application/pdf";
                    break;
                case "exe": $ctype = "application/octet-stream";
                    break;
                case "zip": $ctype = "application/zip";
                    break;
                case "doc": $ctype = "application/msword";
                    break;
                case "xls": $ctype = "application/vnd.ms-excel";
                    break;
                case "ppt": $ctype = "application/vnd.ms-powerpoint";
                    break;
                case "gif": $ctype = "image/gif";
                    break;
                case "png": $ctype = "image/png";
                    break;
                case "jpeg":
                case "jpg": $ctype = "image/jpg";
                    break;
                default: $ctype = "application/force-download";
            }

            header("Pragma: public"); // required
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private", false); // required for certain browsers
            header("Content-Type: $ctype");
            header("Content-Disposition: attachment; filename=\"" . basename($fullPath) . "\";");
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: " . $fsize);
            ob_clean();
            flush();
            readfile($fullPath);
        } else {
            throw new Cylix_Exception($arquivo, 'File not found.');
        }
    }
	
	static function union(array $files,$name){
		// criamos a variável
		$union = NULL;
		for($i=0;$i<count($files);$i++){
			// o arquivo existe ?
			if(file_exists($files[$i])){
				// concatenamos os arquivos e inserimos uma quebra de linha
				$union .= file_get_contents($files[$i])."\n";
			}
		}
		// criamos um arquivo e abrimos somente para escrita    
		$fp = fopen($name,"w");
		// escrevemos o conteúdo da variável
		fwrite($fp,$union);
		// fechamos o arquivo
		fclose($fp);
	}

}