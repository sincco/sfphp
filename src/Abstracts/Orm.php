<?php
namespace Sincco\Sfphp\Abstracts;

use Sincco\Sfphp\Config\Reader;
use Sincco\Sfphp\Crypt;

/**
 * Define un modelo con conexion a base de datos
 */
abstract class Orm extends \Sincco\Sfphp\DB\Crud {
	protected $_entity;

	public function save() {

		$class = new \ReflectionClass($this);
		$tableName = strtolower($class->getShortName());

		$propsToImplode = [];

		foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) { // consider only public properties of the providen 
		  $propertyName = $property->getName();
		  $propsToImplode[] = '`'.$propertyName.'` = "'.$this->{$propertyName}.'"';
		}

		$setClause = implode(',',$propsToImplode); // glue all key value pairs together
		$sqlQuery = '';

		#if ($this->id > 0) {
		  $sqlQuery = 'UPDATE `'.$tableName.'` SET '.$setClause.' WHERE id = '.$this->id;
		#} else {
		  $sqlQuery = 'INSERT INTO `'.$tableName.'` SET '.$setClause.', id = '.$this->id;
		#}
		return $sqlQuery;

		//$result = self::$db->exec($sqlQuery);

		//if (self::$db->errorCode()) {
		//	throw new \Exception(self::$db->errorInfo()[2]);
		//}

		//return $result;
	}
}