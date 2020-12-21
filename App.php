<?php

class Cylix_App {

    static $_script;
    static $_css;
    static $_dir_tmp_terminal = 'tmp/terminal';
    static $_dir_maintenance = '/maintenance';

    static function setJS($chave, $url, $cached=true) {
        if (@is_file($url) && $cached) {
            $url .= '?' . filemtime($url);
        }
        self::$_script[$chave] = $url;
    }

    static function setCSS($chave, $url, $media='all', $cached=true, $rel='stylesheet') {
        if (@is_file($url) && $cached) {
            $url .= '?' . filemtime($url);
        }
        self::$_css[$chave] = array($url, $media, $rel);
    }
    
    static function getJavascripts(){
        if(isset(self::$_script)):
            foreach (self::$_script as $key => $valor) {
                echo '<script type="text/javascript" src="' . $valor . '"></script>
';
            }
        endif;
    }

    static function getStylesheets(){
        if(isset(self::$_css)):
            foreach (self::$_css as $key => $valor) {
                echo '<link rel="' . $valor[2] . '" media="' . $valor[1] . '" href="' . $valor[0] . '" />
';
            }
        endif;
    }

    static function getScripts() {
        self::getStylesheets();
        self::getJavascripts();
        
    }

    /**
     * Acontece tudo
     */
    static function run($_ctrl,$_action,$_helper=null,$_layout='html',$_permalink='',$_plugin=null, $auto_render=true) {
        try{
            
            //puxa o abstract dos controles, caso tenha
            $abstract = getCtrlFile('abstract');
            
            $arqCtrl = getCtrlFile($_ctrl);
            $CtrlReal = self::camelcase(FLOW).'_'.self::camelcase($_ctrl.'-controller');
            //controle requerido
            if (@file_exists($arqCtrl)) {
                //construindo o controller, setando a view, format, helpers e plugins
                eval("\$CTRL = new $CtrlReal('".$_ctrl."', '".$_action."', '".$_layout."',\$_helper,'".$_permalink."','index',\$_plugin);");
                //testando se a action existe
                $action = self::camelcase("action-$_action", true);
                if(method_exists($CTRL, $action)){
                    //init
                    $e = $CTRL->beforeAction();
                    if($e instanceof Cylix_Exception){
                        throw $e;
                    }
                    //chamando a action
                    eval("\$e = \$CTRL->{$action}();");
                    if($e instanceof Cylix_Exception){
                        throw $e;
                    }
                    //end
                    $e = $CTRL->afterAction();
                    if($e instanceof Cylix_Exception){
                        throw $e;
                    }
                    //renderizando
                    if($CTRL->view->show()){
                        //achando o yield
                        if(!isset($CTRL->view->yield)){
                            //pegando o yield, caso nao tenha mexido nele ainda
                            $r = $CTRL->view->setYield();
                            if($r instanceof Cylix_Exception){
                                //se retornou uma excessao
                                throw $r;
                            }
                        }
                        //puxando o layout
                        $r = $CTRL->view->getLayout();
                        if($r instanceof Cylix_Exception){
                            //se retornou uma excessao
                            throw $r;
                        }else{
							//plota a saída ou retorna a view
							if($auto_render){
								echo $r;
							}else{
								return $CTRL->view;
							}
                            
                        }
                    }
                }else{
                    throw new Cylix_Exception("Action: $action \nCtrl: $CtrlReal", "Action not found: $action");
                }
            } else {
                throw new Cylix_Exception($arqCtrl, "Controller not found: $CtrlReal");
            }
        } catch (Cylix_Exception $e) {
            ob_end_clean();
            die(Cylix_Exception::msg404($e));
        }
    }

    /**
     * normaliza em CamelCase ex: teste-meu >> TesteMeu
     * @param string $str
     * @param boolean $lc_first 1ª letra será minuscula
     * @return string
     */
    static function camelcase($str, $lc_first=false) {
        $aux = preg_split("/[-_]/", $str);
        $aux2 = array();
        foreach ($aux as $v) {
            $aux2[] = ucfirst($v);
        }
        $str = implode('', $aux2);
        $str = ($lc_first) ? lcfirst($str) : $str;
        return $str;
    }
	
	static function logFile($content='',$file='none',$dir=null){
		$dir = ($dir) ? $dir : SYS_PATH."tmp/log";
		$fh = @fopen("$dir/$file", 'a') or die("can't open file");
		@fwrite($fh, $content."\n") or die("can't write in file");
		@fclose($fh);
	}
    
    /**
     * Cria a flag de manutenção
     * @return boolean 
     */
    static function lockSite(){
        $path = SYS_PATH.self::$_dir_tmp_terminal;
        $f_site = $path.self::$_dir_maintenance;
        $r = false;
        if(@is_dir($path)){
            if(!@file_exists($f_site)){
                $u = umask(0);
                $fh = fopen($f_site, 'w') or die("impossível obter $f_site");
                @fwrite($fh, '1');
                fclose($fh);
                umask($u);
                $r = true;
            }
        }
        return $r;
    }
	/**
	 * force to run a Request URI
	 * @param string $uri URI desejada para processar
	 * @param string $layout 
	 * @param boolean $return_view true = retorna a View | false = saída padrão de browser 
	 * @return type 
	 */
	static function runRequest($uri='/',$layout='html',$return_view=true){
		################################
		#  flush cache via GET
		################################
		include_once SYS_PATH . 'config/flush_cache.php';
		
		//no php-cache for AJAX
		if(IS_AJAX){
			Cylix_Cache::noHeaderCache();
		}
		################################
		#  setando as permissoes
		################################
		//pastas e permissoes de escrita
		if(!IS_AJAX){//otimização para ajax
			include_once SYS_PATH . 'config/permissions.php';
		}

		##################################
		#  Plugins
		##################################
		$_Plugins = new Cylix_Plugin(SYS_PATH.'plugins');

		################################
		#  setando os plugins
		################################
		//inicializando todos os plugins registrados

		include_once SYS_PATH . 'config/plugins.php';

		################################
		#  roteador (determinar o modulo, controle, acao e parametro get
		#  * adaptacao para rodar via API, bastando setar $_URI
		################################
		
		$_URI = $uri; //forçando a URI
		
		//modulos + permalinks
		$root_permalinks=array();//variavel usada pelo router.php
		include SYS_PATH.'config/modules.php';
		
		include_once SYS_PATH . 'config/router.php';#critico2
		//jah foi guardado as variaveis $_GET, $_Link (permalink atual), $_Ctrl e $_Action
		//definido o FLOW (pasta de referencia != permalink)

		################################
		#  I18n - mecanismos de tradução inline via csv
		################################

		include_once SYS_PATH . 'config/i18n.php';

		################################
		#  flags
		################################

		//principais flags
		include_once SYS_PATH . 'config/flags.php';



		##################################
		#  Helpers
		##################################
		$_Helper = new Cylix_Helper(SYS_PATH.'helpers');


		##################################
		#  Temas
		##################################

		//chamada do tema em www(admin,site,natal,etc.)
		include_once SYS_PATH . 'config/themes.php';
		
		##################################
		#  App
		##################################
		$_Layout = (isset($_GET['layout'])) ? $_GET['layout'] : $layout;
		//executa a aplicação e retorna a view livre
		
		$return = Cylix_App::run($_Ctrl,$_Action,$_Helper,$_Layout,$_Link,$_Plugins,!$return_view);
		if($return instanceof Cylix_View){
			return $return;
		}
	}

}
