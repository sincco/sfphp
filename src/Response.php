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
# Manejo de respuestas
# -----------------------

namespace Sincco\Sfphp;

final class Response extends \stdClass {

	public function __construct($type, $data) {
		switch ( strtolower( $type ) ) {
			case 'json':
				if( gettype( $data ) == "array" )
					$data = $this->UTF8Parser( $data );
				$header = 'Content-Type: application/json';
				$data = json_encode( $data );
				break;
			case 'htmlstatuscode':
				$header = "HTTP/1.0 " . $data;
				break;
			default:
				break;
		}
		header($header);
		echo $data;
	}

	public static function UTF8Parser( $array ) {
		array_walk_recursive( $array, function( &$item, $key ){
			if(!mb_detect_encoding( $item, 'utf-8', true ))
				$item = utf8_encode( $item );
		});
		return $array;
	}
}