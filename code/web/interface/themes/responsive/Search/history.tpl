
	<div class="page">
		{if $user->web_note}
			<div class="row">
				<div id="web_note" class="alert alert-info text-center col-xs-12">{$user->web_note}</div>
			</div>
		{/if}

		{* Alternate Mobile MyAccount Menu *}
		{include file="MyAccount/mobilePageHeader.tpl"}

		<span class='availableHoldsNoticePlaceHolder'></span>

		{if !$noHistory}
			{if $saved}
				<h2>{translate text="history_saved_searches"}</h2>
				<table class="table table-bordered table-striped" width="100%">
					<tr>
						<th width="4%">{translate text="history_id"}</th>
						<th width="18%">{translate text="history_time"}</th>
						<th width="30%">{translate text="history_search"}</th>
						<th width="28%">{translate text="history_limits"}</th>
						<th width="10%">{translate text="history_search_source"}</th>
						<th width="5%">{translate text="history_results"}</th>
						<th width="5%">{translate text="history_delete"}</th>
					</tr>
					{foreach item=info from=$saved name=historyLoop}
					<tr>
						<td>{$info.id}</td>
						<td>{$info.time}</td>
						<td><a href="{$info.url|escape}">{if empty($info.description)}{translate text="history_empty_search"}{else}{$info.description|escape}{/if}</a></td>
						<td>{foreach from=$info.filters item=filters key=field}{foreach from=$filters item=filter}
							<b>{translate text=$field|escape}</b>: {$filter.display|escape}<br/>
						{/foreach}{/foreach}</td>
						<td>{$info.source}</td>
						<td>{$info.hits}</td>
						<td><a class="btn btn-xs btn-warning" role="button" href="{$path}/MyAccount/SaveSearch?delete={$info.searchId|escape:"url"}&amp;mode=history">{translate text="history_delete_link"}</a></td>
					</tr>
					{/foreach}
				</table>
				<br/>
			{/if}

			{if $links}
				<div class="resulthead"><h3>{translate text="history_recent_searches"}</h3></div>
				<table class="table table-bordered table-striped" width="100%">
					<tr>
						<th width="15%">{translate text="history_time"}</th>
						<th width="30%">{translate text="history_search"}</th>
						<th width="30%">{translate text="history_limits"}</th>
						<th width="10%">{translate text="history_search_source"}</th>
						<th width="10%">{translate text="history_results"}</th>
						<th width="5%">{translate text="history_save"}</th>
					</tr>
					{foreach item=info from=$links name=historyLoop}
					<tr>
							<td>{$info.time}</td>
							<td><a href="{$info.url|escape}">{if empty($info.description)}{translate text="history_empty_search"}{else}{$info.description|escape}{/if}</a></td>
							<td>
							{foreach from=$info.filters item=filters key=field}
								{foreach from=$filters item=filter}
									<b>{translate text=$field|escape}</b>: {$filter.display|escape}<br>
								{/foreach}
							{/foreach}</td>
							<td>{$info.source}</td>
							<td>{$info.hits}</td>
							<td><a class="btn btn-xs btn-info" role="button" href="{$path}/MyAccount/SaveSearch?save={$info.searchId|escape:"url"}&amp;mode=history">{translate text="history_save_link"}</a></td>
						</tr>
					{/foreach}
				</table>
				<br><a class="btn btn-warning" role="button" href="{$path}/Search/History?purge=true"><span class="silk delete">&nbsp;</span>{translate text="history_purge"}</a>
			{/if}

		{else}
			<h3>{translate text="history_recent_searches"}</h3>
			{translate text="history_no_searches"}
		{/if}
	</div>

