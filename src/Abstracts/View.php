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

namespace Sincco\Sfphp\Abstracts;

use Sincco\Sfphp\Session;
use Sincco\Sfphp\Translations;
use Sincco\Tools\Tokenizer;
use Desarrolla2\Cache\Cache;
use Desarrolla2\Cache\Adapter\File;

final class View extends \stdClass {
	private $file;
	private $template;
	
	public function __construct($file)
	{
		$path = explode('\\', $file);
		array_push($path, $path[(count($path) - 1)]);
		$path[count($path) - 2] = 'Views';
		$this->file = array_pop($path) . '.html';
		$loader = new \Twig_Loader_Filesystem(PATH_ROOT . '/app/' . implode('/', $path));
		$params = [];
		if (DEV_CACHE == 1) {
			$params = array('cache' => PATH_CACHE);
		}
		$this->template = new \Twig_Environment($loader, $params);
		$this->_twigFunctions();
	}

	public function render($params = array())
	{
		$_parsed = $params;
		$params['_session'] = $_SESSION;
		foreach (get_object_vars($this) as $key => $value) {
			$params[$key] = $value;
		}
		echo $this->template->render($this->file, $params);
	}

	public function getContent($params = array())
	{
		$_parsed = $params;
		foreach (get_object_vars($this) as $key => $value) {
			$params[$key] = $value;
		}
		return $this->template->render($this->file, $params);
	}

	private function _twigFunctions() 
	{
		$function = new \Twig_SimpleFunction('translate', function ($text) {
			return $this->_translate($text);
		});
		$this->template->addFunction($function);
		$function = new \Twig_SimpleFunction('token', function ($type) {
			return $this->_token($type);
		});
		$this->template->addFunction($function);
	}

	private function _translate($text)
	{
		$translate = $text;
		if (defined('APP_TRANSLATE')) {
			$translate = Translations::get($text, APP_TRANSLATE);
		}
		return $translate;
	}

	private function _token($type)
	{
		if ($type == 'Generic') {
			$adapter = new File(PATH_CACHE);
			$adapter->setOption('ttl', 10800);
			$cache = new Cache($adapter);
			$token = $cache->get('token');
			if(is_null($token)) {
				$token = Tokenizer::create(['GENERIC_API'=>true], APP_KEY, 180);
				$cache->set('token', $token, 10800);
			}
			return $token;
		}
		if ($type == 'User') {
			//var_dump(Session::get());
			return Session::get('sincco\login\token');
		}
	}
}