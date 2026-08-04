<?php /** @noinspection SqlResolve */

function getAspenEventNotificationUpdates() {
	return [
		'add_event_email_notification_preferences_to_user_db_table' =>[
			'title' => 'Add Event Email Notification Preferences to User DB Table',
			'description' => 'Add a column to store user preference about events notification emails',
			'sql' => [
				'ALTER TABLE user ADD COLUMN IF NOT EXISTS eventRegistrationNotificationsByEmail TINYINT DEFAULT 0',
			],
		], //add_event_email_notification_preferences_to_user_db_table
		'display_event_notifications_in_account' => [
			'title' => 'Display Event Notifications in Account',
			'description' => 'Add a column to track whether to allow event notifications in user accounts',
			'sql' => [
				'ALTER TABLE library ADD COLUMN IF NOT EXISTS displayEventNotificationsInAccount TINYINT DEFAULT 0',
			],
		], //display_event_notifications_in_account
	];
}