{assign var=propName value=$property.property}
{if $property.type != 'section'}
	{if !empty($property.validationPattern)}
		$("#{$propName}").rules("add", {ldelim} regex: "{$property.validationPattern}" {rdelim})
{*		$('#{$propName}').rules("add", {ldelim} regex: '/{$property.validationPattern}/' {rdelim});*}
{*		$.validator.addMethod("{$propName}", function(value, element) {ldelim}*}
{*		return this.optional(element) || /{$property.validationPattern}/i.test(value);*}
{*		{rdelim}, "{$property.label} is invalid: Please enter a valid email address.");*}
	{/if}
{else}
	{foreach from=$property.properties item=property}
		{include file="DataObjectUtil/validationRule.tpl"}
	{/foreach}
{/if}