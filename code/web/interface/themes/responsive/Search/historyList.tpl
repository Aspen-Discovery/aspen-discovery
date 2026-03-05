{if $userSearchType == 'saved'}
	<div class="col-xs-12">
		<div class="row">
			<div class="form-group col-md-3" id="sortOptions">
				<select aria-label="{translate text="Sort By" inAttribute=true isPublicFacing=true}" id="savedHistorySort" class="form-control">
                    {foreach from=$sortOptions item=sortDesc key=sortVal}
						<option value="{$sortVal}"{if $sort == $sortVal} selected="selected"{/if}>{translate text="Sort By %1%" 1=$sortDesc isPublicFacing=true inAttribute=true}</option>
                    {/foreach}
				</select>

                {literal}
					<script>
						$('#savedHistorySort').on('change', function() {
							const sortOrder = $(this).val();
							AspenDiscovery.Account.loadSearchHistory('saved', 1, 20, sortOrder);
						});
					</script>
                {/literal}
			</div>
			<div class="form-group col-md-9">
				<form class="form-inline" name="savedSearchFilterForm" onsubmit="return AspenDiscovery.Account.loadSearchHistory('saved', 1, 20, $('#savedHistorySort option:selected').val(), $('#savedSearchFilter').val());">
					<div class="input-group">
						<input aria-label="{translate text="Filter Saved Searches" inAttribute=true isPublicFacing=true}" type="text" class="form-control" name="savedSearchFilter" id="savedSearchFilter" value="{$savedSearchFilter}"/>
						<span class="input-group-btn">
							<button type="submit" class="btn btn-default" onclick="return AspenDiscovery.Account.loadSearchHistory('saved', 1, 20, $('#savedHistorySort option:selected').val(), $('#savedSearchFilter').val())"><span class="glyphicon glyphicon-search" aria-hidden="true"></span><span class="sr-only">{translate text="Filter" isPublicFacing=true}</span></button>
							{if !empty($savedSearchFilter)}
								<button type="submit" class="btn btn-default" onclick="return AspenDiscovery.Account.loadSearchHistory('saved', 1, 20, $('#savedHistorySort option:selected').val(), '')">{translate text="Clear" isPublicFacing=true}</button>
	                        {/if}
						</span>
					</div>
				</form>
			</div>
		</div>
	</div>
{/if}
{if $userSearchType == 'saved' && $totalCount === 0}
<div class="row">
	<div class="col-xs-12">
		<div class="alert alert-warning">{translate text="No saved searches match the specified filter, please search again." isPublcFacing=true}</div>
	</div>
</div>
{else}
<table class="table table-bordered table-striped table-responsive" {if $userSearchType == 'saved'}id="searchHistoryTable"{else}id="recentSearchHistoryTable"{/if}>
	<thead>
		<tr>
	        {if $userSearchType == 'saved'}<th style="width: fit-content;">{translate text="Id" isPublicFacing=true}</th>{/if}
			<th>{translate text="Time" isPublicFacing=true}</th>
			{if $userSearchType == 'saved'}<th>{translate text="Name" isPublicFacing=true}</th>{/if}
			<th style="width: fit-content;">{translate text="Search" isPublicFacing=true}</th>
			<th style="width: fit-content;">{translate text="Limits" isPublicFacing=true}</th>
			<th style="width: fit-content;">{translate text="Search Source" isPublicFacing=true}</th>
			<th style="width: fit-content;">{translate text="Results" isPublicFacing=true}</th>
			<th></th>
		</tr>
	</thead>
    {foreach item=info from=$searches name=historyLoop}
		<tr>
			{if $userSearchType == 'saved'}<td>{$info.id}</td>{/if}
			<td>{$info.time}</td>
			{if $userSearchType == 'saved'}<td>{$info.title}{if !empty($info.hasNewResults)}<a href="{$info.newTitlesUrl|escape}"><span class="label badge-updated">{translate text="Updated" isPublicFacing=true}</span></a>{/if}</td>{/if}
			<td><a href="{$info.url|escape}">{if empty($info.description)}{translate text="Anything (empty search)" isPublicFacing=true}{else}{$info.description|escape}{/if}</a></td>
			<td>
                {foreach from=$info.filters item=filters key=field}
                    {foreach from=$filters item=filter}
						<b>{translate text=$field|escape isPublicFacing=true}</b>: {$filter.display|escape}<br>
                    {/foreach}
                {/foreach}</td>
			<td>{$info.source}</td>
			<td>{$info.hits}</td>
			<td>
                {if $userSearchType == 'saved'}
                    <a class="btn btn-xs btn-warning" role="button" href="/MyAccount/SaveSearch?delete={$info.searchId|escape:"url"}&amp;mode=history">{translate text="Delete" isPublicFacing=true}</a>
				{else}
	                <a class="btn btn-xs btn-info" role="button" onclick="return AspenDiscovery.Account.showSaveSearchForm('{$info.searchId}', true)">{translate text="Save" isPublicFacing=true}</a>
				{/if}
			</td>
		</tr>
    {/foreach}
</table>
{/if}
{if $userSearchType == 'recent' && $totalCount > 0}
<a class="btn btn-warning" role="button" href="/Search/History?purge=true">{translate text="Delete my unsaved searches" isPublicFacing=true}</a>
{/if}
