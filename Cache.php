<?php
/**
 * Executa funcionalidades para cache
 */
class Cylix_Cache {
    
    static $inst;
    /**
     * pasta de armazenamento de arquivos resultantes de uma query ou variáveis
     */
    const TYPE_QUERY = 'selections/';
    /**
     * pasta de armazenamento de arquivos resultantes de uma rota para views
     */
    const TYPE_VIEW = 'views/';
    /**
     * pasta de armazenamento de arquivos resultantes de uma rota para views
     */
    const TYPE_CACHE = 'cache/';
    /**
     * pasta de armazenamento de cache que não será limpada apenas renovada esporadicamente
     */
    const TYPE_CACHE_FIXED = 'cache_fixed/';
    
    protected $_timelife;
    protected $_type;
    protected $_tmp_path;
    

    function __construct() {
        $this->_tmp_path = SYS_PATH.'tmp/';
        $this->_timelife = 120;//segundos
        $this->_type = self::TYPE_CACHE;
    }
    /**
     * singleton: retorna instancia
     * @return Cylix_Cache 
     */
    static function me(){
        if(isset(self::$inst)){
           $return =  self::$inst;
        }else{
            self::$inst = $return = new self();
        }
        return $return;
    }
	/**
	 *force no-cache 
	 */
    static function noHeaderCache(){
        header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }
    /**
     * get e set -> tempo de vida do arquivo em segundos
     * @param int $sec segundos (d: 120)
     * @return int 
     */
    public function lifetime($sec=null){
        if($sec!=null){
            $this->_timelife = $sec;
        }
        return $this->_timelife;
    }
    /**
     * get e set -> tipo de armazenamento (pasta em tmp)
     * @param string $type pasta no tmp (d: QUERY)
     * @return string 
     */
    public function type($type=null){
        if($type!=null){
            $this->_type = $type;
        }
        return $this->_type;
    }
    /**
     * get e set -> pasta tmp
     * @param string $path pasta tmp (d: sys/tmp)
     * @return string 
     */
    public function tmpPath($path=null){
        if($path!=null){
            $this->_tmp_path = $path;
        }
        return $this->_tmp_path;
    }

    function isValid($name){
        $name = md5($name);
        $file = $this->_tmp_path.$this->_type.$name.'.cache';
        
        //testando se existe o arquivo
        if(file_exists($file)){
            //se existe, testar validade
            return (time() - $this->_timelife < filemtime($file));
        }else{
            //se nao, entao nao eh valido
            return false;
        }
    }
    /**
	 * returns cache content unserialized
	 * @param string $name
	 * @return mixed 
	 */
    function get($name){
        $name = md5($name).'.cache';
        if(file_exists($this->_tmp_path.$this->_type.$name)){
            $str = file_get_contents($this->_tmp_path.$this->_type.$name);
            return unserialize($str);
        }else{
            return new Cylix_Exception('cache file not found', 'Cache error');
        }
    }
    /**
	 * insert contant into the cache file serialized
	 * @param string $name key for file
	 * @param mixed $content 
	 */
    function set($name,$content=''){
        $name = md5($name);
        $file = $this->_tmp_path.$this->_type.$name.'.cache';
        $u = umask(0);
        $fh = fopen($file, 'w') or die("impossível obter a cache $name. Permissões não definidas");
        @fwrite($fh, serialize($content));
        fclose($fh);
        umask($u);
    }
    
    function destroy($name){
        $name = md5($name);
        $file = $this->_tmp_path.$this->_type.$name.'.cache';
        return @unlink($file);
    }
    /**
     * flush temp cache dir
     * @param array $array folders from tmp/
     */
    function flush($array=null){
        $error = array();
        if(is_null($array)){
            $array = array('');//direto na pasta
        }
        foreach($array as $f){
            $pasta = $this->_tmp_path.$f;
            $arquivos = _listDir($pasta);
            if(count($arquivos)){
                foreach($arquivos as $arq){
                    $ret = @unlink("$pasta/$arq");
                    if(!$ret){
                        $error[] = "$pasta/$arq";
                    }
                }
            }
        }
        if(count($error)){
            //cria um log de erro de cache
            $u = umask(0);
            $fh = fopen($this->_tmp_path.'flush_cache_erros.cache', 'w') or die("not logging the cache flush errors");
            @fwrite($fh, serialize(implode("\n", $error)));
            fclose($fh);
            umask($u);
        }
    }
	/**
	 * verify if cache already exists
	 * @param string $name cache key
	 * @return boolean 
	 */
	function isCreated($name){
		$name = md5($name);
        $file = $this->_tmp_path.$this->_type.$name.'.cache';
        return is_file($file);
	}
}