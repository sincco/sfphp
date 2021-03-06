<?php
# NOTICE OF LICENSE
#
# This source file is subject to the Open Software License (OSL 3.0)
# that is available through the world-wide-web at this URL:
# http://opensource.org/licenses/osl-3.0.php
#
# -----------------------
# @author: Iván Miranda
# @version: 2.0.0
# -----------------------
# Simplifies the data explotarion with PDO
# -----------------------

namespace Sincco\Sfphp\DB;

use Sincco\Sfphp\DB\Connector;
use Sincco\Sfphp\Logger;
use Desarrolla2\Cache\File;

class QueryManager extends Connector {

	private $sQuery;
	private $parameters;
	private $connectionData;
	private $cache;

	public function setTable($table) {
		$this->_mainTable = $table;
	}

	private function Init($query, $parameters = []) {
		try {
			$this->sQuery = $this->prepare($query);
			$this->bindMore($parameters);
			if (!empty($this->parameters)) {
				foreach ($this->parameters as $param => $value) {
					$type = self::PARAM_STR;
					switch ($value[1]) {
						case is_int($value[1]):
							$type = self::PARAM_INT;
							break;
						case is_bool($value[1]):
							$type = self::PARAM_BOOL;
							break;
						case is_null($value[1]):
							$type = self::PARAM_NULL;
							break;
					}
					$this->sQuery->bindValue($value[0], $value[1], $type);
				}
			}
			if (!empty($this->parameters)) {
				$this->sQuery->execute();
			} else
			{
				$this->sQuery->execute($parameters);
			}
		} catch (\PDOException $err) {
			Logger::error('Base de Datos', [$err, $err, $err->getFile(), $err->getLine()]);
			return false;
		}
		$this->parameters = array();
	}

	public function bind($para, $value) {
		if (is_array($this->parameters)) {
			$this->parameters[sizeof($this->parameters)] = [":" . $para , $value];
		}
	}

	public function bindMore($parray) {
		if (empty($this->parameters) && is_array($parray)) {
			$columns = array_keys($parray);
			foreach ($columns as $i => &$column) {
				$this->bind($column, $parray[$column]);
			}
		}
	}

	public function query($query, $params = null, $fetchmode = self::FETCH_ASSOC) {
		$response = false;
		$query = trim(str_replace("\r", " ", $query));
		$idQuery = md5($query . serialize($params));
		$idQuery = 'qry_' . $this->connectionData['type'].$idQuery;
		$cache = new File(PATH_CACHE);
		$rawStatement = explode(" ", preg_replace("/\s+|\t+|\n+/", " ", $query));
		$statement = strtolower($rawStatement[0]);
		if ($cache->has($idQuery)) {
			$reponse = $cache->get($idQuery);
		} else {
			$this->Init($query, $params);
			switch ( $statement ) {
				case 'select':
				case 'desc':
				case 'describe':
				case 'show':
					$response = $this->sQuery->fetchAll( $fetchmode );
					break;
				case 'insert':
					$response = $this->insertId();
					break;
				case 'update':
				case 'delete':
					$response = $this->sQuery->rowCount();
				default:
					$response = NULL;
					break;
			}
			if (intval(DEV_CACHE) == 1) {
				$cache->set($idQuery, $response);
			}
		}
		return $response;
	}

	public function queryObject($query, $params = null) {
		$response = false;
		$query = trim(str_replace("\r", " ", $query));
		$idQuery = md5($query . serialize($params));
		$idQuery = 'qry_' . $this->connectionData['type'].$idQuery;
		$cache = new File(PATH_CACHE);
		$rawStatement = explode(" ", preg_replace("/\s+|\t+|\n+/", " ", $query));
		$statement = strtolower($rawStatement[0]);
		if ($cache->has($idQuery)) {
			$reponse = $cache->get($idQuery);
		} else {
			$this->Init($query, $params);
			switch ( $statement ) {
				case 'select':
				case 'show':
					$response = $this->sQuery->fetchAll(self::FETCH_CLASS, '\Sincco\Sfphp\DB\Entity');
					break;
				default:
					$response = NULL;
					break;
			}
			if (intval(DEV_CACHE) == 1) {
				$cache->set($idQuery, $response);
			}
		}
		return $response;
	}

	public function insertId() {
		return $this->lastInsertId();
	}

	public function beginTransaction() {
		return $this->beginTransaction();
	}

	public function executeTransaction() {
		return $this->commit();
	}

	public function rollBack() {
		return $this->rollBack();
	}

	public function column($query, $params = null) {
		$this->Init($query, $params);
		$Columns = $this->sQuery->fetchAll(self::FETCH_NUM);
		$column = null;
		foreach ($Columns as $cells) {
			$column[] = $cells[0];
		}
		return $column;
	}

	public function row($query, $params = null, $fetchmode = self::FETCH_ASSOC) {
		$this->Init($query, $params);
		$result = $this->sQuery->fetch($fetchmode);
		$this->sQuery->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued,
		return $result;
	}

	public function single($query, $params = null) {
		$this->Init($query, $params);
		$result = $this->sQuery->fetchColumn();
		$this->sQuery->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued
		return $result;
	}

	public function direct($query, $params = null) {
		try {
			$this->Init($query, $params);
			$result = $this->sQuery->fetchColumn();
			$this->sQuery->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued
			return $result;
		} catch (\PDOException $err) {
			Logger::error('Base de Datos', [$err, $err, $err->getFile(), $err->getLine()]);
		}
	}
}
