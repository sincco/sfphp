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
use Sincco\Sfphp\Messages;
use Sincco\Sfphp\Translations;
use Sincco\Tools\Tokenizer;
use Sincco\Sfphp\Request;
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
		$params = array('cache' => PATH_CACHE);
		if(defined('DEV_CACHE'))
			$params = array();
		$this->template = new \Twig_Environment($loader, $params);
		$this->_twigFunctions();
	}


	public function render($params = array())
	{
		$_parsed = $params;
		$params['_session'] = $_SESSION;
		$params['__messages'] = [];
		foreach (get_object_vars($this) as $key => $value) {
			$params[$key] = $value;
		}
		$params['__messages'] = Messages::get();
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
		$function = new \Twig_SimpleFunction('initJS', function () {
			return $this->_initJS();
		});
		$this->template->addFunction($function);
		$function = new \Twig_SimpleFunction('loadJS', function () {
			return $this->_loadJS();
		});
		$this->template->addFunction($function);
		$function = new \Twig_SimpleFunction('urlSection', function () {
			return $this->_urlSection();
		});
		$this->template->addFunction($function);
		$function = new \Twig_SimpleFunction('getUrl', function ($url) {
			return $this->_getUrl($url);
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
			return Session::get('sincco\login\token');
		}
	}

	private function _initJS() {
		$file = $this->file;
		$file = str_replace("Views/", "", $file);
		$file = str_replace("/", "_", $file);
		$file = strtolower($file);
		$file = str_replace(".html", "", $file);
		return $file . '.init();';
	}

	private function _loadJS() {
		$file = $this->file;
		$file = str_replace("Views/", "", $file);
		$file = str_replace("/", "_", $file);
		$file = strtolower($file);
		$file = str_replace(".html", "", $file);
		if (file_exists(PATH_ROOT . "public/js/views/" . $file . '.js')) {
			return BASE_URL . "public/js/views/" . $file . '.js';
		} else {
			return "";
		}
	}

	private function _urlSection() {
		$section = implode(" ", Request::get('path'));
		if (Request::get('controller') != "Index") {
			$section .= " " . Request::get('controller');
		}
		if (Request::get('action') != "Index") {
			$section .= " " . Request::get('action');
		}
		return strtoupper($section);
	}

	private function _getUrl($url) {
		return BASE_URL . $url;
	}

	private function grid($data, $selection=false)
	{
		$_html = '<table data-toggle="table" data-search="true" data-show-export="true" data-page-size="20" data-pagination="true" data-show-pagination-switch="true" data-show-columns="true" data-mobile-responsive="true" data-sortable="true"><thead><tr>';
		foreach (array_keys($data) as $col) {
			$_html .= '<th data-sortable="true">' . $col . '</th>';
		}
		$_html .= '</tr></thead><tbody>';
		foreach ($data as $row) {
			$_html .= '<tr>';
			foreach ($row as $value) {
				$_html .= '<td>' . $value . '</td>';
			}
			$_html .= '</tr>';
		}
		$_html .= '</tr></tbody></table>';
		return $_html;
	}
}