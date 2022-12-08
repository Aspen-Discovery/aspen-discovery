{strip}
	{* No need to calculate total fines if in offline mode*}
	{if ($ilsSummary->totalFines > 0 && $showFines) || ($showExpirationWarnings && $ilsSummary->isExpirationClose())}
		<div id="myAccountFines">
			{if $ilsSummary->totalFines > 0 && $showFines}
				{if $finePaymentType && $ilsSummary->totalFines > $minimumFineAmount}
					<div class="myAccountLink">
						<a href="{$eCommerceLink}" {if $finePaymentType == 1}target="_blank"{/if}{if $showRefreshAccountButton} onclick="AspenDiscovery.Account.ajaxLightbox('/AJAX/JSON?method=getPayFinesAfterAction')"{/if}  style="color:#c62828; font-weight:bold;">
							{if count($user->getLinkedUsers())>0}
								{translate text="Your accounts have %1% in fines." 1=$ilsSummary->totalFines|formatCurrency inAttribute=true isPublicFacing=true}
							{else}
								{translate text="Your account has %1% in fines." 1=$ilsSummary->totalFines|formatCurrency inAttribute=true isPublicFacing=true}
							{/if}
						</a>
					</div>
					<div class="myAccountLink">
						<a href="{$eCommerceLink}" {if $finePaymentType == 1}target="_blank"{/if}{if $showRefreshAccountButton} onclick="AspenDiscovery.Account.ajaxLightbox('/AJAX/JSON?method=getPayFinesAfterAction')"{/if}>
							{if $payFinesLinkText}{translate text=$payFinesLinkText isPublicFacing=true isAdminEnteredData=true}{else}{translate text="Pay Fines Online" isPublicFacing=true}{/if}
						</a>
					</div>
				{else}
					<div class="myAccountLink" title="{translate text="Please contact your local library to pay fines or charges." isPublicFacing=true}" style="color:#c62828; font-weight:bold;" onclick="alert('{translate text='Please contact your local library to pay fines or charges.' inAttribute=true isPublicFacing=true}')">
						{if count($user->getLinkedUsers())>0}
							{translate text="Your accounts have %1% in fines." 1=$ilsSummary->totalFines|formatCurrency inAttribute=true isPublicFacing=true}
						{else}
							{translate text="Your account has %1% in fines." 1=$ilsSummary->totalFines|formatCurrency inAttribute=true isPublicFacing=true}
						{/if}
					</div>
				{/if}
			{/if}

			{if $showExpirationWarnings && $ilsSummary->isExpirationClose()}
				<div class="myAccountLink">
					{if $ilsSummary->isExpired()}
						{if $expiredMessage}
							{$expiredMessage}
						{else}
							{translate text="Your library card expired on %1%." 1=$ilsSummary->expirationDate|date_format:"%D" isPublicFacing=true}
						{/if}
					{else}
						{if $expirationNearMessage}
							{$expirationNearMessage}
						{else}
							{translate text="Your library card will expire on %1%." 1=$ilsSummary->expirationDate|date_format:"%D" isPublicFacing=true}
						{/if}
					{/if}
				</div>
			{/if}

		</div>
		<hr class="menu">
	{/if}
{/strip}
