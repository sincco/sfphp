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
use Sincco\Sfphp\Response;
use Sincco\Sfphp\Translations;
use Sincco\Tools\Debug;
use Sincco\Sfphp\Plugger;
use Sincco\Sfphp\ClassLoader;

final class Launcher extends \stdClass {
	public function __construct() {
		$_config = Reader::get('app');

		if(isset($_config['timezone']))
			date_default_timezone_set($_config['timezone']);

		Debug::path(PATH_LOGS);
		Debug::reporting(DEV_SHOWERRORS);

		Plugger::dispatchGlobal('pre', 'ResolveUrl');

		$observer = implode('_', Request::get('path')) . '_' . Request::get('controller') . '_' . Request::get('action');
		$objClass = ClassLoader::load(Request::get('path'), Request::get('controller'));
		if(is_callable(array($objClass, Request::get('action')))) {
			Plugger::dispatchAction('pre', $observer);
			call_user_func(array($objClass, Request::get('action')));
			Plugger::dispatchAction('post', $observer);
		}
		else {
			if (DEV_SHOWERRORS) {
				$segments = Request::get('path');
				$segments[] = Request::get('controller');
				$segments[] = Request::get('action');
				Debug::dump("ERROR :: No es posible lanzar " . implode("->", $segments));
			} else {
				new Response('htmlstatuscode', '404 Not Found');
			}
		}
	}
}