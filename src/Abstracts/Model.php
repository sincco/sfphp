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

use Sincco\Sfphp\Config\Reader;
use Sincco\Sfphp\Crypt;

abstract class Model extends \Sincco\Sfphp\DB\Connector {
	protected $db;
	protected $_campos = array();
	
	public function __construct($dataBase = 'default') {
		$_config = Reader::get('bases');
		$_base = $_config[$dataBase];
		$_base['password'] = Crypt::decrypt($_base['password']);
		parent::connectionData($_base);
	}
}