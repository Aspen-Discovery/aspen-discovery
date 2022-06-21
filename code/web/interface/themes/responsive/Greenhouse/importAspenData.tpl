{strip}
	<div class="row">
		<div class="col-xs-12">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>
	{if isset($importResults)}
		<div class="row">
			<div class="col-xs-12">
				<div class="alert {if $importResults.success}alert-success{else}alert-danger{/if}">
					{$importResults.message}
				</div>
			</div>
		</div>
	{else}
		<div class="row">
			<div class="col-xs-12">
				<div class="alert alert-info">{translate text="This tool can be used to import Local Enrichment from another Aspen instance.  Useful for migrations and setting up test servers." isAdminFacing=true}</div>
			</div>
		</div>
		{if !empty($setupErrors)}
			<div class="row">
				<div class="col-xs-12">
					{foreach from=$setupErrors item=setupError}
						<div class="alert alert-danger">
							{$setupError}
						</div>
					{/foreach}
				</div>
			</div>
		{else}
			<form id='importForm' method="post" role="form" onsubmit="setFormSubmitting();" aria-label="{translate text="Information to Import" isAdminFacing=true inAttribute=true}">
				<div class='editor'>
					<div class="row">
						<div class="col-xs-12">
							<div style="margin-bottom: .5em">
								<p class="h2" style="display: inline; vertical-align: top; margin-right: .25em">{translate text="Select Enrichment to Import" isAdminFacing=true}</p>
							</div>
							{if count($validEnrichmentToImport) > 1}
								<div class="form-group checkbox">
									<label for="selectAllEnrichmentElements">
										<input type="checkbox" name="selectAllEnrichmentElements" id="selectAllEnrichmentElements" onchange="AspenDiscovery.toggleCheckboxes('.enrichmentElement', '#selectAllEnrichmentElements');">
										<strong>{translate text="Select All" isAdminFacing=true}</strong>
									</label>
								</div>
							{/if}
							<div class="checkbox">
								{foreach from=$validEnrichmentToImport item=propertyName key=propertyValue}
									<label for="enrichmentElement_{$propertyValue|escape:css}">
										<input class="enrichmentElement" id="enrichmentElement_{$propertyValue|escape:css}" name='enrichmentElement[]' type="checkbox" value='{$propertyValue}' checked="checked'"> {translate text=$propertyName isPublicFacing=$property.isPublicFacing isAdminFacing=true}<br>
									</label>
								{/foreach}
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-xs-12">
							<div class="form-group">
								<label for='overrideExisting'>{translate text='Override Existing Values?' isAdminFacing=true}</label>
								<select name="overrideExisting" id="overrideExisting" class="form-control">
									<option value="keepExisting">{translate text='Keep Existing Values' isAdminFacing=true}</option>
									<option value="updateExisting">{translate text='Update Existing Values' isAdminFacing=true}</option>
									<option value="deleteAllExisting">{translate text='Delete Existing Values' isAdminFacing=true}</option>
								</select>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-xs-12">
							<div class="form-group">
								<button type="submit" name="submit" value="startImport" class="btn btn-primary">{translate text="Start Import" isAdminFacing=true}</button>
							</div>
						</div>
					</div>
				</div>
			</form>
		{/if}
	{/if}
{/strip}