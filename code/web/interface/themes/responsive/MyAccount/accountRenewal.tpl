{strip}
	<div id="main-content">
		{if empty($loggedIn)}
			{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
		{elseif $ilsUnsupported} 
			{translate text="Card and account renewals are not supported." isPublicFacing=true}
		{else}
			<h1>{translate text='Account Renewal' isPublicFacing=true}</h1>
			<h2>{translate text="{$currentStep.title}" isPublicFacing=true}</h2>
			{include file="MyAccount/accountRenewal/warnings.tpl"}
			<form method="post" action="/MyAccount/AccountRenewal">
				{include file="MyAccount/accountRenewal/step.tpl"}
				{include file="MyAccount/accountRenewal/navigation.tpl"}
			</form>
		{/if}
	</div>
{/strip}