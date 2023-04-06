{strip}
	{if ($showExpirationWarnings && $ilsSummary->isExpirationClose())}
		<div id="myAccountExpirationNotice">
			{if !empty($showExpirationWarnings) && $ilsSummary->isExpirationClose()}
				<div class="alert alert-warning">
					{if $ilsSummary->isExpired()}
						{if !empty($expiredMessage)}
							{$expiredMessage}
						{else}
							{translate text="Your library card expired on %1%." 1=$ilsSummary->expirationDate|date_format:"%D" isPublicFacing=true}
						{/if}
					{else}
						{if !empty($expirationNearMessage)}
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