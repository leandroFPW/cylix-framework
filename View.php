<?php

class Cylix_View extends stdClass {

    //var

    protected $_ctrl;
    protected $_action;
    protected $_context;
    protected $_theme;
    protected $_helper;
    protected $_plugin;
    protected $_permalink;
    protected $_layout;
    
    const ALERT_WARNING = 'warning';
    const ALERT_ERROR = 'danger';
    const ALERT_INFO = 'info';
    const ALERT_OK = 'success';
    
    public $yield;
    public $flag_render;
    /**
     * requisições
     * @var Cylix_Request 
     */
    public $request;
	
	/**
	 * file extension for view/action/action.extension
	 * @var string phtml is default
	 */
	static $template_extension='phtml';

    public function __construct($ctrl='index', $action='index', $layout='html',$permalink='', $context='index') {
        $this->_permalink = $permalink;
        $this->_ctrl = $ctrl;
        $this->_action = $action;
        $this->flag_render = true;
        $this->_theme = SKIN;
        $this->_context = $context;
        $this->_layout = $layout;
    }
    /**
     * artificio de criar instancia para visualizar os metodos<br/>
     * OBS.: trocar Cylix_View::aux() por $this nas views
     * @return Cylix_View 
     */
    public function aux(){
        return new Cylix_View();
    }
    
    public function show(){
        return $this->flag_render;
    }
    
    public function setContext($context='index'){
        $this->_context = $context;
    }
    /**
     * muda o layout (html, popup, ajax, etc)
     * @param string $format 
     */
    public function setLayout($format){
        $this->_layout = $format;
    }

    /* #################################
     * Layouts -> formatos (html, ajax e json)
      ################################# */

    public function getLayout() {
        try {
			$ext = self::$template_extension;
            ##########
            # find format
            #########
            $Path = SYS_PATH.'app_layers/views/';
            //skin
            $a = $Path . SKIN . '/layouts/' . $this->_layout . '.'.$ext;
            if (!file_exists($a)) {
                //flow
                $a = $Path . FLOW . '/layouts/' . $this->_layout . '.'.$ext;
            }
            
            if (file_exists($a)) {
                ob_start();
                include $a;
                return ob_get_clean();
            } else {
                throw new Cylix_Exception("File: $a \n Format: {$this->_layout}", 'View layout not found');
            }
        } catch (Cylix_Exception $e) {
            return $e;
        }
    }
    /**
     * puxa o partial segundo a combinação skin+ctrl+action e monta o yield (produto resultante)
     * @return Cylix_Exception caso tenha excessões
     */
    public function setYield() {
		$yield = $this->_ctrl . '/' . $this->_action;
		if($this->_action_partial){
			$yield = $this->_action_partial;
		}
        try {
			$ext = self::$template_extension;
            ##########
            # find partial
            #########
            $Path = SYS_PATH.'app_layers/views/';
            //skin+context
			
            $a = $Path . SKIN . '/actions/' . $yield . '_' . $this->_context . '.'.$ext;
            if (!file_exists($a)) {
                //skin
                $a = $Path . SKIN . '/actions/' . $yield . '.phtml';
                if (!file_exists($a)) {
                    //flow+context
                    $a = $Path . FLOW . '/actions/' . $yield . '_' . $this->_context . '.'.$ext;
                    if (!file_exists($a)) {
                        //flow
                        $a = $Path . FLOW . '/actions/' . $yield . '.'.$ext;
                    }
                }
            }
            
            if (file_exists($a)) {
                ob_start();
                include $a;
                $this->yield = ob_get_clean();
                return true;
            } else {
                throw new Cylix_Exception("file: $a", 'Yield not found');
            }
        } catch (Cylix_Exception $e) {
            //retorna para a aplicaÃ§Ã£o tratar
            return $e;
        }
    }
	
	/**
	 * Altera o arquivo final da view (action/ctrl/action)
	 * @param string $action_partial action partial OR actions path partial with ctrl
	 * @example $this->view->alterRender('index') pega o index.phtml dentro de action/controller-atual
	 */
	public function alterRender($action_partial){
		if(strpos($action_partial, '/')>0){
			$this->_action_partial = $action_partial;
		}else{
			$this->_action_partial = $this->_ctrl . '/' . $action_partial;
		}
	}

    /**
     * retorna o phtml correspondente ao layout ou action
     * @param string $name ex.: teste, _teste, partials/teste etc.
     * @param string $is_action true -> pasta actions, false -> pasta layouts
     * @param string $ctrl caso queira outra pasta ctrl
     * @return string html 
     */
    public function partial($name, $is_action=true, $ctrl=null,$params=array()) {
        try {
			$ext = self::$template_extension;
            $Path = SYS_PATH . 'app_layers/views/';
            //se eh pra procurar na action atual
            if($is_action){
                $Subpath = 'actions/';
                if(is_string($ctrl)){
                    $Subpath .= $ctrl;
                }else{
                    $Subpath .= $this->_ctrl;
                }
            }else{
                //ou no layout 
                $Subpath = 'layouts/partials';
            }
            //skin
            $a = $Path . SKIN . "/{$Subpath}/" . $name . '.'.$ext;
            if (!file_exists($a)) {
                //flow
                $a = $Path . FLOW . "/{$Subpath}/" . $name . '.'.$ext;
            }
			//tratamento de variaveis
			foreach($params as $k => $v){
				eval("\$$k = \$v;");
			}
			//------
            if (file_exists($a)) {
                ob_start();
                include $a;
                return ob_get_clean();
            }else{
                return 'partial '.$a.' desconhecido (not found)';
            }
        } catch (Cylix_Exception $e) {
            return '';
        }
    }
    /**
     * busca o arquivo na pasta de templates e subistitui variaveis<br>
     * path: etc/templates/$template
     * @param string $template nome do arquivo, ex.: template.html
     * @param array $VAR trcará as chaves por valores contidos no array
     * @return string 
     */
    static function getTemplate($template, $VAR=array()) {
        $arquivo = SYS_PATH . 'etc/templates/' . $template;
        if(file_exists($arquivo)){
            $string = file_get_contents($arquivo);
            $chaves = $valores = array();
            foreach($VAR as $k=>$v){
                $chaves[] = '{{'.$k.'}}';
                $valores[] = $v;
            }
            $string = str_replace($chaves, $valores, $string);
        }else{
            if(ENV != 'online')
                $string = 'template missing: '.$arquivo;
            else
                $string = 'template missing: '.$template;
        }
        return $string;
    }
    
    /**
     * flash de alerta (div)
     * @param string $texto
     * @param string $chave chave indicadora de unicidade (default: site)
     * @param string $tipo default: ALERTA_INFO (ALERTA_AVISO,ALERTA_ERRO,ALERTA_INFO,ALERTA_OK)
     */
    public function setAlert($texto,$chave='site', $tipo=self::ALERT_INFO, $aux_class='alert') {
        $_SESSION['alert'][$chave] = '<div class="' . $aux_class . ' ' . $aux_class . '-' . $tipo . '">' . $texto . '</div>';
    }
    public function getAlert($chave='site'){
        if(isset($_SESSION['alert'][$chave])){
            $a = $_SESSION['alert'][$chave];
            unset($_SESSION['alert'][$chave]);
        }else{
            $a =FALSE;
        }
        return $a;
    }
	
	function getRequest(){
		return $this->request;
	}
	
	function noRender(){
		$this->flag_render = false;
	}

}
