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
# Ejecución de eventos según la petición realizada desde el navegador
# -----------------------

namespace Sincco\Sfphp\Abstracts;

use League\CLImate\CLImate;

abstract class Command extends \Sincco\Sfphp\Abstracts\Generic
{

	public function red($data) {
		$climate = new CLImate;
		$climate->lightRed($data);
	}

	public function green($data) {
		$climate = new CLImate;
		$climate->lightGreen($data);
	}
}