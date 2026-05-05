<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/Events/EventInstance.php';

class UserAspenEventInstanceRegistration extends DataObject {
	public $__table = 'user_aspen_event_instance_registrations';
	public $id;
	public $userId;
	public $eventInstanceId;
	public $success;
	public $attended;

	public function getUniquenessFields(): array {
		return [
			'userId',
			'eventInstanceId',
		];
	}

	public function isUserRegisteredForEvent(): bool {
		return $this->find(true);
	}
}