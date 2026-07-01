<?php /** @noinspection SqlResolve */
function getDailyUsageUpdates() {
	return [
		'add_day_to_api_usage' => [
			'title' => 'Add Day to API Usage',
			'description' => 'Add Day to API Usage',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE api_usage ADD COLUMN IF NOT EXISTS day int(11) NOT NULL',
				'ALTER TABLE api_usage DROP INDEX IF EXISTS uniqueness',
				'ALTER TABLE api_usage ADD UNIQUE INDEX uniqueness(instance, year, month, day, module, method)',
			]
		], //add_day_to_api_usage
		'add_day_to_axis360_record_usage' => [
			'title' => 'Add Day to Axis360 Record Usage',
			'description' => 'Add Day to Axis360 Record Usage',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE axis360_record_usage ADD COLUMN IF NOT EXISTS day int(11) NOT NULL',
				'ALTER TABLE axis360_record_usage DROP KEY IF EXISTS instance',
				'ALTER TABLE axis360_record_usage DROP KEY IF EXISTS instance_2',
				'ALTER TABLE axis360_record_usage DROP KEY IF EXISTS instance_3',
				'ALTER TABLE axis360_record_usage ADD KEY instance(instance, axis360Id, year, month, day)',
				'ALTER TABLE axis360_record_usage ADD KEY instance_2(instance, year, month, day)',
  				'ALTER TABLE axis360_record_usage ADD KEY instance_3(instance, year, month, day)',
			]
		], //add_day_to_axis360_record_usage
		'add_day_to_user_axis360_usage' => [
			'title' => 'Add Day to User Axis360 Usage',
			'description' => 'Add Day to User Axis360 Usage',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user_axis360_usage ADD COLUMN IF NOT EXISTS day int(11) NOT NULL',
				'ALTER TABLE user_axis360_usage DROP KEY IF EXISTS instance',
				'ALTER TABLE user_axis360_usage DROP KEY IF EXISTS instance_2',
				'ALTER TABLE user_axis360_usage ADD KEY instance(instance, userId, year, month, day)',
				'ALTER TABLE user_axis360_usage ADD KEY instance_2(instance, year, month, day)',
			]
		], //add_day_to_user_axis360_usage
		'add_day_to_axis360_stats' => [
			'title' => 'Add Day to Axis Stats',
			'description' => 'Add Day to Axis Stats',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE axis360_stats ADD COLUMN IF NOT EXISTS day int(11) NOT NULL',
				'ALTER TABLE axis360_stats DROP KEY IF EXISTS instance',
				'ALTER TABLE axis360_stats ADD KEY instance(instance, year, month, day)',
			]
		], //add_day_to_axis360_stats
		'add_day_to_ils_record_usage' => [
			'title' => 'Add Day to ILS Record Usage',
			'description' => 'Add Day to ILS Record Usage',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE ils_record_usage ADD COLUMN IF NOT EXISTS day int(11) NOT NULL',
				'ALTER TABLE ils_record_usage DROP KEY IF EXISTS instance',
				'ALTER TABLE ils_record_usage ADD KEY instance(instance, year, month, day)',
			]
		], //add_day_to_ils_record_usage
		'add_day_to_user_ils_usage' => [
			'title' => 'Add Day to User ILS Usage',
			'description' => 'Add Day to User ILS Usage',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user_ils_usage ADD COLUMN IF NOT EXISTS day int(11) NOT NULL',
				'ALTER TABLE user_ils_usage DROP KEY IF EXISTS instance',
				'ALTER TABLE user_ils_usage ADD KEY instance(instance, userId, indexingProfileId, year, month, day)',
			]
		], //add_day_to_user_ils_usage
		'add_day_to_user_sideload_usage' => [
			'title' => 'Add Day to User Sideload Usage',
			'description' => 'Add Day to User Sideload Usage',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user_sideload_usage ADD COLUMN IF NOT EXISTS day int(11) NOT NULL',
				'ALTER TABLE user_sideload_usage DROP KEY IF EXISTS instance',
				'ALTER TABLE user_sideload_usage ADD KEY instance(instance, userId, sideloadId, year, month, day)',
			]
		], //add_day_to_user_sideload_usage
		'add_day_to_sideload_record_usage' => [
			'title' => 'Add Day to Sideload Record Usage',
			'description' => 'Add Day to Sideload Record Usage',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE sideload_record_usage ADD COLUMN IF NOT EXISTS day int(11) NOT NULL',
				'ALTER TABLE sideload_record_usage DROP KEY IF EXISTS instance',
				'ALTER TABLE sideload_record_usage ADD KEY instance(instance, recordId, sideloadId, year, month, day)',
			]
		], //add_day_to_sideload_record_usage
		'add_day_to_materials_request_usage' => [
			'title' => 'Add Day to Materials Request Usage',
			'description' => 'Add Day to Materials Request Usage',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE materials_request_usage ADD COLUMN IF NOT EXISTS day int(11) NOT NULL',
			]
		], //add_day_to_materials_request_usage
		'add_day_to_summon_usage' => [
			'title' => 'Add Day to Summon Usage',
			'description' => 'Add Day to Summon Usage',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE summon_usage ADD COLUMN IF NOT EXISTS day int(11) NOT NULL',
				'ALTER TABLE summon_usage DROP KEY IF EXISTS instance',
				'ALTER TABLE summon_usage ADD KEY instance(instance, summonId, year, month, day)',
			]
		], //add_day_to_summon_usage
		'add_day_to_user_summon_usage' => [
			'title' => 'Add Day to User Summon Usage',
			'description' => 'Add Day to User Summon Usage',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE user_summon_usage ADD COLUMN IF NOT EXISTS day int(11) NOT NULL',
				'ALTER TABLE user_summon_usage DROP KEY IF EXISTS instance',
				'ALTER TABLE user_summon_usage ADD KEY instance(instance, userId, year, month, day)',
			]
		], //add_day_to_user_summon_usage
		'add_day_to_aspen_usage' => [
			'title' => 'Add Day to Aspen Usage',
			'description' => 'Add Day to Aspen Usage',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE aspen_usage ADD COLUMN IF NOT EXISTS day int(11) NOT NULL',
				'ALTER TABLE aspen_usage DROP KEY IF EXISTS instance',
				'ALTER TABLE aspen_usage ADD KEY instance(instance, year, month, day)',
			]
		], //add_day_to_aspen_usage
	];
}