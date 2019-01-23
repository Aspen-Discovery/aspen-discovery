<?php
/**
 * Table Definition for LocationHours.
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class LocationHours extends DB_DataObject
{
	public $__table = 'location_hours';   // table name
	public $id;                           // int(11)  not_null primary_key auto_increment
	public $locationId;                   // int(11)
	public $day;                          // int(11)
	public $open;                         // varchar(10)
	public $close;                        // varchar(10)
	public $closed;
	
	function keys() {
		return array('id');
	}

	static function getObjectStructure(){
		$location = new Location();
		$location->orderBy('displayName');
		$location->find();
		$locationList = array();
		while ($location->fetch()){
			$locationList[$location->locationId] = $location->displayName;
		}
		$days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
		$time = array(
			'00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30',
			'07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30',
			'13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30',
			'19:00', '19:30', '20:00', '20:30', '21:00', '21:30', '22:00', '22:30', '23:00', '23:30', '24:00'
		);
		$timeList = array();
		foreach ($time as $t) {
			$timeList[$t] = $t;
		}
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the hours within the database'),
			'locationId' => array('property'=>'locationId', 'type'=>'enum', 'values'=>$locationList, 'label'=>'Location', 'description'=>'The library location.'),
			'day' => array('property'=>'day', 'type'=>'enum', 'values'=>$days, 'label'=>'Day of Week', 'description'=>'The day of the week 0 to 6 (0 = Sunday to 6 = Saturday)'),
			'closed' => array('property'=>'closed', 'type'=>'checkbox', 'label'=>'Closed', 'description'=>'Check to indicate that the library is closed on this day.'),
			'open' => array('property'=>'open', 'type'=>'enum', 'values'=>$timeList, 'label'=>'Opening Hour', 'description'=>'The opening hour. Use 24 hour format HH:MM, eg: 08:30'),
			'close' => array('property'=>'close', 'type'=>'enum', 'values'=>$timeList, 'label'=>'Closing Hour', 'description'=>'The closing hour. Use 24 hour format HH:MM, eg: 16:30'),
		);
		return $structure;
	}
}