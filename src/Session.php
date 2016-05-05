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
# Clase para control de sesion en el framework
# -----------------------

namespace Sincco\Sfphp;

use Sincco\Sfphp;
use Sincco\Sfphp\Config\Reader;

final class Session extends \stdClass {
    
    protected static $instance;

    protected function __construct() {
            $config = Reader::get();
            $config = $config['session'];
            $httponly = true;
            $session_hash = 'sha512';
            if (in_array($session_hash, hash_algos()))
                ini_set('session.hash_function', $session_hash);
            ini_set('session.hash_bits_per_character', 5);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.gc_maxlifetime', $config['inactivity']);
            $cookieParams = session_get_cookie_params();
            if($cookieParams["lifetime"] == 0)
                $cookieParams["lifetime"] = 28800; #Se mantiene una sesion activa hasta por 8 horas en el navegador
            session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $config['ssl'], true); 
            session_save_path(PATH_SESSION);
            session_name($config['name']);
            session_start();
            $_cookie = $_SERVER['HTTP_COOKIE'];
            if(trim($_cookie) == "")
                $_cookie = $config['name'] . "=" . session_id();
            self::set('sincco\sfphp\client\browser', $_SERVER['HTTP_USER_AGENT']);
            self::set('sincco\sfphp\client\address', $_SERVER['REMOTE_ADDR']);
            if(is_null(self::get('sincco\sfphp\client\token')))
                self::set('sincco\sfphp\client\uid', md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].$_cookie));
            else {
                if(self::get('sincco\sfphp\client\uid') != md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].$_cookie))
                    throw new \Sincco\Sfphp\Exception('Violacion de seguridad', 403);
            }
            if(is_null(self::get('sincco\sfphp\client\token')))
                self::set('sincco\sfphp\client\token', md5(\Sincco\Sfphp\UUID::v4()));
    }

    public static function get($section = '') {
        if(trim(session_id()) == "")
            self::$instance = new self();
        if(strlen(trim($section))) {
            if(isset($_SESSION[$section]))
                return trim(Crypt::decrypt($_SESSION[$section]));
            else
                return NULL;
        }
        else
            return $_SESSION;
    }

    public function set($section, $valor) {
        if(trim(session_id()) == "")
            self::$instance = new self();
        $_SESSION[$section] = Crypt::encrypt($valor);
    }

    public function del($section) {
        unset($_SESSION[$section]);
    }
}