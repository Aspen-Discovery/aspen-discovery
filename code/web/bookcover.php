<?php
require_once 'bootstrap.php';
//define ('ROOT_DIR', __DIR__);
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


require_once ROOT_DIR . '/sys/BookCoverProcessor.php';

//Create class to handle processing of covers
$processor = new BookCoverProcessor();
$processor->loadCover($configArray, $timer, $logger);
if ($processor->error){
	header('Content-type: text/plain'); //Use for debugging notices and warnings
	$logger->log("Error processing cover " . $processor->error, PEAR_LOG_ERR);
	echo($processor->error);
}
