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
# Carga de clases
# -----------------------

namespace Sincco\Sfphp;

use Sincco\Sfphp\ClassLoader;
use Sincco\sfphp\Logger;

final class ClassLoader extends \stdClass {

	public static function load($path, $class, $type = 'Controller') {
		try{
			$_path = implode('/', $path) . '/' . $type . 's/' . $class;
			if (file_exists(PATH_ROOT.'/app/'.$_path.'.php')) {
				require_once(PATH_ROOT.'/app/'.$_path.'.php');
				$class .= $type;
				return new $class();
			} else {
				return new \stdClass();
			}
		} catch (\Error $err) {
			errorException(new \ErrorException($err, 0, 0, $err->getFile(), $err->getLine()));
		}
	}
}