<?php


class AuthorAuthorityAlternative extends DataObject {
	public $__table = 'author_authority_alternative';
	public $id;
	public $authorId;
	public /** @noinspection PhpUnused */
		$alternativeAuthor;
	public $normalized;

	public static function getObjectStructure(): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'alternativeAuthor' => [
				'property' => 'alternativeAuthor',
				'type' => 'text',
				'label' => 'Alternative Name',
				'description' => 'Another name for the author',
			],
			'normalized' => [
				'property' => 'normalized',
				'type' => 'text',
				'label' => 'Normalized Value',
				'description' => 'The normalized value for grouping',
				'readOnly' => true,
			],
		];
	}
}