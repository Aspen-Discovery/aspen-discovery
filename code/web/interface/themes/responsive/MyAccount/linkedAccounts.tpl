{strip}
	<div id="main-content">
		{if $loggedIn}
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

			<h1>{translate text='Linked Accounts'}</h1>
			{if $offline}
				<div class="alert alert-warning"><strong>{translate text="The library system is currently offline." isPublicFacing=true}</strong> {translate text="We are unable to retrieve information about your account at this time." isPublicFacing=true}</div>
			{else}
{* MDN 7/26/2019 Do not allow access to linked accounts for linked users *}
{*                {include file="MyAccount/switch-linked-user-form.tpl" label="View Account Settings for" actionPath="/MyAccount/LinkedAccounts"}*}

				<p class="alert alert-info">
					{translate text="linked_account_explanation" defaultText="Linked accounts allow you to easily maintain multiple accounts for the library so you can see all of your information in one place. Information from linked accounts will appear when you view your checkouts, holds, etc in the main account."}
				</p>
				<h2>{translate text="Additional accounts to manage"}</h2>
				<p>{translate text="linked_account_additional" defaultText="The following accounts can be managed from this account."}</p>
				<ul>
					{foreach from=$profile->linkedUsers item=tmpUser}  {* Show linking for the account currently chosen for display in account settings *}
						<li>{$tmpUser->getNameAndLibraryLabel()} <button class="btn btn-xs btn-warning" onclick="AspenDiscovery.Account.removeLinkedUser({$tmpUser->id});">Remove</button> </li>
						{foreachelse}
						<li>None</li>
					{/foreach}
				</ul>
				{if $user->id == $profile->id}{* Only allow account adding for the actual account user is logged in with *}
					<button class="btn btn-primary btn-xs" onclick="AspenDiscovery.Account.addAccountLink()">{translate text="Add an Account"}</button>
				{else}
					<p>{translate text="Log into this account to add other accounts to it."}</p>
				{/if}
				<h2>{translate text="Other accounts that can view this account"}</h2>
				<p>{translate text="linked_account_who_can_view" defaultText="The following accounts can view checkout and hold information from this account.  If someone is viewing your account that you do not want to have access, please contact library staff."}</p>
				<ul>
				{foreach from=$profile->getViewers() item=tmpUser}
					<li>{$tmpUser->getNameAndLibraryLabel()}</li>
				{foreachelse}
					<li>{translate text="None"}</li>
				{/foreach}
				</ul>
			{/if}
		{else}
			<div class="page">
				{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
			</div>
		{/if}
	</div>
{/strip}
