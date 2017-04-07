<?php
# NOTICE OF LICENSE
#
# This source file is subject to the Open Software License (OSL 3.0)
# that is available through the world-wide-web at this URL:
# http://opensource.org/licenses/osl-3.0.php
#
# -----------------------
# @author: Iván Miranda (@deivanmiranda)
# @version: 1.0.0
# -----------------------

namespace Sincco\Sfphp;

use Sincco\Sfphp\Cli;
use Sincco\Tools\Singleton;
use Sincco\Sfphp\Config\Writer;
use Sincco\Sfphp\Config\Reader;
use Sincco\Sfphp\Crypt;
use Sincco\Sfphp\DB\DataManager;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

final class Console extends \stdClass {

	public function run($argv) {
		Paths::init();
		Reader::get('app');

		array_shift($argv);
		$arguments = $argv;

		// It's a petition for an URL registered on system
		if(count($arguments) == 1 AND strtolower(trim($arguments[0])) != 'help'){
			new Cli('/' . $arguments[0]);
			return;
		}

		// If it's an internal function on this script
		$command = array_shift($argv);
		$action = array_shift($argv);
		$params = $argv;

		// Check if its an function registered on this script
		// if not launc the cli interface of framework
		if(is_callable(array($this,$command . '_' . $action)))
			call_user_func_array(array($this,$command . '_' . $action), $params);
		else
			echo 'No existe el comando solicitado';
	}

	public function help_() {
		echo 'Comandos disponibles' . PHP_EOL;
		$commands = get_class_methods($this);
		foreach ($commands as $command) {
			$command = explode('_', $command);
			if(count($command) > 1){
				echo '     ' . implode(' ', $command) ;
				$ref = new \ReflectionMethod($this,implode('_', $command));
				$params = [];
				foreach ($ref->getParameters() as $key => $value) {
				 	$params[] = $value->name;
				 }
				echo ' [' . implode('] [', $params) . ']' . PHP_EOL;
			}
		}
	}

	// ------------------------------
	// ------------------ APP section
	// ------------------------------

	/**
	 * Defines main configuration app
	 * @param  string $cia
	 * @param  string $url
	 * @return none
	 */
	public function app_init($app, $cia, $url, $force = '') {
		$_SERVER['SERVER_NAME'] = NULL;
		$_SERVER['REQUEST_URI'] = NULL;
		$config = Reader::get();
		if(count($config)) {
			if(strtolower(trim($force)) == 'force')
				$this->cache_clean();
			else
				die('Ya existe una configuración, si desea reiniciar la APP especifique la opción "force"'.PHP_EOL);
		}
		echo 'Reiniciando configuración de aplicación...' . PHP_EOL;
		$_llave_encripcion = strtoupper(md5(microtime().rand()));
		$bases = [];
		$_config = [
			'app' => [
				'key' => $_llave_encripcion,
				'name' => $app,
				'company' => $cia,
				'timezone' => 'America/Chicago',
			],
			'front' => [
				'url' => $url,
			],
			'bases' => $bases,
			'sesion' => [
				'type' => 'DEFAULT',
				'name' => str_replace(' ', '', strtolower($app)),
				'ssl' => 0,
				'inactivity' => 300,
			],
			'dev' => [
				'showerrors' => 1,
			],
		];
		if(!defined('APP_KEY')) {
			define('APP_KEY', $_llave_encripcion);
		}
		Writer::write($_config, 'config', 'etc/config/config.xml');
		chmod("./etc/config/config.xml", 0775);
		echo 'Archivo de configuración inicializado' . PHP_EOL;
	}

	/**
	 * Adds new connection data for a DB
	 * @param  string $id       
	 * @param  string $type     
	 * @param  string $host     
	 * @param  string $user     
	 * @param  string $bdname   
	 * @param  string $password 
	 * @return none           
	 */
	public function app_db($id, $type, $host, $user, $dbname, $password){
		echo 'Registrando base de datos...' . PHP_EOL;
		$config = Reader::get();
		$config['bases'][$id]['type'] = $type;
		$config['bases'][$id]['host'] = $host;
		$config['bases'][$id]['user'] = $user;
		$config['bases'][$id]['dbname'] = $dbname;
		$config['bases'][$id]['password'] = Crypt::encrypt($password);
		Writer::write($config, 'config', 'etc/config/config.xml');
		chmod("./etc/config/config.xml", 0775);
		echo 'OK' . PHP_EOL;
	}

