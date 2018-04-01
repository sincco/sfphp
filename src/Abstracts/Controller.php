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

namespace Sincco\Sfphp\Abstracts;

use Sincco\Sfphp\Request;
use Sincco\Sfphp\Response;
use Sincco\Tools\Singleton;

abstract class Controller extends \Sincco\Sfphp\Abstracts\Generic
{

	public function newView($view)
	{
		return Singleton::get('Sincco\Sfphp\Abstracts\View', $view, $view);
	}

	public function getRequest()
	{
		return Request::get();
	}

	public function response($type, $data)
	{
		new Response($type, $data);
	}

}