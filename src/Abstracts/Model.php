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

abstract class Model extends \Sincco\Sfphp\DB\Crud {
	
	public function __construct( $dataBase = NULL ) {
		if( is_null( $dataBase ) )
			$dataBase = 'default';
		$_config = Reader::get( 'bases' );
		$base = $_config[ $dataBase ];
		$base[ 'password' ] = Crypt::decrypt( $base[ 'password' ] );
		parent::connect( $base );
	}
}