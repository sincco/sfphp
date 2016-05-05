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

namespace Sincco\Sfphp;

final class Exception extends \Exception {
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

	public static function logError( $err ) {
		$errorInfo = sprintf( '%s: %s in %s on line %s.',
			self::getErrorType( $err->getCode() ),
			$err,
			$err->getFile(),
			$err->getLine()
		);
		new \Sincco\Sfphp\Logger($errorInfo);
		return $errorInfo;
	}

	public static function screenError ( $err ) {
		self::logError($err);
		$errorInfo = sprintf( '%s: %s in %s on line %s.',
			self::getErrorType( $err->getCode() ),
			$err,
			$err->getFile(),
			$err->getLine()
		);
		switch ($err->getCode()) {
			case 404:
			case 403:
				if(file_exists(PATH_ERROR_TEMPLATE . '/template.html')) {
					$loader = new \Twig_Loader_Filesystem(PATH_ROOT . '/errors');
					$twig = new \Twig_Environment($loader, array(
						'cache' => PATH_CACHE,
					));
					echo $twig->render('template.html', array('file' => 'In ' . $err->getFile() . ' on line ' . $err->getLine(), 'description' => $err, 'type' => self::getErrorType($err->getCode()), 'code' => $err->getCode()));
					die();
				} else {
					echo $errorInfo;
					header("HTTP/1.0 " . $err->getCode() . " " . $errorInfo);
					die();
				}
				break;
			default:
				break;
		}
	}

	private static function getErrorType( $errorNumber ) {
		switch ( $errorNumber ) {
			case E_NOTICE:
			case E_USER_NOTICE:
				$type = 'Notice';
				break;
			case E_WARNING:
			case E_USER_WARNING:
				$type = 'Warning';
				break;
			case E_ERROR:
			case E_USER_ERROR:
				$type = 'Fatal Error';
				break;
			default:
				$type = 'Framework Exception';
			break;
		}
		return $type;
	}
}
