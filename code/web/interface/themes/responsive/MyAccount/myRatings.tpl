{*{if (isset($title)) }*}
	{*<script type="text/javascript">*}
		{*alert("{$title}");*}
	{*</script>*}
{*{/if}*}
{if $loggedIn}

	{if $profile->web_note}
		<div class="row">
			<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->web_note}</div>
		</div>
	{/if}

	{* Alternate Mobile MyAccount Menu *}
	{include file="MyAccount/mobilePageHeader.tpl"}

	<span class='availableHoldsNoticePlaceHolder'></span>

	<div class="resulthead">
		<h3>{translate text='My Ratings'}</h3>

		<div class="page">
			{if $ratings}
				<table class="table table-striped" id="myRatingsTable">
					<thead>
						<tr>
							<th>{translate text='Date'}</th>
							<th>{translate text='Title'}</th>
							<th>{translate text='Author'}</th>
							<th>{translate text='Rating'}</th>
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
									<span class="btn btn-xs btn-warning" onclick="return VuFind.GroupedWork.clearUserRating('{$rating.groupedWorkId}');">{translate text="Clear"}</span>
								</td>
							</tr>
						{/foreach}
						</tbody>
					</table>
				{else}
					You have not rated any titles yet.
				{/if}

			{if $notInterested}
				<h3>{translate text='Not Interested'}</h3>
				<table class="myAccountTable table table-striped" id="notInterestedTable">
					<thead>
						<tr>
							<th>Date</th>
							<th>Title</th>
							<th>Author</th>
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
						{foreach from=$notInterested item=notInterestedTitle}
							<tr id="notInterested{$notInterestedTitle.id}">
								<td>{$notInterestedTitle.dateMarked|date_format}</td>
								<td><a href="{$notInterestedTitle.link}">{$notInterestedTitle.title}</a></td>
								<td>{$notInterestedTitle.author}</td>
								<td><span class="btn btn-xs btn-warning" onclick="return VuFind.GroupedWork.clearNotInterested('{$notInterestedTitle.id}');">Clear</span></td>
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
		You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
	</div>
{/if}
