{strip}
<div id="main-content" class="col-md-12">
	<h1>{translate text="Import Translations" isAdminFacing=true}</h1>

	{if !empty($error)}
		<div class="alert-warning alert">
			{translate text=$error isAdminFacing=true}
		</div>
	{/if}

	<form class="form" method="post" enctype="multipart/form-data">
		<div class="form-group">
			<div class="checkbox">
				<label for="overwriteExisting" >{translate text="Overrite Existing Translations?" isAdminFacing=true}
					<input type="checkbox" name="overwriteExisting" id="overwriteExisting" />
				</label>
			</div>
		</div>

		<div>{translate text="Languages to Import" isAdminFacing=true}</div>
		{foreach from=$validLanguages item=language}
			<div class="form-group">
				<div class="checkbox">
					<label for="import_{$language->code}" >{$language->displayName}
						<input type="checkbox" name="import_{$language->code}" id="import_{$language->code}" />
					</label>
				</div>
			</div>
		{/foreach}

		<div class="form-group">
			<label for="importFile" class="control-label">{translate text="Select a file to import" isAdminFacing=true}</label>
			<input type="file" name="importFile" id="importFile">
		</div>

		<div class="form-group">
			<button type="submit" name="submit" class="btn btn-primary">{translate text="Import" isAdminFacing=true}</button>
		</div>
	</form>
</div>
{/strip}