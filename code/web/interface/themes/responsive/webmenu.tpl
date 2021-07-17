{strip}
	<div id="webMenuNavBar" class="navbar navbar-default navbar-static-top row">
		<div class="container">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#webmenu-navbar-collapse-1" aria-expanded="false">
					<span class="sr-only">Menu</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
			</div>
			<div class="collapse navbar-collapse" id="webmenu-navbar-collapse-1">
				<ul class="nav navbar-nav">
					{foreach from=$webMenu item=menu}
						{assign var="childItems" value=$menu->getChildMenuItems()}
						{if count($childItems) == 0}
							<li>{if $menu->url}<a href="{$menu->url}">{/if}{$menu->label}{if $menu->url}</a>{/if}</li>
						{else}
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{$menu->label} <span class="caret"></span></a>
								<ul class="dropdown-menu">
									{foreach from=$childItems item=childItem}
										<li>{if $childItem->url}<a href="{$childItem->url}">{/if}{$childItem->label}{if $childItem->url}</a>{/if}</li>
									{/foreach}
								</ul>
							</li>
						{/if}
					{/foreach}
					{* Add an account link *}
					{if $loggedIn}
						<li class="dropdown">
							<a href="/MyAccount/Home" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{translate text="My Account"} <span class="caret"></span></a>
							<div class="dropdown-menu panel-body">
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
											<a href="/MyAccount/CheckedOut?tab=ils" id="checkedOutIls">
												{translate text="Physical Materials"} {if !$offline}<span class="badge"><span class="ils-checkouts-placeholder">??</span></span> <span class="ils-overdue" style="display: none"> <span class="label label-danger"><span class="ils-overdue-placeholder"></span> {translate text="Overdue"}</span></span>{/if}
											</a>
										</li>
										{if $user->isValidForEContentSource('overdrive')}
											<li class="myAccountLink">
												&nbsp;&nbsp;&raquo;&nbsp;
												<a href="/MyAccount/CheckedOut?tab=overdrive" id="checkedOutOverDrive">
													{translate text="OverDrive"} {if !$offline}<span class="badge"><span class="overdrive-checkouts-placeholder">??</span></span>{/if}
												</a>
											</li>
										{/if}
										{if $user->isValidForEContentSource('hoopla')}
											<li class="myAccountLink">
												&nbsp;&nbsp;&raquo;&nbsp;
												<a href="/MyAccount/CheckedOut?tab=hoopla" id="checkedOutHoopla">
													{translate text="Hoopla"} {if !$offline}<span class="badge"><span class="hoopla-checkouts-placeholder">??</span></span>{/if}
												</a>
											</li>
										{/if}
										{if $user->isValidForEContentSource('cloud_library')}
											<li class="myAccountLink">
											&nbsp;&nbsp;&raquo;&nbsp;
												<a href="/MyAccount/CheckedOut?tab=cloud_library" id="checkedOutCloudLibrary">
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
											<a href="/MyAccount/Holds?tab=ils" id="holdsIls">
												{translate text="Physical Materials"} {if !$offline}<span class="badge"><span class="ils-holds-placeholder">??</span></span> <span class="ils-available-holds" style="display: none"> <span class="label label-success"><span class="ils-available-holds-placeholder"></span> {translate text="Ready for Pickup"}</span></span>{/if}
											</a>
										</li>
										{if $user->isValidForEContentSource('overdrive')}
											<li class="myAccountLink">
												&nbsp;&nbsp;&raquo;&nbsp;
												<a href="/MyAccount/Holds?tab=overdrive" id="holdsOverDrive">
													{translate text="OverDrive"} {if !$offline}<span class="badge"><span class="overdrive-holds-placeholder">??</span></span> <span class="overdrive-available-holds" style="display: none"> <span class="label label-success"><span class="overdrive-available-holds-placeholder"></span> {translate text="Available Now"}</span></span>{/if}
												</a>
											</li>
										{/if}
										{if $user->isValidForEContentSource('cloud_library')}
											<li class="myAccountLink">
												&nbsp;&nbsp;&raquo;&nbsp;
												<a href="/MyAccount/Holds?tab=cloud_library" id="holdsCloudLibrary">
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
							</div>
						</li>
					{else}
						{if $showLoginButton}
						<li><a href="/MyAccount/Home" onclick="{if !empty($isLoginPage)}$('#username').focus();return false{else}return AspenDiscovery.Account.followLinkIfLoggedIn(this);{/if}">{translate text="Sign In"}</a></li>
						{/if}
					{/if}
				</ul>
			</div>
		</div>
	</div>
{/strip}