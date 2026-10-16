<?php /** @noinspection PhpMissingFieldTypeInspection */
class SelfRegistrationTerms extends DataObject {
	public $__table = 'self_registration_tos';
	public $id;
	public $name;
	public $terms;
	/** @noinspection PhpUnused */
	public $showTOSFirst;
	public $redirect;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		/** @noinspection HtmlRequiredAltAttribute */
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The name for the TOS pages',
				'size' => '40',
				'maxLength' => 75,
			],
			'terms' => [
				'property' => 'terms',
				'type' => 'html',
				'label' => 'Terms of Service',
				'description' => 'The body of the initial Terms of Service page before a patron registers.',
				'allowableTags' => '<p><em><i><strong><b><a><ul><ol><li><h1><h2><h3><h4><h5><h6><h7><pre><code><hr><table><tbody><tr><th><td><caption><img><br><div><span><sub><sup>',
				'hideInLists' => true,
			],
			'showTOSFirst' => [
				'property' => 'showTOSFirst',
				'type' => 'checkbox',
				'label' => 'Show TOS First',
				'description' => 'If checked, show TOS before showing registration form',
				'default' => false,
			]
/*			'redirect' => [
				'property' => 'redirect',
				'type' => 'html',
				'label' => 'TOS Redirect',
				'description' => 'The body of the page a patron is redirected to if they do not agree to the Terms of Service.',
				'allowableTags' => '<p><em><i><strong><b><a><ul><ol><li><h1><h2><h3><h4><h5><h6><h7><pre><code><hr><table><tbody><tr><th><td><caption><img><br><div><span><sub><sup>',
				'hideInLists' => true,
			],*/
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}
}