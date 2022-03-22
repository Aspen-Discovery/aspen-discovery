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
				<div class="alert alert-info">{translate text="This tool can be used to clear existing Aspen Data.  Useful for migrations and setting up test servers." isAdminFacing=true}</div>
				<div class="alert alert-danger">{translate text="Data will be lost when you use this! Make sure to backup data first!" isAdminFacing=true}</div>
			</div>
		</div>
		<form id='clearDataForm' method="post" role="form" onsubmit="setFormSubmitting();" aria-label="{translate text="Information to Clear" isAdminFacing=true inAttribute=true}">
			<div class='editor'>
				<div class="row">
					<div class="col-xs-12">
						<div style="margin-bottom: .5em">
							<p class="h2" style="display: inline; vertical-align: top; margin-right: .25em">{translate text="Select Information to Export" isAdminFacing=true}</p>
						</div>
						<div class="form-group checkbox">
							<label for="selectAllDataElements">
								<input type="checkbox" name="selectAllDataElements" id="selectAllDataElements" onchange="AspenDiscovery.toggleCheckboxes('.dataElement', '#selectAllDataElements');">
								<strong>{translate text="Select All" isAdminFacing=true}</strong>
							</label>
						</div>
						<div class="checkbox">
							<label for="dataElement_userData">
								<input class="dataElement" id="dataElement_userData" name='dataElement[]' type="checkbox" value='userData'> {translate text="User Data" isPublicFacing=$property.isPublicFacing isAdminFacing=true}<br>
							</label>
						</div>
						<div class="checkbox">
							<label for="dataElement_userLists">
								<input class="dataElement" id="dataElement_userLists" name='dataElement[]' type="checkbox" value='userLists'> {translate text="User Lists" isPublicFacing=$property.isPublicFacing isAdminFacing=true}<br>
							</label>
						</div>
						<div class="checkbox">
							<label for="dataElement_bibData">
								<input class="dataElement" id="dataElement_bibData" name='dataElement[]' type="checkbox" value='bibData'> {translate text="Bib Data" isPublicFacing=$property.isPublicFacing isAdminFacing=true}<br>
							</label>
						</div>
						<div class="checkbox">
							<label for="dataElement_statistics">
								<input class="dataElement" id="dataElement_statistics" name='dataElement[]' type="checkbox" value='statistics'> {translate text="Statistics" isPublicFacing=$property.isPublicFacing isAdminFacing=true}<br>
							</label>
						</div>
						<div class="checkbox">
							<label for="dataElement_webBuilderContent">
								<input class="dataElement" id="dataElement_webBuilderContent" name='dataElement[]' type="checkbox" value='webBuilderContent'> {translate text="Web Builder Content" isPublicFacing=$property.isPublicFacing isAdminFacing=true}<br>
							</label>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xs-12">
						<div class="form-group">
							<label for="confirmation">{translate text="Enter the server name" isAdminFacing=true}</label>
							<input class="form-control" type="text" name="confirmation" id="confirmation" autocomplete="off" />
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xs-12">
						<div class="form-group">
							<button type="submit" name="submit" value="clearAspenData" class="btn btn-primary">{translate text="Clear Aspen Data" isAdminFacing=true}</button>
						</div>
					</div>
				</div>
			</div>
		</form>
    {/if}
{/strip}