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

use Sincco\Sfphp\DB\DataManager;
use Sincco\Tools\Singleton;

class Crud extends \stdClass {
	protected $connector;
	private $table;
	private $query;

	private $db;
	private $fields;

	/**
	 * Connects to a database
	 * @param  array  $data Connection information [type][host][user][dbname][password]
	 * @return [type]       [description]
	 */
	public function connect($data = array()) {
		$this->connector =  Singleton::get( 'Sincco\Sfphp\DB\DataManager', $data, $data[ 'dbname' ] );
	}

	/**
	 * Set table for CRUD
	 * @param string $table Table name
	 */
	public function setTable( $table ) {
		$this->table = $table;
	}

	/**
	 * Get table for CRUD
	 * @return string
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * Return all data on table
	 * @return array
	 */
	public function getAll() {
		$query = 'SELECT * FROM ' . $this->table;
		return $this->connector->query( $query );
	}

	public function getSql() {
		var_dump($this->fields);
	}

	public function init() {
		$this->fields 	= [];
		$this->query 	= NULL;
	}

	/**
	 * Returns a result for table $name.
	 * If $id is given, return the row with that id.
	 *
	 * Examples:
	 * $db->user()->where( ... )
	 * $db->user( 1 )
	 *
	 * @param string $name
	 * @param array $args
	 * @return Result|Row|null
	 */
	function __call( $name, $args ) {
		array_unshift( $args, $name );
		return call_user_func_array( array( $this, 'table' ), $args );
	}


	public function __set($name,$value){
		if(strtolower($name) === $this->pk) {
			$this->fields[$this->pk] = $value;
		}
		else {
			$this->fields[$name] = $value;
		}
	}
	public function __get($name)
	{	
		if(is_array($this->fields)) {
			if(array_key_exists($name,$this->fields)) {
				return $this->fields[$name];
			}
		}
		return null;
	}
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
	/**
	* @param array $fields.
	* @param array $sort.
	* @return array of Collection.
	* Example: $user = new User;
	* $found_user_array = $user->search(array('sex' => 'Male', 'age' => '18'), array('dob' => 'DESC'));
	* // Will produce: SELECT * FROM {$this->table_name} WHERE sex = :sex AND age = :age ORDER BY dob DESC;
	* // And rest is binding those params with the Query. Which will return an array.
	* // Now we can use for each on $found_user_array.
	* Other functionalities ex: Support for LIKE, >, <, >=, <= ... Are not yet supported.
	*/
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
	
	public function min($field)  {
		if($field)
		return $this->connector->single("SELECT min(" . $field . ")" . " FROM " . $this->table);
	}
	public function max($field)  {
		if($field)
		return $this->connector->single("SELECT max(" . $field . ")" . " FROM " . $this->table);
	}
	public function avg($field)  {
		if($field)
		return $this->connector->single("SELECT avg(" . $field . ")" . " FROM " . $this->table);
	}
	public function sum($field)  {
		if($field)
		return $this->connector->single("SELECT sum(" . $field . ")" . " FROM " . $this->table);
	}
	public function count($field)  {
		if($field)
		return $this->connector->single("SELECT count(" . $field . ")" . " FROM " . $this->table);
	}	
	
	/**
	 * Returns a result for table $name.
	 * If $id is given, return the row with that id.
	 *
	 * @param $name
	 * @param int|null $id
	 * @return Result|Row|null
	 */
	public function table( $name, $id = null ) {
		var_dump($name,$id);
		// ignore List suffix
		/*$name = preg_replace( '/List$/', '', $name );
		if ( $id !== null ) {
			$result = $this->createResult( $this, $name );
			if ( !is_array( $id ) ) {
				$table = $this->getAlias( $name );
				$primary = $this->getPrimary( $table );
				$id = array( $primary => $id );
			}
			return $result->where( $id )->fetch();
		}
		return $this->createResult( $this, $name );
		*/
	}

	public function where( $fields, $values, $condition ) {
		var_dump($fields,$values,$condition);
	}

}