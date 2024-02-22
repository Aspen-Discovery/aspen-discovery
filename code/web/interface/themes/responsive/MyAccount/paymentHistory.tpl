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
			<div id="paymentHistory">
				<div class="row">
					<div class="col-sm-3">{translate text="Date" isPublicFacing=true}</div>
					<div class="col-sm-2">{translate text="Type" isPublicFacing=true}</div>
					<div class="col-sm-3">{translate text="Amount" isPublicFacing=true}</div>
					<div class="col-sm-2">{translate text="Completed?" isPublicFacing=true}</div>
					<div class="col-sm-2"></div>
				</div>
				<div id="paymentHistoryBody" class="striped">
					{foreach from=$paymentHistory item=$payment}
						<div class="row">
							<div class="col-sm-3">{$payment.date|date_format:"%D %T"}</div>
							<div class="col-sm-2">{$payment.type}</div>
							<div class="col-sm-3">{$payment.totalPaid}</div>
							<div class="col-sm-2">{$payment.completed}</div>
							<div class="col-sm-2"><a href="/MyAccount/PaymentDetails?id={$payment.id}" class="btn btn-sm btn-info">More details<</a>/div>
						</div>
					{/foreach}
				</div>
			</div>
		{/strip}
	{else}
		<div class="page">
			{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
		</div>
	{/if}
</div>
