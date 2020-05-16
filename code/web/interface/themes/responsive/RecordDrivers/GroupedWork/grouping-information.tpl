{strip}
<h4>Grouping Information</h4>
<table class="table-striped table table-condensed notranslate">
	<tr>
		<th>Grouped Work ID</th>
		<td>{$recordDriver->getPermanentId()}</td>
	</tr>
	{foreach from=$groupedWorkDetails key='field' item='value'}
	<tr>
		<th>{$field|escape}</th>
		<td>
			{$value}
		</td>
	</tr>
	{/foreach}
</table>

{if !empty($specifiedDisplayInfo)}
	<div id="groupedWorkDisplayInfo">
		<h4>Display Information</h4>
		<table class="table-striped table table-condensed notranslate">
			<tr><th>Title</th><td>{$specifiedDisplayInfo->title}</td></tr>
			<tr><th>Subtitle</th><td>{$specifiedDisplayInfo->subtitle}</td></tr>
			<tr><th>Author</th><td>{$specifiedDisplayInfo->author}</td></tr>
			<tr><th>Series Name</th><td>{$specifiedDisplayInfo->seriesName}</td></tr>
			<tr><th>Series Display Order</th><td>{$specifiedDisplayInfo->seriesDisplayOrder}</td></tr>
		</table>
	    {if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
		    <tr><th></th><td><a onclick="AspenDiscovery.GroupedWork.deleteDisplayInfo('{$recordDriver->getPermanentId()}')" class="btn btn-danger btn-sm">Delete</a></td></tr>
	    {/if}
	</div>
{/if}

{if (!empty($alternateTitles))}
	<h4>Alternate Titles and Authors</h4>
	<table class="table-striped table table-condensed notranslate">
		<thead>
		<tr><th>Title</th><th>Author</th>{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}<th>Actions</th>{/if}</tr>
		</thead>
		{foreach from=$alternateTitles item="alternateTitle"}
			<tr id="alternateTitle{$alternateTitle->id}">
				<td>{$alternateTitle->alternateTitle}</td>
				<td>{$alternateTitle->alternateAuthor}</td>
				{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('cataloging', $userRoles))}
					<td><a onclick="AspenDiscovery.GroupedWork.deleteAlternateTitle('{$alternateTitle->id}')" class="btn btn-danger btn-sm">Delete</a></td>
				{/if}
			</tr>
		{/foreach}
	</table>
{/if}

{if (!empty($primaryIdentifiers))}
	<h4>Grouped Records</h4>
	<table class="table-striped table table-condensed notranslate">
		<thead>
		<tr><th>Type</th><th>Identifier</th></tr>
		</thead>
		{foreach from=$primaryIdentifiers item="groupedRecord"}
			<tr><td>{$groupedRecord->type}</td><td>{$groupedRecord->identifier}</td></tr>
		{/foreach}
	</table>
{/if}

{if !empty($bookcoverInfo)}
	<h4>Book Cover Information</h4>
	<table class="table-striped table table-condensed notranslate">
		<tr><th>Image Source</th><td>{$bookcoverInfo->imageSource}</td></tr>
		<tr><th>First Loaded</th><td>{$bookcoverInfo->firstLoaded|date_format}</td></tr>
		<tr><th>Last Used</th><td>{$bookcoverInfo->lastUsed|date_format}</td></tr>
	</table>
{/if}
{/strip}