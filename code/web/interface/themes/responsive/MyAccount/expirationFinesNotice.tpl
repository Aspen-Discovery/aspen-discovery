{strip}
	{* No need to calculate total fines if in offline mode*}
	{if ($ilsSummary.totalFines > 0 && $showFines) || ($showExpirationWarnings && $ilsSummary.expireClose)}
		<div id="myAccountFines">
			{if $ilsSummary.totalFines > 0 && $showFines}
				{if $showECommerceLink && $totalFines > $minimumFineAmount}
					<div class="myAccountLink">
						<a href="{$eCommerceLink}" target="_blank"{if $showRefreshAccountButton} onclick="AspenDiscovery.Account.ajaxLightbox('{$path}/AJAX/JSON?method=getPayFinesAfterAction')"{/if}  style="color:red; font-weight:bold;">
							{if count($user->getLinkedUsers())>0}
								{translate text="Your accounts have %1% in fines." 1=$ilsSummary.totalFines|number_format:2}
							{else}
								{translate text="Your account has %1% in fines." 1=$ilsSummary.totalFines|number_format:2}
							{/if}
						</a>
					</div>
					<div class="myAccountLink">
						<a href="{$eCommerceLink}" target="_blank"{if $showRefreshAccountButton} onclick="AspenDiscovery.Account.ajaxLightbox('{$path}/AJAX/JSON?method=getPayFinesAfterAction')"{/if}>
							{if $payFinesLinkText}{$payFinesLinkText|translate}{else}{translate text="Pay Fines Online"}{/if}
						</a>
					</div>
				{else}
					<div class="myAccountLink" title="Please contact your local library to pay fines or charges." style="color:red; font-weight:bold;" onclick="alert('Please contact your local library to pay fines or charges.')">
						{if count($user->getLinkedUsers())>0}
							{translate text="Your accounts have $%1% in fines." 1=$ilsSummary.totalFines|number_format:2}
						{else}
							{translate text="Your account has $%1% in fines." 1=$ilsSummary.totalFines|number_format:2}
						{/if}
					</div>
				{/if}
			{/if}

			{if $showExpirationWarnings && $ilsSummary.expireClose}
				<div class="myAccountLink">
					<a class="alignright" title="Please contact your local library to have your library card renewed." style="color:red; font-weight:bold;" onclick="alert('Please Contact your local library to have your library card renewed.')" href="#">
						{if $ilsSummary.expired}
							{if $expiredMessage}
								{$expiredMessage}
							{else}
								{translate text="Your library card expired on %1%." 1=$ilsSummary.expires}
							{/if}
						{else}
							{if $expirationNearMessage}
								{$expirationNearMessage}
							{else}
								{translate text="Your library card will expire on %1%." 1=$ilsSummary.expires}
							{/if}
						{/if}
					</a>
				</div>
			{/if}

		</div>
		<hr class="menu">
	{/if}
{/strip}
