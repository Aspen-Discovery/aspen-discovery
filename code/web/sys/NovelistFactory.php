<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 4/29/13
 * Time: 8:48 AM
 */

class NovelistFactory {
	static function getNovelist(){
		global $configArray;
		if (!isset($configArray['Novelist']['apiVersion']) || $configArray['Novelist']['apiVersion'] == 1){
			die("This version of Novelist is no longer supported!");
		}elseif ($configArray['Novelist']['apiVersion'] == 2){
			die("This version of Novelist is no longer supported!");
		}else{
			require_once ROOT_DIR . '/sys/Novelist/Novelist3.php';
			$novelist = new Novelist3();
		}
		return $novelist;
	}
}