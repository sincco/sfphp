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
# Create a new PDO connection
# -----------------------

namespace Sincco\Sfphp\DB;

use Sincco\Tools\Debug;

class Connector extends \PDO {

    public function __construct($connectionData) {
        $connection = NULL;
        if(!isset($connectionData["charset"]))
            $connectionData["charset"] = "utf8";
        $params = array();
        if($connectionData["type"] == "mysql")
            $params = array(self::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '. $connectionData["charset"]);
        else
            $params = array();
        try {

            switch ($connectionData["type"]) {
                case 'sqlsrv':
                    $hostname = $connectionData["type"].":Server=".$connectionData["host"].";";
                break;
                case 'mysql':
                    $hostname = $connectionData["type"].":host=".$connectionData["host"].";dbname=".$connectionData["dbname"];
                break;
                case 'firebird':
                    $params = array(
                    self::FB_ATTR_TIMESTAMP_FORMAT,"%d-%m-%Y",
                    self::FB_ATTR_DATE_FORMAT ,"%d-%m-%Y"
                   );
                    $hostname = $connectionData["type"].":dbname=".$connectionData["host"].$connectionData["dbname"].";charset=UTF8";
                break;
                default:
                    $hostname = $connectionData["type"].":host=".$connectionData["host"].";dbname=".$connectionData["dbname"];
                break;
            }
            parent::__construct($hostname, $connectionData['user'], trim($connectionData['password']), $params);
            $this->setAttribute(self::ATTR_ERRMODE, self::ERRMODE_EXCEPTION);
            $this->setAttribute(self::ATTR_EMULATE_PREPARES, false);
        } catch (\PDOException $err) {
            $errorInfo = sprintf('%s: %s in %s on line %s.',
                'Database Error',
                $err,
                $err->getFile(),
                $err->getLine()
           );
            Debug::dump($errorInfo);
        }
    }
}