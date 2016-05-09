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

abstract class Helper extends \stdClass {

	public function getModel( $model ) {
		$path = explode( '\\', $model );
		array_push( $path, $path[( count( $path ) - 1 )] );
		$path[count( $path ) - 2] = 'Models';
		include_once( PATH_ROOT . '/app/' . implode( '/', $path ) . '.php' );
		$class = $path[count( $path ) - 1]."Model";
		return new $class();
	}
	
}