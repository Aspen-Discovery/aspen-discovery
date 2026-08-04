<?php /** @noinspection PhpMissingFieldTypeInspection */

class UserAspenEventInstanceRegistrationEventField extends DataObject {
	public $__table = 'user_aspen_event_instance_registrations_event_field';
	public $id;
	public $eventInstanceRegistrationId;
	public $eventFieldId;
	public $value;

	public static function getValuesForRegistration(int $registrationId): array {
		$field = new UserAspenEventInstanceRegistrationEventField();
		$field->eventInstanceRegistrationId = $registrationId;
		$field->find();
		$values = [];
		while ($field->fetch()) {
			$values[(int)$field->eventFieldId] = $field->value;
		}
		return $values;
	}
}