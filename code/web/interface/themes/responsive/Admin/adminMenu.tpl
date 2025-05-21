{strip}
	{if !empty($loggedIn) && !empty($adminActions)}
		<div id="account-menu-label" class="sidebar-label row">
			<div class="col-xs-12">
				{translate text='Administration Options' isAdminFacing=true}
				<div id="sidebar-collapse"><button class="btn btn-default btn-sm" aria-label="{translate text="Collapse Sidebar" inAttribute=true isAdminFacing=true}"  title="{translate text="Collapse Sidebar" inAttribute=true isAdminFacing=true}" onclick="$('#side-bar').hide();$('#main-content-with-sidebar').addClass('col-sm-12').addClass('col-md-12').addClass('col-lg-12').removeClass('col-sm-8').removeClass('col-md-9').removeClass('col-lg-10').removeClass('col-lg-9');$('#sidebar-expand').show();"><i class="fas fa-angle-double-left"></i></button></div>
			</div>
			<div class="col-xs-12">
				<a class='searchSettings searchSettingsColor' onClick="return AspenDiscovery.Admin.showSearch();" id='showSearchButton'><i class="fas fa-search" role="presentation"></i> {translate text="Search" isAdminFacing=true}</a>
			</div>
			<div class="col-xs-12">
				<form id='adminSearchBox' role="form" class="form-inline" style='display:none'>
					<div class="form-group">
						<label class='searchSettings' for="searchAdminBar">{translate text="Search for a Setting" isAdminFacing=true}</label>
						<div class="input-group input-group-sm">
							<input type="text" name="searchAdminBar" id="searchAdminBar"
							       onkeyup="return AspenDiscovery.Admin.searchAdminBar();" class="form-control"/>
							<span class="input-group-btn"><button class="btn btn-default" type="button"
							                                      onclick="$('#searchAdminBar').val('');return AspenDiscovery.Admin.searchAdminBar();"
							                                      title="{translate text="Clear" inAttribute=true isAdminFacing=true}"><i
										class="fas fa-times-circle" role="presentation"></i></button></span>
							<script type="text/javascript">
								{literal}
								$(document).ready(function () {
									$("#searchAdminBar").keydown("keydown", function (e) {
										if (e.which === 13) {
											e.preventDefault();
										}
									});

								});
								{/literal}
							</script>
						</div>
					</div>
				</form>
			</div>
		</div>

		<div id="home-account-links" class="sidebar-links row">
			<div class="panel-group accordion" id="account-link-accordion">
				{foreach from=$adminActions item=adminSection key=adminSectionKey}
					{if !empty($adminSection->actions)}
						<div class="admin-menu-section panel{if $adminSectionKey==$activeAdminSection} active{/if}">
							<a href="#{$adminSectionKey}Group" data-toggle="collapse" data-parent="#adminMenuAccordion"
							   aria-label="{translate text="%1% Menu" 1=$adminSection->label inAttribute=true isAdminFacing=true}">
								<div class="panel-heading adminTitleItem">
									<div class="panel-title">
										{translate text=$adminSection->label isAdminFacing=true}
									</div>
								</div>
							</a>
							<div id="{$adminSectionKey}Group"
								 class="panel-collapse collapse admin-search-collapse {if $adminSectionKey==$activeAdminSection}in{/if}">
								<div class="panel-body">
									{foreach from=$adminSection->actions item=adminAction key=adminActionKey}
										<div class="adminMenuLink "><a class='adminLink' href="{$adminAction->link}">{translate text=$adminAction->label isAdminFacing=true}</a>
										</div>
										{if !empty($adminAction->subActions)}
											{foreach from=$adminAction->subActions item=adminSubAction}
												<div class="adminMenuLink ">&nbsp;&raquo;&nbsp;<a href="{$adminSubAction->link}">{translate text=$adminSubAction->label isAdminFacing=true}</a>
												</div>
											{/foreach}
										{/if}
									{/foreach}
								</div>
							</div>
						</div>
					{/if}
				{/foreach}
			</div>
		</div>
	{/if}

{/strip}
