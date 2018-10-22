<?php
# NOTICE OF LICENSE
#
# This source file is subject to the Open Software License (OSL 3.0)
# that is available through the world-wide-web at this URL:
# http://opensource.org/licenses/osl-3.0.php
#
# -----------------------
# @author: Iván Miranda (@deivanmiranda)
# @version: 2.0.0
# -----------------------

namespace Sincco\Sfphp;

use Sincco\Sfphp\ClassLoader;
use Sincco\Sfphp\Crypt;
use Sincco\Sfphp\DB\DataManager;
use Sincco\Sfphp\Config\Writer;
use Sincco\Sfphp\Config\Reader;
use Sincco\Tools\Singleton;

use League\CLImate\CLImate;
use Composer\Factory;
use Composer\Console\Application;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\ArrayInput;

final class Console extends \stdClass {

	public function run($argv) {
		Paths::init();
		Reader::get('app');
		Translations::init();

		array_shift($argv);
		$arguments = $argv;
		$command = array_shift($arguments);

		// It's a petition for an URL registered on system
		$_SERVER['SERVER_SOFTWARE'] = '';
		$_SERVER['REQUEST_METHOD'] = 'cli';
		$_config = Reader::get('app');
		if(isset($_config['timezone'])) {
			date_default_timezone_set($_config['timezone']);
		}
		
		$params = [];
		foreach ($arguments as $_param) {
			$param = explode('=', $_param);
			$params[$param[0]] = $param[1];
		}

		// Internal Functions
		if(is_callable(array($this,str_replace(':', '_', $command)))) {
			$params = [];
			$ref = new \ReflectionMethod($this,str_replace(':', '_', $command));
			$int = 0;
			foreach ($ref->getParameters() as $key => $value) {
				$int++;
			 	$cli = new CLImate;
			 	$input = $cli->input($value->name . '?');
			 	$params[]= $input->prompt();
			}
			call_user_func_array(array($this,str_replace(':', '_', $command)), $params);
		}
		else {
			$command = explode(':', ucfirst($command));
			$objClass = ClassLoader::load([], $command[0], 'Command');
			if(is_callable(array($objClass, $command[1]))) {
				call_user_func_array(array($objClass, $command[1]), $params);
			}
			else {
				throw new \Exception('No es posible lanzar ' . $command[0] . '->' . $command[1], 0);
			}
		}
	}

	public function help_() {
		$commands = get_class_methods($this);
		$data = [];
		foreach ($commands as $command) {
			$command = str_replace('help_', 'help', $command);
			$command = explode('_', $command);
			if (count($command) > 1){
				$data[] = ['Comandos'=>implode(':', $command)];
			}
		}
		$cli = new CLImate;
		$cli->table($data);
	}

	// ------------------------------
	// ------------------ APP section
	// ------------------------------

	/**
	 * Defines main configuration app
	 */
	public function app_init($nombreApp, $cliente, $url, $force_yN = 'N') {
		$climate = new CLImate;
		$_SERVER['SERVER_NAME'] = NULL;
		$_SERVER['REQUEST_URI'] = NULL;
		$config = Reader::get();
		if (count($config)) {
			if (strtolower(trim($force_yN)) == 'y') {
				$this->cache_clean();
			}
			else {
				$climate->red('Ya existe una configuración, si desea reiniciar la APP especifique la opción "force"');
			}
		}
		$climate->green('Reiniciando configuración de aplicación...');
		$_llave_encripcion = Crypt::newKey();
		$bases = [];
		$_config = [
			'app' => [
				'key' => $_llave_encripcion,
				'name' => $nombreApp,
				'company' => $cliente,
				'timezone' => 'America/Mexico_City',
			],
			'front' => [
				'url' => $url,
			],
			'bases' => $bases,
			'sesion' => [
				'type' => 'DEFAULT',
				'name' => str_replace(' ', '', strtolower($nombreApp)),
				'ssl' => 0,
				'inactivity' => 300,
			],
			'dev' => [
				'showerrors' => 1,
			],
		];
		if (!defined('APP_KEY')) {
			define('APP_KEY', $_llave_encripcion);
		}

		$fileSystem = new Filesystem();
		if (!$fileSystem->exists('etc/config')) {
			$fileSystem->mkdir('etc/config/', 0750);
		}

		Writer::write($_config, 'config', 'etc/config/config.xml');
		$fileSystem->chmod('etc/config/config.xml', 0750);
		$climate->lightGreen('Archivo de configuración inicializado');
	}

