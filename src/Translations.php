<?php
# NOTICE OF LICENSE
#
# This source file is subject to the Open Software License (OSL 3.0)
# that is available through the world-wide-web at this URL:
# http://opensource.org/licenses/osl-3.0.php
#
# -----------------------
# @author: Iván Miranda
# @version: 2.3.0
# -----------------------
# Carga de configuración de la APP
# -----------------------

namespace Sincco\Sfphp;

use Desarrolla2\Cache\File;

final class Translations extends \stdClass {
	private static $instance;
	private $cache;

	private function __construct()
	{
		$this->cache = new File(PATH_CACHE);
		if(is_null($this->cache->get('trn'))) {
			$translations = [];
			if (is_dir(PATH_LOCALE)) {
				$available = array_slice(scandir(PATH_LOCALE),2);
				foreach ($available as $locale) {
					$data = file(PATH_LOCALE . '/' . $locale);
					foreach ($data as $_trans) {
						$translations[basename($locale, ".csv")][] = str_getcsv($_trans);
					}
				}
				
			}
			$this->cache->set('trn', $translations);
		}
	}

	public static function get($text, $locale)
	{
		if(!self::$instance instanceof self)
			self::$instance = new self();
		$translations = self::$instance->cache->get('trn');
		$response = '';
		if (isset($translations[$locale])) {
			$translation = $translations[$locale];
			foreach ($translation as $_translation) {
				if (strtoupper(trim($_translation[0])) == strtoupper(trim($text))) {
					$response = $_translation[1];
					 break;
				}
			}
		}
		if (trim($response) == '') {
			$response = $text;
		}
		return $response;
	}

	public static function init()
	{
		if(!self::$instance instanceof self)
			self::$instance = new self();
	}
}
