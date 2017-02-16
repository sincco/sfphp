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
# Ejecución de eventos según la petición realizada desde la línea de comandos
# -----------------------

namespace Sincco\Sfphp;

use Sincco\Sfphp\Request;
use Sincco\Sfphp\Config\Reader;
use Sincco\Tools\Debug;

final class Cli extends \stdClass {
	private $data;
	private $params;

	private static $_instance;

	public function __construct($url) {
		$_GET[ 'url' ] = $url;

		$_SERVER['SERVER_SOFTWARE'] = '';
		$_SERVER['REQUEST_METHOD'] = 'cli';

		#$segments = Request::get();
		#$segments = $segments[ 'segments' ];

		$_config = Reader::get('app');
		if(isset($_config['timezone']))
			date_default_timezone_set($_config['timezone']);

		Debug::path(PATH_LOGS);
		Debug::reporting(DEV_SHOWERRORS);
		Debug::cli(1);

		$path = "";

		$objClass = ClassLoader::load(Request::get('path'), Request::get('controller'));
		if(is_callable(array($objClass, Request::get('action')))) {
			#Plugger::dispatchAction('pre', $observer);
			call_user_func(array($objClass, Request::get('action')));
			#Plugger::dispatchAction('post', $observer);
		}
		else {
			Debug::dump("ERROR :: No es posible lanzar " . implode("->", $segments));
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