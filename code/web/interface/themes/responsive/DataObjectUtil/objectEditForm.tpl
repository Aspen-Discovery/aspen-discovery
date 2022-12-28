{* Errors *}
{if isset($errors) && count($errors) > 0}
	<div id='errors' class="alert alert-danger">
	{foreach from=$errors item=error}
		<div class='error'>{$error}</div>
	{/foreach}
	</div>
{/if}

{* Create the base form *}
<form id='objectEditor-{if !empty($id)}{$id}{else}-1{/if}' method="post" {if !empty($contentType)}enctype="{$contentType}"{/if} {if !empty($submitUrl)}action="{$submitUrl}"{/if} role="form" onsubmit="setFormSubmitting();" {if !empty($formLabel)}aria-label="{translate text=$formLabel isAdminFacing=true inAttribute=true}"{/if}>
	<div class='editor'>
		<input type='hidden' name='objectAction' value='save' />
		{if !empty($id)}
		<input type='hidden' name='id' value='{$id}' id="id" />
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

			{if empty($saveButtonText)}
				<div class="form-group" id="FloatingSave">
					<button type="submit" name="submitStay" class="btn btn-primary"><i class="fas fa-save fa-2x"></i></button>
				</div>
			{/if}

			<div class="form-group">
				{if !empty($saveButtonText)}
					<button type="submit" name="submit" value="{$saveButtonText}" class="btn btn-primary">{translate text=$saveButtonText isAdminFacing=true}</button>
				{else}
					<div id="objectEditorSaveButtons" class="btn-group">
					<button type="submit" name="submitReturnToList" value="Save Changes and Return" class="btn btn-primary"><i class="fas fa-save"></i> {translate text="Save Changes and Return" isAdminFacing=true}</button>
					{if !empty($id)}
						<button type="submit" name="submitStay" value="Save Changes and Stay Here" class="btn btn-default"><i class="fas fa-pencil-alt"></i> {translate text="Save Changes and Stay Here" isAdminFacing=true}</button>
					{else}
						<button type="submit" name="submitStay" value="Save Changes and Continue Editing" class="btn btn-default"><i class="fas fa-pencil-alt"></i> {translate text="Save Changes and Continue Editing" isAdminFacing=true}</button>
						<button type="submit" name="submitAddAnother" value="Save Changes and Add Another" class="btn btn-default"><i class="fas fa-plus"></i> {translate text="Save Changes and Add Another" isAdminFacing=true}</button>
					{/if}
					</div>
				{/if}
			</div>
		{/if}
	</div>

{if !empty($captcha)}
	{literal}
	<script type="text/javascript">
      var onloadCallback = function() {
          var captchas = document.getElementsByClassName("g-recaptcha");
          for(var i = 0; i < captchas.length; i++) {
              grecaptcha.render(captchas[i], {'sitekey' : '{/literal}{$captchaKey}{literal}'});
          }
      };
    </script>
	{/literal}
{/if}

	{literal}
	<script type="text/javascript">
		var savingForm = false;
		function setFormSubmitting(){
			savingForm = true;
		}
		$.validator.addMethod(
			"regex",
			function(value, element, regexp) {
				var re = new RegExp(regexp);
				return this.optional(element) || re.test(value);
			},
			"{/literal}{translate text="Please check your input." isAdminFacing=true inAttribute=true}{literal}"
		);
		$(document).ready(function(){
			var objectEditorObject = $('#objectEditor-{/literal}{if !empty($id)}{$id}{else}-1{/if}{literal}');

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

			$(window).bind('beforeunload', function(e){
				if (!savingForm) {
					// if form state change show warning box, else don't show it.
					var objectEditorObject = $('#objectEditor-{/literal}{if !empty($id)}{$id}{else}-1{/if}{literal}');
					if (objectEditorObject.serialize() !== objectEditorObject.data('serialize')) {
						return "{/literal}{translate text="You have made changes to the configuration, would you like to save them before continuing?" isAdminFacing=true inAttribute=true}{literal}";
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
</form>