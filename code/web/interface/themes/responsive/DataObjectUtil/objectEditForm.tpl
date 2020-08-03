{* Errors *}
{if isset($errors) && count($errors) > 0}
	<div id='errors' class="alert alert-danger">
	{foreach from=$errors item=error}
		<div class='error'>{$error}</div>
	{/foreach}
	</div>
{/if}

{* Create the base form *}
<form id='objectEditor' method="post" {if !empty($contentType)}enctype="{$contentType}"{/if} action="{$submitUrl}" role="form" onsubmit="setFormSubmitting();" aria-label="{$formLabel}">
	{literal}

	<script type="text/javascript">
	let savingForm = false;
	function setFormSubmitting(){
		savingForm = true;
	}
	$(document).ready(function(){
		let objectEditorObject = $('#objectEditor');

		objectEditorObject.validate();
		objectEditorObject.data('serialize',objectEditorObject.serialize()); // On load save form current state
        {/literal}
		{if !empty($initializationJs)}
			{$initializationJs}
		{/if}
		{literal}

		$(window).bind('beforeunload', function(e){
			if (!savingForm) {
				// if form state change show warning box, else don't show it.
				let objectEditorObject = $('#objectEditor');
				if (objectEditorObject.serialize() !== objectEditorObject.data('serialize')) {
					return 'You have made changes to the configuration, would you like to save them before continuing?';
				} else {
					e = null;
				}
			}else{
				e = null;
			}
		}).bind('onsubmit', function(e){
			savingForm = true;
		});
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
						<button type="submit" name="submitStay" value="Save Changes and Continue Editing" class="btn">{translate text="Save Changes and Continue Editing"}</button>
						<button type="submit" name="submitAddAnother" value="Save Changes and Add Another" class="btn">{translate text="Save Changes and Add Another"}</button>
					{/if}
					</div>
				{/if}
			</div>
		{/if}
	</div>
</form>