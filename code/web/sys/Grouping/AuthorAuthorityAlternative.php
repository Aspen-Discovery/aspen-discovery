<?php /** @noinspection PhpMissingFieldTypeInspection */


class AuthorAuthorityAlternative extends DataObject {
	public $__table = 'author_authority_alternative';
	public $id;
	public $authorId;
	public /** @noinspection PhpUnused */
		$alternativeAuthor;
	public $normalized;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$structure = [
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

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}