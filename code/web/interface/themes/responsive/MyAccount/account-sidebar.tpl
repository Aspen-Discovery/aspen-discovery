{strip}
	{if $loggedIn}
		{* Setup the accoridon *}
		<!--suppress HtmlUnknownTarget -->
		<div id="home-account-links" class="sidebar-links row">
			<div class="panel-group accordion" id="account-link-accordion">
				{* My Account *}
				<a id="account-menu"></a>
				{if $module == 'MyAccount' || ($module == 'Search' && $action == 'Home') || ($module == 'MaterialsRequest' && $action == 'MyRequests')}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}

				<div class="panel{if $curSection} active{/if}">
					{* With SidebarMenu on, we should always keep the MyAccount Panel open. *}

					{* Clickable header for my account section *}
					<a data-toggle="collapse" href="#myAccountPanel" aria-label="{translate text="My Account Menu"}">
						<div class="panel-heading">
							<div class="panel-title">
								{*MY ACCOUNT*}
								{translate text="My Account"}
							</div>
						</div>
					</a>
					{*  This content is duplicated in MyAccount/mobilePageHeader.tpl; Update any changes there as well *}
					<div id="myAccountPanel" class="panel-collapse collapse{if  $curSection} in{/if}">
						<div class="panel-body">
							<span class="expirationFinesNotice-placeholder"></span>
							{if $userHasCatalogConnection}
								<div class="myAccountLink">
									<a href="/MyAccount/CheckedOut" id="checkedOut">
										{translate text="Checked Out Titles"}
									</a>
								</div>
								<ul class="account-submenu">
									<li class="myAccountLink">
										&nbsp;&nbsp;&raquo;&nbsp;
										<a href="/MyAccount/CheckedOut?tab=ils" id="checkedOutIls" title="View checkouts of physical materials">
											{translate text="Physical Materials"} {if !$offline}<span class="badge"><span class="ils-checkouts-placeholder">??</span></span> <span class="ils-overdue" style="display: none"> <span class="label label-danger"><span class="ils-overdue-placeholder"></span> {translate text="Overdue"}</span></span>{/if}
										</a>
									</li>
									{if $user->isValidForEContentSource('overdrive')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/CheckedOut?tab=overdrive" id="checkedOutOverDrive" title="View checkouts from OverDrive">
												{translate text="OverDrive"} {if !$offline}<span class="badge"><span class="overdrive-checkouts-placeholder">??</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->isValidForEContentSource('hoopla')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/CheckedOut?tab=hoopla" id="checkedOutHoopla" title="View checkouts from Hoopla">
												{translate text="Hoopla"} {if !$offline}<span class="badge"><span class="hoopla-checkouts-placeholder">??</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->isValidForEContentSource('rbdigital')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/CheckedOut?tab=rbdigital" id="checkedOutRBdigital" title="View checkouts from RBdigital">
												{translate text="RBdigital"} {if !$offline}<span class="badge"><span class="rbdigital-checkouts-placeholder">??</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->isValidForEContentSource('cloud_library')}
										<li class="myAccountLink">
										&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/CheckedOut?tab=cloud_library" id="checkedOutCloudLibrary" title="View checkouts from CloudLibrary">
												{translate text="Cloud Library"} {if !$offline}<span class="badge"><span class="cloud_library-checkouts-placeholder">??</span></span>{/if}
											</a>
										</li>
									{/if}
								</ul>

								<div class="myAccountLink">
									<a href="/MyAccount/Holds" id="holds">
										{translate text="Titles On Hold"}
									</a>
								</div>
								<ul class="account-submenu">
									<li class="myAccountLink">
										&nbsp;&nbsp;&raquo;&nbsp;
										<a href="/MyAccount/Holds?tab=ils" id="holdsIls" title="View holds on physical materials">
											{translate text="Physical Materials"} {if !$offline}<span class="badge"><span class="ils-holds-placeholder">??</span></span> <span class="ils-available-holds" style="display: none"> <span class="label label-success"><span class="ils-available-holds-placeholder"></span> {translate text="Ready for Pickup"}</span></span>{/if}
										</a>
									</li>
									{if $user->isValidForEContentSource('overdrive')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/Holds?tab=overdrive" id="holdsOverDrive" title="View holds from OverDrive">
												{translate text="OverDrive"} {if !$offline}<span class="badge"><span class="overdrive-holds-placeholder">??</span></span> <span class="overdrive-available-holds" style="display: none"> <span class="label label-success"><span class="overdrive-available-holds-placeholder"></span> {translate text="Available Now"}</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->isValidForEContentSource('rbdigital')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/Holds?tab=rbdigital" id="holdsRBdigital" title="View holds from RBdigital">
												{translate text="RBdigital"} {if !$offline}<span class="badge"><span class="rbdigital-holds-placeholder">??</span></span>{/if}
											</a>
										</li>
									{/if}
									{if $user->isValidForEContentSource('cloud_library')}
										<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
											<a href="/MyAccount/Holds?tab=cloud_library" id="holdsCloudLibrary" title="View holds from CloudLibrary">
												{translate text="Cloud Library"} {if !$offline}<span class="badge"><span class="cloud_library-holds-placeholder">??</span></span> <span class="cloud_library-available-holds" style="display: none"> <span class="label label-success"><span class="cloud_library-available-holds-placeholder"></span> {translate text="Available Now"}</span></span>{/if}
											</a>
										</li>
									{/if}
								</ul>

								{if $enableMaterialsBooking}
									<div class="myAccountLink">
										<a href="/MyAccount/Bookings" id="bookings">
											{translate text="Scheduled Items"} {if !$offline}<span class="badge"><span class="bookings-placeholder">??</span></span>{/if}
										</a>
									</div>
								{/if}
								<div class="myAccountLink">
									<a href="/MyAccount/ReadingHistory">
										{translate text="Reading History"} {if !$offline}<span class="badge"><span class="readingHistory-placeholder">??</span></span>{/if}
									</a>
								</div>
								{if $showFines}
									<hr class="menu">
									<div class="myAccountLink" title="Fines and account messages">
										<a href="/MyAccount/Fines">{translate text='Fines and Messages'}</a>
									</div>
								{/if}
							{/if}
							{if $materialRequestType == 1 && $enableAspenMaterialsRequest}
								<div class="myAccountLink" title="{translate text='Materials Requests' inAttribute=true}">
									<a href="/MaterialsRequest/MyRequests">{translate text='Materials Requests'} <span class="badge"><span class="materialsRequests-placeholder">??</span></span></a>
								</div>
							{elseif $materialRequestType == 2 && $userHasCatalogConnection}
								<div class="myAccountLink" title="{translate text='Materials Requests' inAttribute=true}">
									<a href="/MaterialsRequest/IlsRequests">{translate text='Materials Requests'} <span class="badge"><span class="materialsRequests-placeholder">??</span></span></a>
								</div>
							{/if}
							{if $showRatings}
								<hr class="menu">
								<div class="myAccountLink"><a href="/MyAccount/MyRatings">{translate text='Titles You Rated'} <span class="badge"><span class="ratings-placeholder">??</span></span></a></div>
								<ul class="account-submenu">
								{if $user->disableRecommendations == 0}
									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/SuggestedTitles">{translate text='Recommended For You'}</span></a></li>
								{/if}
								</ul>
							{/if}
							<hr class="menu">
							<div class="myAccountLink">{translate text='Account Settings'}</div>
							<ul class="account-submenu">
								{if $userHasCatalogConnection}
									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/LibraryCard">{if $showAlternateLibraryCard}{translate text='My Library Card(s)'}{else}{translate text='My Library Card'}{/if}</a></li>
								{/if}
								<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/MyPreferences">{translate text='My Preferences'}</a></li>
								<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/ContactInformation">{translate text='Contact Information'}</a></li>
								{if $user->showMessagingSettings()}
									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/MessagingSettings">{translate text='Messaging Settings'}</a></li>
								{/if}
								{if $allowAccountLinking}
									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/LinkedAccounts">{translate text='Linked Accounts'}</a></li>
								{/if}
								{if $allowPinReset && !$offline}
									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/ResetPinPage">{translate text='Reset PIN/Password'}</a></li>
								{/if}
								{if $user->isValidForEContentSource('overdrive')}
									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/OverDriveOptions">{translate text='OverDrive Options'}</a></li>
								{/if}
{*								{if $user->isValidForEContentSource('rbdigital')}*}
{*									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/RBdigitalOptions">{translate text='RBdigital Options'}</a></li>*}
{*								{/if}*}
								{if $user->isValidForEContentSource('hoopla')}
									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/HooplaOptions">{translate text='Hoopla Options'}</a></li>
								{/if}
								{if $userIsStaff}
									<li class="myAccountLink">&nbsp;&nbsp;&raquo;&nbsp;<a href="/MyAccount/StaffSettings">{translate text='Staff Settings'}</a></li>
								{/if}
							</ul>
							{* Only highlight saved searches as active if user is logged in: *}
							<div class="myAccountLink{if $user && $pageTemplate=="history.tpl"} active{/if}"><a href="/Search/History?require_login">{translate text='history_saved_searches'}</a></div>
							{if $allowMasqueradeMode && !$masqueradeMode}
								{if $canMasquerade}
									<hr class="menu">
									<div class="myAccountLink"><a onclick="AspenDiscovery.Account.getMasqueradeForm();" href="#">{translate text="Masquerade"}</a></div>
								{/if}
							{/if}
						</div>
					</div>
				</div>

				{* My Lists*}
				{if $action == 'MyList'}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a data-toggle="collapse" href="#myListsPanel">
						<div class="panel-heading">
							<div class="panel-title">
								{translate text='My Lists'}
							</div>
						</div>
					</a>
					<div id="myListsPanel" class="panel-collapse collapse{if $action == 'MyRatings' || $action == 'Suggested Titles' || $action == 'MyList'} in{/if}">
						<div class="panel-body">
							<div id="lists-placeholder"><img src="/images/loading.gif" alt="loading"></div>

							<div class="myAccountLink">
								<a href="#" onclick="return AspenDiscovery.Account.showCreateListForm();" class="btn btn-sm btn-primary">{translate text='Create a New List'}</a>
							</div>
							{if $showConvertListsFromClassic}
								<br>
								<div class="myAccountLink">
									<a href="/MyAccount/ImportListsFromClassic" class="btn btn-sm btn-default">{translate text="Import From Old Catalog"}</a>
								</div>
							{/if}
						</div>
					</div>
				</div>
			</div>
		</div>
	{/if}
	{if $userHasCatalogConnection}
		<script type="text/javascript">
			AspenDiscovery.Account.loadMenuData();
		</script>
	{/if}
	<script type="text/javascript">
		AspenDiscovery.Account.loadListData();
		AspenDiscovery.Account.loadRatingsData();
	</script>
{/strip}
