<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/CatalogConnection.php';

class RequestPinReset extends Action{
	protected $catalog;

	function launch($msg = null)
	{
		global $interface;

		if (isset($_REQUEST['submit'])){
			$this->catalog = CatalogFactory::getCatalogConnectionInstance();
			$driver = $this->catalog->driver;
			if ($this->catalog->checkFunction('requestPinReset')){
				$barcode = strip_tags($_REQUEST['barcode']);
				$requestPinResetResult = $this->catalog->requestPinReset($barcode);
			}else{
				$requestPinResetResult = array(
					'error' => 'This functionality is not available in the ILS.',
				);
			}
			$interface->assign('requestPinResetResult', $requestPinResetResult);
			$template = 'requestPinResetResults.tpl';
		}else{
			$template = ('requestPinReset.tpl');
		}

		$this->display($template, 'Pin Reset');
	}
}
