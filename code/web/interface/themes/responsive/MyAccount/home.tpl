{strip}
	<div data-role="content">
		{if $loggedIn}
			{if !empty($profile->_web_note)}
				<div id="web_note" class="text-info text-center alert alert-warning"><strong>{$profile->_web_note}</strong></div>
			{/if}
			{if !empty($accountMessages)}
				{include file='systemMessages.tpl' messages=$accountMessages}
			{/if}
			{if !empty($ilsMessages)}
				{include file='ilsMessages.tpl' messages=$ilsMessages}
			{/if}

			<h1>{translate text='My Account'}</h1>
			{if $userHasCatalogConnection}
				<h2>{translate text='Account Summary'}</h2>
				<div>
					{if $offline}
						<div class="alert alert-warning">{translate text=offline_notice defaultText="<strong>The library system is currently offline.</strong> We are unable to retrieve information about your account at this time."}</div>
					{else}

						{translate text='You currently have'}
						<ul>
							<li><strong><span class="checkouts-placeholder"><img src="/images/loading.gif" alt="loading"></span></strong> titles <a href="/MyAccount/CheckedOut">checked out</a></li>
							<li><strong><span class="holds-placeholder"><img src="/images/loading.gif" alt="loading"></span></strong> titles on <a href="/MyAccount/Holds">hold</a>
							<ul class="ils-available-holds" style="display: none">
								<li><strong><span class="ils-available-holds-placeholder"><img src="/images/loading.gif" alt="loading"></span></strong> ready for pickup</li>
							</ul>
							</li>
						</ul>
						{* TODO: Show an alert if any titles are expired or are going to expire *}
					{/if}
				</div>
			{/if}
			{if $showRatings}
				<h2>{translate text='Recommended for you'}</h2>
				{if !$hasRatings}
					<p>
						{translate text='You have not rated any titles.'}
					</p>
					<p>
						{translate text ='If you rate titles, we can provide you with suggestions for titles you might like to read. Suggestions are based on titles you like and information within the catalog. Library staff does not have access to your suggestions.'}
					</p>
				{else}
					<p>Based on the titles you have <a href="/MyAccount/MyRatings">rated</a>, we have <a href="/MyAccount/SuggestedTitles">suggestions for you</a>.  To improve your suggestions keep rating more titles.</p>
				{/if}
			{/if}
		{else}
			You must sign in to view this information. Click <a href="/MyAccount/Login">here</a> to sign in.
		{/if}
	</div>
{/strip}