<div class="col-xs-12">
	{if !empty($loggedIn)}

		<h1>{translate text='My Payment History' isPublicFacing = true}</h1>

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

		{if !empty($explanationText)}
			<div id="paymentHistoryExplanation" class="alert alert-info">
				{$explanationText}
			</div>
		{/if}

		{strip}
			{assign var="hasStripePayments" value=false}
			{foreach from=$paymentHistory item=$payment}
				{if $payment.paymentType == 'stripe'}
					{assign var="hasStripePayments" value=true}
				{/if}
			{/foreach}

			<table id="paymentHistory" class="table table-striped">
				<thead>
					<tr>
						<th>{translate text="Date" isPublicFacing=true}</th>
						<th>{translate text="Type" isPublicFacing=true}</th>
						<th>{translate text="Amount" isPublicFacing=true}</th>
						<th>{translate text="Completed?" isPublicFacing=true}</th>
						{if $hasStripePayments}
							<th>{translate text="Receipt" isPublicFacing=true}</th>
						{/if}
						<th></th>
					</tr>
				</thead>
				<tbody id="paymentHistoryBody">
					{foreach from=$paymentHistory item=$payment}
						<tr>
							<td>{$payment.date|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
							<td>{$payment.type}</td>
							<td>{$payment.totalPaid}</td>
							<td>{$payment.completed}</td>
							{if $hasStripePayments}
								<td>
									{if !empty($payment.receiptUrl)}
										<a href="{$payment.receiptUrl}" target="_blank" rel="noopener noreferrer" class="btn btn-xs btn-default" title="{translate text='View Receipt' isPublicFacing=true inAttribute=true}">
											<i class="fas fa-receipt"></i> {translate text="View" isPublicFacing=true}
										</a>
									{/if}
								</td>
							{/if}
							<td><a href="/MyAccount/PaymentDetails?paymentId={$payment.id}" class="btn btn-sm btn-info">{translate text="More details" isPublicFacing=true}</a></td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		{/strip}
	{else}
		<div class="page">
			{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
		</div>
	{/if}
</div>
