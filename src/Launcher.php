<?php
# NOTICE OF LICENSE
#
# This source file is subject to the Open Software License (OSL 3.0)
# that is available through the world-wide-web at this URL:
# http://opensource.org/licenses/osl-3.0.php
#
# -----------------------
# @author: Iván Miranda
# @version: 1.0.0
# -----------------------
# Ejecución de eventos según la petición realizada desde el navegador
# -----------------------

namespace Sincco\Sfphp;

final class Launcher extends \stdClass {
	public function __construct() {
		var_dump(\Sincco\Sfphp\Session::get('sincco\sfphp\client\uid'));
		var_dump(\Sincco\Sfphp\Session::get('sincco\sfphp\client\token'));
		#\Sincco\Sfphp\Logger::log($_SESSION);
		\Sincco\Sfphp\Logger::log(array("param1"=>array("val1"=>array("otro","mas"),"val2")));
		$segments = \Sincco\Sfphp\Request::get('segments');
		$path = "";
		if(trim($segments['controller']) == "")
			$segments['controller'] = "Index";
		if(trim($segments['action']) == "")
			$segments['action'] = "index";
		if(trim($segments['module']) != "")
			$path .= "\\{$segments['module']}";
		$path .= "\\Controllers\\{$segments['controller']}";

		$objClass = $this->_loadClass($path, $segments['controller']);
		if(is_callable(array($objClass, $segments['action']))) {
			if($segments['action'] != "index")
				call_user_func(array($objClass, $segments['action']));
		}
		else {
			header("HTTP/1.0 404 Not Found");
			die();
		}
		// $clase = NULL;
		// if(!is_null($request['_modulo']))
		// 	$clase = ucwords($request['_modulo'])."_";
		// $clase .= "Controladores_".ucwords($request['_control']);
		// try {
		// 	$objSeguridad = new Seguridad();
		// 	if($objSeguridad->validarAcceso(ucwords($request['_control']))) {
		// 		$objClase = new $clase();
		// 		if(is_callable(array($objClase, $request['_accion'])))
		// 			call_user_func(array($objClase, $request['_accion']));
		// 		else {
		// 			header("Location: ".BASE_URL."Etc/Errors/process.php?code=401");
		// 			die();
		// 		}
		// 	} else {
		// 		trigger_error("La accion {$request['_accion']} no esta definida en {$clase}", E_USER_ERROR);
		// 	}
		// } catch (Sfphp_Error $e) {
		// 	Sfphp_Log::error($e);
		// }
	}

	private function _loadClass($path, $class) {
		$_path = str_replace("\\", "/", $path);
		if(file_exists(PATH_ROOT."/app".$_path.".php")) {
			require_once(PATH_ROOT."/app".$_path.".php");
			return new $class();
		} else {
			return new \stdClass();
		}
	}
}