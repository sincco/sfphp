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

use Sincco\Sfphp\Translations;

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
		$params = array('cache' => PATH_CACHE);
		if(defined('DEV_CACHE'))
			$params = array();
		$this->template = new \Twig_Environment($loader, $params);
		$function = new \Twig_SimpleFunction('translate', function ($text) {
			return $this->translate($text);
		});
		$this->template->addFunction($function);
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

	private function translate($text)
	{
		$translate = $text;
		if (defined('APP_TRANSLATE')) {
			$translate = Translations::get($text, APP_TRANSLATE);
		}
		return $translate;
	}
}