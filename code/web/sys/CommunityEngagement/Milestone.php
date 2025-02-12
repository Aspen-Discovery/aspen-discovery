<?php
require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestone.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneUsersProgress.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Reward.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
class Milestone extends DataObject {
    public $__table = 'ce_milestone';
    public $id;
    public $name;
    public $milestoneType;
    public $conditionalField;
    public $conditionalValue;
    public $campaignId;
    public $conditionalOperator;
    public $progressBeyondOneHundredPercent;

  

	public static function getObjectStructure($context = ''): array {
	 
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'maxLength' => 50,
				'description' => 'A name for the milestone',
				'required' => true,
			],
            'milestoneType' => [
                'property' => 'milestoneType',
                'type' => 'enum',
                'label' => 'When: ',
                'values' => [
                    'user_checkout' => 'Checkout',
                    'user_hold' => 'Hold',
                    'user_work_review' => 'Rating',
                ],
                'onchange' => 'updateConditionalField(this.value)',
            ],
            'conditionalField' => [
                'property' => 'conditionalField',
                'type' => 'enum',
                'label' => 'Conditional Field: ',
                'values' => [
                    'title_display' => 'Title',
                    'author_display' => 'Author',
                    'subject_facet' => 'Subject',
                    'user_list' => 'List (id)',
                ],
                'required' => false,
            ],
            'conditionalOperator' => [
                'property' => 'conditionalOperator',
                'type' => 'enum',
                'label' => 'Conditional Operator',
                'values' => [
                    'equals' => 'Is',
                    'is_not' => 'Is Not',
                    'like' => 'Is Like',
                ],
            ],
            'conditionalValue' => [
                'property' => 'conditionalValue',
                'type' => 'text',
                'label' => 'Conditional Value: ',
                'maxLength' => 100,
                'description' => 'Optional value e.g. Fantasy. This should be left blank to create a milestone with no condition.',
                'required' => false,
            ],
            'progressBeyondOneHundredPercent' => [
                'property' => 'progressBeyondOneHundredPercent',
                'type' => 'checkbox',
                'label' => 'Track Progress Beyond 100%',
                'description' => 'Whether or not progress should continue to be tracked once the milestone has reached 100% completion.',
                'default' => false,
            ],
        ];
        return $structure;
    } 


	/**
  * @return array
  */
  public static function getMilestoneList(): array {
	$milestone = new Milestone();
	$milestoneList = [];
	 
	if ($milestone->find()) {
		while ($milestone->fetch()) {
			$milestoneList[$milestone->id] = $milestone->name;
		}
	}
	return $milestoneList;
  }
}
