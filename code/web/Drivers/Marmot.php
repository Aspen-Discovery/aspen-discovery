<?php

require_once ROOT_DIR . '/Drivers/Sierra.php';

class Marmot extends Sierra{
	function allowFreezingPendingHolds(){
		return true;
	}
}