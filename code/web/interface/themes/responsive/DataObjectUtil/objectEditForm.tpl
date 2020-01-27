{* Errors *}
{if isset($errors) && count($errors) > 0}
	<div id='errors' class="alert alert-danger">
	{foreach from=$errors item=error}
		<div class='error'>{$error}</div>
	{/foreach}
	</div>
{/if}

{if !empty($instructions)}
	<div class="alert alert-info">
		{$instructions}
	</div>
{/if}

{* Create the base form *}
<form id='objectEditor' method="post" {if !empty($contentType)}enctype="{$contentType}"{/if} action="{$submitUrl}" role="form">
	{literal}

	<script type="text/javascript">
	$(document).ready(function(){
		$("#objectEditor").validate();
		{/literal}
		{if !empty($initializationJs)}
			{$initializationJs}
		{/if}
		{literal}
	});
	</script>
	{/literal}
	
	<div class='editor'>
		<input type='hidden' name='objectAction' value='save' />
		{if !empty($id)}
		<input type='hidden' name='id' value='{$id}' />
		{/if}

		{foreach from=$structure item=property}
			{include file="DataObjectUtil/property.tpl"}
		{/foreach}

		{if (!isset($canSave) || ($canSave == true))}
			{* Show Recaptcha spam control if set. *}
			{if !empty($captcha)}
			<div class="form-group">
				{$captcha}
			</div>
			{/if}

			<div>
				{if $saveButtonText}
					<button type="submit" name="submit" value="{$saveButtonText}" class="btn btn-primary">{$saveButtonText|translate}</button>
				{else}
					<div id="objectEditorSaveButtons">
					<button type="submit" name="submitReturnToList" value="Save Changes and Return" class="btn btn-primary">{translate text="Save Changes and Return"}</button>
					{if $id}
						<button type="submit" name="submitStay" value="Save Changes and Stay Here" class="btn">{translate text="Save Changes and Stay Here"}</button>
					{else}
						<button type="submit" name="submitAddAnother" value="Save Changes and Add Another" class="btn">{translate text="Save Changes and Add Another"}</button>
					{/if}
					</div>
				{/if}
			</div>
		{/if}
	</div>
</form>