<?php


class StaffMember extends DataObject
{
	public $__table = 'staff_members';
	public $id;
	public $name;
	public $role;
	public $email;
	public $phone;
	public $libraryId;
	public $photo;
	public $description;

	static function getObjectStructure() : array
	{
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Staff Members'));
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'libraryId' => array('property'=>'libraryId',         'type'=>'enum', 'values'=>$libraryList, 'label'=>'Library', 'description'=>'A link to the library which the location belongs to'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'The name of the staffer', 'size' => '100', 'maxLength'=>100),
			'role' => array('property' => 'role', 'type' => 'text', 'label' => 'Role', 'description' => 'The role of the staffer at the library', 'size' => '100', 'maxLength'=>100),
			'email' => array('property' => 'email', 'type' => 'email', 'label' => 'Email', 'description' => 'The email for the staffer', 'size' => '100', 'maxLength'=>255),
			'phone' => array('property' => 'phone', 'type' => 'text', 'label' => 'Phone', 'description' => 'The phone number for the staffer', 'size' => '13', 'maxLength'=>13),
			'photo' => array('property' => 'photo', 'type' => 'image', 'label' => 'Photo (500px x 500px max)', 'description' => 'The photo for use in the header', 'required' => false, 'maxWidth' => 500, 'maxHeight' => 500, 'thumbWidth' => 150, 'mediumWidth' => 250, 'hideInLists' => true),
			'description' => array('property' => 'description', 'type' => 'markdown', 'label' => 'Description', 'description' => 'A description for the staff member', 'hideInLists' => true),
		];
	}

	function getFormattedDescription(){
		require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
		$parsedown = AspenParsedown::instance();
		$parsedown->setBreaksEnabled(true);
		return $parsedown->parse($this->description);
	}
}