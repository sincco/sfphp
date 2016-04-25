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

use Desarrolla2\Cache\Cache;
use Desarrolla2\Cache\Adapter\File;

final class Reader extends \stdClass {
	private static $instance;
	private $cache;

	private function __construct() {
		$adapter = new File(PATH_CACHE);
		$adapter->setOption('ttl', 3600);
		$this->cache = new Cache($adapter);
		if(is_null($this->cache->get('config'))) {
			$file = PATH_CONFIG . "/config.xml";
			if(file_exists(PATH_CONFIG . "/config_local.xml"))
				$file = PATH_CONFIG . "/config_local.xml";

			if(!file_exists($file)) {
				$_config = array();
			} else {
				$_config = self::xml2array(new \SimpleXMLElement(file_get_contents($file)));
				$this->cache->set('config', $_config, 3600);
			}
		}
	}

	public static function get($atributo = '') {
		if(!self::$instance instanceof self)
			self::$instance = new self();
		$_config = self::$instance->cache->get('config');
		self::defineConstants($_config);
		if(strlen(trim($atributo)))
			if(isset($_config[$atributo]))
				return $_config[$atributo];
			else
				return NULL;
		else
			return $_config;
	}

	private static function defineConstants($array) {
		if(!isset($array["front"]["url"]))
			$array["front"]["url"] = self::url();
		if(!defined("BASE_URL"))
			define("BASE_URL",$array["front"]["url"]);
		foreach ($array["app"] as $key => $value) {
			if(!defined(strtoupper("app_".$key)))
				define(strtoupper("app_".$key),$value);
		}
		foreach ($array["dev"] as $key => $value) {
			if(!defined(strtoupper("dev_".$key)))
				define(strtoupper("dev_".$key),$value);
		}
		foreach ($array["session"] as $key => $value) {
			if(!defined(strtoupper("session_".$key)))
				define(strtoupper("session_".$key),$value);
		}
	}

	private function xml2array($xml) {
		$resp = array();
		foreach ( (array) $xml as $indice => $nodo )
			$resp[$indice] = ( is_object ( $nodo ) ) ? self::xml2array($nodo) : $nodo;
		return $resp;
	}

	private function url() {
		return sprintf(
			"%s://%s%s",
			isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
			$_SERVER['SERVER_NAME'],
			$_SERVER['REQUEST_URI']
		);
	}
}