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

abstract class Controller {
	protected $_vista;
	protected $_modelo;

	# Cualquier instancia tiene el atributo de vista inicilizado
	public function __construct(){
		# $this->_vista = new Sfphp_Vista();
	}
	
	# Metodo mágico para cargar elementos al controlador
	public function __get($elemento) {
	# Modelos
		if("modelo" == substr($elemento,0,6)) {
			$clase = "Models_".substr($elemento,6);
			$_modelo = explode("_", get_class($this));
			$_modelo = strtolower($_modelo[0]);
			echo $clase;
			//return new $clase();
		}
	# Vistas
		if("vista" == substr($elemento,0,5)) {
			#var_dump($this->_vista);
			$this->_vista->dibuja(substr($elemento,5));
		}
	# Atributos
		if("get" == substr($elemento,0,3)) {
			$elemento = $this->nombreAtributo($elemento);
			return $this->$elemento;
		}
	}

	# Activa el método mágico SET para cualquier elemento privado
	public function __set($elemento, $valor) {
		$this->$elemento = $valor;
	}

	public function getModel($model) {
		$path = explode('\\', $model);
		array_push($path, $path[(count($path) - 1)]);
		$path[count($path) - 2] = 'Models';
		include_once(PATH_ROOT . '/app/' . implode('/', $path) . '.php');
		$class = $path[count($path) - 1]."Model";
		return new $class();
	}

	# Nombre del atributo a usarse en los __get __set
	private function nombreAtributo($atributo) {
		$atributo = str_replace("(", "", $atributo);
		$atributo = str_replace(")", "", $atributo);
		$atributo = "_".strtolower(substr($atributo, 3));
		return $atributo;
	}
}