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

		<h1>{translate text='My Payment History' isPublicFacing = true}</h1>

		{if !empty($explanationText)}
			<div id="paymentHistoryExplanation" class="alert alert-info">
				{$explanationText}
			</div>
		{/if}

		{strip}
			<table id="paymentHistory" class="table table-striped">
				<thead>
					<tr>
						<th>{translate text="Date" isPublicFacing=true}</th>
						<th>{translate text="Type" isPublicFacing=true}</th>
						<th>{translate text="Amount" isPublicFacing=true}</th>
						<th>{translate text="Completed?" isPublicFacing=true}</th>
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
