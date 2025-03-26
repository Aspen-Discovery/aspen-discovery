<?php
/**@noinspection SqlResolve*/
function getCommunityEngagementUpdates() {
	return [
		'community_builder_module' => [
			'title' => 'Community Module',
			'description' => 'Create Community Module',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Community', '', '')",
			],
		],
        'create_campaigns' => [
            'title' => 'Create Campaigns',
            'description' => 'Add table for campaigns',
            'sql' => [
                "CREATE TABLE ce_campaign (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    description VARCHAR(255),
                    startDate DATE NULL,
                    endDate DATE NULL,
                    enrollmentCounter INT(11) DEFAULT 0,
                    unenrollmentCounter INT(11) DEFAULT 0,
                    currentEnrollments INT NOT NULL DEFAULT 0,
                    campaignReward INT(11) DEFAULT -1
                ) ENGINE = InnoDB",
            ],
        ],
        'create_campaign_patron_type_access' => [
            'title' => 'Create Campaign Patron Type Access',
            'description' => 'Add table for patron type campaign access',
            'sql' => [
                "CREATE TABLE ce_campaign_patron_type_access (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    campaignId INT NOT NULL,
                    patronTypeId INT NOT NULL
                ) ENGINE = InnoDB",
            ],
        ],
        'create_campaign_library_access' => [
            'title' => 'Create Campaign Library Access',
            'description' => 'Add table for library campaign access',
            'sql' => [
                "CREATE TABLE ce_campaign_library_access (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    campaignId INT NOT NULL,
                    libraryId INT NOT NULL
                ) ENGINE = InnoDB",
            ],
        ],
        'create_milestones' => [
            'title' => 'Create Milestones',
            'description' => 'Add table for milestones',
            'sql' => [
                "CREATE TABLE ce_milestone (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    conditionalField VARCHAR(100),
                    conditionalOperator VARCHAR(100),
                    conditionalValue VARCHAR(100),
                    milestoneType VARCHAR(100),
                    campaignId INT
                ) ENGINE = InnoDB",
            ],
        ],
        'create_reward_table' => [
            'title' => 'Create Reward Table',
            'description' => 'Create a table to store types of reward',
            'sql' => [
                "CREATE TABLE IF NOT EXISTS ce_reward (
                     id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    description VARCHAR(255),
                    rewardType INT(11) DEFAULT -1
                )ENGINE = InnoDB",
            ],
        ],
        'add_ce_campaign_milestone_progress_entries' => [
            'title' => 'Add add_ce_campaign_milestone_progress_entries database table',
            'description' => 'Store milestone progress entries to be processed by cronjob',
            'sql' => [
                "CREATE TABLE IF NOT EXISTS ce_campaign_milestone_progress_entries (
                     id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                     userId INT NOT NULL,
                     ce_campaign_id INT NOT NULL,
                     ce_milestone_id INT NOT NULL,
                     ce_campaign_milestone_users_progress_id INT NOT NULL,
                     tableName VARCHAR(100),
                     processed TINYINT DEFAULT 0,
                     object MEDIUMTEXT
                )ENGINE = InnoDB",
            ],
        ],
        'add_ce_campaign_milestone_users_progress' => [
            'title' => 'Add add_ce_campaign_milestone_users_progress database table',
            'description' => 'Store milestone progress for each user',
            'sql' => [
                "CREATE TABLE IF NOT EXISTS ce_campaign_milestone_users_progress (
                     id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                     userId INT NOT NULL,
                     ce_campaign_id INT NOT NULL,
                     ce_milestone_id INT NOT NULL,
                     progress INT NOT NULL,
                     rewardGiven TINYINT DEFAULT 0
                )ENGINE = InnoDB",
            ],
        ],
        'create_user_campaign_table' => [
            'title' => 'Create User Campaign Table',
            'description' => 'Create a table to link users and campaigns',
            'sql' => [
                "CREATE TABLE IF NOT EXISTS ce_user_campaign (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    userId INT NOT NULL,
                    campaignId INT NOT NULL,
                    enrollmentDate DATETIME DEFAULT CURRENT_TIMESTAMP,
                    unenrollmentDate DATETIME,
                    completed TINYINT DEFAULT 0,
                    rewardGiven TINYINT DEFAULT 0
                )ENGINE = InnoDB",
            ],
        ],
        'add_campaign_milestones_table' => [
            'title' => 'Add Campaign Milestones Table',
            'description' => 'Add a new table to link campaigns and milestones',
            'sql' => [
                "CREATE TABLE IF NOT EXISTS ce_campaign_milestones (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    campaignId INT NOT NULL, 
                    milestoneId INT NOT NULL,
                    goal INT DEFAULT 0,
                    reward INT(11) DEFAULT -1
                )ENGINE = InnoDB",
            ],
        ],
        'create_user_completed_milestones_table' => [
            'title' => 'Create User Completed Milestones Table',
            'description' => 'Add table to store completed milestone information',
            'sql' => [
                "CREATE TABLE IF NOT EXISTS ce_user_completed_milestones (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    userId INT NOT NULL,
                    milestoneId INT NOT NULL, 
                    campaignId INT NOT NULL, 
                    completedAt DATETIME NOT NULL
                )ENGINE = InnoDB",
            ],
        ],
        'add_campaign_data_table' => [
            'title' => 'Add Campaign Data Table',
            'description' => 'Add campaign data table for dashboard',
            'sql' => [
                "CREATE TABLE IF NOT EXISTS ce_campaign_data (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
                    campaignId INT NOT NULL, 
                    month INT(2) NOT NULL, 
                    year INT(4) NOT NULL, 
                    totalEnrollments INT NOT NULL, 
                    currentEnrollments INT NOT NULL, 
                    totalUnenrollments INT NOT NULL,
                    instance VARCHAR(100)
                )ENGINE = InnoDB",
            ],
        ],
        'add_table_for_user_campaign_data' => [
            'title' => 'Add Table For User Campaign Data',
            'description' => 'Add user campaign data table for dashboard',
            'sql' => [
                "CREATE TABLE IF NOT EXISTS ce_user_campaign_data (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
                    userId INT NOT NULL, 
                    month INT(2) NOT NULL, 
                    year INT(4) NOT NULL, 
                    enrollmentCount INT(11) DEFAULT 0,
                    campaignId INT NOT NULL,
                    instance VARCHAR(100)
                )ENGINE = InnoDB",
            ],
        ],
        'community_engagement_roles' => [
            'title' => 'Community Engagement Module Roles and Permissions',
            'description' => 'Set up roles and permissions for the Community Engagement Module',
            'sql' => [
                "INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
                    ('Community', 'Administer Community Module',' Community', 180, 'Allows the user to create rewards, milestones and campaigns.')"
            ],
        ],
        'add_role_permissions_for_community_engagement_module' => [
            'title' => 'Add Role Permissions For Community Engagement Module',
            'description' => 'Set up role permissions to administer the Community Engagement module',
            'sql' => [
                "INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Community Module'))"
            ],
        ],
        'view_community_dashboard_permissions' => [
            'title' => 'View Community Dashboard Permissions',
            'description' => 'Set up permissions to restrict who can view the community dashboard',
            'sql' => [
                "INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES 
                    ('Reporting', 'View Community Dashboard', 'Community', 190, 'Allows the user to view the community engagement dashboard.')"
            ],
        ],
        'add_role_permissions_for_community_engagement_dashboard' => [
            'title' => 'Add Role Permissions For Community Engagement Dashboard',
            'description' => 'Set up role permissions for Community Engagement Dashboard',
            'sql' => [
                "INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='View Community Dashboard'))"
            ],
        ],
        'add_image_uploads_to_rewards' => [
            'title' => 'Add Image Uploads To Rewards',
            'description' => 'Allow image uploads for digital badges',
            'sql' => [
                "ALTER TABLE ce_reward ADD COLUMN badgeImage VARCHAR(255) NULL"
            ],
        ],
        'update_community_engagement_roles' => [
            'title' => 'Update Community Engagement Roles',
            'description' => 'Alter the community engagement roles placement',
            'sql' => [
                "DELETE FROM role_permissions WHERE permissionId = (SELECT id FROM permissions WHERE name='Administer Community Module')",
                "DELETE FROM permissions 
                WHERE sectionName = 'Community'
                AND name = 'Administer Community Module'",
                "INSERT INTO permissions (sectionName, name, requiredModule, weight, description)
                VALUES ('Primary Configuration', 'Administer Community Module', 'Community', 180, 'Allows the user to create rewards, milestones and campaigns')",
                "INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Community Module'))"
            ],
        ],
        'add_date_of_birth_to_user' => [
            'title' => 'Add Date Of Birth To User',
            'description' => 'Add the date of birth to the user object',
            'sql' => [
                "ALTER TABLE user ADD COLUMN dateOfBirth DATE"
            ],
        ],
        'add_user_age_range_to_campaign' => [
            'title' => 'Add User Age Range To Campaign',
            'description' => 'Add an age range to the campaign',
            'sql' => [
                "ALTER TABLE ce_campaign ADD COLUMN userAgeRange VARCHAR(255) DEFAULT 'All Ages'"
            ],
        ],
        'update_community_engagement_module_name' => [
            'title' => 'Update Community Engagement Module Name',
            'description' => 'Update module name to Community Engagement',
            'sql' => [
                "UPDATE modules SET name = 'Community Engagement' WHERE name = 'Community'"
            ],
        ],
        'update_community_engagement_permissions' => [
            'title' => 'Update Community Engagement Permissions',
            'description' => 'Update permissions and role assignments for the Community Engagement Module',
            'sql' => [
                "DELETE FROM role_permissions WHERE permissionId = (SELECT id FROM permissions WHERE name = 'Administer Community Module') AND roleId = (SELECT roleId FROM roles WHERE name = 'opacAdmin')",
                "DELETE FROM role_permissions WHERE permissionId = (SELECT id FROM permissions WHERE name = 'View Community Dashboard') AND roleId = (SELECT roleId FROM roles WHERE name = 'opacAdmin')",
                "UPDATE permissions SET name = 'Administer Community Engagement Module', requiredModule = 'Community Engagement' WHERE name = 'Administer Community Module' AND requiredModule = 'Community'",
                "UPDATE permissions SET name = 'View Community Engagement Dashboard', requiredModule = 'Community Engagement' WHERE name = 'View Community Dashboard' AND requiredModule = 'Community'",
                "INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Community Engagement Module'))",
                "INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='View Community Engagement Dashboard'))"
            ],
        ],
		'drop_date_of_birth_from_user_table' => [
			'title' => 'Drop Date Of Birth From User Table',
			'description' => 'Remove patron date of birth from user table',
			'sql' => [
				"ALTER TABLE user DROP COLUMN dateOfBirth"
			],
		],
        'add_campaign_enrollment_dates' => [
            'title' => 'Add Campaign Enrollment Dates',
            'description' => 'Allow librarians to set an enrollment period for campaigns',
            'sql' => [
                "ALTER TABLE ce_campaign ADD COLUMN enrollmentStartDate DATE NULL",
                "ALTER TABLE ce_campaign ADD COLUMN enrollmentEndDate DATE NULL"
            ],
        ],
        'add_campaign_leaderboard_display_options' => [
            'title' => 'Add Campaign Leaderboard Display Options',
            'description' => 'Add the ability for libraries to choose whether to display the leaderboard by user or by branch',
            'sql' => [
                "ALTER TABLE library ADD COLUMN campaignLeaderboardDisplay VARCHAR(20) DEFAULT 'displayBranch'",
            ],
        ],
        'allow_milestones_to_track_beyond_one_hundred_percent_completion_option' => [
            'title' => 'Allow Milestones To Track Beyond One Hundred Percent Completion Option',
            'description' => 'Add the option of setting milestones to continue to track progress beyond one hundred percent completion',
            'sql' => [
                "ALTER TABLE ce_milestone ADD COLUMN progressBeyondOneHundredPercent TINYINT DEFAULT 0",
            ],
        ],
        'add_allow_patron_progress_input' => [
            'title' => 'Add Allow Patron Progress Input',
            'description' => 'Add the option of allowing patrons to update their own progress',
            'sql' => [
                "ALTER TABLE ce_milestone ADD COLUMN allowPatronProgressInput TINYINT DEFAULT 0",
            ],
        ],
        'add_ability_to_opt_in_to_all_campaign_leaderboards' => [
            'title' => 'Add Ability To Opt In To All Campaign Leaderboards',
            'description' => 'Add the ability to opt in to all campaigns',
            'sql' => [
                "ALTER TABLE user ADD COLUMN optInToAllCampaignLeaderboards TINYINT DEFAULT 0",
            ],
        ],
		'drop_date_of_birth_from_user_table' => [
			'title' => 'Drop Date Of Birth From User Table',
			'description' => 'Remove patron date of birth from user table',
			'sql' => [
				"ALTER TABLE user DROP COLUMN dateOfBirth"
			],
		],
        'add_individual_campaign_leaderboard_opt_in' => [
            'title' => 'Add Individual Campaign Leaderboard Opt In',
            'description' => 'Add the ability to opt in and out of individual campaigns',
            'sql' => [
                "ALTER TABLE ce_user_campaign ADD COLUMN optInToCampaignLeaderboard TINYINT DEFAULT NULL",
            ],
        ],
        'get_campaign_notifications_by_email' => [
            'title' => 'Get Campaign Notifications By Email',
            'description' => 'Opt in or out to getting campaign notifications by email',
            'sql' => [
                "ALTER TABLE user ADD COLUMN campaignNotificationsByEmail TINYINT DEFAULT 0",
            ],
        ],
        'add_individual_campaign_email_notification_opt_in' => [
            'title' => 'Add Individual Campaign Email Notification Opt In',
            'description' => 'Add the ability to opt in and out of notifications by email for individual campaigns',
            'sql' => [
                "ALTER TABLE ce_user_campaign ADD COLUMN optInToCampaignEmailNotifications TINYINT DEFAULT NULL",
            ],
        ],
        'add_campaign_complete_email_sent' => [
            'title' => 'Add Campaign Complete Email Sent',
            'description' => 'Add a column to track when a campaign completed email has been sent',
            'sql' => [
                "ALTER TABLE ce_user_campaign ADD COLUMN campaignCompleteEmailSent TINYINT(1) DEFAULT 0"
            ],
        ],
        'add_description_for_manual_milestones' => [
            'title' => 'Add Description for Manual Milestones',
            'description' => 'Add a text block description for manual milestones',
            'sql' => [
                "ALTER TABLE ce_milestone ADD COLUMN description VARCHAR(255)"
            ],
        ],
        'add_control_over_whether_reward_name_displays' => [
            'title' =>'Add Control Over Whether Reward Name Displays',
            'description' => 'Add control over whether reward name is displayed',
            'sql' => [
                "ALTER TABLE ce_reward ADD COLUMN displayName TINYINT(1) DEFAULT 1"
            ],
        ],
        'track_emails_sent_on_milestone_completion' => [
            'title' => 'Track Emails Sent On Milestone Completion',
            'description' => 'Add a column to track emails sent when campaign milestones have been completed',
            'sql' => [
                "ALTER TABLE ce_campaign_milestone_users_progress ADD COLUMN milestoneCompleteEmailSent TINYINT(1) DEFAULT 0"
            ],
        ],
        'add_option_to_send_staff_email_on_campaign_completion' => [
            'title' => 'Add Option To Send Email On Campaign Completion',
            'description' => 'Add the option of sending am email to staff when patrons complete a campaign',
            'sql' => [
                "ALTER TABLE library ADD COLUMN campaignCompletionNewEmail VARCHAR(125) DEFAULT NULL",
                "ALTER TABLE library ADD COLUMN sendStaffEmailOnCampaignCompletion TINYINT(1) DEFAULT 0",
            ],
        ],
        'add_control_over_whether_reward_is_given_automatically' => [
            'title' =>'Add Control Over Whether Reward Is Given Automatically',
            'description' => 'Add control over whether digital reward is given automatically',
            'sql' => [
                "ALTER TABLE ce_reward ADD COLUMN awardAutomatically TINYINT(1) DEFAULT 1"
            ],
        ],
    ];
}