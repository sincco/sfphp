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

	function __call( $name, $args ) {
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

	/*
	public function save($id = "0") {
		$this->fields[$this->pk] = (empty($this->fields[$this->pk])) ? $id : $this->fields[$this->pk];
		$fieldsvals = '';
		$columns = array_keys($this->fields);
		foreach($columns as $column)
		{
			if($column !== $this->pk)
			$fieldsvals .= $column . " = :". $column . ",";
		}
		$fieldsvals = substr_replace($fieldsvals , '', -1);
		if(count($columns) > 1 ) {
			$sql = "UPDATE " . $this->table .  " SET " . $fieldsvals . " WHERE " . $this->pk . "= :" . $this->pk;
			if($id === "0" && $this->fields[$this->pk] === "0") { 
				unset($this->fields[$this->pk]);
				$sql = "UPDATE " . $this->table .  " SET " . $fieldsvals;
			}
			return $this->exec($sql);
		}
		return null;
	}
	public function create() { 
		$bindings   	= $this->fields;
		if(!empty($bindings)) {
			$fields     =  array_keys($bindings);
			$fieldsvals =  array(implode(",",$fields),":" . implode(",:",$fields));
			$sql 		= "INSERT INTO ".$this->table." (".$fieldsvals[0].") VALUES (".$fieldsvals[1].")";
		}
		else {
			$sql 		= "INSERT INTO ".$this->table." () VALUES ()";
		}
		return $this->exec($sql);
	}
	public function delete($id = "") {
		$id = (empty($this->fields[$this->pk])) ? $id : $this->fields[$this->pk];
		if(!empty($id)) {
			$sql = "DELETE FROM " . $this->table . " WHERE " . $this->pk . "= :" . $this->pk. " LIMIT 1" ;
		}
		return $this->exec($sql, array($this->pk=>$id));
	}
	public function find($id = "") {
		$id = (empty($this->fields[$this->pk])) ? $id : $this->fields[$this->pk];
		if(!empty($id)) {
			$sql = "SELECT * FROM " . $this->table ." WHERE " . $this->pk . "= :" . $this->pk . " LIMIT 1";	
			
			$result = $this->connector->row($sql, array($this->pk=>$id));
			$this->fields = ($result != false) ? $result : null;
		}
	}

	public function search($fields = array(), $sort = array()) {
		$bindings = empty($fields) ? $this->fields : $fields;
		$sql = "SELECT * FROM " . $this->table;
		if (!empty($bindings)) {
			$fieldsvals = array();
			$columns = array_keys($bindings);
			foreach($columns as $column) {
				$fieldsvals [] = $column . " = :". $column;
			}
			$sql .= " WHERE " . implode(" AND ", $fieldsvals);
		}
		
		if (!empty($sort)) {
			$sortvals = array();
			foreach ($sort as $key => $value) {
				$sortvals[] = $key . " " . $value;
			}
			$sql .= " ORDER BY " . implode(", ", $sortvals);
		}
		return $this->exec($sql);
	}
	public function all(){
		return $this->connector->query("SELECT * FROM " . $this->table);
	}	
	*/

}