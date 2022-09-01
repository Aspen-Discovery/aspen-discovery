{* Create the base form *}
<form id='objectEditor-{$id}' method="post" {if !empty($contentType)}enctype="{$contentType}"{/if} action="{$submitUrl}" role="form" onsubmit="setFormSubmitting();" aria-label="{translate text=$formLabel isAdminFacing=true inAttribute=true}">
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
			var objectEditorObject = $('#objectEditor-{/literal}{$id}{literal}');

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