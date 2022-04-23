<?php
# NOTICE OF LICENSE
#
# This source file is subject to the Open Software License (OSL 3.0)
# that is available through the world-wide-web at this URL:
# http://opensource.org/licenses/osl-3.0.php
#
# -----------------------
# @author: Iván Miranda
# @version: 2.3.0
# -----------------------
# Create a new PDO connection
# -----------------------

namespace Sincco\Sfphp\DB;

use Sincco\Tools\Debug;
use Sincco\Sfphp\Config\Reader;
use Sincco\Sfphp\Crypt;

final class Connector extends \stdClass {
	private static $instance;
	private $conn;

	public static function get($dataBase) {
		if (!self::$instance instanceof self) {
			self::$instance = new self();
			$_config = Reader::get( 'bases' );
			$base = $_config[ $dataBase ];
			$base[ 'password' ] = Crypt::decrypt( $base[ 'password' ] );
			switch ($base['type']) {
				case 'mysql':
					$base['driver'] = 'pdo_mysql';
					break;
				default:
					$base['driver'] = 'pdo';
					break;
			}
			$connectionParams = [
				'dbname' => $base['dbname'],
				'user' => $base['user'],
				'password' => $base['password'],
				'host' => $base['host'],
				'driver' => $base['driver'],
			];
			self::$instance->conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
		}
		return self::$instance->conn;
	}
}