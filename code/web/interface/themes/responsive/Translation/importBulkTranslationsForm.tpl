{strip}
<div id="main-content" class="col-md-12">
	<h1>{translate text="Import Translations"}</h1>

	{if !empty($error)}
		<div class="alert-warning alert">
			{$error|translate}
		</div>
	{/if}

	<form class="form" method="post" enctype="multipart/form-data">
		<div class="form-group">
			<label for="importFile" class="control-label">{translate text="Select a file to import with %1% translations" 1=$userLang->displayName}</label>
			<input type="file" name="importFile" id="importFile">
		</div>

		<div class="form-group">
			<button type="submit" name="submit" class="btn btn-primary">{translate text="Import"}</button>
		</div>
	</form>
</div>
{/strip}