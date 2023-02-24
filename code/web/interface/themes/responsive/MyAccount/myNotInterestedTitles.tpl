{*{if (isset($title)) }*}
	{*<script type="text/javascript">*}
		{*alert("{$title}");*}
	{*</script>*}
{*{/if}*}
{if !empty($loggedIn)}

	{if !empty($profile->_web_note)}
		<div class="row">
			<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
		</div>
	{/if}
	{if !empty($accountMessages)}
		{include file='systemMessages.tpl' messages=$accountMessages}
	{/if}
	{if !empty($ilsMessages)}
		{include file='ilsMessages.tpl' messages=$ilsMessages}
	{/if}

	<div class="resultHead">
		<h1>{translate text="Titles You're Not Interested In" isPublicFacing=true}</h1>

		<div class="page">

			{if !empty($notInterested)}
				<table class="myAccountTable table table-striped" id="notInterestedTable">
					<thead>
						<tr>
							<th>{translate text="Date" isPublicFacing=true}</th>
							<th>{translate text="Title" isPublicFacing=true}</th>
							<th>{translate text="Author" isPublicFacing=true}</th>
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$notInterested item=notInterestedTitle}
							<tr id="notInterested{$notInterestedTitle.id}">
								<td>{$notInterestedTitle.dateMarked|date_format}</td>
								<td><a href="{$notInterestedTitle.link}">{$notInterestedTitle.title}</a></td>
								<td>{$notInterestedTitle.author}</td>
								<td><span class="btn btn-xs btn-warning" onclick="return AspenDiscovery.GroupedWork.clearNotInterested('{$notInterestedTitle.id}');">{translate text="Clear" isPublicFacing=true}</span></td>
							</tr>
						{/foreach}
					</tbody>
				</table>
				{if !empty($pageLinks.all)}
                    <div class="text-center">{$pageLinks.all}</div>
                {/if}
				<script type="text/javascript">
					$(document).ready(function () {literal} {
						$("#notInterestedTable")
							.tablesorter({
								cssAsc: 'sortAscHeader',
								cssDesc: 'sortDescHeader',
								cssHeader: 'unsortedHeader',
								headers: { 0: { sorter: 'date' }, 3: { sorter: false } },
								sortList: [[0, 1]]
							})
					});
					{/literal}
				</script>
			{else}
				{translate text="You have not marked any titles as not interested in yet." isPublicFacing=true}
			{/if}
		</div>
	</div>
{else}
	<div class="page">
		{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
	</div>
{/if}