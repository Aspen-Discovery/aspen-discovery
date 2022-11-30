{strip}
	{if $loggedIn}

		{* Setup the accordion *}
		<!--suppress HtmlUnknownTarget -->
		<div id="home-account-links" class="sidebar-links row">
			<div class="panel-group accordion" id="account-link-accordion">
				{if $showMyAccount}
				<div class="panel active">
					{* With SidebarMenu on, we should always keep the MyAccount Panel open. *}

					{* Clickable header for your account section *}
					<a data-toggle="collapse" href="#myAccountPanel" aria-label="{translate text="Your Account Menu" inAttribute="true" isPublicFacing=true}">
						<div class="panel-heading">
							<div class="panel-title">
								{*Your ACCOUNT*}
								{translate text="Your Account" isPublicFacing=true}
							</div>
						</div>
					</a>
					{*  This content is duplicated in MyAccount/mobilePageHeader.tpl; Update any changes there as well *}
					<div id="myAccountPanel" class="panel-collapse collapse in">
						<div class="panel-body">
							{if !$offline}
								<span class="expirationFinesNotice-placeholder"></span>
							{/if}
							{if $userHasCatalogConnection && (!$offline || $enableEContentWhileOffline) && $showUserCirculationModules}
								<div class="myAccountLink">
									<a href="/MyAccount/CheckedOut" id="checkedOut">
										{translate text="Checked Out Titles" isPublicFacing=true}
									</a>
								</div>
								<ul class="account-submenu">
									{if !$offline}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/CheckedOut?tab=ils" id="checkedOutIls" title="View checkouts of physical materials">
												{translate text="Physical Materials" isPublicFacing=true} {if !$offline}<span class="badge"><span class="ils-checkouts-placeholder">??</span></span> <span class="ils-overdue" style="display: none"> <span class="label label-danger"><span class="ils-overdue-placeholder"></span> {translate text="Overdue" isPublicFacing=true}</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->isValidForEContentSource('overdrive')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/CheckedOut?tab=overdrive" id="checkedOutOverDrive" title="View checkouts from OverDrive">
												{translate text="OverDrive" isPublicFacing=true} {if !$offline}<span class="badge"><span class="overdrive-checkouts-placeholder">??</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->isValidForEContentSource('hoopla')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/CheckedOut?tab=hoopla" id="checkedOutHoopla" title="View checkouts from Hoopla">
												{translate text="Hoopla" isPublicFacing=true} {if !$offline}<span class="badge"><span class="hoopla-checkouts-placeholder">??</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->isValidForEContentSource('cloud_library')}
										<li class="myAccountLink">
										&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/CheckedOut?tab=cloud_library" id="checkedOutCloudLibrary" title="View checkouts from CloudLibrary">
												{translate text="cloudLibrary" isPublicFacing=true} {if !$offline}<span class="badge"><span class="cloud_library-checkouts-placeholder">??</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->isValidForEContentSource('axis360')}
										<li class="myAccountLink">
										&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/CheckedOut?tab=axis360" id="checkedOutAxis360" title="View checkouts from Axis 360">
												{translate text="Axis 360" isPublicFacing=true} {if !$offline}<span class="badge"><span class="axis360-checkouts-placeholder">??</span></span>{/if}
											</a>
										</li>
									{/if}
								</ul>

								<div class="myAccountLink">
									<a href="/MyAccount/Holds" id="holds">
										{translate text="Titles On Hold" isPublicFacing=true}
									</a>
								</div>
								<ul class="account-submenu">
									{if !$offline}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/Holds?tab=ils" id="holdsIls" title="View holds on physical materials">
												{translate text="Physical Materials" isPublicFacing=true} {if !$offline}<span class="badge"><span class="ils-holds-placeholder">??</span></span> <span class="ils-available-holds" style="display: none"> <span class="label label-success"><span class="ils-available-holds-placeholder"></span> {translate text="Ready for Pickup" isPublicFacing=true}</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->hasInterlibraryLoan()}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/Holds?tab=interlibrary_loan" id="holdsInterlibraryLoan" title="View Interlibrary Loan Requests">
												{translate text="Interlibrary Loan Requests" isPublicFacing=true} {if !$offline}<span class="badge"><span class="interlibrary-loan-requests-placeholder">??</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->isValidForEContentSource('overdrive')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/Holds?tab=overdrive" id="holdsOverDrive" title="View holds from OverDrive">
												{translate text="OverDrive" isPublicFacing=true} {if !$offline}<span class="badge"><span class="overdrive-holds-placeholder">??</span></span> <span class="overdrive-available-holds" style="display: none"> <span class="label label-success"><span class="overdrive-available-holds-placeholder"></span> {translate text="Available Now" isPublicFacing=true}</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->isValidForEContentSource('cloud_library')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/Holds?tab=cloud_library" id="holdsCloudLibrary" title="View holds from CloudLibrary">
												{translate text="cloudLibrary" isPublicFacing=true} {if !$offline}<span class="badge"><span class="cloud_library-holds-placeholder">??</span></span> <span class="cloud_library-available-holds" style="display: none"> <span class="label label-success"><span class="cloud_library-available-holds-placeholder"></span> {translate text="Available Now" isPublicFacing=true}</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->isValidForEContentSource('axis360')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/Holds?tab=axis360" id="holdsAxis360" title="View holds from Axis 360">
												{translate text="Axis 360" isPublicFacing=true} {if !$offline}<span class="badge"><span class="axis360-holds-placeholder">??</span></span> <span class="axis360-available-holds" style="display: none"> <span class="label label-success"><span class="axis360-available-holds-placeholder"></span> {translate text="Available Now" isPublicFacing=true}</span></span>{/if}
											</a>
										</li>
									{/if}
								</ul>

								{if !$offline}
									{if $showCurbsidePickups}
										<div class="myAccountLink" title="Curbside Pickups">
											<a href="/MyAccount/CurbsidePickups">{translate text='Curbside Pickups' isPublicFacing=true}</a>
										</div>
									{/if}
									{if $showFines}
										<div class="myAccountLink" title="Fines">
											<a href="/MyAccount/Fines">{translate text='Fines' isPublicFacing=true}</a>
										</div>
									{/if}
								{/if}
							{/if}
							{if !$offline && $userHasCatalogConnection && $showUserCirculationModules}
								{if $materialRequestType == 1 && $enableAspenMaterialsRequest && $displayMaterialsRequest}
									<div class="myAccountLink materialsRequestLink" title="{translate text='Materials Requests' inAttribute=true isPublicFacing=true}">
										<a href="/MaterialsRequest/MyRequests">{translate text='Materials Requests' isPublicFacing=true} <span class="badge"><span class="materialsRequests-placeholder">??</span></span></a>
									</div>
								{elseif $materialRequestType == 2 && $userHasCatalogConnection && $displayMaterialsRequest}
									<div class="myAccountLink" title="{translate text='Materials Requests' inAttribute=true isPublicFacing=true}">
										<a href="/MaterialsRequest/IlsRequests">{translate text='Materials Requests' isPublicFacing=true} <span class="badge"><span class="materialsRequests-placeholder">??</span></span></a>
									</div>
								{/if}
							{/if}

							{if $userHasCatalogConnection && $showUserCirculationModules}
								<div class="myAccountLink libraryCardLink" title="{translate text='Your Library Card(s)' inAttribute=true isPublicFacing=true}">
									<a href="/MyAccount/LibraryCard">{if $showAlternateLibraryCard}{translate text='Your Library Card(s)' isPublicFacing=true}{else}{translate text='Your Library Card' isPublicFacing=true}{/if}</a>
								</div>
							{/if}

							{if !$offline}
								{if $showRatings || $enableSavedSearches || ($enableReadingHistory && $userHasCatalogConnection) | $showFavorites}
									<hr class="menu">
								{/if}
								{if $showRatings}
									<div class="myAccountLink"><a href="/MyAccount/SuggestedTitles">{translate text='Recommended For You' isPublicFacing=true}</span></a></div>
									<ul class="account-submenu">
									{if $user->disableRecommendations == 0}
										<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/MyRatings">{translate text='Titles You Rated' isPublicFacing=true} <span class="badge"><span class="ratings-placeholder">??</span></span></a></li>
									{/if}
									</ul>
								{/if}
								{if $showFavorites == 1}
									<div class="myAccountLink"><a href="/MyAccount/Lists">{translate text='Your Lists' isPublicFacing=true}</a></div>
								{/if}
								{if $enableSavedSearches}
									{* Only highlight saved searches as active if user is logged in: *}
									<div class="myAccountLink"><a href="/Search/History?require_login">{translate text='Your Searches' isPublicFacing=true}</a> <span class="label badge-updated newSavedSearchBadge" style="display: none"><span class="saved-searches-placeholder">??</span></span></div>
								{/if}
								{if $enableReadingHistory && $userHasCatalogConnection}
									<div class="myAccountLink">
										<a href="/MyAccount/ReadingHistory">
											{translate text="Reading History" isPublicFacing=true} {if !$offline}<span class="badge"><span class="readingHistory-placeholder">??</span></span>{/if}
										</a>
									</div>
								{/if}
							{/if}
						</div>
					</div>
				</div>

				{if $action=='MyPreferences' || $action=='ContactInformation' || $action=='MessagingSettings' || $action=='LinkedAccounts' || $action=='Security' || $action=='ResetPinPage' || $action=='OverDriveOptions' || $action=='HooplaOptions' || $action=='Axis360Options' || $action=='StaffSettings' || $action=='HoldNotificationPreferences'}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				{/if}
				{if $showAccountSettings}
				<div class="panel {if ($curSection || !$showMyAccount)}active{/if}">
					{* Clickable header for account settings section *}
					<a data-toggle="collapse" href="#mySettingsPanel" aria-label="{translate text="Account Settings Menu" inAttribute="true" isPublicFacing=true}">
						<div class="panel-heading">
							<div class="panel-title">
								{translate text="Account Settings" isPublicFacing=true}
							</div>
						</div>
					</a>
					<div id="mySettingsPanel" class="panel-collapse collapse {if ($curSection || !$showMyAccount)}in{/if}">
						<div class="panel-body">
							{if !$offline}
								{if $showUserPreferences}<div class="myAccountLink"><a href="/MyAccount/MyPreferences">{translate text='Your Preferences' isPublicFacing=true}</a></div>{/if}
								{if $showUserContactInformation}<div class="myAccountLink"><a href="/MyAccount/ContactInformation">{translate text='Contact Information' isPublicFacing=true}</a></div>{/if}
								{if $user->showHoldNotificationPreferences()}
									<div class="myAccountLink"><a href="/MyAccount/HoldNotificationPreferences">{translate text='Hold Notification Preferences' isPublicFacing=true}</a></div>
								{/if}
								{if $user->showMessagingSettings()}
									<div class="myAccountLink"><a href="/MyAccount/MessagingSettings">{translate text='Messaging Settings' isPublicFacing=true}</a></div>
								{/if}
								{if $allowAccountLinking}
									<div class="myAccountLink"><a href="/MyAccount/LinkedAccounts">{translate text='Linked Accounts' isPublicFacing=true}</a></div>
								{/if}
								{if $twoFactorEnabled}
									<div class="myAccountLink"><a href="/MyAccount/Security">{translate text='Security Settings' isPublicFacing=true}</a></div>
								{/if}
								{if $showResetUsernameLink}
									<div class="myAccountLink" ><a href="/MyAccount/ResetUsername">{translate text='Reset Username' isPublicFacing=true}</a></div>
								{/if}
								{if $allowPinReset}
									<div class="myAccountLink" ><a href="/MyAccount/ResetPinPage">{translate text='Reset PIN/Password' isPublicFacing=true}</a></div>
								{/if}
								{if $user->isValidForEContentSource('overdrive') && $showUserCirculationModules}
									<div class="myAccountLink"><a href="/MyAccount/OverDriveOptions">{translate text='OverDrive Options' isPublicFacing=true}</a></div>
								{/if}
								{if $user->isValidForEContentSource('hoopla') && $showUserCirculationModules}
									<div class="myAccountLink"><a href="/MyAccount/HooplaOptions">{translate text='Hoopla Options' isPublicFacing=true}</a></div>
								{/if}
								{if $user->isValidForEContentSource('axis360') && $showUserCirculationModules}
									<div class="myAccountLink"><a href="/MyAccount/Axis360Options">{translate text='Axis 360 Options' isPublicFacing=true}</a></div>
								{/if}
								{if $userIsStaff}
									<div class="myAccountLink"><a href="/MyAccount/StaffSettings">{translate text='Staff Settings' isPublicFacing=true}</a></div>
								{/if}
							{/if}
						</div>
					</div>
				</div>
			</div>
			{/if}

			{if $allowMasqueradeMode && !$masqueradeMode}
				{if $canMasquerade}
					<div>
						<div class="myAccountLink btn btn-default btn-sm btn-block"><a onclick="AspenDiscovery.Account.getMasqueradeForm();" href="#">{translate text="Masquerade" isPublicFacing=true}</a></div>
					</div>
				{/if}
			{/if}
		{if !$showMyAccount}</div>{/if}
		</div>
	{/if}
	<script type="text/javascript">
		{literal}
		$(document).ready(function() {
			{/literal}
			{if $userHasCatalogConnection}
				AspenDiscovery.Account.loadMenuData();
			{/if}
			{literal}
			AspenDiscovery.Account.loadListData();
			AspenDiscovery.Account.loadRatingsData();
		});
		{/literal}
	</script>
{/strip}
