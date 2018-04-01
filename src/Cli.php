<?php
# NOTICE OF LICENSE
#
# This source file is subject to the Open Software License (OSL 3.0)
# that is available through the world-wide-web at this URL:
# http://opensource.org/licenses/osl-3.0.php
#
# -----------------------
# @author: Iván Miranda
# @version: 2.0.0
# -----------------------
# Ejecución de eventos según la petición realizada desde la línea de comandos
# -----------------------

namespace Sincco\Sfphp;

use Sincco\Sfphp\Console;
use Sincco\Sfphp\Request;
use Sincco\Sfphp\ClassLoader;
use Sincco\Sfphp\Config\Reader;

final class Cli extends \stdClass {
	private $data;
	private $params;

	private static $_instance;

	public function __construct($url) {
		Logger::register();
		Paths::init();
		Messages::init();

		$_GET['url'] = $url;

		$_SERVER['SERVER_SOFTWARE'] = '';
		$_SERVER['REQUEST_METHOD'] = 'cli';

		$_config = Reader::get('app');
		if(isset($_config['timezone'])) {
			date_default_timezone_set($_config['timezone']);
		}

		$objClass = ClassLoader::load('Commands', Request::get('controller'), 'Command');
		if(is_callable(array($objClass, Request::get('action')))) {
			call_user_func(array($objClass, Request::get('action')));
		}
		else {
			throw new \Exception('No es posible lanzar ' . Request::get('controller') . '->' . Request::get('action'), 0);
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