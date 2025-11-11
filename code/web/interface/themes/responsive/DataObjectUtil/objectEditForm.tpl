{* Errors *}
{if isset($errors) && count($errors) > 0}
	<div id='errors' class="alert alert-danger">
	{foreach from=$errors item=error}
		<div class='error'>{$error}</div>
	{/foreach}
	</div>
{/if}

{if !empty($objectAction) && $objectAction == 'copy'}
	{if !empty($copyNotes)}
		<div class="alert alert-info">{$copyNotes}</div>
	{/if}
{/if}

{if !empty($linkedObjectNotifications)}
	<div class="alert alert-info">{$linkedObjectNotifications}</div>
{/if}

<div id="editFormInstructions" class="alert alert-info" {if empty($editFormInstructions)}style="display: none"{/if}>{if !empty($editFormInstructions)}{$editFormInstructions}{/if}</div>

{* Create the base form *}
<form id='objectEditor-{if !empty($id)}{$id}{else}-1{/if}' method="post" {if !empty($contentType)}enctype="{$contentType}"{/if} {if !empty($submitUrl)}action="{$submitUrl}"{/if} role="form" onsubmit="setFormSubmitting();" {if !empty($formLabel)}aria-label="{translate text=$formLabel isAdminFacing=true inAttribute=true}"{/if}>
	<div class='editor'>
		{if !empty($objectAction) && $objectAction == 'copy'}
			<input type='hidden' name='objectAction' value='saveCopy' />
		{else}
			<input type='hidden' name='objectAction' value='save' />
		{/if}
		{if !empty($id)}
		<input type='hidden' name='id' value='{$id}' id="id" />
		{/if}
		{if !empty($sourceId)}
			<input type='hidden' name='sourceId' value='{$sourceId}' id="sourceId" />
		{/if}
		{if !empty($patronIdCheck)}
			<input type="hidden" name="patronIdCheck" value={$patronIdCheck|escape}>
		{else}
			<input type="hidden" name="patronIdCheck" value=0>
		{/if}
		{if !empty($activeIls)}
			<input type="hidden" name="activeIls" id="activeIls" value={$activeIls}>
		{/if}
		{* Preserve all context parameters for list state *}
		{if !empty($page)}
			<input type="hidden" name="page" value="{$page|escape}">
		{/if}
		{if !empty($pageSize)}
			<input type="hidden" name="pageSize" value="{$pageSize|escape}">
		{/if}
		{if !empty($sort)}
			<input type="hidden" name="sort" value="{$sort|escape}">
		{/if}
		{if !empty($filterType)}
			{foreach from=$filterType item=filterTypeValue key=filterKey}
				<input type="hidden" name="filterType[{$filterKey|escape}]" value="{$filterTypeValue|escape}">
			{/foreach}
		{/if}
		{if !empty($filterValue)}
			{foreach from=$filterValue item=filterValueValue key=filterKey}
				<input type="hidden" name="filterValue[{$filterKey|escape}]" value="{$filterValueValue|escape}">
			{/foreach}
		{/if}
		{if !empty($filterValue2)}
			{foreach from=$filterValue2 item=filterValue2Value key=filterKey}
				<input type="hidden" name="filterValue2[{$filterKey|escape}]" value="{$filterValue2Value|escape}">
			{/foreach}
		{/if}

		{foreach from=$structure item=property}
			{if is_array($property) && isset($property.property) && isset($property.type)}
				{include file="DataObjectUtil/property.tpl"}
			{/if}
		{/foreach}

		{if (!isset($canSave) || ($canSave == true))}
			{if !empty($tos)}
				<div class="form-group">
					<input id="tosCheckbox" type="checkbox" {if $tosAccept}checked="checked"{/if}> {translate text="I have read and accept the " isPublicFacing=true} <a onclick="AspenDiscovery.Account.selfRegistrationTermsModal('{$selfRegTermsID}');"> {translate text="Terms of Service" isPublicFacing=true} </a>
				</div>
			{/if}
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
					{if !empty($isSelfRegistration) && !empty($tos) && !$tosAccept}
						<button type="submit" name="submit" disabled="true" value="{$saveButtonText}" class="btn btn-primary">{translate text=$saveButtonText isAdminFacing=true}</button>
					{else}
						<button type="submit" name="submit" value="{$saveButtonText}" class="btn btn-primary">{translate text=$saveButtonText isAdminFacing=true}</button>
					{/if}
				{else}
					{if !empty($objectAction) && $objectAction == 'addNew' && $hasMultiStepAddNew}
						<div id="objectEditorSaveButtons" class="btn-group">
							<button type="submit" name="submitStay" value="Next" class="btn btn-default"><i class="fas fa-pencil-alt"></i> {translate text="Next" isAdminFacing=true}</button>
						</div>
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
	{if !empty($tos)}
		<script type="text/javascript">
			var checkbox = document.querySelector("#tosCheckbox");
			var target = document.querySelector("div.form-group button[value='Register']");
			checkbox.onclick = function() {
				if (checkbox.checked) {
					target.disabled = false;}
				else {
					target.disabled = true;
				}
			}
			{if !empty($showTOSFirst)}
				AspenDiscovery.Account.selfRegistrationTermsModal('{$selfRegTermsID}');
			{/if}
		</script>
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
		$(() => {
			let objectEditorObject = $('#objectEditor-{/literal}{if !empty($id)}{$id}{else}-1{/if}{literal}');

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
			{if !empty($onSubmissionJS)}
			{literal}
			var shouldPrevent = true;
			objectEditorObject.on("submit", function (e) {
				if (shouldPrevent) {
					e.preventDefault();
					var submitForm = function() {
						shouldPrevent = false;
						objectEditorObject.trigger('submit');
					};
					{/literal}{$onSubmissionJS}{literal};
				}
			});
			{/literal}
			{/if}
			{literal}

			let beforeUnloadHandlerAttached = false;
			// Attach beforeunload handler only after user interaction.
			objectEditorObject.one('input change', 'input,select,textarea', () => {
				if (!beforeUnloadHandlerAttached) {
					$(window).on('beforeunload', (e) => {
						if (!savingForm) {
							objectEditorObject = $('#objectEditor-{/literal}{if !empty($id)}{$id}{else}-1{/if}{literal}');
							const currentState = objectEditorObject.serialize();
							const originalState = objectEditorObject.data('serialize');

							// Also check SimpleMDE editors for changes.
							let markdownChanged = false;
							if (typeof AspenDiscovery !== 'undefined' && AspenDiscovery.WebBuilder && AspenDiscovery.WebBuilder.editors) {
								Object.keys(AspenDiscovery.WebBuilder.editors).forEach(editorKey => {
									const editor = AspenDiscovery.WebBuilder.editors[editorKey];
									if (editor && editor.value) {
										const currentMarkdown = editor.value();
										const $editorKey = $('#' + editorKey);
										const originalMarkdown = $editorKey.data('original-value') || $editorKey.val();
										if (currentMarkdown !== originalMarkdown) {
											markdownChanged = true;
										}
									}
								});
							}

							if (currentState !== originalState || markdownChanged) {
								// Modern browsers ignore custom text, but this is required to trigger the dialog.
								e.preventDefault();
								e.returnValue = '';
							}
						}
					});
					beforeUnloadHandlerAttached = true;
				}
			});

			objectEditorObject.on('submit', () => {
				savingForm = true;
			});
		});
	</script>
	{/literal}
</form>
