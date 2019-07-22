{if $loggedIn}
	{if !empty($profile->_web_note)}
		<div class="row">
			<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
		</div>
	{/if}

	{* Alternate Mobile MyAccount Menu *}
	{include file="MyAccount/mobilePageHeader.tpl"}
	<span class='availableHoldsNoticePlaceHolder'></span>
	<h1>{translate text='Fines'}</h1>
	{if $offline}
		<div class="alert alert-warning">{translate text=offline_notice defaultText="<strong>The library system is currently offline.</strong> We are unable to retrieve information about your account at this time."}</div>
	{else}

		{if count($userFines) > 0}

			{* Show Fine Alert when the user has no linked accounts *}
			{if  count($userFines) == 1 && $profile->_fines}
				<div class="alert alert-info">
					{translate text="fines_summary" defaultText="Your account has <strong>%1%</strong> in fines." 1=$profile->_fines}
				</div>
			{/if}

			{foreach from=$userFines item=fines key=userId name=fineTable}
				{if count($userFines) > 1}<h3>{$userAccountLabel.$userId}</h3>{/if}{* Only show account name if there is more than one account. *}
				{if $fines}
					<table id="finesTable{$smarty.foreach.fineTable.index}" class="fines-table table table-striped">
						<thead>
						<tr>
							{if $showDate}
								<th>{translate text="Date"}</th>
							{/if}
							{if $showReason}
								<th>{translate text="Message"}</th>
							{/if}
							<th>{translate text="Title"}</th>
							<th>{translate text="Fine/Fee Amount"}</th>
							{if $showOutstanding}
								<th>{translate text="Amount Outstanding"}</th>
							{/if}
						</tr>
						</thead>
						<tbody>
						{foreach from=$fines item=fine}
							<tr>
								{if $showDate}
									<td>{$fine.date}</td>
								{/if}
								{if $showReason}
									<td>
										{$fine.reason}
									</td>
								{/if}
								<td>
									{$fine.message|removeTrailingPunctuation}
									{if $fine.details}
										{foreach from=$fine.details item=detail}
											<div class="row">
												<div class="col-xs-5"><strong>{$detail.label}</strong></div>
												<div class="col-xs-7">{$detail.value}</div>
											</div>
										{/foreach}
									{/if}
								</td>
								<td>{$fine.amount}</td>
								{if $showOutstanding}
									<td>{$fine.amountOutstanding}</td>
								{/if}
							</tr>
						{/foreach}
						</tbody>
						<tfoot>
						<tr class="info">
							<th>{translate text="Total"}</th>
							{if $showDate}
								<td></td>
							{/if}
							{if $showReason}
								<td></td>
							{/if}
							<th>{$fineTotals.$userId}</th>
							{if $showOutstanding}
								<th>{$outstandingTotal.$userId}</th>
							{/if}
						</tr>
						</tfoot>
					</table>
				{else}
					<p class="alert alert-success">{translate text="no_fines_for_account_message" defaultText="This account does not have any fines within the system."}</p>
				{/if}
			{/foreach}

			{if $showFinePayments}
				{* We are doing an actual payment of fines online *}
				{include file="MyAccount/finePayments.tpl"}
			{else}
				{* Pay Fines Button *}
				{if $showECommerceLink && $profile->_finesVal > $minimumFineAmount}
					<a href="{$eCommerceLink}" target="_blank"{if $showRefreshAccountButton} onclick="AspenDiscovery.Account.ajaxLightbox('{$path}/AJAX/JSON?method=getPayFinesAfterAction')"{/if}>
						<div class="btn btn-sm btn-primary">{if $payFinesLinkText}{$payFinesLinkText}{else}{translate text="Click to Pay Fines Online"}{/if}</div>
					</a>
				{/if}
			{/if}

		{else}
			<p class="alert alert-success">{translate text="no_fines_message" defaultText="You do not have any fines within the system."}</p>
		{/if}
	{/if}
{else}
	You must login to view this information. Click
	<a href="{$path}/MyAccount/Login">here</a>
	to login.
{/if}