	/**
	 * Apply update for app
	 * @return none
	 */
	public function app_update() {
		echo 'Aplicando actualizaciones...' . PHP_EOL;
		exec('git pull origin master');
		exec('composer update');
		echo 'Terminado' . PHP_EOL;
	}

	// ------------------------------
	// ------------------- DB section
	// ------------------------------

	/**
	 * Create an backup script for a DB
	 * @param  string $dbId
	 * @return none      
	 */
	public function db_backup($dbId) {
		if(trim($dbId) == '')
			$dbId = 'default';
		$data = Reader::get('bases');
		$data = $data[$dbId];
		$data['password'] = trim(Crypt::decrypt($data['password']));
		$db = new DataManager($data);
		
		echo 'Respaldando información...' . PHP_EOL;

		if($data['type'] == 'firebird') {
			$data['dbname'] = $dbId;
			$query = 'select rdb$relation_name AS Tables_in_' . $data['dbname'] .
				' from rdb$relations where rdb$view_blr is null and (rdb$system_flag is null or rdb$system_flag = 0);';
		} else {
			$query = 'SHOW TABLES';
		}
		$tables = $db->query($query);

		$fileData = NULL;

		foreach($tables as $table) {
			if($data['type'] == 'firebird')
				$table = $table['TABLES_IN_' . strtoupper($data['dbname'])];
			else
				$table = $table['Tables_in_' . $data['dbname']];

			$tableData = $db->query('SELECT * FROM '.$table);
			
			if($data['type'] != 'firebird') {
				$fileData.= "-- ----------------------------------------------\n" .
					"DROP TABLE IF EXISTS `" . $table . "`;";
				$create = $db->query('SHOW CREATE TABLE ' . $table);
				$fileData.= "\n\n" . str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS ', $create[0]['Create Table']) . ";\n\n";
			} else {
				$fileData.= "-- ----------------------------------------------\n";
			}

			if(count($tableData) > 0) {
				$fileData .= 'INSERT INTO `' . $table . '` (' .
					implode(',', array_keys($tableData[0])) . ") VALUES \n";
				$dump = NULL;
				foreach($tableData as $register) {
					$dump[] = "('" . implode("','", array_values($register)) . "')";
				}
				$fileData .= implode(",\n", $dump) . ';';
				
			}
			$fileData.="\n\n";
		}

		$file = $dbId . '-backup-' . date("ymdGis") . '.sql';
		$handle = fopen('./bkp/' . $file ,'w+');
		fwrite($handle,$fileData);
		fclose($handle);
		echo 'Archivo ' . $file . ' creado' . PHP_EOL;
	}

	/**
	 * Restores an SQL backup script on a DB
	 * @param  string $dbId 
	 * @param  string $file 
	 * @return none       
	 */
	public function db_restore($dbId, $file) {
		echo 'Recuperando base de datos...' . PHP_EOL;
		$dbData = Reader::get('bases');
		$dbData = $dbData[$dbId];
		$dbData['password'] = trim(Crypt::decrypt($dbData['password']));
		$db = new DataManager($dbData);

		$lines = file($file);
		$query = '';
		foreach ($lines as $line) {
			if (substr($line, 0, 2) == '--' || $line == '')
			continue;
			$query .= $line;
			if (substr(trim($line), -1, 1) == ';') {
				$db->query($query);
				$query = '';
			}
		}
		echo 'Terminado' . PHP_EOL;
	}

