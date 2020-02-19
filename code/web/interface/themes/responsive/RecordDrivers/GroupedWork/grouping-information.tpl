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

{if (!empty($alternateTitles))}
	<h4>Alternate Titles and Authors</h4>
	<table class="table-striped table table-condensed notranslate">
		<thead>
		<tr><th>Title</th><th>Author</th></tr>
		</thead>
		{foreach from=$alternateTitles item="alternateTitle"}
			<tr><td>{$alternateTitle->alternateTitle}</td><td>{$alternateTitle->alternateAuthor}</td></tr>
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