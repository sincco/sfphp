<?php

namespace Sincco\Sfphp\DB;

class Entity extends ORM{
	//protected $_dataBaseConnection;
	private $_table;
	private $_tableUniqueKey;
	private $_tableFields;
	private $_fields;

	public function __get( $fieldName ) {
		$fieldName = $this->_camelToUnderscore($fieldName);
		return $this->_fields[$fieldName];
	}

	public function __set( $fieldName, $value ) {
		$fieldName = $this->_camelToUnderscore($fieldName);
		$this->_fields[$fieldName] = $this->_sanitizeValue($value);
	}

	public function _setKeys($table, $key, $columns) {
		$this->_table = $table;
		$this->_tableUniqueKey = $key;
		$this->_tableFields = $columns;
	}

	public function _setDataBase( $data ) {
		parent::_setDataBase($data);
	}

	public function save() {
		$response;
		$fields = [];
		$columns = array_column($this->_tableFields, 'Field');
		foreach ($this->_tableFields as $field) {
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
		$setClause = implode(',',$fields);
		$keyField = $this->_tableUniqueKey;
		$keyValue = $this->_fields[$keyField];
		$sqlQuery = 'UPDATE `'.$this->_table.'` SET '.$setClause.' WHERE ' . $keyField . ' = :'. $keyField . ';';
		$params[$keyField] = $keyValue;
		$this->_connect();
		$response = $this->_->query($sqlQuery, $params);
		return $response;
	}

	public function delete() {
		$response;
		$params = [];
		$keyField = $this->_tableUniqueKey;
		$keyValue = $this->_fields[$keyField];
		$sqlQuery = 'DELETE FROM `'.$this->_table.'` WHERE ' . $keyField . ' = :'. $keyField . ';';
		$params[$keyField] = $keyValue;
		$this->_connect();
		$response = $this->_->query($sqlQuery, $params);
		return $response;
	}

	private function _sanitizeValue( $value ) {
		$value = addslashes($value);
		$value = filter_var($value, FILTER_SANITIZE_STRING);
		$value = strip_tags($value);
		return $value;
	}
}