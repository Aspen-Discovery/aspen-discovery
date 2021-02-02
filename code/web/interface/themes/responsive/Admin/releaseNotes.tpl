{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text="Release Notes"}</h1>
		<hr>

		<form class="navbar form-inline row">
			<div class="form-group col-xs-12">
				<label for="releaseSelector" class="control-label">Select a release&nbsp;</label>
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