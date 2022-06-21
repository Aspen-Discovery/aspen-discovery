{*{if (isset($title)) }*}
	{*<script type="text/javascript">*}
		{*alert("{$title}");*}
	{*</script>*}
{*{/if}*}
{if $loggedIn}

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
		<h1>{translate text='Titles You Rated' isPublicFacing=true}</h1>

		<div class="page">
			{if $ratings}
				<table class="table table-striped" id="myRatingsTable">
					<thead>
						<tr>
							<th>{translate text='Date' isPublicFacing=true}</th>
							<th>{translate text='Title' isPublicFacing=true}</th>
							<th>{translate text='Author' isPublicFacing=true}</th>
							<th>{translate text='Rating' isPublicFacing=true}</th>
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
					{foreach from=$ratings name="recordLoop" key=recordKey item=rating}
						<tr id="myRating{$rating.groupedWorkId|escape}" class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
							<td>
								{if isset($rating.dateRated)}
									{$rating.dateRated|date_format}
								{/if}
							</td>
							<td class="myAccountCell">
								<a href='{$rating.link}'>{$rating.title}</a>
							</td>
							<td class="myAccountCell">
								{$rating.author}
							</td>
							<td class="myAccountCell">
								{*{include file='GroupedWork/title-rating.tpl' shortId=$rating.shortId recordId=$rating.fullId ratingData=$rating.ratingData}*}
								{include file='GroupedWork/title-rating.tpl' id=$rating.groupedWorkId ratingData=$rating.ratingData}
								<p>{$rating.review}</p>
							</td>
							<td class="myAccountCell">
								<span class="btn btn-xs btn-warning" onclick="return AspenDiscovery.GroupedWork.clearUserRating('{$rating.groupedWorkId}');">{translate text="Clear" isPublicFacing=true}</span>
							</td>
						</tr>
					{/foreach}
					</tbody>
				</table>
			{else}
			{translate text="You have not rated any titles yet." isPublicFacing=true}
			{/if}

			{if $notInterested}
				<h1>{translate text="Titles You Don't Want Recommended"}</h1>
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
								<td><span class="btn btn-xs btn-warning" onclick="return AspenDiscovery.GroupedWork.clearNotInterested('{$notInterestedTitle.id}');">{translate text="Clear"}</span></td>
							</tr>
						{/foreach}
					</tbody>
				</table>
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
			{/if}
		</div>
	</div>
{else}
	<div class="page">
		{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
	</div>
{/if}
