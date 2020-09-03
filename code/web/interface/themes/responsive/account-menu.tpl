{strip}
	{if $loggedIn}
		{* Setup the accoridon *}
		<!--suppress HtmlUnknownTarget -->
		<div id="account-menu" style="display: none">
			<span class="expirationFinesNotice-placeholder"></span>
			{if $userHasCatalogConnection}
				<a href="/MyAccount/CheckedOut">
					<div class="header-menu-option" >
						{translate text="Checked Out Titles"}
					</div>
				</a>
				<div class="header-menu-option" >
					<a href="/MyAccount/Holds" id="holds">
						{translate text="Titles On Hold"}
					</a>
				</div>

				{if $enableMaterialsBooking}
					<div class="header-menu-option" >
						<a href="/MyAccount/Bookings" id="bookings">
							{translate text="Scheduled Items"}
						</a>
					</div>
				{/if}
				<div class="header-menu-option" >
					<a href="/MyAccount/ReadingHistory">
						{translate text="Reading History"}
					</a>
				</div>
				{if $showFines}
					<div class="header-menu-option" >
						<a href="/MyAccount/Fines">{translate text='Fines and Messages'}</a>
					</div>
				{/if}
			{/if}
			{if $materialRequestType == 1 && $enableAspenMaterialsRequest}
				<div class="header-menu-option" >
					<a href="/MaterialsRequest/MyRequests">{translate text='Materials Requests'}</a>
				</div>
			{elseif $materialRequestType == 2 && $userHasCatalogConnection}
				<div class="header-menu-option" >
					<a href="/MaterialsRequest/IlsRequests">{translate text='Materials Requests'}</a>
				</div>
			{/if}
			{if $showRatings}
				<div class="header-menu-option" >
					<a href="/MyAccount/MyRatings">{translate text='Titles You Rated'}</a>
				</div>
				{if $user->disableRecommendations == 0}
					<div class="header-menu-option" >
						<a href="/MyAccount/SuggestedTitles">{translate text='Recommended For You'}</a>
					</div>
				{/if}
			{/if}
			{if $showFavorites == 1}
				<div class="header-menu-option" >
					<a href="/MyAccount/Lists">{translate text='Lists'}</a>
				</div>
			{/if}
			<a onclick="return AspenDiscovery.toggleMenuSection('accountSettings');">
				<div class="header-menu-section" id="accountSettingsMenuSection">
					<i class="fas fa-caret-right"></i>{translate text='Account Settings'}
				</div>
			</a>
			<div id="accountSettingsMenuSectionBody" class="menuSectionBody" style="display: none">
				{if $userHasCatalogConnection}
					<div class="header-menu-option childMenuItem" ><a href="/MyAccount/LibraryCard">{if $showAlternateLibraryCard}{translate text='My Library Card(s)'}{else}{translate text='My Library Card'}{/if}</a></div>
				{/if}
				<div class="header-menu-option childMenuItem" ><a href="/MyAccount/MyPreferences">{translate text='My Preferences'}</a></div>
				<div class="header-menu-option childMenuItem" ><a href="/MyAccount/ContactInformation">{translate text='Contact Information'}</a></div>
				{if $user->showMessagingSettings()}
					<div class="header-menu-option childMenuItem" ><a href="/MyAccount/MessagingSettings">{translate text='Messaging Settings'}</a></div>
				{/if}
				{if $allowAccountLinking}
					<div class="header-menu-option childMenuItem" ><a href="/MyAccount/LinkedAccounts">{translate text='Linked Accounts'}</a></div>
				{/if}
				{if $allowPinReset && !$offline}
					<div class="header-menu-option childMenuItem" ><a href="/MyAccount/ResetPinPage">{translate text='Reset PIN/Password'}</a></div>
				{/if}
				{if $user->isValidForEContentSource('overdrive')}
					<div class="header-menu-option childMenuItem" ><a href="/MyAccount/OverDriveOptions">{translate text='OverDrive Options'}</a></div>
				{/if}
				{if $user->isValidForEContentSource('hoopla')}
					<div class="header-menu-option childMenuItem" ><a href="/MyAccount/HooplaOptions">{translate text='Hoopla Options'}</a></div>
				{/if}
				{if $userIsStaff || count($user->roles) > 0}
					<div class="header-menu-option childMenuItem" ><a href="/MyAccount/StaffSettings">{translate text='Staff Settings'}</a></div>
				{/if}
			</div>
			{* Only highlight saved searches as active if user is logged in: *}
			<div class="header-menu-option" ><a href="/Search/History?require_login">{translate text='history_saved_searches'}</a></div>

			{if $allowMasqueradeMode && !$masqueradeMode}
				{if $canMasquerade}
					<div class="header-menu-option" ><a onclick="AspenDiscovery.Account.getMasqueradeForm();" href="#">{translate text="Masquerade"}</a></div>
				{/if}
			{/if}

			{if $masqueradeMode}
				<a class="btn btn-default btn-sm btn-block" onclick="AspenDiscovery.Account.endMasquerade()">{translate text="End Masquerade"}</a>
			{/if}

			{if $loggedIn}
				<a href="/MyAccount/Logout" id="logoutLink" title="{translate text="Sign Out"}" class="btn btn-default btn-sm btn-block">
					{translate text="Sign Out"}
				</a>
			{/if}
		</div>
	{/if}
{/strip}