	/**
	 * Updates the principal DB with updates scripts
	 * @return none
	 */
	public function db_update() {
		$data = Reader::get('bases');
		$data = $data['default'];
		$data['password'] = trim(Crypt::decrypt($data['password']));
		$db = new DataManager($data);
		echo 'Buscando actualizaciones disponibles...' . PHP_EOL;
		foreach (glob('*.upd.sql') as $update) {
			echo '--' . $update . PHP_EOL;
			$lines = file($update);
			$query = '';
			foreach ($lines as $line) {
				if (substr($line, 0, 2) == '--' || $line == '')
				continue;
				$query .= $line;
				if (substr(trim($line), -1, 1) == ';') {
					$db->query($query);
					$query = '';
				}
			}
			rename($update, './bkp/' . $update);
		}
		echo 'Actualizaciones aplicadas' . PHP_EOL;
	}

	// ------------------------------
	// ---------------- CACHE section
	// ------------------------------

	/**
	 * Clean app cache
	 * @return none
	 */
	public function cache_clean($file = PATH_CACHE) {
		echo 'Limpiando cache...' . PHP_EOL;
		$files = glob($file . '/*'); 
		foreach($files as $file){
			if (is_dir($file) and !in_array($file, array('..', '.')))  {
				$this->cache_clean($file);
				rmdir($file);
			} else if(is_file($file) and ($file != __FILE__)) {
				unlink($file); 
			}
		}
		echo 'Cache limpia' . PHP_EOL;
	}

	public function cache_off() {
		echo 'Apagando cache...' . PHP_EOL;
		$config = Reader::get();
		$config['dev']['cache'] = 0;
		Writer::write($config, 'config', 'etc/config/config.xml');
		chmod("./etc/config/config.xml", 0775);
		echo 'OK' . PHP_EOL;
	}

	public function cache_on() {
		echo 'Encendiendo cache...' . PHP_EOL;
		$config = Reader::get();
		$config['dev']['cache'] = 1;
		Writer::write($config, 'config', 'etc/config/config.xml');
		chmod("./etc/config/config.xml", 0775);
		echo 'OK' . PHP_EOL;
	}

	public function app_user($name, $email, $password) {
		include_once(PATH_ROOT . '/app/Helpers/UsersAccount.php');
		include_once(PATH_ROOT . '/app/Catalogo/Models/Usuarios.php');
		$class = 'UsersAccountHelper';
		$helper = Singleton::get($class);
		$mdlUsuarios = Singleton::get('UsuariosModel');
		$data = array('user'=>$name,'email'=>$email,'password'=>$password);
		$userId = $helper->createUser($data);
		
		$data = Reader::get('bases');
		$data = $data['default'];
		$data['password'] = trim(Crypt::decrypt($data['password']));
		$db = new DataManager($data);
		$data = $db->query('SELECT MAX(userId) userId FROM __usersControl');
		$userId = array_pop($data);
		$userId = $userId['userId'];
		echo 'Usuario creado ' . $userId . PHP_EOL;
	}

	public function extra_elasticemail($username, $api_key, $from, $test=0) {
		echo 'Registrando elastic email...' . PHP_EOL;
		$config = Reader::get();
		$config['elasticemail']['username'] = $username;
		$config['elasticemail']['api_key'] = $api_key;
		$config['elasticemail']['from'] = $from;
		$config['elasticemail']['test'] = $test;
		Writer::write($config, 'config', 'etc/config/config.xml');
		chmod("./etc/config/config.xml", 0775);
		echo 'OK' . PHP_EOL;
	}

	// ------------------------------
	// -------------- SESSION section
	// ------------------------------

	/**
	 * Terminate app sessions
	 * @return none
	 */
	public function session_clean($file = PATH_SESSION) {
		echo 'Cerrando sesiones...' . PHP_EOL;
		$files = glob($file . '/*'); 
		foreach($files as $file){
			if (is_dir($file) and !in_array($file, array('..', '.')))  {
				$this->session_clean($file);
				rmdir($file);
			} else if(is_file($file) and ($file != __FILE__)) {
				unlink($file); 
			}
		}
		echo 'Sesiones cerradas' . PHP_EOL;
	}

}