<?php

class NovelistFactory {
	static function getNovelist(){
		require_once ROOT_DIR . '/sys/Novelist/Novelist3.php';
		return new Novelist3();
	}
}