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

final class Logger extends \stdClass {

	public function __construct($data) {
		$data = self::parseData($data);
		if (!is_dir(PATH_LOGS)) {
			mkdir(PATH_LOGS);
			chmod(PATH_LOGS, 0750);
			file_put_contents(PATH_LOGS . '/.htaccess', 'Options -Indexes');
		}
		if($log_file = fopen(PATH_LOGS . '/' . date('YW').'.err', 'a+')) {
			fwrite($log_file, date("mdGis") . "::\r\n");
			fwrite($log_file, $data . "\r\n");
			fwrite($log_file,'URL: http://'.$_SERVER['HTTP_HOST'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI']."\r\n");
			fwrite($log_file, 'SESSION: ' . "\r\n-->id: ".session_id()."\r\n-->data: \r\n");
			foreach ($_SESSION as $key => $value) {
				if(!is_array($value))
					fwrite($log_file, "-->-->{$key} = " . \Sincco\Sfphp\Crypt::decrypt($value) . "\r\n");
			}
			fwrite($log_file, "PHP ".phpversion()." - ".PHP_OS."(".PHP_SYSCONFDIR." ".PHP_BINARY.")\r\n");
			fwrite($log_file,"--------------------------------------------\r\n\r\n");
			fclose($log_file);
		} else
			echo 'No se puede escribir el log '.DEV_LOGFILE;
	}

	public static function log($data) {
		$data = self::parseData($data);
		if (!is_dir(PATH_LOGS)) {
			mkdir(PATH_LOGS);
			chmod(PATH_LOGS, 0750);
			file_put_contents(PATH_LOGS . '/.htaccess', 'Options -Indexes');
		}
		if($log_file = fopen(PATH_LOGS . '/' . date('YW').'.txt', 'a+')) {
			fwrite($log_file, $data);
			fwrite($log_file,"\r\n--------------------------------------------\r\n");
			fclose($log_file);
		} else
			echo 'No se puede escribir el log '.DEV_LOGFILE;
	}

	private function parseData($data, $tabs = "") {
		$return = "";
		if(is_array($data)) {
			foreach ($data as $key => $value) {
				$value = self::parseData($value, $tabs . "\t");
				$return .= "\r\n{$tabs}[{$key}] => {$value} ";
			}
		} else
			$return = $data;
		return $return;
	}
}