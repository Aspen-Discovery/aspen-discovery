<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';
require_once ROOT_DIR . '/sys/CronLogEntry.php';
require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistration.php';
require_once ROOT_DIR . '/sys/Events/EventInstance.php';
require_once ROOT_DIR . '/services/EventRegistrationService.php';

// prevent a new run from starting if the previous has not terminated.
$lockFile = sys_get_temp_dir() . '/aspen_event_registration_invitations.lock';
$lockHandle = fopen($lockFile, 'c');
if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
	exit(0);
}

$cronLogEntry = new CronLogEntry();
$cronLogEntry->startTime = time();
$cronLogEntry->name = 'Update Event Registration Invites';
$cronLogEntry->insert();

$expiredCount = 0;
$invitedCount = 0;

$expiredRowIds = UserAspenEventInstanceRegistration::getExpiredInvitedRowIds();

$affectedInstanceIds = [];

foreach ($expiredRowIds as $rowId) {
	$row = new UserAspenEventInstanceRegistration();
	$row->id = $rowId;
	if (!$row->find(true)) {
		continue;
	}
	$affectedInstanceIds[$row->eventInstanceId] = true;

	$eventInstance = new EventInstance();
	$eventInstance->id = $row->eventInstanceId;
	if ($eventInstance->find(true)) {
		$event = $eventInstance->getParentEvent();
		$eventInstance->sendEventEmail((int)$row->userId, 'eventWaitingListInviteExpired', [
			'eventTitle' => $event->title,
			'eventDate' => DateUtils::formatHumanDate($eventInstance->date),
			'eventTime' => $eventInstance->time,
		]);
	}

	$row->delete();
	$expiredCount++;
}

foreach (array_keys($affectedInstanceIds) as $eventInstanceId) {
	$eventInstance = new EventInstance();
	$eventInstance->id = $eventInstanceId;
	if (!$eventInstance->find(true)) {
		continue;
	}
	if (EventRegistrationService::inviteNextOnWaitingList($eventInstance)) {
		$invitedCount++;
	}
}

$cronLogEntry->notes = "Expired: $expiredCount, Invited: $invitedCount";
$cronLogEntry->endTime = time();
$cronLogEntry->update();
