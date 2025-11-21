<?php /** @noinspection PhpMissingFieldTypeInspection */

class GrapesTemplate extends DataObject {
	public $__table = 'grapes_templates';
	public $id;
	public $templateName;
	public $templateContent;
	public $htmlData;
	public $cssData;


	public function getUniquenessFields(): array {
		return [
			'id',
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'templateName' => [
				'property' => 'templateName',
				'type' => 'text',
				'label' => 'Template Name',
				'description' => 'The Name assigned to the template',
			],
			'templateContent' => [
				'property' => 'templateContent',
				'type' => 'hidden',
				'label' => 'templateContent',
				'description' => 'The content of the template',
				'hideInLists' => true,
			],
			'htmlData' => [
				'property' => 'htmlData',
				'type' => 'hidden',
				'label' => 'htmlData',
				'description' => 'html data',
				'hideInLists' => true,
			],
			'cssData' => [
				'property' => 'cssData',
				'type' => 'hidden',
				'label' => 'cssData',
				'description' => 'css data',
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function findById($id) : bool {
		$this->id = $id;
		return $this->find(true);
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		$objectActions = [];

		if ($existingObject instanceof GrapesTemplate) {
			$objectActions[] = [
				'text' => 'Open in Editor',
				'url' => '/services/WebBuilder/GrapesJSTemplates?objectAction=edit&id=' . $existingObject->id,
			];
		}
		return $objectActions;
	}

	function getAdditionalListActions(): array {
		require_once ROOT_DIR . '/services/WebBuilder/Templates.php';
		$objectActions = [];
		$objectActions[] = [
			'text' => 'Open in Editor',
			'url' => '/services/WebBuilder/GrapesJSTemplates?objectAction=edit&id=' . $this->id,
		];
		return $objectActions;
	}

	public function getFormattedContents() {
		require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
		$parsedown = AspenParsedown::instance();
		$parsedown->setBreaksEnabled(true);
		return $parsedown->parse();
	}

	/**
	 * @return array
	 */
	static function getTemplateList(): array {
		$template = new GrapesTemplate();
		$template->orderBy('templateName');
		$template->find();
		$templateList = [];
		while ($template->fetch()) {
			$currentTemplate = new stdClass();
			$currentTemplate->id = $template->id;
			$currentTemplate->templateName = $template->templateName;
			$templateList[$currentTemplate->id] = $currentTemplate->templateName;
		}
		return $templateList;
	}

	function saveAsTemplate() : void {
		$newGrapesTemplate = json_decode(file_get_contents("php://input"), true);
		$html = $newGrapesTemplate['html'];
		$template = new GrapesTemplate();
		$template->htmlData = $html;
		$template->insert();
	}

	function addTemplate($templateName, $templateContent, $htmlData, $cssData) : void {
		$this->templateName = $templateName;
		$this->templateContent = $templateContent;
		$this->htmlData = $htmlData;
		$this->cssData = $cssData;
		$this->insert();
	}


}