{assign var=propName value=$property.property}
{if $property.type != 'section'}
	{if !empty($property.validationPattern)}
		$("#{$propName}").rules("add", {ldelim}
			regex: "{$property.validationPattern}"
			{if !empty($property.validationMessage)}, messages: {ldelim}regex: "{$property.validationMessage}"{rdelim}{/if}
		{rdelim});
{*		$('#{$propName}').rules("add", {ldelim} regex: '/{$property.validationPattern}/' {rdelim});*}
{*		$.validator.addMethod("{$propName}", function(value, element) {ldelim}*}
{*		return this.optional(element) || /{$property.validationPattern}/i.test(value);*}
{*		{rdelim}, "{$property.label} is invalid: Please enter a valid email address.");*}
	{/if}
{elseif $propName == 'ssoSection'}
	objectEditorObject.rules("add",
		{ldelim}
			ssoPatronTypeAttr: {ldelim}
				require_from_group: [1, ".patronType-validation-group"]
			{rdelim},
			ssoPatronTypeFallback: {ldelim}
				require_from_group: [1, ".patronType-validation-group"]
			{rdelim},
			ssoLibraryIdAttr: {ldelim}
				require_from_group: [1, ".libraryId-validation-group"]
			{rdelim},
			ssoLibraryIdFallback: {ldelim}
				require_from_group: [1, ".libraryId-validation-group"]
			{rdelim},
			ssoCategoryIdAttr: {ldelim}
				require_from_group: [1, ".categoryId-validation-group"]
			{rdelim},
			ssoCategoryIdFallback: {ldelim}
				require_from_group: [1, ".categoryId-validation-group"]
			{rdelim}
		{rdelim}
	);
{else}
	{foreach from=$property.properties item=property}
		{include file="DataObjectUtil/validationRule.tpl"}
	{/foreach}
{/if}