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

	<h1>{translate text='Fines' isPublicFacing=true}</h1>
	{if !empty($offline)}
		<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
	{else}
		{if !empty($finePaymentResult)}
			<div class="alert alert-{if $finePaymentResult->success === true}success{else}danger{/if}" id="finePaymentResult">{$finePaymentResult->message}</div>
		{/if}
		{if !empty($termsOfService)}
			<div class="alert alert-info">
				{translate text=$termsOfService isPublicFacing=true}
			</div>
		{/if}
		{if count($userFines) > 0}
			{* Show Fine Alert when the user has no linked accounts *}
			{if count($userFines) == 1 && $profile->_fines}
				<div class="alert alert-info">
					{translate text="Your account has <strong>%1%</strong> in fines." 1=$profile->_fines inAttribute=true isPublicFacing=true}
				</div>
			{/if}

			{foreach from=$userFines item=fines key=userId name=fineTable}
				<form id="fines{$userId}" method="post">
					{if count($userFines) > 1}<h2>{$userAccountLabel.$userId|escape}</h2>{/if}{* Only show account name if there is more than one account. *}
					{if !empty($fines)}
						<div class="table-responsive">
							<table id="finesTable{$smarty.foreach.fineTable.index}" class="fines-table table table-condensed table-striped" style="table-layout: fixed">
								<thead>
								<tr>
									{if ($finePaymentType >= 2) && $finesToPay >= 1 && $fineTotalsVal.$userId > $minimumFineAmount}
										<th scope="col" style="width: 5%"><input type="checkbox" checked name="selectAllFines{$userId}" id="selectAllFines{$userId}" aria-label="Select all fines" onclick="$('#fines{$userId} .selectedFine').prop('checked', $('#selectAllFines{$userId}').prop('checked'));AspenDiscovery.Account.updateFineTotal('#fines{$userId}', '{$userId}', '{$finesToPay}');"></th>
									{/if}
									<th scope="col"><span>{translate text="Details" isPublicFacing=true}</span></th>
									<th scope="col">
										<div style="width: 100%">
											<span style="white-space:pre-line">{translate text="Fine/Fee Amount" isPublicFacing=true}</span>
										</div>
									</th>
									{if !empty($showOutstanding)}
										<th scope="col">
											<div style="width: 100%">
												<span style="white-space:pre-line">{translate text="Amount Outstanding" isPublicFacing=true}</span>
											</div>
										</th>
									{/if}
									{if $finesToPay == 2 && $fineTotalsVal.$userId > $minimumFineAmount}
										<th scope="col">
											<div style="width: 100%">
												<span style="white-space:pre-line">{translate text="Amount To Pay" isPublicFacing=true}</span>
											</div>
										</th>
									{/if}
								</tr>
								</thead>
								<tbody>
								{assign var=counter value=0}
								{foreach from=$fines item=fine }
									{assign var=counter value=$counter++}
									<tr>
										{if ($finePaymentType >= 2) && $finesToPay >= 1 && $fineTotalsVal.$userId > $minimumFineAmount && $fine.canPayFine !== false}
											<td>
												<input type="checkbox" checked class="selectedFine" name="selectedFine[{$fine.fineId}]" aria-label="Pay Fine {$fine.reason|escapeCSS}" onchange="AspenDiscovery.Account.updateFineTotal('#fines{$userId}', '{$userId}', '{$finesToPay}')" data-fine_id="{$fine.fineId}" data-fine_reason="{$fine.reason}" data-fine_type="{$fine.type}" data-fine_item_description="{$fine.message}" data-fine_item_barcode="{if isset($fine.barcode)}{$fine.barcode}{else}{/if}" data-fine_amt="{$fine.amountVal}" data-outstanding_amt="{if !empty($showOutstanding)}{$fine.amountOutstandingVal}{else}0{/if}">
											</td>
										{elseif ($finePaymentType >= 2) && $finesToPay >= 1 && $fineTotalsVal.$userId > $minimumFineAmount}
											<td></td>
										{/if}
										<td>
											<div style="width: 100%">
											{if !empty($showDate)}
												<p class="fines-table-date" style="white-space:pre-line">{$fine.date}</p>
											{/if}
											<p class="fines-table-reason" style="white-space:pre-line">{$fine.reason}</p>
											<p class="fines-table-message" style="white-space:pre-line">{$fine.message|removeTrailingPunctuation}</p>
											{if !empty($fine.details)}
												{foreach from=$fine.details item=detail}
													<div class="row fines-table-details">
														<div class="col-xs-5 fines-table-details-label" style="white-space:pre-line"><strong>{$detail.label}</strong></div>
														<div class="col-xs-7 fines-table-details-value" style="white-space:pre-line">{$detail.value}</div>
													</div>
												{/foreach}
											{/if}
											{if !empty($showSystem)}
												<p class="fines-table-system" style="white-space:pre-line">{$fine.system}</p>
											{/if}
											</div>
										</td>

										<td>{$fine.amountVal|formatCurrency}</td>
										{if !empty($showOutstanding)}
											<td>{$fine.amountOutstandingVal|formatCurrency}</td>
										{/if}
										{if $finesToPay == 2 && $fineTotalsVal.$userId > $minimumFineAmount && $fine.canPayFine !== false}
											{if !empty($showOutstanding)}
												<td><input aria-label="Amount to Pay for fine {$counter}" type="text" min="0" max="{$fine.amountOutstandingVal}" class="form-control amountToPay" name="amountToPay[{$fine.fineId}]" id="amountToPay{$fine.fineId}" value="{$fine.amountOutstandingVal|string_format:'%.2f'}" onchange="AspenDiscovery.Account.updateFineTotal('#fines{$userId}', '{$userId}', '{$finesToPay}');"> {if !empty($overPayWarning)}
														<div style="width: 100%"><span id="overPayWarning{$fine.fineId}" class="text-danger" style="white-space:pre-line; display:none;"><i class="fas fa-exclamation-triangle"></i> {$overPayWarning}</span></div>{/if}</td>
											{else}
												<td><input aria-label="Amount to Pay for fine {$counter}" type="text" min="0" max="{$fine.amountVal}" name="amountToPay[{$fine.fineId}]" id="amountToPay{$fine.fineId}" value="{$fine.amountVal|string_format:'%.2f'}" class="form-control amountToPay" onchange="AspenDiscovery.Account.updateFineTotal('#fines{$userId}', '{$userId}', '{$finesToPay}');">
													{if !empty($overPayWarning)}<div style="width: 100%"><span id="overPayWarning{$fine.fineId}" class="text-danger" style="white-space:pre-line; display:none;"><i class="fas fa-exclamation-triangle"></i> {$overPayWarning}</span></div>{/if}</td>
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
									<th>
										<div style="width: 100%">
											<span style="white-space:pre-line">{translate text="Total" isPublicFacing=true}</span>
										</div>
									</th>
									<th id="formattedTotal{$userId}">{$fineTotalsVal.$userId|formatCurrency}</th>
									{if !empty($showOutstanding)}
										<th id="formattedOutstandingTotal{$userId}">{$outstandingTotalVal.$userId|formatCurrency}</th>
									{/if}
									{if $finesToPay == 2}
										{* just cleaning up the footer styling *}
										<th></th>
									{/if}
								</tr>
								{if !empty($convenienceFee) && $convenienceFee > 0}
									<tr>
										{if ($finePaymentType >= 2) && $finesToPay >= 1 && $fineTotalsVal.$userId > $minimumFineAmount}
											<td></td>
										{/if}
										{if empty($showOutstanding)}<td></td>{/if}
										<th>
											<div style="width: 100%">
												<span style="white-space:pre-line">{translate text="Convenience Fee" isPublicFacing=true}</span>
											</div>
										</th>
										<th></th>
										<th id="convenienceFee" data-fee_amt="{$convenienceFee}">{$convenienceFee|formatCurrency}</th>
										<th></th>
									</tr>
									<tr>
										{if ($finePaymentType >= 2) && $finesToPay >= 1 && $fineTotalsVal.$userId > $minimumFineAmount}
											<td></td>
										{/if}
										{if empty($showOutstanding)}<td></td>{/if}
										<th>
											<div style="width: 100%">
												<span style="white-space:pre-line">{translate text="Grand Total" isPublicFacing=true}</span>
											</div>
										</th>
										<th></th>
										<th id="outstandingGrandTotal{$userId}">{$outstandingGrandTotalVal.$userId|formatCurrency}</th>
										<th></th>
									</tr>
								{/if}
								</tfoot>
							</table>
						</div>
						{if $finePaymentType == 1}
							{* Pay Fines Button *}
							{if !empty($finePaymentType) && $fineTotalsVal.$userId > $minimumFineAmount}
								<a class="btn btn-sm btn-primary" href="{$eCommerceLink}" {if $finePaymentType == 1}target="_blank"  aria-label="{if !empty($payFinesLinkText)}{$payFinesLinkText}{else}{translate text="Click to Pay Fines Online" isPublicFacing=true}{/if} ({translate text='opens in new window' isPublicFacing=true})"{/if}{if !empty($showRefreshAccountButton)} onclick="AspenDiscovery.Account.ajaxLightbox('/AJAX/JSON?method=getPayFinesAfterAction')"{/if}>
									{if !empty($payFinesLinkText)}{$payFinesLinkText}{else}{translate text="Click to Pay Fines Online" isPublicFacing=true}{/if}
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
								{elseif $finePaymentType == 7}
									{include file="MyAccount/worldPayPayments.tpl"}
								{elseif $finePaymentType == 8}
									{include file="MyAccount/ACISpeedpayPayments.tpl"}
								{elseif $finePaymentType == 9}
									{include file="MyAccount/invoiceCloudPayments.tpl"}
								{elseif $finePaymentType == 10}
									{include file="MyAccount/deluxeCertifiedPaymentsPayments.tpl"}
								{elseif $finePaymentType == 11}
									{include file="MyAccount/paypalPayflowPayments.tpl"}
								{elseif $finePaymentType == 12}
									{include file="MyAccount/squarePayments.tpl"}
								{elseif $finePaymentType == 13}
									{include file="MyAccount/stripePayments.tpl"}
								{elseif $finePaymentType == 14}
									{include file="MyAccount/NCRPayments.tpl"}
								{elseif $finePaymentType == 15}
									{include file="MyAccount/snapPayPayments.tpl"}
                                {elseif $finePaymentType == 16}
                                    {include file="MyAccount/heyCentricPayments.tpl"}
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
