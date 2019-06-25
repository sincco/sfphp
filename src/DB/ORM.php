<?php

namespace Sincco\Sfphp\DB;

use Sincco\Tools\Singleton;

class ORM {
	// PDO
	protected $_;
	protected $_dataBaseConnection;
	// Actual Record
	protected $_actualEntity;
	// Actual Query
	protected $_sqlQuery;
	//
	protected $_from;
	protected $_joins;
	protected $_filter;
	protected $_limit;
	protected $_order;
	protected $_params;
	// Aditional Data
	private $_table;
	private $_methods;
	private $_tableUniqueKey;
	private $_tableFields;
	private $_fields;


	public function __call( $methodName, $args = [] ) {
		if (is_null($this->_methods))
		{
			$this->_methods = [];
		}
		$function = $this->_getFunctionCalled($methodName);
		switch ($function) {
			case 'filter':
				$field = str_replace($function, '', $methodName);
				$field = $this->_camelToUnderscore($field);
				if (count($args) < 2)
				{
					$args = array_pop($args);
				}
				$this->_processFilter($field,$args);
				$methodName = NULL;
				break;
			case 'fields':
				$table = str_replace($function, '', $methodName);
				$table = $this->_camelToUnderscore($table);
				$this->_from[$table] = $args[0];
				$methodName = NULL;
				break;
			case 'inner':
			case 'left':
			case 'right':
				$table = str_replace($function, '', $methodName);
				$table = $this->_camelToUnderscore($table);
				foreach ($args as $arg) {
					foreach ($arg as $type => $value) {
						$join = strtoupper($function) . ' JOIN ' . $table . ' ' . $type . ' (' . $this->_camelToUnderscore($value) . ')';
						$this->_joins[] =  $join;
						$this->_from[$table] = '*';
					}
				}
				$methodName = NULL;
				break;
			default:
				# code...
				break;
		}
		if (!is_null($methodName))
		{
			if (!in_array($methodName, $this->_methods))
			{
				$tableUniqueKey = $this->_camelToUnderscore($this->_tableUniqueKey);
				$this->_tableUniqueKey = array_pop($args);
				$this->_tableUniqueKey = $this->_camelToUnderscore($this->_tableUniqueKey);
				$this->_table = $this->_camelToUnderscore($methodName);
				$this->_from[$this->_table] = '*';
			}
		}
	}

	public function __construct() {
		$this->_reset();
	}

	public function __get( $fieldName ) {
		$fieldName = $this->_camelToUnderscore($fieldName);
		return $this->_fields[$fieldName];
	}

	public function __set( $fieldName, $value ) {
		$fieldName = $this->_camelToUnderscore($fieldName);
		$this->_fields[$fieldName] = $this->_sanitizeValue($value);
	}

	private function _processFilter( $field, $conditions ) {
		if (is_array($conditions))
		{
			foreach ($conditions as $key => $value) {
				switch (strtolower($key)) {
					case 'like':
						$type = 'like';
						$value = '%' . $value . '%';
						break;
					case '>=':
						$type = 'laet';
						break;
					case '<=':
						$type = 'loet';
						break;
					case '>':
						$type = 'lat';
						break;
					case '<':
						$type = 'lot';
						break;
					case 'in':
						$type = 'in';
						break;
					case '!=':
						$type = 'dif';
						break;
				}
				$var = $field . $type;
				$condition = $field . ' ' . $key . ' :' . $var;
				$this->_filter[] = $condition;
				$this->_params[$var] = $value;
					// default:
					// 	$filters = [];
					// 	foreach ($conditions as $key=>$value) {
					// 		$var = $field.$key;
					// 		$condition = $field . ' = :' . $var;
					// 		$filters[] = $condition;
					// 		$this->_params[$var] = $value;
					// 	}
					// 	$this->_filter[] = '(' . implode(' OR ', $filters) . ')';
					// 	break;
				//}
			}
		} else
		{
			$var = $field . 'eq';
			$condition = $field . ' = :' . $var;
			$this->_filter[] = $condition;
			$this->_params[$var] = $conditions;
		}
	}

	public function _camelToUnderscore($string, $us = "_") {
		$response = preg_replace(
			'/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', $us, $string);
		if (!is_array($response))
		{
			return strtolower($response);
		} else
		{
			return $string;
		}
	}

	private function _getFunctionCalled($methodName) {
		$expr = '/(?<=\s|^)[A-Z]/';
		preg_match_all('/[A-Z]/', $methodName, $matches, PREG_OFFSET_CAPTURE);
		if (isset($matches[0][0][1]))
		{
			return substr($methodName, 0, $matches[0][0][1]);
		} else
		{
			return $methodName;
		}
	}

