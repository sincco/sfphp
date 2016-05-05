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

use Sincco\Sfphp\Config\Reader;
use Sincco\Sfphp\Request;

final class Launcher extends \stdClass {
	public function __construct() {
		$_config = Reader::get('app');
		if(isset($_config['timezone']))
			date_default_timezone_set($_config['timezone']);
		
		$path = "";
		$segments = Request::get('segments');
		if(trim($segments['controller']) == "")
			$segments['controller'] = "Index";
		if(trim($segments['action']) == "")
			$segments['action'] = "index";
		if(trim($segments['module']) != "")
			$path .= "\\{$segments['module']}";
		$path .= "\\Controllers\\{$segments['controller']}";

		$objClass = $this->_loadClass($path, $segments['controller']."Controller");
		if(is_callable(array($objClass, $segments['action']))) {
			//if($segments['action'] != "index")
				call_user_func(array($objClass, $segments['action']));
		}
		else {
			throw new \Sincco\Sfphp\Exception("ERROR :: No es posible lanzar " . implode("->", $segments), 404);
		}
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