{if $loggedIn}
	{if !empty($profile->_web_note)}
		<div class="row">
			<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
		</div>
	{/if}
	{if !empty($accountMessages)}
		{include file='systemMessages.tpl' messages=$accountMessages}
	{/if}

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
				<form id="fines{$userId}" method="post">
					{if count($userFines) > 1}<h2>{$userAccountLabel.$userId}</h2>{/if}{* Only show account name if there is more than one account. *}
					{if $fines}
						<table id="finesTable{$smarty.foreach.fineTable.index}" class="fines-table table table-striped">
							<thead>
							<tr>
								{if ($finePaymentType == 2 || $finePaymentType == 3) && $finesToPay >= 1 && $fineTotalsVal.$userId > $minimumFineAmount}
									<th><input type="checkbox" checked name="selectAllFines{$userId}" id="selectAllFines{$userId}" aria-label="Select all fines" onclick="$('#fines{$userId} .selectedFine').prop('checked', $('#selectAllFines{$userId}').prop('checked'));AspenDiscovery.Account.updateFineTotal('#fines{$userId}', '{$userId}', '{$finesToPay}');"></th>
								{/if}
								{if $showDate}
									<th>{translate text="Date"}</th>
								{/if}
								{if $showReason}
									<th>{translate text="Reason"}</th>
								{/if}
								<th>{translate text="Title"}</th>
								{if $showSystem}
									<th>{translate text="fine_system" defaultText="System"}</th>
								{/if}
								<th>{translate text="Fine/Fee Amount"}</th>
								{if $showOutstanding}
									<th>{translate text="Amount Outstanding"}</th>
								{/if}
								{if $finesToPay == 2 && $fineTotalsVal.$userId > $minimumFineAmount}
									<th>{translate text="Amount To Pay"}</th>
								{/if}
							</tr>
							</thead>
							<tbody>
								{foreach from=$fines item=fine}
									<tr>
										{if ($finePaymentType == 2 || $finePaymentType == 3) && $finesToPay >= 1 && $fineTotalsVal.$userId > $minimumFineAmount && $fine.canPayFine !== false}
											<td>
												<input type="checkbox" checked class="selectedFine" name="selectedFine[{$fine.fineId}]" aria-label="Pay Fine {$fine.reason|escapeCSS}" onchange="AspenDiscovery.Account.updateFineTotal('#fines{$userId}', '{$userId}', '{$finesToPay}')" data-fine_id="{$fine.fineId}" data-fine_amt="{$fine.amountVal}" data-outstanding_amt="{if $showOutstanding}{$fine.amountOutstandingVal}{else}0{/if}">
											</td>
										{elseif ($finePaymentType == 2 || $finePaymentType == 3) && $finesToPay >= 1 && $fineTotalsVal.$userId > $minimumFineAmount}
											<td></td>
									    {/if}
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
										{if $showSystem}
											<td>
												{$fine.system}
											</td>
										{/if}
										<td>{$fine.amountVal|formatCurrency}</td>
										{if $showOutstanding}
											<td>{$fine.amountOutstanding|formatCurrency}</td>
										{/if}
										{if $finesToPay == 2 && $fineTotalsVal.$userId > $minimumFineAmount && $fine.canPayFine !== false}
											{if $showOutstanding}
												<td><input aria-label="Amount to Pay for fine {$detail.label}" type="text" min="0" max="{$fine.amountOutstandingVal}" name="amountToPay[{$fine.fineId}]" id="amountToPay{$fine.fineId}" value="{$fine.amountOutstandingVal|string_format:'%.2f'}" onchange="AspenDiscovery.Account.updateFineTotal('#fines{$userId}', '{$userId}', '{$finesToPay}');"> </td>
											{else}
												<td><input aria-label="Amount to Pay for fine {$detail.label}" type="text" min="0" max="{$fine.amountVal}" name="amountToPay[{$fine.fineId}]" id="amountToPay{$fine.fineId}" value="{$fine.amountVal|string_format:'%.2f'}" onchange="AspenDiscovery.Account.updateFineTotal('#fines{$userId}', '{$userId}', '{$finesToPay}');"> </td>
											{/if}
										{elseif $finesToPay == 2 && $fineTotalsVal.$userId > $minimumFineAmount}
											<td></td>
										{/if}
									</tr>
								{/foreach}
							</tbody>
							<tfoot>
								<tr class="info">
									{if ($finePaymentType == 2 || $finePaymentType == 3) && $finesToPay >= 1 && $fineTotalsVal.$userId > $minimumFineAmount}
										<td></td>
									{/if}
									<th>{translate text="Total"}</th>
									{if $showDate}
										<td></td>
									{/if}
									{if $showReason}
										<td></td>
									{/if}
									{if $showSystem}
										<td></td>
									{/if}
										<th id="formattedTotal{$userId}">{$fineTotalsVal.$userId|formatCurrency}</th>
									{if $showOutstanding}
										<th id="formattedOutstandingTotal{$userId}">{$outstandingTotalVal.$userId|formatCurrency}</th>
									{/if}
								</tr>
							</tfoot>
						</table>
						{if $finePaymentType == 1}
							{* Pay Fines Button *}
							{if $finePaymentType && $fineTotalsVal.$userId > $minimumFineAmount}
								<a href="{$eCommerceLink}" {if $finePaymentType == 1}target="_blank"{/if}{if $showRefreshAccountButton} onclick="AspenDiscovery.Account.ajaxLightbox('/AJAX/JSON?method=getPayFinesAfterAction')"{/if}>
									<div class="btn btn-sm btn-primary">{if $payFinesLinkText}{$payFinesLinkText}{else}{translate text="Click to Pay Fines Online"}{/if}</div>
								</a>
							{/if}
						{elseif $finePaymentType == 2}
							{if $fineTotalsVal.$userId > $minimumFineAmount}
								{* We are doing an actual payment of fines online *}
								{include file="MyAccount/paypalPayments.tpl"}
							{else}
								<p>{translate text="Fines and fees can be paid online when you owe more than %1%." 1=$minimumFineAmount|formatCurrency}</p>
							{/if}
						{elseif $finePaymentType == 3}
							{if $fineTotalsVal.$userId > $minimumFineAmount}
								{* We are doing an actual payment of fines online *}
								{include file="MyAccount/msbPayments.tpl"}
							{else}
								<p>{translate text="Fines and fees can be paid online when you owe more than %1%." 1=$minimumFineAmount|formatCurrency}</p>
							{/if}
						{/if}
					{else}
						<p class="alert alert-success">{translate text="no_fines_for_account_message" defaultText="This account does not have any fines within the system."}</p>
					{/if}
				</form>
			{/foreach}
		{else}
			<p class="alert alert-success">{translate text="no_fines_message" defaultText="You do not have any fines within the system."}</p>
		{/if}
	{/if}
{else}
	You must sign in to view this information. Click <a href="/MyAccount/Login">here</a> to sign in.
{/if}
