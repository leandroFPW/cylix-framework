<?php

class Cylix_Controller{

    //var
    
    protected $_permalink; /* tipo de fluxo: admin, site ou terminal */
    protected $_ctrl;
    protected $_action;
    /**
     * todos os helpers
     * @var Cylix_Helper 
     */
    public $helper;
    /**
     * todos os plugins
     * @var Cylix_Plugin 
     */
    public $plugin;
    /**
     * conterá variáveis para view e setará: flag_render, context, helpers e plugins
     * @var Cylix_View 
     */
    public $view;
    /**
     * requisições
     * @var Cylix_Request 
     */
    public $request;

    public function __construct($ctrl='index', $action='index',$layout='html',$helper=null,$permalink='', $context='index',$plugin=null) {
        $this->_permalink = $permalink;
        $this->_ctrl = $ctrl;
        $this->_action = $action;
        $this->view = new Cylix_View($ctrl,$action,$layout,$permalink, $context);
        
        $this->view->helper = $this->helper = ($helper) ? $helper : new Cylix_Helper();//invocador de auxliares
        $this->view->plugin = $this->plugin = ($plugin) ? $plugin : new Cylix_Plugin();//invocador de plugins
        $this->view->request = $this->request = new Cylix_Request();
    }

	
	public function beforeAction() {$this->ini();}
    
    public function afterAction() {$this->end();}

	//ALIAS para antigas actions
	/**
	 * @deprecated use  beforeAction()
	 */
    public function ini() {}
	/**
	 * @deprecated use  afterAction()
	 */
    
    public function end() {}
    

    public function getParam($p) {
        return $this->request->getParam($p);
    }
    /**
     * retorna todos os valores de $_POST
     * @return array com chave => valor
     */
    public function getPost(){
        return $this->request->getPost();
    }
    /**
     * redireciona normalmente
     * obs.: colocar em cada flow um redirect a rota do mesmo
     * @param type $url 
     */
    function _redirect($url){
        $this->view->flag_render = false;
        redirect($url);
    }

    /**
     * apply defined scopes for select object from filter
     * aplica os scopes montados. ex.:
     * protected $_scopes = array(
            'termo' => array('termo LIKE ?', '%$%', true),
            'info' => array('info LIKE ?', '%$%', false), #like|valor|fatiar termo=true
            'usuarios_id' => array('usuarios_id = ?', '$'),
            'data_de' => array('data >= ?', '$'),
        );
     * @param Cylix_SQL_Select $sql (ref) sql that will receive the scopes
     * @param boolean $persist filtro persistente (persistent filter)
     * @return Cylix_SQL_Select $sql
     */
    public function applyEscopes(Cylix_SQL_Select &$sql,$persist=true){
        $ret = $sql;
        foreach ($this->_scopes as $name => $filter){
            if($persist){
                $key = FLOW.'-'.$this->_ctrl.'-'.$this->_action;
                $v = $this->getParam($name);
                if($v !== null){
                    $_SESSION[$key][$name] = $v;
                }else{
                    $v = $_SESSION[$key][$name];
                }
            }else{
                //testa somente se veio via get
                $v = $this->getParam($name);
            }
            
            if($v != null || trim($v) != ''){
                $w = $filter[0];//where clause
                if(isset($filter[1])){
                    //replace value
                    if(isset($filter[2]) && $filter[2]===true){
                        //fatiar os termos
                        $t = explode(' ', $v);
                    }elseif(isset($filter[2]) && $filter[2]==='date'){
						//um termo convertido em data SQL
                        $t = array(Cylix_SQL::parseDate($v));
					}else{
                        //um termo somente
                        $t = array($v);
                    }
                    for($i=0;$i<count($t) && strlen($v);$i++){
                        if($i==0)
                            $ret = $sql->where($w,  str_replace('$', $t[$i], $filter[1]));#pode ser AND
                        else
                            $ret = $sql->where($w,  str_replace('$', $t[$i], $filter[1]),true);//somente OR
                    }
                    
                }else{
                    $ret = $sql->where($w);
                }
            }
        }
        
        return $ret;
    }
    /**
     * return the defined scopes (GET or persitent) - using in view form
     * retorna os scopes definidos, buscando em get ou no filtro persistent - usando-o na view (colocar valores no form)
     * @param boolean $persist pegar do filtro persistente (yes/no)
     * @return array 
     */
    public function getFilterScopes($persist=true){
        $ret = array();
        foreach ($this->_scopes as $name => $filter){
            $v = $this->getParam($name);
            $ret[$name] = ($v==null && $persist) ? $_SESSION[FLOW.'-'.$this->_ctrl.'-'.$this->_action][$name] : $v;
        }
        return $ret;
    }
    
    function getOneScope($name,$persist=true){
        $v = $this->getParam($name);
        return ($v==null && $persist) ? $_SESSION[FLOW.'-'.$this->_ctrl.'-'.$this->_action][$name] : $v;
    }
}