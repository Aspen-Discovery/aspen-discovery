{strip}
<h3>{translate text='Register Parent and Student for Rosen LevelUP'}</h3>
<div class="page">
	{if (isset($registerRosenLevelUPResult) && $registerRosenLevelUPResult.success)}
		<div id="regSuccess" class="alert alert-success">
				Congratulations, you have successfully registered for Rosen LevelUP.
		</div>
	{else}
		<div id="regDescription" class="alert alert-info">
			{if $registerRosenLevelUPFormMessage}
				{$registerRosenLevelUPFormMessage}
			{else}
				This page allows Limitless Libraries students to register with their parents for Rosen LevelUP
			{/if}
		</div>
		{if (isset($registerRosenLevelUPResult))}
			<div id="registerRosenLevelUPFail" class="alert alert-warning">
				{if !empty($registerRosenLevelUPResult.message)}
					{$registerRosenLevelUPResult.message}
				{else}
					Sorry, we were unable to create Rosen LevelUP accounts for you.
				{/if}
			</div>
		{/if}
		{if $captchaMessage}
			<div id="registerRosenLevelUPFail" class="alert alert-warning">
			{$captchaMessage}
			</div>
		{/if}

		{* // TO DO: encourage unlinked accounts to link accounts between parents and children in Aspen *}

		{* // TO DO: establish logged in user as either Parent or Student and autofill form
		{include file="MyAccount/switch-linked-user-form.tpl" label="Parent account information from" actionPath="/MyAccount/ContactInformation"}
		*}

		<div id="registerRosenLevelUPForm">
			{$registerRosenLevelUPForm}
		</div>
	{/if}
</div>
{/strip}
