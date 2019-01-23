<?php

class SwitchDatabase
{	
	
	static private $changedDB = false;
	
	static public function switchToVuFind()
	{
		global $configArray;
		
		if(!SwitchDatabase::$changedDB)
		{
			if(SwitchDatabase::isEcontentDatabase())
			{
				mysql_selectdb($configArray['Database']['database_vufind_dbname']);
				SwitchDatabase::$changedDB = true;
			}
		}
		
	}
	
	static public function switchToEcontent()
	{
		global $configArray;
		
		if(!SwitchDatabase::$changedDB)
		{
			if(SwitchDatabase::isVuFindDatabase())
			{
				mysql_selectdb($configArray['Database']['database_econtent_dbname']);
				SwitchDatabase::$changedDB = true;
			}
		}
	}
	
	static public function restoreDatabase()
	{
		if (SwitchDatabase::$changedDB)
		{
			if(SwitchDatabase::isEcontentDatabase())
			{
				SwitchDatabase::switchToVuFind();
			}
			else
			{
				SwitchDatabase::switchToEcontent();
			}
			SwitchDatabase::$changedDB = false;
		}
	}

	static private function isEcontentDatabase()
	{
		global $configArray;
		$currentDB = SwitchDatabase::getCurrentDatabase();
		if ($currentDB==$configArray['Database']['database_econtent_dbname'])
		{
			return true;
		}
		return false;
	}
	
	static private function isVuFindDatabase()
	{
		global $configArray;
		$currentDB = SwitchDatabase::getCurrentDatabase();
		if ($currentDB==$configArray['Database']['database_vufind_dbname'])
		{
			return true;
		}
		return false;
	}
	
	
	static private function getCurrentDatabase()
	{
		global $configArray;
		$sql = "SELECT DATABASE()";
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result, MYSQL_NUM);
		return $row[0];
	}
}
?>