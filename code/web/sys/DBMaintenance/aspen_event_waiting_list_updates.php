<?php /** @noinspection SqlResolve */

function getAspenEventWaitingListUpdates() {
	return [
		'add_waitingList_to_events_and_instances' => [
			'title' => 'Add waitingList to Events and Event Instances',
			'description' => 'Add waiting list column to events.',
			'sql' => [
				'ALTER TABLE event ADD COLUMN IF NOT EXISTS waitingList TINYINT(1) DEFAULT 0',
				'ALTER TABLE event_instance ADD COLUMN IF NOT EXISTS waitingList TINYINT(1) DEFAULT NULL',
			],
		], // add_waitingList_to_events_and_instance
		'add_waitingListNumberOfSeats_to_events' => [
			'title' => 'Add waitingListNumberOfSeats to Events',
			'description' => 'Add waiting list number of seats column to Events and Event Instances',
			'sql' => [
				'ALTER TABLE event ADD COLUMN IF NOT EXISTS waitingListNumberOfSeats SMALLINT UNSIGNED DEFAULT NULL',
				'ALTER TABLE event_instance ADD COLUMN IF NOT EXISTS waitingListNumberOfSeats SMALLINT UNSIGNED DEFAULT NULL',
			],
		], // add_waitingListNumberOfSeats_to_events
		'replace_registered_with_status_in_user_aspen_event_instance_registrations' => [
			'title' => 'Replace Registered with Status in User Aspen Event Instance Registrations',
			'description' => 'Update the aspen event instance registration table to support waiting lists',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE user_aspen_event_instance_registrations ADD COLUMN IF NOT EXISTS createdAt DATETIME DEFAULT CURRENT_TIMESTAMP',
				'ALTER TABLE user_aspen_event_instance_registrations ADD COLUMN IF NOT EXISTS notifiedAt DATETIME DEFAULT NULL',
				'ALTER TABLE user_aspen_event_instance_registrations ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT "waiting"',
				'UPDATE user_aspen_event_instance_registrations SET status = "registered" WHERE registered = 1',
				'ALTER TABLE user_aspen_event_instance_registrations DROP COLUMN IF EXISTS registered',
			],
		], // replace_registered_with_status_in_user_aspen_event_instance_registrations
		'add_waiting_list_invite_expiry_hours_to_event_type' => [
			'title' => 'Add Waiting List Invite Expiry Hours to Event Type',
			'description' => 'Add a configurable invite expiry window for waiting list invitations',
			'sql' => [
				'ALTER TABLE event_type ADD COLUMN IF NOT EXISTS waitingListInviteExpiryHours INT DEFAULT 24',
			],
		], // add_waiting_list_invite_expiry_hours_to_event_type
		'seed_event_register_from_waiting_list_email_template' => [
			'title' => 'Seed Email Template — Register for Event From Waiting List',
			'description' => 'Default email template sent when a patron is promoted from the waiting list and invited to register',
			'sql' => [
				"INSERT INTO email_template (name, templateType, languageCode, subject, plainTextBody, htmlBody)
					SELECT 'Default Register for Event From Waiting List', 'registerForEventFromWaitingList', 'en',
						'A seat is now available for %event.title%',
						'Hello %user.firstName%,\r\n\r\nA seat has opened up for %event.title% on %event.date% at %event.time%. You can register until %canRegisterUntil%. If you do not register in time, the seat will be offered to the next person on the waiting list.',
						'<p>Hello %user.firstName%,</p><p>A seat has opened up for <strong>%event.title%</strong> on %event.date% at %event.time%.</p><p>You can register until %canRegisterUntil%. If you do not register in time, the seat will be offered to the next person on the waiting list.</p>'
					WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM email_template WHERE templateType = 'registerForEventFromWaitingList') AS t)",
			],
		], // seed_event_register_from_waiting_list_email_template
		'seed_event_waiting_list_invite_expired_email_template' => [
			'title' => 'Seed Email Template — Waiting List Invitation Expired',
			'description' => 'Default email template sent when a waiting list invitation expires before the patron registers',
			'sql' => [
				"INSERT INTO email_template (name, templateType, languageCode, subject, plainTextBody, htmlBody)
					SELECT 'Default Waiting List Invitation Expired', 'eventWaitingListInviteExpired', 'en',
						'Your invitation to register for %event.title% has expired',
						'Hello %user.firstName%,\r\n\r\nYour invitation to register for %event.title% on %event.date% at %event.time% has expired. If seats remain, you can re-join the waiting list from the event page.',
						'<p>Hello %user.firstName%,</p><p>Your invitation to register for <strong>%event.title%</strong> on %event.date% at %event.time% has expired. If seats remain, you can re-join the waiting list from the event page.</p>'
					WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM email_template WHERE templateType = 'eventWaitingListInviteExpired') AS t)",
			],
		], // seed_event_waiting_list_invite_expired_email_template
		'seed_event_cancellation_registered_email_template' => [
			'title' => 'Seed Email Template — Event Cancellation (Registered Patron)',
			'description' => 'Default email template sent to registered patrons when an event is cancelled or significantly changed',
			'sql' => [
				"INSERT INTO email_template (name, templateType, languageCode, subject, plainTextBody, htmlBody)
					SELECT 'Default Event Cancellation (Registered Patron)', 'eventCancellationRegistered', 'en',
						'An event you registered for has been %changeType%',
						'Hello %user.firstName%,\r\n\r\nThe following event(s) you are registered for have been %changeType%:\r\n\r\n%eventInstances%',
						'<p>Hello %user.firstName%,</p><p>The following event(s) you are registered for have been %changeType%:</p><pre>%eventInstances%</pre>'
					WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM email_template WHERE templateType = 'eventCancellationRegistered') AS t)",
			],
		], // seed_event_cancellation_registered_email_template
		'seed_event_cancellation_invited_email_template' => [
			'title' => 'Seed Email Template — Event Cancellation (Invited from Waiting List)',
			'description' => 'Default email template sent to invited patrons when an event is cancelled or significantly changed',
			'sql' => [
				"INSERT INTO email_template (name, templateType, languageCode, subject, plainTextBody, htmlBody)
					SELECT 'Default Event Cancellation (Invited)', 'eventCancellationInvited', 'en',
						'An event you were invited to has been %changeType%',
						'Hello %user.firstName%,\r\n\r\nThe following event(s) you were invited to register for have been %changeType%:\r\n\r\n%eventInstances%',
						'<p>Hello %user.firstName%,</p><p>The following event(s) you were invited to register for have been %changeType%:</p><pre>%eventInstances%</pre>'
					WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM email_template WHERE templateType = 'eventCancellationInvited') AS t)",
			],
		], // seed_event_cancellation_invited_email_template
		'seed_event_cancellation_waiting_email_template' => [
			'title' => 'Seed Email Template — Event Cancellation (On Waiting List)',
			'description' => 'Default email template sent to waiting list patrons when an event is cancelled or significantly changed',
			'sql' => [
				"INSERT INTO email_template (name, templateType, languageCode, subject, plainTextBody, htmlBody)
					SELECT 'Default Event Cancellation (On Waiting List)', 'eventCancellationWaiting', 'en',
						'An event you were waiting for has been %changeType%',
						'Hello %user.firstName%,\r\n\r\nThe following event(s) you are on the waiting list for have been %changeType%:\r\n\r\n%eventInstances%',
						'<p>Hello %user.firstName%,</p><p>The following event(s) you are on the waiting list for have been %changeType%:</p><pre>%eventInstances%</pre>'
					WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM email_template WHERE templateType = 'eventCancellationWaiting') AS t)",
			],
		], // seed_event_cancellation_waiting_email_template
	];
}