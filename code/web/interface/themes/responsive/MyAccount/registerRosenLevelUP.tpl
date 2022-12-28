{strip}
<h1>{translate text='Register Parent and Student for Rosen LevelUP'}</h1>
<div class="page">
	{if (isset($registerRosenLevelUPResult) && $registerRosenLevelUPResult.success)}
		<div id="regSuccess" class="alert alert-success">
		{if !empty($registerRosenLevelUPResult.message)}
			{$registerRosenLevelUPResult.message}
		{else}
			{translate text='Congratulations, you have successfully registered for Rosen LevelUP.'}
		{/if}
		</div>
	{else}
		<div id="regDescription">
			{if !empty($registerRosenLevelUPFormMessage)}
				{$registerRosenLevelUPFormMessage}
			{else}
				{translate text='This page allows students to register with their parents for Rosen LevelUP'}
			{/if}
		</div>
		{if (isset($registerRosenLevelUPResult))}
			<div id="registerRosenLevelUPFail" class="alert alert-warning">
				{if !empty($registerRosenLevelUPResult.message)}
					{$registerRosenLevelUPResult.message}
				{else}
					{translate text='Sorry, we were unable to create Rosen LevelUP accounts for you.'}
				{/if}
			</div>
		{/if}
		{if !empty($captchaMessage)}
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
