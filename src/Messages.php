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

use Sincco\Sfphp\Translations;

final class Messages extends \stdClass
{
	static $messages;

	static public function init() {
		self::$messages = [];
	}

	static public function add($data, $class='message') {
		self::$messages[] = ['message'=>$data, 'class'=>$class];
	}

	static public function get() {
		return self::$messages;
	}
}