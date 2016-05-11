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
# Carga de configuración de la APP
# -----------------------

namespace Sincco\Sfphp\Config;

final class Writer extends \stdClass {
	private static $instance;

	public static function write ($arreglo, $root, $archivo) {
		if(!self::$instance instanceof self)
			self::$instance = new self();
		$_xml = new \SimpleXMLElement("<?xml version=\"1.0\"?><".$root."></".$root.">");
		self::array_to_xml($arreglo,$_xml);
		return $_xml->asXML($archivo);
	}

	private function array_to_xml($array, &$_xml) {
		foreach($array as $key => $value) {
			if(is_array($value)) {
				if(!is_numeric($key)){
					$subnode = $_xml->addChild("$key");
					self::array_to_xml($value, $subnode);
				} else{
					$subnode = $_xml->addChild("item$key");
					self::array_to_xml($value, $subnode);
				}
			} else {
				$_xml->addChild("$key",htmlspecialchars("$value"));
			}
		}
	}
}