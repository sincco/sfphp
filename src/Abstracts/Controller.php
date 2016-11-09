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

use Sincco\Tools\Debug;
use Sincco\Sfphp\Request;
use Sincco\Sfphp\Response;
use Sincco\Tools\Singleton;

abstract class Controller
{

	private $helpers;

	public function getModel($model)
	{
		$path = explode('\\', $model);
		array_push($path, $path[(count($path) - 1)]);
		$path[count($path) - 2] = 'Models';
		include_once(PATH_ROOT . '/app/' . implode('/', $path) . '.php');
		$class = $path[count($path) - 1]."Model";
		return Singleton::get($class);
	}

	public function newView($view)
	{
		return Singleton::get('Sincco\Sfphp\Abstracts\View', $view, $view);
	}

	public function helper($helper)
	{
		include_once(PATH_ROOT . '/app/Helpers/' . $helper . '.php');
		$class = $helper . "Helper";
		return Singleton::get($class);
	}

	public function getParams($param = '')
	{
		return Request::getParams($param);
	}

	public function getRequest()
	{
		return Request::get();
	}

	public function response($type, $data)
	{
		new Response($type, $data);
	}

	public function log($data)
	{
		Debug::log($data);
	}
}