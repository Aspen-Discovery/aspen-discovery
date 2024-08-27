<?php


class GroupedWorkFormatSort extends DataObject {
	public $__table = 'grouped_work_format_sort';
	public $id;
	public $formatSortingGroupId;
	public $groupingCategory;
	public $format;
	public $weight;

	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'numeric',
				'label' => 'Weight',
				'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.',
				'required' => true,
			],
			'formatSortingGroupId' => [
				'property' => 'formatSortingGroupId',
				'type' => 'integer',
				'label' => 'Format Sorting Group ID',
				'description' => 'The sorting group this belongs to',
			],
			'groupingCategory' => [
				'property' => 'groupingCategory',
				'type' => 'enum',
				'values' => [
					'book' => 'book',
					'comic' => 'comic',
					'movie' => 'movie',
					'music' => 'music',
					'other' => 'other'
				],
				'label' => 'Grouping Category',
				'description' => 'The category this format belongs in',
			],
			'format' => [
				'property' => 'format',
				'type' => 'text',
				'label' => 'Format',
				'description' => 'The format being sorted',
				'size' => '40',
				'maxLength' => 255,
				'readOnly' => true,
			],
		];
	}
}