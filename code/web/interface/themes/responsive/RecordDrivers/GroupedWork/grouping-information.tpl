{strip}
<h4>{translate text="Grouping Information" isPublicFacing=true}</h4>
<table class="table-striped table table-condensed notranslate">
	<tr>
		<th>{translate text="Grouped Work ID" isPublicFacing=true}</th>
		<td>{$recordDriver->getPermanentId()}</td>
	</tr>
	{foreach from=$groupedWorkDetails key='field' item='value'}
	<tr>
		<th>{translate text=$field isPublicFacing=true}</th>
		<td>
			{$value}
		</td>
	</tr>
	{/foreach}
</table>

{if !empty($specifiedDisplayInfo)}
	<div id="groupedWorkDisplayInfo">
		<h4>{translate text="Display Information" isPublicFacing=true}</h4>
		<table class="table-striped table table-condensed notranslate">
			<tr><th>{translate text="Title" isPublicFacing=true}</th><td>{$specifiedDisplayInfo->title}</td></tr>
			<tr><th>{translate text="Subtitle" isPublicFacing=true}</th><td>{$specifiedDisplayInfo->subtitle}</td></tr>
			<tr><th>{translate text="Author" isPublicFacing=true}</th><td>{$specifiedDisplayInfo->author}</td></tr>
			<tr><th>{translate text="Series Name" isPublicFacing=true}</th><td>{$specifiedDisplayInfo->seriesName}</td></tr>
			<tr><th>{translate text="Series Display Order" isPublicFacing=true}</th><td>{if $specifiedDisplayInfo->seriesDisplayOrder != 0}{$specifiedDisplayInfo->seriesDisplayOrder}{/if}</td></tr>
		</table>
		{if $loggedIn && in_array('Set Grouped Work Display Information', $userPermissions)}
			<tr><th></th><td><a onclick="AspenDiscovery.GroupedWork.deleteDisplayInfo('{$recordDriver->getPermanentId()}')" class="btn btn-danger btn-sm">{translate text="Delete" isPublicFacing=true}</a></td></tr>
		{/if}
	</div>
{/if}

{if (!empty($alternateTitles))}
	<h4>{translate text="Alternate Titles and Authors" isPublicFacing=true}</h4>
	<table class="table-striped table table-condensed notranslate">
		<thead>
		<tr><th>{translate text="Title" isPublicFacing=true}</th><th>{translate text="Author" isPublicFacing=true}</th>{if $loggedIn && in_array('Manually Group and Ungroup Works', $userPermissions)}<th>{translate text="Actions" isPublicFacing=true}</th>{/if}</tr>
		</thead>
		{foreach from=$alternateTitles item="alternateTitle"}
			<tr id="alternateTitle{$alternateTitle->id}">
				<td>{$alternateTitle->alternateTitle}</td>
				<td>{$alternateTitle->alternateAuthor}</td>
				{if $loggedIn && in_array('Manually Group and Ungroup Works', $userPermissions)}
					<td><a onclick="AspenDiscovery.GroupedWork.deleteAlternateTitle('{$alternateTitle->id}')" class="btn btn-danger btn-sm">{translate text="Delete" isPublicFacing=true}</a></td>
				{/if}
			</tr>
		{/foreach}
	</table>
{/if}

{if !empty($isUngrouped)}
	<div id="ungrouping">
		<h4>{translate text="Record Ungrouped" isPublicFacing=true}</h4>
		<table class="table-striped table table-condensed notranslate">
		<tr><td>{translate text="This record has been ungrouped from all other records" isPublicFacing=true}</td><td><a onclick="AspenDiscovery.GroupedWork.deleteUngrouping('{$recordDriver->getPermanentId()}', '{$ungroupingId}')" class="btn btn-danger btn-sm">{translate text="Allow to Group" isPublicFacing=true}</a></td></tr>
		</table>
	</div>
{/if}

{if (!empty($primaryIdentifiers))}
	<h4>{translate text="Grouped Records" isPublicFacing=true}</h4>
	<table class="table-striped table table-condensed notranslate">
		<thead>
		<tr><th>{translate text="Type" isPublicFacing=true}</th><th>{translate text="Identifier" isPublicFacing=true}</th><th>{translate text="Use Cover for Grouped Work" isPublicFacing=true}</th></tr>
		</thead>
		{foreach from=$primaryIdentifiers item="groupedRecord"}
			<tr>
				<td>{$groupedRecord->type}</td>
				<td>{$groupedRecord->identifier}</td>
				<td><button onclick="return AspenDiscovery.GroupedWork.getPreviewRelatedCover('{$groupedRecord->identifier}', '{$recordDriver->getPermanentId()}', '{$groupedRecord->type}')" class="btn btn-sm {if strpos($bookcoverInfo->imageSource, $groupedRecord->identifier) == true}btn-info{else}btn-default{/if}">
					{if strpos($bookcoverInfo->imageSource, $groupedRecord->identifier) == true}{translate text="Using this Cover" isPublicFacing=true}{else}{translate text="Preview Cover" isPublicFacing=true}{/if}</button>{if strpos($bookcoverInfo->imageSource, $groupedRecord->identifier) == true} <button onclick="return AspenDiscovery.GroupedWork.clearRelatedCover('{$recordDriver->getPermanentId()}')" class="btn btn-sm btn-warning">{translate text="Reset" isPublicFacing=true}</button>{/if}
				</td>
			</tr>
		{/foreach}
	</table>
{/if}

{if !empty($bookcoverInfo)}
	<h4>{translate text="Book Cover Information" isPublicFacing=true}</h4>
	<table class="table-striped table table-condensed notranslate">
		<tr><th>{translate text="Image Source" isPublicFacing=true}</th><td>{$bookcoverInfo->imageSource}</td></tr>
		<tr><th>{translate text="First Loaded" isPublicFacing=true}</th><td>{$bookcoverInfo->firstLoaded|date_format}</td></tr>
		<tr><th>{translate text="Last Used" isPublicFacing=true}</th><td>{$bookcoverInfo->lastUsed|date_format}</td></tr>
	</table>
{/if}
{/strip}