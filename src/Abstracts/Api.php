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
use Sincco\Tools\Tokenizer;

abstract class Api extends \Sincco\Sfphp\Abstracts\Generic
{

	public function getRequest($key = '')
	{
		return Request::get($key);
	}

	public function response($type, $data)
	{
		new Response($type, $data);
	}

	public function createToken($data, $password = APP_KEY, $duration = 3) {
		return Tokenizer::create($data, $password, $duration);
	}

	public function validateToken($data, $password = APP_KEY) {
		return Tokenizer::validate($data, $password);
	}
}