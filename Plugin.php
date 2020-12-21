<?php

/**
 * Invocador de Plugins semelhante ao helper
 */
class Cylix_Plugin {
	
	function getTmpFile(){
		return SYS_PATH.'tmp/terminal/plugins_list';
	}
	
	function getPluginList(){
		$_tmp_file = $this->getTmpFile();
		$f_list = trim(file_get_contents($_tmp_file));
		$array_list = array();
		if(strpos($f_list, 'a:')===0){
			$array_list = unserialize($f_list);
		}elseif(strpos($f_list, '=')!== false){
			foreach(explode("\n",$f_list) as $linha){
				$cols = explode("=",$linha);
				if($cols[0]){
					$array_list[$cols[0]] = $cols[1];
				}
			}
		}else{
			$u = umask(0);
			$fh = fopen($_tmp_file, 'w') or die("impossível obter $_tmp_file (write error)");
			@fwrite($fh, '-') or die("acesso negado para $_tmp_file (deny)");
			fclose($fh);
			umask($u);
		}
		return $array_list;
	}

	/**
	 * Invoke a plugin using the standard call mode
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 * @throws Cylix_Exception 
	 * @example $this->plugin->googleAnalytics();
	 */
    public function __call($name, $arguments) {
        $nome = Cylix_App::camelcase($name);
		if(function_exists($name)){
			call_user_func($name, $arguments);
		}else{
			//somente plugins registrados
			$array_list = $this->getPluginList();
			$dir_name = '';
			foreach($array_list as $n => $v){
				if(Cylix_App::camelcase($n) == $nome){
					$dir_name = $n;//achando o nome dele
				}
			}
			if($dir_name){
				$arq = SYS_PATH.'plugins/'.$dir_name.'/P_' . $nome . '.php';
				if (file_exists($arq)) {
					require_once($arq);
					$classe = $nome . 'Plugin'; //TestePlugin
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
					throw new Cylix_Exception("Plugin not found: " . $arq, 'Include error');
				}/**/
			}else{
				Cylix_App::logFile(date('[Y-d-m H:i:s')."] Plugin not included: " . $name.' - See the plugin list', 'plugin.log');
			}
		}
    }

	
}

?>
