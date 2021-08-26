{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="Release Notes" isAdminFacing=true}</h1>
		<hr>

		<form class="navbar form-inline row">
			<div class="form-group col-xs-12">
				<label for="releaseSelector" class="control-label">{translate text="Select a release" isAdminFacing=true}</label>&nbsp;
				<select id="releaseSelector" name="releaseSelector" class="form-control input-sm" onchange="return AspenDiscovery.Admin.displayReleaseNotes()">
					{foreach from=$releaseNotes item=releaseNote}
						<option value="{$releaseNote}">{$releaseNote}</option>
					{/foreach}
				</select>
			</div>
		</form>
		<div id="releaseNotes">
			{$releaseNotesFormatted}
		</div>
	</div>
{/strip}