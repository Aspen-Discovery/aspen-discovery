{if empty($communitySearchResults)}
	<div class="alert alert-warning">{translate text="The community server is not configured correctly or it is not responding now." isAdminFacing=true}</div>
{else}
	<form role="form" id="searchCommunityContentForm">
		<input type="hidden" name="objectType" id="objectType" value="{$objectType}">
			<div class="form-group">
			<label for="facetSearchTerm">{translate text="Search" isPublicFacing=true}</label>
			<div class="input-group input-group-sm">
				<input  type="text" name="greenhouseSearchTerm" id="greenhouseSearchTerm" class="form-control" onkeydown="AspenDiscovery.Searches.searchFacetValuesKeyDown(event)"/>
				<span class="btn btn-sm btn-primary input-group-addon" onclick="return AspenDiscovery.Admin.searchCommunityContent('{$toolModule}', '{$toolName}');">{translate text="Search" isPublicFacing=true}</span>
			</div>
		</div>
	</form>
	<div class="col-xs-12">
		<div id="communitySearchResultsLoading" class="alert alert-info" style="display: none">
			{translate text="Loading results" isPublicFacing=true}
		</div>
			<table id="communitySearchResults" class="table table-striped table-bordered">
			<thead>
				<tr>
					<th>{translate text="Name" isAdminFacing=true}</th>
					<th>{translate text="Contibuted By" isAdminFacing=true}</th>
					<th>{translate text="Description" isAdminFacing=true}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$communitySearchResults->results item=$communitySearchResult}
					<tr>
						<td>{$communitySearchResult->name}</td>
						<td>{$communitySearchResult->sharedFrom} {$communitySearchResult->shareDate|date_format:"%D"}</td>
						<td>{$communitySearchResult->description}</td>
						<td><a class="btn btn-default btn-sm" href="/{$toolModule}/{$toolName}?objectAction=importFromCommunity&objectType={$communitySearchResult->type}&sourceId={$communitySearchResult->id}">{translate text="Import" isAdminFacing=true}</a> </td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
{/if}