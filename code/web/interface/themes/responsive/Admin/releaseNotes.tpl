{strip}
	<div id="main-content" class="col-md-12">
		<h1><span id="releaseVersion">{$releaseVersion}</span> {translate text="Release Information" isAdminFacing=true}</h1>
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
		{if !empty($actionItemsFormatted)}
			<div id="actionItemsSection">
				<h2>{translate text="Post Release To Do" isAdminFacing=true}</h2>
				<div>{translate text="After deployment, we suggest Aspen administrators check the following settings" isAdminFacing=true}</div>
				<div id="actionItems" class="alert alert-info">
					{$actionItemsFormatted}
				</div>
				<hr/>
			</div>
		{else}
			<div id="actionItemsSection" style="display: none;">
				<h2>{translate text="Post Release To Do" isAdminFacing=true}</h2>
				<div>{translate text="After deployment, we suggest Aspen administrators check the following settings" isAdminFacing=true}</div>
				<div id="actionItems" class="alert alert-info">

				</div>
				<hr/>
			</div>
		{/if}
		<div id="releaseNotes">
			<h2>{translate text="Changes This Release" isAdminFacing=true}</h2>
			{$releaseNotesFormatted}
		</div>
		{if !empty($testingSuggestionsFormatted)}
			<div id="testingSection">
				<hr/>
				<h2>{translate text="Testing Suggestions" isAdminFacing=true}</h2>
				<div id="testingSuggestions">
					{$testingSuggestionsFormatted}
				</div>
			</div>
		{else}
			<div id="testingSection" style="display: none;">
				<hr/>
				<h2>{translate text="Testing Suggestions" isAdminFacing=true}</h2>
				<div id="testingSuggestions">
				</div>
			</div>
		{/if}
	</div>
{/strip}