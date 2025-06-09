<?php


class TalpaSettings extends DataObject {
	public $__table = 'talpa_settings';
	public $id;
	public $name;
	public $talpaApiToken;
	public $talpaSearchSourceString;

	public $talpaTryItButton;
	public $tryThisSearchInTalpaText;
	public $tryThisSearchInTalpaSidebarSwitch;
	public $tryThisSearchInTalpaNoResultsSwitch;
	public $talpaExplainerText;

	public $includeTalpaLogoSwitch;

	public $includeTalpaOtherResultsSwitch;
	public $talpaOtherResultsExplainerText;

	public static function getObjectStructure($context = ''): array {
		$buttonOptions = [
			0 => 'None',
			1 => 'Plain Text (custom)',
			2 => 'Talpa Button 1 ("What\'s that book?")',
			3 => 'Talpa Button 2 (light theme)',
			4 => 'Talpa Button 3 (dark theme)',
		];

		return [

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
				'description' => 'A name for these settings',
				'required' => true,
			],
			'talpaApiToken' => [
				'property' => 'talpaApiToken',
				'type' => 'storedPassword',
				'label' => 'Talpa API Token',
				'description' => 'The API token to use when connecting to Talpa',
				'hideInLists' => true,
				'required' => true,
			],

			'talpaSearchSourceString' => [
				'property' => 'talpaSearchSourceString',
				'type' => 'text',
				'label' => 'Search Source String for Talpa',
				'description' => 'What to show in search dropdown menu to search in Talpa. Leave blank to use the default value (&rdquo;Talpa Search&rdquo;).',
				'hideInLists' => false,
				'default' => 'Talpa Search',
			],

			//Try this search in Talpa
			'tryThisSearchInTalpaSection' => [
				'property' => 'tryThisSearchInTalpa',
				'type' => 'section',
				'label' => '"Try This Search In Talpa" Options',
				'hideInLists' => true,
				'properties' => [
					'talpaTryItButton' => [
						'property' => 'talpaTryItButton',
						'type' => 'enum',
						'values' => $buttonOptions,
						'label' => '"Try this search in Talpa" button',
						'description' => 'Promote the use of Talpa within your catalog! Refer to your configuration documentation for visuals of the button options.',
						'hideInLists' => true,
						'onchange' => 'return AspenDiscovery.Talpa.updateTalpaButtonFields();',
						'default' => 2,
					],
					'tryThisSearchInTalpaText' => [
						'property' => 'tryThisSearchInTalpaText',
						'type' => 'text',
						'label' => 'Custom "Try this search in Talpa" button text',
						'description' => 'This text explains what the Talpa button in your catalog does. If you select the Plain Text button, this will be the text on the button. Otherwise, this text will appear underneath the selected button. Leave blank to use the default text (&rdquo;Try this search in Talpa&rdquo;).',
						'hideInLists' => false,
						'default' => 'Try this search in Talpa',
					],
					'tryThisSearchInTalpaSidebarSwitch' => [
						'property' => 'tryThisSearchInTalpaSidebarSwitch',
						'type' => 'checkbox',
						'label' => 'Include button in Sidebar',
						'description' => 'You can promote the use of Talpa by adding this button in your main library catalog sidebar, which will launch the patron\'s search terms in Talpa.',
						'hideInLists' => false,
						'default' => 1,
					],

					'tryThisSearchInTalpaNoResultsSwitch' => [
						'property' => 'tryThisSearchInTalpaNoResultsSwitch',
						'type' => 'checkbox',
						'label' => 'Include button in "No Results"',
						'description' => 'You can promote the use of Talpa by adding this button when no search results are found, which will launch the patron\'s search terms in Talpa. ',
						'hideInLists' => false,
						'default' => 1,
					],
				],
			],

			//Talpa Explainer Text
			'talpaExplainerSection' => [
				'property' => 'talpaExplainerSection',
				'type' => 'section',
				'label' => 'Talpa Explanation Options',
				'hideInLists' => true,
				'properties' => [
					'talpaExplainerText' => [
						'property' => 'talpaExplainerText',
						'type' => 'html',
						'allowableTags' => '<p><em><i><strong><b><a><ul><ol><li><h1><h2><h3><h4><h5><h6><h7><pre><hr><table><tbody><tr><th><td><caption><img><br><div><span><sub><sup>',
						'label' => 'Custom Talpa Explainer Text',
						'description' => 'This is the text that customers will see in the sidebar when performing a Talpa Search; it explains what Talpa is and how it works. Leave blank to use the default value.',
						'hideInLists' => true,
						'default' => '<p>Talpa Search is a new way to search for books and other media using natural language to find items by plot details, genre, descriptions, and more. Talpa combines cutting-edge technology with data from libraries, publishers and readers to enable entirely new ways of searching&mdash;and find what you\'re looking for.</p>
				
						<p>Try searches like: "astronaut stranded on Mars", "novels about France during World War II", "recent cozy mysteries", or "books set in jacksonville florida".</p>
						<p><a href="https://www.talpasearch.com/about" target="_blank">Learn more about Talpa</a>.</p>',
					],
					'includeTalpaLogoSwitch' => [
						'property' => 'includeTalpaLogoSwitch',
						'type' => 'checkbox',
						'label' => 'Include Talpa logo in sidebar explainer text',
						'description' => 'Show Talpa logo on Talpa Search Results page in the explainer text area. ',
						'hideInLists' => true,
						'default' => 1,
					],
				],
			],

			//Other Results
			'talpaOtherResultsSection' => [
				'property' => 'talpaOtherResultsSection',
				'type' => 'section',
				'label' => 'Other Results',
				'hideInLists' => true,
				'properties' => [
					'includeTalpaOtherResultsSwitch' => [
						'property' => 'includeTalpaOtherResultsSwitch',
						'type' => 'checkbox',
						'label' => 'Include Talpa\'s "Other Results" section',
						'description' => 'Allow Talpa to show the &rdquo;Other Results&rdquo; tab in search results. These are results that are unowned by your library, but are relevant to the search query, and at times, may be the best possible answer. Enabling this setting is recommended.',
						'hideInLists' => true,
						'default' => 1,
					],
					'talpaOtherResultsExplainerText' => [
						'property' => 'talpaOtherResultsExplainerText',
						'type' => 'text',
						'label' => 'Custom "Other Results" explainer text',
						'description' => 'If &rdquo;Other Results&rdquo; is enabled, this text appears under the filter and explains the results that Talpa found that are not held by your library. Leave blank to use the default value (Talpa Search found these other results not owned by your library).',
						'hideInLists' => true,
						'default' => 'Talpa Search found these other results not owned by your library.',
					],
				],
			],
		];
	}
}


