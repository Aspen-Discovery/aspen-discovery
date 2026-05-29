<?php

require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestone.php';

/**
 * after_checkout_insert
 *
 * React to a new user_checkout being added to the database.
 * Add a new ce_milestone_progress_entry to be processed later if all conditions are met
 *
 * @param $value Checkout() object
 */

add_action('after_object_insert', 'after_checkout_insert', function ($value) {
	CampaignMilestone::processCampaignMilestoneProgress($value, 'user_checkout', $value->userId, $value->checkoutDate, $value->groupedWorkId);
});

/**;
 * after_hold_insert
 *
 * React to a new user_hold being added to the database.
 * Add a new ce_milestone_progress_entry to be processed later
 *
 * @param $value Hold() object
 */

add_action('after_object_insert', 'after_hold_insert', function ($value) {
	CampaignMilestone::processCampaignMilestoneProgress($value, 'user_hold', $value->userId, $value->createDate, $value->groupedWorkId);
});

/**
 * after_list_insert
 *
 * React to a new user_list being added to the database.
 * Add a new ce_milestone_progress_entry to be processed later
 *
 * @param $value UserList() object
 */

// add_action('after_object_insert', 'after_list_insert', function ($value) {
//     $campaignMilestone = CampaignMilestone::getCampaignMilestonesToUpdate($value, 'user_list', $value->user_id);
//     if (!$campaignMilestone)
//         return;

//     while ($campaignMilestone->fetch()) {
//         $campaignMilestone->addCampaignMilestoneProgressEntry($value, $value->user_id);
//     }
//     return;
// });

/**
 * after_work_review_insert
 *
 * React to a new user_work_review being added to the database.
 * Add a new ce_milestone_progress_entry to be processed later
 *
 * @param $value UserWorkReview() object
 */

add_action('after_object_insert', 'after_work_review_insert', function ($value) {
	CampaignMilestone::processCampaignMilestoneProgress($value, 'user_work_review', $value->userId, $value->dateRated, $value->groupedRecordPermanentId);
});

