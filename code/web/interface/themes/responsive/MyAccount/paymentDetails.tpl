<div class="col-xs-12">
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

		<h1>{translate text='Payment Details' isPublicFacing=true}</h1>

		{strip}
			{if $success}
				<div class="row">
					<div class="result-label col-sm-3 col-xs-12">{translate text="Date" isPublicFacing=true}</div>
					<div class="result-value col-sm-9 col-xs-12">
						{$paymentDetails.transactionDate|date_format:"%D %T"}
					</div>
				</div>
				<div class="row">
					<div class="result-label col-sm-3 col-xs-12">{translate text="Transaction Type" isPublicFacing=true}</div>
					<div class="result-value col-sm-9 col-xs-12">
						{translate text=$paymentDetails.transactionType isPublicFacing=true}
					</div>
				</div>
				<div class="row">
					<div class="result-label col-sm-3 col-xs-12">{translate text="Payment Method" isPublicFacing=true}</div>
					<div class="result-value col-sm-9 col-xs-12">
						{translate text=$paymentDetails.paymentType isPublicFacing=true}
					</div>
				</div>
				<div class="row">
					<div class="result-label col-sm-3 col-xs-12">{translate text="Order ID" isPublicFacing=true}</div>
					<div class="result-value col-sm-9 col-xs-12">
						{translate text=$paymentDetails.orderId isPublicFacing=true}
					</div>
				</div>
				{if $paymentDetails.transactionId}
					<div class="row">
						<div class="result-label col-sm-3 col-xs-12">{translate text="Transaction ID" isPublicFacing=true}</div>
						<div class="result-value col-sm-9 col-xs-12">
							{translate text=$paymentDetails.transactionId isPublicFacing=true}
						</div>
					</div>
				{/if}
				<div class="row">
					<div class="result-label col-sm-3 col-xs-12">{translate text="Completed?" isPublicFacing=true}</div>
					<div class="result-value col-sm-9 col-xs-12">
						{if $paymentDetails.completed}{translate text="Yes" isPublicFacing=true}{else}{translate text="No" isPublicFacing=true}{/if}
					</div>
				</div>
				{if $paymentDetails.cancelled}
					<div class="row">
						<div class="result-label col-sm-3 col-xs-12">{translate text="Cancelled?" isPublicFacing=true}</div>
						<div class="result-value col-sm-9 col-xs-12">
							{translate text="Yes" isPublicFacing=true}
						</div>
					</div>
				{/if}
				{if $paymentDetails.error}
					<div class="row">
						<div class="result-label col-sm-3 col-xs-12">{translate text="Error?" isPublicFacing=true}</div>
						<div class="result-value col-sm-9 col-xs-12">
							{translate text="Yes" isPublicFacing=true}
						</div>
					</div>
				{/if}
				{if !empty($paymentDetails.message)}
					<div class="row">
						<div class="result-label col-sm-3 col-xs-12">{translate text="Transaction Information" isPublicFacing=true}</div>
						<div class="result-value col-sm-9 col-xs-12">
							{if $paymentDetails.message}{translate text="Yes" isPublicFacing=true}{else}{translate text="No" isPublicFacing=true}{/if}
						</div>
					</div>
				{/if}

				{if !empty($paymentDetails.paymentLines)}
					<h2>{translate text='Payment Lines' isPublicFacing=true}</h2>
					<table class="table table-condensed table-striped table-bordered">
						<thead>
							<tr>
								<th>{translate text="Description" isPublicFacing=true}</th>
								<th>{translate text="Amount" isPublicFacing=true}</th>
							</tr>
						</thead>
						<tbody>
							{foreach from=$paymentDetails.paymentLines item=paymentLine}
								<tr>
									<td>{$paymentLine.description}</td>
									<td>{$paymentLine.amountPaid|formatCurrency}</td>
								</tr>
							{/foreach}
							<tr>
								<td class="result-label">{translate text="Total" isPublicFacing=true}</td>
								<td class="result-label">{$paymentDetails.totalPaid|formatCurrency}</td>
							</tr>
						</tbody>
					</table>
				{else}
					<div class="row">
						<div class="result-label col-sm-3 col-xs-12">{translate text="Total" isPublicFacing=true}</div>
						<div class="result-value col-sm-9 col-xs-12">
							{$paymentDetails.totalPaid|formatCurrency}
						</div>
					</div>
				{/if}
			{else}
				<div class="alert alert-danger">
					{$message}
				</div>
			{/if}

		{/strip}
	{else}
		<div class="page">
			{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
		</div>
	{/if}
</div>
