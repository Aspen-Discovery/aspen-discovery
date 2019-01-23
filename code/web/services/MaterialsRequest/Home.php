<?php
/**
 *
 * Copyright (C) Anythink Libraries 2012.
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
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Anythink Libraries 2012.
 * 
 */

require_once ROOT_DIR . "/Action.php";

/**
 * Materials Request Home Page to view a request after it has been submitted.
 *
 * This controller needs some cleanup and organization.
 *
 * @version  $Revision: 1.27 $
 */
class MaterialsRequest_Home extends Action
{

	function launch()
	{
		global $interface;

		$interface->setTemplate('home.tpl');
		
		$interface->display('layout.tpl');
	}
}