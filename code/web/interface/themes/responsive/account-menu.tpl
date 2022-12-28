{strip}
	{if !empty($loggedIn)}
		{* Setup the accordion *}
		<!--suppress HtmlUnknownTarget -->
		<div id="account-menu" class="dropdown-menu dropdownMenu" aria-labelledby="account-menu-dropdown" aria-label="{translate text="Account Menu" isPublicFacing=true inAttribute=true}">
			<span class="expirationFinesNotice-placeholder"></span>
			{if !empty($userHasCatalogConnection) && $showUserCirculationModules}
				<a href="/MyAccount/CheckedOut">
					<div class="header-menu-option" >
						{translate text="Checked Out Titles" isPublicFacing=true}
					</div>
				</a>
				<div class="header-menu-option" >
					<a href="/MyAccount/Holds" id="holds">
						{translate text="Titles On Hold" isPublicFacing=true}
					</a>
				</div>
				{if !empty($showCurbsidePickups)}
					<div class="header-menu-option">
						<a href="/MyAccount/CurbsidePickups">{translate text='Curbside Pickups' isPublicFacing=true}</a>
					</div>
				{/if}
				{if !empty($showFines)}
					<div class="header-menu-option" >
						<a href="/MyAccount/Fines">{translate text='Fines and Messages' isPublicFacing=true}</a>
					</div>
				{/if}
			{/if}
			{if $materialRequestType == 1 && $enableAspenMaterialsRequest && $displayMaterialsRequest}
				<div class="header-menu-option materialsRequestLink">
					<a href="/MaterialsRequest/MyRequests">{translate text='Materials Requests' isPublicFacing=true}</a>
				</div>
			{elseif $materialRequestType == 2 && $userHasCatalogConnection && $displayMaterialsRequest}
				<div class="header-menu-option" >
					<a href="/MaterialsRequest/IlsRequests">{translate text='Materials Requests' isPublicFacing=true}</a>
				</div>
			{/if}
			{if !empty($userHasCatalogConnection) && $showUserCirculationModules}
				<div class="header-menu-option" ><a href="/MyAccount/LibraryCard">{if !empty($showAlternateLibraryCard)}{translate text='Your Library Card(s)' isPublicFacing=true}{else}{translate text='Your Library Card' isPublicFacing=true}{/if}</a></div>
			{/if}
			{if !empty($showRatings)}
				{if $user->disableRecommendations == 0}
					<div class="header-menu-option" >
						<a href="/MyAccount/SuggestedTitles">{translate text='Recommended For You' isPublicFacing=true}</a>
					</div>
				{/if}
				<div class="header-menu-option" >
					<a href="/MyAccount/MyRatings">{translate text='Titles You Rated' isPublicFacing=true}</a>
				</div>
			{/if}
			{if $showFavorites == 1}
				<div class="header-menu-option" >
					<a href="/MyAccount/Lists">{translate text='Your Lists' isPublicFacing=true}</a>
				</div>
			{/if}
			{if !empty($enableSavedSearches)}
				{* Only highlight saved searches as active if user is logged in: *}
				<div class="header-menu-option" ><a href="/Search/History?require_login">{translate text='Your Searches' isPublicFacing=true}</a></div>
			{/if}
			{if !empty($userHasCatalogConnection) && $enableReadingHistory}
				<div class="header-menu-option" >
					<a href="/MyAccount/ReadingHistory">
						{translate text="Reading History" isPublicFacing=true}
					</a>
				</div>
			{/if}
			{if !empty($showUserPreferences)}<div class="header-menu-option" ><a href="/MyAccount/MyPreferences">{translate text='Your Preferences' isPublicFacing=true}</a></div>{/if}
			{if !empty($showUserContactInformation)}<div class="header-menu-option" ><a href="/MyAccount/ContactInformation">{translate text='Contact Information' isPublicFacing=true}</a></div>{/if}
			{if $user->showHoldNotificationPreferences()}
				<div class="header-menu-option" ><a href="/MyAccount/HoldNotificationPreferences">{translate text='Hold Notification Preferences' isPublicFacing=true}</a></div>
			{/if}
			{if $user->showMessagingSettings()}
				<div class="header-menu-option" ><a href="/MyAccount/MessagingSettings">{translate text='Messaging Settings' isPublicFacing=true}</a></div>
			{/if}
			{if !empty($allowAccountLinking)}
				<div class="header-menu-option" ><a href="/MyAccount/LinkedAccounts">{translate text='Linked Accounts' isPublicFacing=true}</a></div>
			{/if}
			{if !empty($showResetUsernameLink)}
				<div class="header-menu-option" ><a href="/MyAccount/ResetUsername">{translate text='Reset Username' isPublicFacing=true}</a></div>
			{/if}
			{if !empty($twoFactorEnabled)}
				<div class="header-menu-option"><a href="/MyAccount/Security">{translate text='Security Settings' isPublicFacing=true}</a></div>
			{elseif $allowPinReset && !$offline}
				<div class="header-menu-option" ><a href="/MyAccount/ResetPinPage">{translate text='Reset PIN/Password' isPublicFacing=true}</a></div>
			{/if}
			{if $user->isValidForEContentSource('overdrive') && $showUserCirculationModules}
				<div class="header-menu-option" ><a href="/MyAccount/OverDriveOptions">{translate text='OverDrive Options' isPublicFacing=true}</a></div>
			{/if}
			{if $user->isValidForEContentSource('hoopla') && $showUserCirculationModules}
				<div class="header-menu-option" ><a href="/MyAccount/HooplaOptions">{translate text='Hoopla Options' isPublicFacing=true}</a></div>
			{/if}
			{if $user->isValidForEContentSource('axis360') && $showUserCirculationModules}
				<div class="header-menu-option" ><a href="/MyAccount/Axis360Options">{translate text='Axis 360 Options' isPublicFacing=true}</a></div>
			{/if}
			{if !empty($userIsStaff)}
				<div class="header-menu-option" ><a href="/MyAccount/StaffSettings">{translate text='Staff Settings' isPublicFacing=true}</a></div>
			{/if}

			{if !empty($allowMasqueradeMode) && !$masqueradeMode}
				{if !empty($canMasquerade)}
					<div class="header-menu-option" ><a onclick="AspenDiscovery.Account.getMasqueradeForm();" href="#">{translate text="Masquerade" isPublicFacing=true}</a></div>
				{/if}
			{/if}

			{if !empty($masqueradeMode)}
				<a class="btn btn-default btn-sm btn-block" onclick="AspenDiscovery.Account.endMasquerade()">{translate text="End Masquerade" isAdminFacing=true}</a>
			{/if}

			{if !empty($loggedIn)}
				<a href="/MyAccount/Logout" id="logoutLink" class="btn btn-default btn-sm btn-block">
					{translate text="Sign Out" isPublicFacing=true}
				</a>
			{/if}
		</div>
	{/if}
{/strip}