	/**
	 * Adds new connection data for a DB
	 * @return none           
	 */
	public function app_db($identificador, $tipo, $host, $user, $dbname, $password){
		$fileSystem = new Filesystem();
		$climate = new CLImate;
		$climate->green('Registrando base de datos...');
		$config = Reader::get();
		$config['bases'][$identificador]['type'] = $tipo;
		$config['bases'][$identificador]['host'] = $host;
		$config['bases'][$identificador]['user'] = $user;
		$config['bases'][$identificador]['dbname'] = $dbname;
		$config['bases'][$identificador]['password'] = Crypt::encrypt($password);
		Writer::write($config, 'config', 'etc/config/config.xml');
		$fileSystem->chmod('etc/config/config.xml', 0750);
		$climate->lightGreen('OK');
	}

	/**
	 * Apply update for app
	 * @return none
	 */
	public function app_update() {
		$climate = new CLImate;
		$progress = $climate->progress()->total(2);
		$progress->advance(1, 'Actualizando dependencias...');
		$app = new Application();
		$factory = new Factory();
		$output = $factory->createOutput();
		$input = new ArrayInput(array(
		  'command' => 'update',
		));
		$input->setInteractive(false);
		$cmdret = $app->doRun($input,$output);		
		$progress->advance(1, 'Hecho');
		$climate->lightGreen('Ok');
	}

	// ------------------------------
	// ---------------- CACHE section
	// ------------------------------

	/**
	 * Clean app cache
	 * @return none
	 */
	public function cache_clean() {
		$file = PATH_CACHE;
		$climate = new CLImate;
		$climate->green('Limpiando cache...');
		$files = glob($file . '/*'); 
		foreach($files as $file){
			if (is_dir($file) and !in_array($file, array('..', '.')))  {
				$this->cache_clean($file);
				rmdir($file);
			} else if(is_file($file) and ($file != __FILE__)) {
				unlink($file); 
			}
		}
		$climate->lightGreen('Ok');
	}

	public function cache_off() {
		$climate = new CLImate;
		$climate->green('Desactivando cache...');
		$config = Reader::get();
		$config['dev']['cache'] = 0;
		Writer::write($config, 'config', 'etc/config/config.xml');
		$fileSystem = new Filesystem();
		$fileSystem->chmod('etc/config/config.xml', 0750);
		$climate->lightGreen('Ok');
	}

	public function cache_on() {
		$climate = new CLImate;
		$climate->green('Activando cache...');
		$config = Reader::get();
		$config['dev']['cache'] = 1;
		Writer::write($config, 'config', 'etc/config/config.xml');
		$fileSystem = new Filesystem();
		$fileSystem->chmod('etc/config/config.xml', 0750);
		$climate->lightGreen('Ok');
	}

	public function cache_status() {
		$climate = new CLImate;
		$climate->green('Estado de cache: ');
		$config = Reader::get();
		isset($config['dev']['cache']) ? $config['dev']['cache'] : $config['dev']['cache'] = 0;
		$climate->lightGreen($config['dev']['cache'] == 1 ? 'Activo' : 'Inactivo');
	} 

	// ------------------------------
	// -------------- SESSION section
	// ------------------------------

	/**
	 * Terminate app sessions
	 * @return none
	 */
	public function session_clean() {
		$file = PATH_SESSION;
		$climate = new CLImate;
		$climate->green('Cerrando sesiones...');
		$files = glob($file . '/*'); 
		foreach($files as $file){
			if (is_dir($file) and !in_array($file, array('..', '.')))  {
				$this->cache_clean($file);
				rmdir($file);
			} else if(is_file($file) and ($file != __FILE__)) {
				unlink($file); 
			}
		}
		$climate->lightGreen('Ok');
	}

}