	private function _sanitizeValue( $value ) {
		$value = filter_var($value, FILTER_SANITIZE_STRING);
		$value = htmlspecialchars($value);
		$value = strip_tags($value);
		return $value;
	}

	public function _reset() {
		$this->_ = NULL;
		$this->_methods = [];
		$class = new \ReflectionClass($this);
		foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
			$this->_methods[] = $method->name;
		}
		$this->_actualEntity = NULL;
		$this->_tableUniqueKey = NULL;
		$this->_table = NULL;
		$this->_sqlQuery = NULL;
		$this->_fields = [];
		$this->_from = [];
		$this->_joins = [];
		$this->_filter = [];
		$this->_order = [];
		$this->_params = [];
		$this->_limit = NULL;	}

	public function _setDataBase( $data ) {
		$this->_dataBaseConnection = $data;
	}

	public function __toString() {
		$filter = '';
		$from = [];
		foreach ($this->_from as $table=>$fields) {
			if (!is_array($fields))
			{
				$from[] = $table . '.'  . $this->_camelToUnderscore($fields);
			}else
			{
				foreach ($fields as $field) {
					$from[] = $table . '.'  . $this->_camelToUnderscore($field);
				}
			}
		}
		$from = implode(',', $from);
		$join = implode(' ', $this->_joins);
		$filter = implode(' AND ', $this->_filter);
		$query = 'SELECT ' . $from . ' ' . ' FROM ' . $this->_table . ' ' . $join;
		if (strlen(trim($filter)) > 0) {
			$query .= ' WHERE ' . $filter;
		}
		if (trim($this->_limit) != '')
		{
			$query .= ' ' . $this->_limit;
		}
		return $query;
	}

	public function _connect() {
		if (is_null($this->_)) {
			$this->_ =  Singleton::get( 'Sincco\Sfphp\DB\QueryManager', ['connectionData'=>$this->_dataBaseConnection], $this->_dataBaseConnection[ 'dbname' ] );
		}
	}

	public function count()
	{
		$filter = '';
		$join = implode(' ', $this->_joins);
		$filter = implode(' AND ', $this->_filter);
		$query = 'SELECT COUNT(*) count FROM ' . $this->_table . ' ' . $join;
		if (strlen(trim($filter)) > 0) {
			$query .= ' WHERE ' . $filter;
		}
		$this->_connect();
		$data = $this->_->query($query, $this->_params);
		$data = array_pop($data);
		return $data['count'];
	}

	public function getData( $query=NULL, $params=[] ) {
		if (is_null($query))
		{
			$sqlQuery = (string)$this;
			$this->_connect();
			return $this->_->query($sqlQuery, $this->_params);
		}
	}

	public function getCollection( $query=NULL, $params=[] ) {
		if (is_null($query))
		{
			$this->_connect();
			$response = $this->_->query('DESC ' . $this->_table);
			$this->_tableFields = $response;
			$sqlQuery = (string)$this;
			$response = $this->_->queryObject($sqlQuery, $this->_params);
			array_walk_recursive(
				$response, function (&$row) {
					$row->_setDataBase($this->_dataBaseConnection);
					$row->_setKeys($this->_table, $this->_tableUniqueKey, $this->_tableFields);
				}
			);
			return $response;
		}
	}

	public function pagination($offset, $limit)
	{
		$this->_limit = " LIMIT " . $offset . "," . $limit;
	}

	public function save() {
		$this->_connect();
		$response = $this->_->query('DESC ' . $this->_table);
		$this->_tableFields = $response;
		$fields = [];
		$columns = array_column($this->_tableFields, 'Field');
		foreach ($this->_tableFields as $field) {
			if (isset($this->_fields[$field['Field']]))
			{
				switch ($field['Type']) {
					case 'date':
						$value = $this->_fields[$field['Field']];
						if (is_null($value))
						{
							$value = 'NULL';
						} else
						{
							$value = '"' . $this->_fields[$field['Field']] . '"';
						}
						break;
					default:
						$value = $this->_fields[$field['Field']];
						if (is_null($value))
						{
							$value = 'NULL';
						} else
						{
							$value = '"' . $this->_fields[$field['Field']] . '"';
						}
						break;
				}
				$fields[] = '`' . $field['Field'] . '` = ' . $value;
			}
		}
		$setClause = implode(',',$fields);
		$sqlQuery = 'INSERT INTO  `'.$this->_table.'` SET '.$setClause . ';';
		$response = $this->_->query($sqlQuery);
		return $response;
	}
}