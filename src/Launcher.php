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
use Sincco\Sfphp\Translations;
use Sincco\Tools\Debug;
use Sincco\Sfphp\Plugger;
use Sincco\Sfphp\ClassLoader;

final class Launcher extends \stdClass {
	public function __construct() {
		//Translations::init();
		$_config = Reader::get('app');

		if(isset($_config['timezone']))
			date_default_timezone_set($_config['timezone']);

		Debug::path(PATH_LOGS);
		Debug::reporting(DEV_SHOWERRORS);

		Plugger::dispatchGlobal('pre', 'ResolveUrl');
		
		$path = "";
		$segments = Request::get('segments');


		if(trim($segments['controller']) == "")
			$segments['controller'] = "Index";
		if(trim($segments['action']) == "")
			$segments['action'] = "index";
		if(trim($segments['module']) != "")
			$path .= "\\{$segments['module']}";
		$path .= "\\Controllers\\{$segments['controller']}";
		if (trim($segments['module']) != '') {
			$observer = $segments['module'] . '_' . $segments['controller'] . '_' . $segments['action'];
		} else {
			$observer = $segments['controller'] . '_' . $segments['action'];
		}

		$objClass = ClassLoader::load($path, $segments['controller']."Controller");
		if(is_callable(array($objClass, $segments['action']))) {
			Plugger::dispatchAction('pre', $observer);
			call_user_func(array($objClass, $segments['action']));
			Plugger::dispatchAction('post', $observer);
		}
		else {
			if (DEV_SHOWERRORS) {
				Debug::dump("ERROR :: No es posible lanzar " . implode("->", $segments));
			} else {
				echo "404";
			}
		}
	}
}