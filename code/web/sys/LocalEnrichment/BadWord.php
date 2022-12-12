<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class BadWord extends DataObject {
	public $__table = 'bad_words';    // table name
	public $id;                      //int(11)
	public $word;                    //varchar(50)

	public static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'word' => [
				'property' => 'word',
				'type' => 'text',
				'label' => 'Word',
				'description' => 'The word to be censored',
				'maxLength' => 50,
				'required' => true,
			],
		];
	}

	/**
	 * @return string[]
	 */
	function getBadWordExpressions(): array {
		global $memCache;
		global $configArray;
		global $timer;
		$badWordsList = $memCache->get('bad_words_list');
		if ($badWordsList === false) {
			$badWordsList = [];
			$this->find();
			if ($this->getNumResults()) {
				while ($this->fetch()) {
					$quotedWord = preg_quote(trim($this->word));
					//$badWordExpression = '/^(?:.*\W)?(' . preg_quote(trim($badWord->word)) . ')(?:\W.*)?$/';
					$badWordsList[] = "/^$quotedWord(?=\W)|(?<=\W)$quotedWord(?=\W)|(?<=\W)$quotedWord$|^$quotedWord$/i";
				}
			}
			$timer->logTime("Loaded bad words");
			$memCache->set('bad_words_list', $badWordsList, $configArray['Caching']['bad_words_list']);
		}
		return $badWordsList;
	}

	function censorBadWords(?string $search, string $replacement = '***'): ?string {
		if ($search == null) {
			return $search;
		}
		$badWordsList = $this->getBadWordExpressions();
		$result = preg_replace($badWordsList, $replacement, $search);
		return $result;
	}

	function hasBadWords($search): bool {
		$badWordsList = $this->getBadWordExpressions();
		foreach ($badWordsList as $badWord) {
			if (preg_match($badWord, $search)) {
				return true;
			}
		}
		return false;
	}

}