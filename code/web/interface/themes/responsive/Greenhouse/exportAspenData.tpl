{strip}
	<div class="row">
		<div class="col-xs-12">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>
	{if isset($submissionResults)}
		<div class="row">
			<div class="col-xs-12">
				<div class="alert {if $submissionResults.success}alert-success{else}alert-danger{/if}">
					{$submissionResults.message}
				</div>
			</div>
		</div>
	{else}
		<div class="row">
			<div class="col-xs-12">
				<div class="alert alert-info">{translate text="This tool can be used to export Local Enrichment from one Aspen instance to another.  Useful for migrations and setting up test servers." isAdminFacing=true}</div>
			</div>
		</div>
		<form id='exportForm' method="post" role="form" onsubmit="setFormSubmitting();" aria-label="{translate text="Information to Export" isAdminFacing=true inAttribute=true}">
			<div class='editor'>
				<div class="row">
					<div class="col-xs-12">
						<div style="margin-bottom: .5em">
							<p class="h2" style="display: inline; vertical-align: top; margin-right: .25em">{translate text="Select Data to Export" isAdminFacing=true}</p>
						</div>
						<div class="form-group checkbox">
							<label for="selectAllDataElements">
								<input type="checkbox" name="selectAllDataElements" id="selectAllDataElements" onchange="AspenDiscovery.toggleCheckboxes('.dataElement', '#selectAllDataElements');">
								<strong>{translate text="Select All" isAdminFacing=true}</strong>
							</label>
						</div>
						<div class="checkbox">
							{foreach from=$dataElements item=propertyName key=propertyValue}
								<label for="dataElement_{$propertyValue|escape:css}">
									<input class="dataElement" id="dataElement_{$propertyValue|escape:css}" name='dataElement[]' type="checkbox" value='{$propertyValue}'> {translate text=$propertyName.name isPublicFacing=$property.isPublicFacing isAdminFacing=true}<br>
								</label>
							{/foreach}
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xs-12">
						<div style="margin-bottom: .5em">
							<p class="h2" style="display: inline; vertical-align: top; margin-right: .25em">{translate text="Export Data for These Libraries" isAdminFacing=true}</p>
						</div>
						<div class="form-group checkbox">
							<label for="selectAllLibraries">
								<input type="checkbox" name="selectAllLibraries" id="selectAllLibraries" onchange="AspenDiscovery.toggleCheckboxes('.libraries', '#selectAllLibraries');">
								<strong>{translate text="Select All" isAdminFacing=true}</strong>
							</label>
						</div>
						<div class="checkbox">
	                        {foreach from=$libraries item=propertyName key=propertyValue}
								<label for="libraries_{$propertyValue|escape:css}">
									<input class="libraries" id="libraries_{$propertyValue|escape:css}" name='libraries[]' type="checkbox" value='{$propertyValue}'> {translate text=$propertyName isPublicFacing=$property.isPublicFacing isAdminFacing=true}<br>
								</label>
	                        {/foreach}
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xs-12">
						<div style="margin-bottom: .5em">
							<p class="h2" style="display: inline; vertical-align: top; margin-right: .25em">{translate text="Export Data for These Locations" isAdminFacing=true}</p>
						</div>
						<div class="form-group checkbox">
							<label for="selectAllLocations">
								<input type="checkbox" name="selectAllLocations" id="selectAllLocations" onchange="AspenDiscovery.toggleCheckboxes('.locations', '#selectAllLocations');">
								<strong>{translate text="Select All" isAdminFacing=true}</strong>
							</label>
						</div>
						<div class="checkbox">
	                        {foreach from=$locations item=propertyName key=propertyValue}
								<label for="locations_{$propertyValue|escape:css}">
									<input class="locations" id="locations_{$propertyValue|escape:css}" name='locations[]' type="checkbox" value='{$propertyValue}'> {translate text=$propertyName isAdminFacing=true}<br>
								</label>
	                        {/foreach}
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xs-12">
						<div style="margin-bottom: .5em">
							<p class="h2" style="display: inline; vertical-align: top; margin-right: .25em">{translate text="Export Data for These Instances" isAdminFacing=true}</p>
						</div>
						<div class="form-group checkbox">
							<label for="selectAllInstances">
								<input type="checkbox" name="selectAllInstances" id="selectAllInstances" onchange="AspenDiscovery.toggleCheckboxes('.instances', '#selectAllInstances');">
								<strong>{translate text="Select All" isAdminFacing=true}</strong>
							</label>
						</div>
						<div class="checkbox">
                            {foreach from=$instances item=propertyName key=propertyValue}
								<label for="instances_{$propertyValue|escape:css}">
									<input class="instances" id="instances_{$propertyValue|escape:css}" name='instances[]' type="checkbox" value='{$propertyValue}'> {translate text=$propertyName isAdminFacing=true}<br>
								</label>
                            {/foreach}
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xs-12">
						<div class="form-group">
							<label for="prettyPrint">
								<input class="prettyPrint" id="prettyPrint" name='prettyPrint' type="checkbox"> {translate text="Pretty Print (for testing only, will not import)" isAdminFacing=true}<br>
							</label>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xs-12">
						<div class="form-group">
							<button type="submit" name="submit" value="startExport" class="btn btn-primary">{translate text="Start Export" isAdminFacing=true}</button>
						</div>
					</div>
				</div>
			</div>
		</form>
	{/if}
{/strip}