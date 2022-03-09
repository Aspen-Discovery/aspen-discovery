{strip}
	{* $profile is set in AJAX.php as the account the hold was placed with. *}
	<div class="content">
		{if $success}
			<p class="alert alert-success">{$message}</p>
			<div class="alert">
				{if $showDetailedHoldNoticeInformation && $profile->_noticePreferenceLabel == 'Mail' && !$treatPrintNoticesAsPhoneNotices}
					{translate text="Once the title arrives at your library you will be mailed a notification informing you that the title is ready for you." isPublicFacing=true}&nbsp;
				{elseif $showDetailedHoldNoticeInformation && ($profile->_noticePreferenceLabel == 'Telephone' || ($profile->_noticePreferenceLabel eq 'Mail' && $treatPrintNoticesAsPhoneNotices))}
					{translate text="Once the title arrives at your library you will receive a phone call informing you that the title is ready for you." isPublicFacing=true}&nbsp;
				{elseif $showDetailedHoldNoticeInformation && $profile->_noticePreferenceLabel == 'Email'}
					{translate text="Once the title arrives at your library you will be emailed a notification informing you that the title is ready for you." isPublicFacing=true}&nbsp;
				{else}
					{translate text="Once the title arrives at your library you will receive a notification informing you that the title is ready for you." isPublicFacing=true}&nbsp;
				{/if}
			</div>

			{if count($whileYouWaitTitles) > 0}
				<h3>{translate text="While You Wait" isPublicFacing=true}</h3>
				{include file='GroupedWork/whileYouWait.tpl'}
			{/if}
		{else}
			<p class="alert alert-danger">{$message}</p>
		{/if}
	</div>
{/strip}