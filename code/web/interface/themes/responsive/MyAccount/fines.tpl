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

	<h1>{translate text='Fines' isPublicFacing=true}</h1>
	{if $offline}
		<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
	{else}
		{if $finePaymentResult}
			<div class="alert alert-{if $finePaymentResult->success === true}success{else}danger{/if}" id="finePaymentResult">{$finePaymentResult->message}</div>
		{/if}
		{if count($userFines) > 0}
			{* Show Fine Alert when the user has no linked accounts *}
			{if count($userFines) == 1 && $profile->_fines}
				<div class="alert alert-info">
					{translate text="Your account has <strong>%1%</strong> in fines." 1=$profile->_fines isPublicFacing=true}
				</div>
			{/if}

			{foreach from=$userFines item=fines key=userId name=fineTable}
				<form id="fines{$userId}" method="post">
					{if count($userFines) > 1}<h2>{$userAccountLabel.$userId}</h2>{/if}{* Only show account name if there is more than one account. *}
					{if $fines}
						<table id="finesTable{$smarty.foreach.fineTable.index}" class="fines-table table table-striped">
							<thead>
							<tr>
								{if ($finePaymentType >= 2) && $finesToPay >= 1 && $fineTotalsVal.$userId > $minimumFineAmount}
									<th><input type="checkbox" checked name="selectAllFines{$userId}" id="selectAllFines{$userId}" aria-label="Select all fines" onclick="$('#fines{$userId} .selectedFine').prop('checked', $('#selectAllFines{$userId}').prop('checked'));AspenDiscovery.Account.updateFineTotal('#fines{$userId}', '{$userId}', '{$finesToPay}');"></th>
								{/if}
								{if $showDate}
									<th>{translate text="Date" isPublicFacing=true}</th>
								{/if}
								{if $showReason}
									<th>{translate text="Reason" isPublicFacing=true}</th>
								{/if}
								<th>{translate text="Title" isPublicFacing=true}</th>
								{if $showSystem}
									<th>{translate text="System" isPublicFacing=true}</th>
								{/if}
								<th>{translate text="Fine/Fee Amount" isPublicFacing=true}</th>
								{if $showOutstanding}
									<th>{translate text="Amount Outstanding" isPublicFacing=true}</th>
								{/if}
								{if $finesToPay == 2 && $fineTotalsVal.$userId > $minimumFineAmount}
									<th>{translate text="Amount To Pay" isPublicFacing=true}</th>
								{/if}
							</tr>
							</thead>
							<tbody>
								{foreach from=$fines item=fine}
									<tr>
										{if ($finePaymentType >= 2) && $finesToPay >= 1 && $fineTotalsVal.$userId > $minimumFineAmount && $fine.canPayFine !== false}
											<td>
												<input type="checkbox" checked class="selectedFine" name="selectedFine[{$fine.fineId}]" aria-label="Pay Fine {$fine.reason|escapeCSS}" onchange="AspenDiscovery.Account.updateFineTotal('#fines{$userId}', '{$userId}', '{$finesToPay}')" data-fine_id="{$fine.fineId}" data-fine_amt="{$fine.amountVal}" data-outstanding_amt="{if $showOutstanding}{$fine.amountOutstandingVal}{else}0{/if}">
											</td>
										{elseif ($finePaymentType >= 2) && $finesToPay >= 1 && $fineTotalsVal.$userId > $minimumFineAmount}
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
											<td>{$fine.amountOutstandingVal|formatCurrency}</td>
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
									{if ($finePaymentType >= 2) && $finesToPay >= 1 && $fineTotalsVal.$userId > $minimumFineAmount}
										<td></td>
									{/if}
									<th>{translate text="Total" isPublicFacing=true}</th>
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
								<a class="btn btn-sm btn-primary" href="{$eCommerceLink}" {if $finePaymentType == 1}target="_blank"{/if}{if $showRefreshAccountButton} onclick="AspenDiscovery.Account.ajaxLightbox('/AJAX/JSON?method=getPayFinesAfterAction')"{/if}>
									{if $payFinesLinkText}{$payFinesLinkText}{else}{translate text="Click to Pay Fines Online" isPublicFacing=true}{/if}
								</a>
							{/if}
						{elseif $finePaymentType >= 2}
							{if $fineTotalsVal.$userId > $minimumFineAmount}
								{* We are doing an actual payment of fines online *}
								{if $finePaymentType == 2}
									{include file="MyAccount/paypalPayments.tpl"}
								{elseif $finePaymentType == 3}
									{include file="MyAccount/msbPayments.tpl"}
								{elseif $finePaymentType == 4}
									{include file="MyAccount/comprisePayments.tpl"}
								{elseif $finePaymentType == 5}
									{include file="MyAccount/proPayPayments.tpl"}
								{elseif $finePaymentType == 6}
									{include file="MyAccount/xpressPayPayments.tpl"}
								{/if}
							{else}
								<p>{translate text="Fines and fees can be paid online when you owe more than %1%." 1=$minimumFineAmount|formatCurrency isPublicFacing=true}</p>
							{/if}
						{/if}
					{else}
						<p class="alert alert-success">{translate text="This account does not have any fines within the system." isPublicFacing=true}</p>
					{/if}
				</form>
			{/foreach}
		{else}
			<p class="alert alert-success">{translate text="You do not have any fines within the system." isPublicFacing=true}</p>
		{/if}
	{/if}
{else}
	{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
{/if}
