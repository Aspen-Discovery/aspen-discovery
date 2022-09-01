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
		{if $actionItemsFormatted}
			<div id="actionItemsSection">
				<h2>Post Release To Do</h2>
				<div id="actionItems" class="alert alert-info">
					<div>After deployment, we suggest Aspen administrators check the following settings</div>
					{$actionItemsFormatted}
				</div>
				<hr/>
			</div>
		{else}
			<div id="actionItemsSection" style="display: none;">
				<h2>Post Release To Do</h2>
				<div id="actionItems" class="alert alert-info">
					<div>After deployment, we suggest Aspen administrators check the following settings</div>
                    {$actionItemsFormatted}
				</div>
				<hr/>
			</div>
		{/if}
		<div id="releaseNotes">
			<h2>Changes This Release {$firstData = $releaseVersion|reset}</h2>
			{$releaseNotesFormatted}
		</div>
		{if $testingSuggestionsFormatted}
			<div id="testingSection">
				<hr/>
				<h2>Testing Suggestions</h2>
				<div id="testingSuggestions">
					{$testingSuggestionsFormatted}
				</div>
			</div>
		{else}
			<div id="testingSection" style="display: none;">
				<hr/>
				<h2>Testing Suggestions</h2>
				<div id="testingSuggestions">
                    {$testingSuggestionsFormatted}
				</div>
			</div>
		{/if}
	</div>
{/strip}