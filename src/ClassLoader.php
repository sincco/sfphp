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
# Carga de clases
# -----------------------

namespace Sincco\Sfphp;

use Sincco\Sfphp\ClassLoader;
use Sincco\Tools\Debug;

final class ClassLoader extends \stdClass {

	public static function load($path, $class) {
		try{
			$_path = str_replace("\\", "/", $path);
			if(file_exists(PATH_ROOT."/app".$_path.".php")) {
				require_once(PATH_ROOT."/app".$_path.".php");
				return new $class();
			} else {
				return new \stdClass();
			}
		} catch(\Error $err) {
			$errorInfo = sprintf('%s: %s in %s on line %s.',
				'Error',
				$err,
				$err->getFile(),
				$err->getLine()
			);
            Debug::dump($errorInfo);
		}
	}
}