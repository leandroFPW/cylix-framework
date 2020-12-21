<?php

class Cylix_AutoLoader {

	public static function includeClass($class) {
		$bkp = $class;
		$path2 = '';
		$inicial = 'Cylix_';
		$layer_path = SYS_PATH . 'app_layers/';
		if (strpos($class, $inicial) === 0) {
			//eh do FRAMEWORK
			$path = FRAMEWORK_PATH;
			$class = substr($class, strlen($inicial));
		} else {
			//MODELOS
			if (preg_match('/.+Model_Row/', $class)) {
				$class = str_replace('Model_Row', '', $class);
				$path = $layer_path . 'models/row/M_';
				$class .= '_Row';
			} elseif (preg_match('/.+Model/', $class)) {
				$tam = strlen($class);
				$class = substr($class, 0, $tam - strlen('Model'));
				$path = $layer_path . 'models/table/M_';
				$path2 = $layer_path . 'models/M_'; //outra opção de caminho
			} elseif (preg_match('/.+Controller/', $class)) {
				////Controller
				$class = substr($class, 0, strlen($class) - strlen('Controller'));
				$aux = explode('_', $class);
				if (count($aux) > 1) {
					$flow = array_shift($aux); //o 1º será o flow
					$class = implode('_', $aux); //emenda o restante
				}
				$path = $layer_path . 'controllers/' . strtolower($flow) . '/C_';
			} elseif (preg_match('/.+Helper/', $class)) {
				//helper
				$class = substr($class, 0, strlen($class) - strlen('Helper'));
				$aux = explode('_', $class);
				if (count($aux) > 1) {
					$flow = array_shift($aux); //o 1º será o flow
					$class = implode('_', $aux); //emenda o restante
				}
				$path = $layer_path . '../helpers/H_';
			} elseif (preg_match('/.+Plugin/', $class)) {
				//plugin class
				$class = substr($class, 0, strlen($class) - strlen('Plugin'));
				$aux = explode('_', $class);
				if (count($aux) > 1) {
					$flow = array_shift($aux); //o 1º será o flow
					$class = implode('_', $aux); //emenda o restante
				}
				$diretorio = dir(SYS_PATH."plugins");
				$dir_list = array();
				//varrendo diretorio
				while ($conteudo = $diretorio->read()) {
					if($conteudo != '.' && $conteudo != '..' && is_dir(SYS_PATH."plugins/$conteudo")){
						$dir_list[] = $conteudo;
					}
				}
				$path = $layer_path . '../plugins/cylix/P_';
				foreach($dir_list as $dir){
					if(self::camelcase($dir) == $class){
						$path = $layer_path . "../plugins/$dir/P_";
					}
				}
			} else {
				$aux = explode('_', $class);
				$path = SYS_PATH . 'lib/';
				if (count($aux) > 1) {
					$class = array_pop($aux); //ultimo será o arquivo
					$path .= implode('/', $aux);
					$path .= '/';
				}
			}
		}
		$file = $path . $class . '.php';
		$path2 = (strlen($path2)) ? $path2 : $path;
		if (is_file($file)) {
			require_once $file;
		} else {//segunda opção
			$file = $path2 . $class . '.php';
			if (is_file($file)) {
				require_once $file;
			}else{
				if (ENV != 'online') {
					die('faltou um ' . $file);
				} else {
					throw new Cylix_Exception($file . ' not found', 'Cylix_AutoLoader');
				}
			}
		}
	}
	
	/**
	* normaliza em CamelCase ex: teste-meu >> TesteMeu
	* @param string $str
	* @param boolean $lc_first 1ª letra será minuscula
	* @param boolean $undescore incluir '_' no split
	* @return string
	*/
   static function camelcase($str, $lc_first = false,$undescore=true) {
		if($undescore){
			$aux = split("[-_]", $str);
		}else{
			$aux = explode("-", $str);
		}
		$aux2 = array();
		foreach ($aux as $v) {
			$aux2[] = ucfirst($v);
		}
		$str = implode('', $aux2);
		$str = ($lc_first) ? lcfirst($str) : $str;
		return $str;
	}

}
