{* Create the base form *}
<form id='{if empty($ajaxFormId)}objectEditor{if !empty($id)}-{$id}{/if}{else}{$ajaxFormId}{/if}' method="post" {if !empty($contentType)}enctype="{$contentType}"{/if} {if !empty($submitUrl)}action="{$submitUrl}"{/if} role="form" onsubmit="setFormSubmitting();" {if !empty($formLabel)}aria-label="{translate text=$formLabel isAdminFacing=true inAttribute=true}"{/if}>
	<div class='editor'>
		{if !empty($id)}
		<input type='hidden' name='id' value='{$id}' id="id" />
		{/if}

		{foreach from=$structure item=property}
			{include file="DataObjectUtil/property.tpl"}
		{/foreach}
	</div>

	{literal}
	<script type="text/javascript">
		$.validator.addMethod(
			"regex",
			function(value, element, regexp) {
				var re = new RegExp(regexp);
				return this.optional(element) || re.test(value);
			},
			"{/literal}{translate text="Please check your input." isAdminFacing=true inAttribute=true}{literal}"
		);
		$(document).ready(function(){
			var objectEditorObject = $('#objectEditor{/literal}{if !empty($id)}-{$id}{/if}{literal}');

			objectEditorObject.validate();

			{/literal}
			{foreach from=$structure item=property}
				{include file="DataObjectUtil/validationRule.tpl"}
			{/foreach}
			objectEditorObject.data('serialize',objectEditorObject.serialize()); // On load save form current state
			{if !empty($initializationJs)}
				{$initializationJs}
			{/if}
			{if !empty($initializationAdditionalJs)}
			{$initializationAdditionalJs}
			{/if}
			{literal}
		});
	</script>
	{/literal}
</form>