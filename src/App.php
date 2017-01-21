<?php
# NOTICE OF LICENSE
#
# This source file is subject to the Open Software License (OSL 3.0)
# that is available through the world-wide-web at this URL:
# http://opensource.org/licenses/osl-3.0.php
#
# -----------------------
# @author: Iván Miranda (@deivanmiranda)
# @version: 1.0.0
# -----------------------

namespace Sincco\Sfphp;

use Sincco\Sfphp\Paths;
use Sincco\Sfphp\Messages;
use Sincco\Sfphp\Session;
use Sincco\Sfphp\Launcher;
use Sincco\Tools\Debug;
use Sincco\Sfphp\Translations;
use Sincco\Sfphp\Config\Reader;

final class App extends \stdClass
{
	static public function run()
	{
		try {
			Paths::init();
			Messages::init();
			Reader::get('app');
			if (!defined('DEV_SHOWERRORS')) {
				define('DEV_SHOWERRORS', false);
			}
			if (!defined('APP_KEY')) {
				define('APP_KEY', 'e77393ef-c24b-4ff5-81f7-ed9fa28b4fb8');
			}
			if (!defined('APP_NAME')) {
				define('APP_NAME', 'sfphp');
			}
			Translations::init();
			Session::get();
			new Launcher();
		}catch (\Exception $err) {
			$errorInfo = sprintf( '%s: %s in %s on line %s.',
				'Error',
				$err,
				$err->getFile(),
				$err->getLine()
			);
			Debug::dump( $errorInfo );
		}
	}
}