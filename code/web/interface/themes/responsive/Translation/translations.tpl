{strip}
<div id="main-content" class="col-md-12">
	<h1>{translate text="Translations" isAdminFacing=true}</h1>
	<form class="form" id="translationSettingstoolbar">
		<div class="form-group">
            {if !empty($translationModeActive)}
				<button class="btn btn-primary" type="submit" name="stopTranslationMode" value="true" onclick="return AspenDiscovery.changeTranslationMode(false)" >{translate text="Exit Translation Mode" isAdminFacing=true}</button>
            {else}
				<button class="btn btn-primary" type="submit" name="startTranslationMode" value="true" onclick="return AspenDiscovery.changeTranslationMode(true)" >{translate text="Start Translation Mode" isAdminFacing=true}</button>
            {/if}

			<button class="btn btn-primary" type="submit" name="exportAllTranslations">{translate text="Export All Translations" isAdminFacing=true}</button>
			<button class="btn btn-primary" type="submit" name="exportForBulkTranslation">{translate text="Export For Bulk Translation" isAdminFacing=true}</button>
			<a class="btn btn-primary" id="importTranslations" href="/Translation/ImportTranslations">{translate text="Import Translations" isAdminFacing=true}</a>
			<a class="btn btn-primary" id="importBulkTranslations" href="/Translation/ImportBulkTranslations">{translate text="Import Bulk Translations" isAdminFacing=true}</a>
		</div>
	</form>

	<form class="form" id="translationSettings">
		<div class="form-group">
			<input type="checkbox" name="hideTranslated" id="hideTranslated" {if !empty($showTranslated)}checked{/if}>&nbsp;
			<label for="hideTranslated">{translate text="Hide terms that have been translated already" isAdminFacing=true}</label>
		</div>
		<div class="form-group">
			<label for="updatedSince">{translate text="Show Terms created / changed since" isAdminFacing=true}</label>
			<div class="row">
				<div class="col-sm-4">
					<input name='updatedSince' id='updatedSince' value='{if !empty($updatedSince)}{$updatedSince|date_format:"%Y-%m-%d %H:%M"}{/if}' max="{$today}" class='form-control'>
				</div>
				<script type="text/javascript">
					$(document).ready(function(){ldelim}
						rome(updatedSince);
                        {rdelim});
				</script>
			</div>
		</div>
		<div class="form-group">
			<label for="interfaceArea">{translate text="Show Terms displayed to" isAdminFacing=true}</label>
			<select id="interfaceArea" name="interfaceArea" class="form-control">
				<option value="both" {if $interfaceArea == 'both'}selected{/if}>{translate text="Either Public or Administration Users" isAdminFacing=true}</option>
				<option value="public" {if $interfaceArea == 'public'}selected{/if}>{translate text="Only Public Users" isAdminFacing=true}</option>
				<option value="admin" {if $interfaceArea == 'admin'}selected{/if}>{translate text="Only Administration Users" isAdminFacing=true}</option>
			</select>
		</div>
		<div class="form-group">
			<input type="checkbox" name="showMetadata" id="showMetadata" {if !empty($showMetadata)}checked{/if}>&nbsp;
			<label for="showMetadata">{translate text="Show metadata (from MARC Records, eContent, etc)" isAdminFacing=true}</label>
		</div>
		<div class="form-group">
			<input type="checkbox" name="showAdminEnteredData" id="showAdminEnteredData" {if !empty($showAdminEnteredData)}checked{/if}>&nbsp;
			<label for="showAdminEnteredData">{translate text="Show admin entered data (list names, system messages, etc)" isAdminFacing=true}</label>
		</div>
		<div class="form-group">
			<label class="control-label" for="filterTerm">{translate text="Show Terms containing" isAdminFacing=true}</label>
			<input class="form-control" type="text" name="filterTerm" id="filterTerm" value="{$filterTerm}">
		</div>
		<div class="form-group">
			<label class="control-label" for="filterTranslation">{translate text="Show Translations containing" isAdminFacing=true}</label>
			<input class="form-control" type="text" name="filterTranslation" id="filterTranslation" value="{$filterTranslation}">
		</div>
		<div class="form-group">
			<button class="btn btn-primary" type="submit" >{translate text="Update Filters" isAdminFacing=true}</button>
		</div>
	</form>

	<br>

	<form method="post">
		{foreach from=$allTerms item=term}
			<div class="row" id="term_{$term->id}">
				<div class="col-sm-1">{$term->id}</div>
				<div class="col-sm-3"><label for="translation_{$term->id}">{$term->term}</label></div>
				<div class="col-sm-4">
					<input type="hidden" name="translation_changed[{$term->id}]" id="translation_changed_{$term->id}" value="0">
					<textarea class="form-control" rows="1" cols="40" name="translation[{$term->id}]" id="translation_{$term->id}" onchange="$('#translation_changed_{$term->id}').val(1)">
						{if $term->translated}
							{$term->translation}
						{/if}
					</textarea>
				</div>
				<div class="col-sm-3">
					<a href="{$term->samplePageUrl}">{$term->samplePageUrl}</a>
				</div>
				<div class="col-sm-1">
					<a href="#" onclick="return AspenDiscovery.deleteTranslationTerm('{$term->id}');">
						{* On delete action, also remove class 'required' to turn off form validation of the deleted input; so that the form can be submitted by the user  *}
						<img src="/images/silk/delete.png" alt="delete term {$term->term|escape}">
					</a>
				</div>
			</div>
		{foreachelse}
			<div class="alert alert-success">{translate text="Congratulations, you have successfully translated everything!" isAdminFacing=true}</div>
		{/foreach}
		<div class="form-group">
			<button type="submit" name="submit" class="btn btn-primary">{translate text="Save Translations" isAdminFacing=true}</button>
		</div>
	</form>

	{if !empty($pageLinks.all)}<div class="text-center">{$pageLinks.all}</div>{/if}
</div>
{/strip}