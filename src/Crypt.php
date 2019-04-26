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
# Ejecución de eventos según la petición realizada desde el navegador
# -----------------------

namespace Sincco\Sfphp;

use Defuse\Crypto\Key;
use Defuse\Crypto\Crypto;

final class Crypt extends \stdClass {

	public static function newKey() {
		$objKey = Key::createNewRandomKey();
		return $objKey->saveToAsciiSafeString();
	}

	public static function encrypt($data, $key = APP_KEY) {
		$objKey = Key::loadFromAsciiSafeString($key);
		return Crypto::encrypt($data, $objKey);
	}
	
	public static function decrypt($data, $key = APP_KEY) {
		$objKey = Key::loadFromAsciiSafeString($key);
		return Crypto::decrypt($data, $objKey);
	}
}