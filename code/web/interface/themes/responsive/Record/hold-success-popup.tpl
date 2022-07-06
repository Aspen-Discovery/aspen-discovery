{strip}
	{* $profile is set in AJAX.php as the account the hold was placed with. *}
	<div class="content">
		{if $success}
			<p class="alert alert-success">{$message}</p>
			<div class="alert">
					{translate text="Once the title arrives at your library you will receive a notification informing you that the title is ready for you." isPublicFacing=true}&nbsp;
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