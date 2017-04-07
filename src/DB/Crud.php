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

namespace Sincco\Sfphp\DB;

use Sincco\Tools\Singleton;

class Crud extends \stdClass {
// PDO
	protected $connector;
// Query segments
	private $table 	= NULL;
	private $where 	= array();
	private $joins	= array();
	private $order 	= array();
	private $fields = array();
// Params to be parsed
	private $params = array();
// Actual query
	private $query 	= NULL;


	public function connect($data = array()) {
		$this->connector =  Singleton::get( 'Sincco\Sfphp\DB\DataManager', $data, $data[ 'dbname' ] );
	}

	public function __call( $name, $args ) {
		array_unshift( $args, $name );
		return call_user_func_array( array( $this, 'table' ), $args );
	}

	public function init() {
		$this->where 	= array();
		$this->joins	= array();
		$this->order 	= array();
		$this->fields 	= array();
		$this->params = array();
		$this->query 	= NULL;
		return $this;
	}

	public function insert($data, $tabla=false) {
		$campos = [];
		$variables = [];
		foreach ($data as $campo => $valor){
			$campos[] = $campo;
			$variables[] = ":" . $campo;
		}
		$campos		= implode(",", $campos);
		$variables	= implode(",", $variables);
		if ($tabla) {
			$query = 'INSERT INTO ' . $tabla . ' (' . $campos . ') VALUES (' . $variables . ')';
		} else {
			$query = 'INSERT INTO ' . $this->table . ' (' . $campos . ') VALUES (' . $variables . ')';
		}
		return $this->connector->query($query, $data);
	}

	public function update($set,$where,$table=false) {
		if (!$table) {
			$table = $this->table;
		}
		$campos = [];
		$condicion = [];
		foreach ($set as $campo => $valor)
			$campos[] = $campo . "=:" . $campo;
		foreach ($where as $campo => $valor)
			$condicion[] = $campo . "=:" . $campo;
		$campos = implode(",", $campos);
		$condicion = implode(" AND ", $condicion);
		$query = 'UPDATE ' . $table . ' 
			SET ' . $campos . ' WHERE ' . $condicion;
		$parametros = array_merge($set, $where);
		return $this->connector->query($query, $parametros);
	}

	public function table( $name ) {
		$this->params = array();
		$this->table = $name;
		return $this;
	}

	public function fields( $fields = array(), $table = 'maintable' ) {
		array_push( $this->fields, $table . '.' . $fields );
		return $this;
	}

	public function join( $table, $on, $type = 'INNER') {
		array_push( $this->joins, serialize( array( $table, $on, $type ) ) );
		return $this;
	}

	public function where( $fields, $values, $logical = ' = ', $table = 'maintable', $condition = ' AND ' ) {
		array_push( $this->where, serialize( array( $fields, $values, $table, ' ' . $logical . ' ', $condition ) ) );
		array_push( $this->params, array( 'where' . $fields=>$values ) );
		return $this;
	}

	public function order( $fields, $order = ' ASC ' ) {
		array_push( $this->order, serialize( array( $fields, $order ) ) );
		return $this;
	}

	public function getCollection() {
		$params = array();
		foreach ( $this->params as $param ) {
			foreach ( $param as $key => $value ) {
				$params[ $key ] = $value;
			}
		}
		$this->generateSql();
		$data = $this->connector->query( $this->query, $params );
		$result = array();
		foreach ( $data as $row ) {
			$object = new \stdClass();
			foreach ( $row as $key => $value ) {
				$object->$key = $value;
			}
			array_push( $result, $object );
		}
		return $result;
	}

	public function getData() {
		$params = array();
		foreach ( $this->params as $param ) {
			foreach ( $param as $key => $value ) {
				$params[ $key ] = $value;
			}
		}
		$this->generateSql();
		return $this->connector->query( $this->query, $params );
	}

	/**
	 * Create an SQL instruction w/all segments defined
	 * @return string
	 */
	private function generateSql() {
		$query = 'SELECT ';
		if( array_count_values( $this->fields) )
			$query .= implode( ', ', $this->fields );
		else
			$query .= '*';
	// FROM
		if( ! is_null( $this->table ) )
			$query .= ' FROM ' . $this->table . ' AS maintable ';
	// JOINS
		$joins = '';
		foreach ( $this->joins as $join ) {
			$join = unserialize( $join );
			$joins .= ' ' . $join[2] . ' JOIN ' . $join[0] . ' ON (' . $join[1] . ') ';
		}
		if( strlen( trim( $joins ) ) )
			$query .= $joins;
	// WHERE
		$wheres = '';
		foreach ( $this->where as $where ) {
			$where = unserialize( $where );
			$wheres .= ( strlen( trim( $wheres ) ) ? $where[4] : '' ) . $where[2] . '.' . $where[0] . $where[3] . ':where' . $where[0];
		}
		if( strlen( trim( $wheres ) ) )
			$query .= ' WHERE ' . $wheres;
	// ORDER
		$orders = '';
		foreach ( $this->order as $order ) {
			$order = unserialize( $order );
			$orders .= $order[0] . $order[1];
		}
		if( strlen( trim( $orders ) ) )
			$query .= ' ORDER BY ' . $orders;
		$this->query = $query;
		return $query;
	}
}