{strip}
	<div id="main-content">
		{if !empty($loggedIn)}
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

			<h1>{translate text='Preferences' isPublicFacing=true}</h1>
			{if !empty($profileUpdateErrors)}
				{foreach from=$profileUpdateErrors item=errorMsg}
					<div class="alert alert-danger">{$errorMsg}</div>
				{/foreach}
			{/if}
			{if !empty($profileUpdateMessage)}
				{foreach from=$profileUpdateMessage item=msg}
					<div class="alert alert-success">{$msg}</div>
				{/foreach}
			{/if}
			{if !empty($offline)}
				<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
			{else}
				{* Empty action attribute uses the page loaded. this keeps the selected user patronId in the parameters passed back to server *}
				<form action="" method="post" role="form">
					<input type="hidden" name="updateScope" value="userPreference">
					<input type="hidden" name="patronId" value={$profile->id|escape}>

					<div id="preferences-accordion" class="panel-group">
						<div class="panel" id="accountPreferencesPanel">
							<a data-toggle="collapse" href="#accountPreferencesPanelBody" class="active">
								<div class="panel-heading">
									<div class="panel-title">
										<h2>{translate text="Account" isPublicFacing=true}</h2>
									</div>
								</div>
							</a>
							<div id="accountPreferencesPanelBody" class="panel-collapse in">
								<div class="panel-body">
									{if !empty($showAutoRenewSwitch)}
										<div class="form-group propertyRow">
											<label for="allowAutoRenewal" class="control-label">{translate text='Allow Auto Renewal' isPublicFacing=true}</label>&nbsp;
											{if $edit == true}
												<input type="checkbox" class="form-control" name="allowAutoRenewal" id="allowAutoRenewal" {if $autoRenewalEnabled==1}checked='checked'{/if} data-switch="">
											{else}
												{if $profile->autoRenewalEnabled==0}{translate text="No" isPublicFacing=true}{else}{translate text="Yes" isPublicFacing=true}{/if}
											{/if}
										</div>
									{/if}
									{if $enableCostSavingsForLibrary}
										<div class="form-group propertyRow">
											<label for="enableCostSavings" class="control-label">{translate text='Display Library Savings' isPublicFacing=true}</label>&nbsp;
											{if $edit == true}
												<input type="checkbox" class="form-control" name="enableCostSavings" id="enableCostSavings" {if $profile->enableCostSavings==1}checked='checked'{/if} data-switch="">
											{else}
												&nbsp;{if $profile->enableCostSavings==0} {translate text='No' isPublicFacing=true}{else} {translate text='Yes' isPublicFacing=true}{/if}
											{/if}
										</div>
									{/if}
									{if !empty($allowHomeLibraryUpdates)  && !empty($isAssociatedWithILS)}
										{* Allow editing home library *}
										<div class="form-group  propertyRow">
											<label for="homeLocation" class="control-label">{translate text='Home Library' isPublicFacing=true}</label>
											{if $edit == true && $canUpdateContactInfo == true}
												<select name="homeLocation" id="homeLocation" class="form-control">
													{if count($homeLibraryLocations) > 0}
														{foreach from=$homeLibraryLocations item=location}
															{if is_object($location)}
																<option value="{$location->code}" {if $location->locationId == $profile->homeLocationId}selected="selected"{/if}>{$location->displayName|escape}</option>
															{else}
																<option value="">{$location|escape}</option>
															{/if}
														{/foreach}
													{else}
														<option>placeholder</option>
													{/if}
												</select>
											{else}
												&nbsp;{$profile->getHomeLocationName()|escape}
											{/if}
										</div>
									{else}
										<div class="form-group propertyRow">
											<strong>{translate text='Home Library' isPublicFacing=true}</strong> {$profile->getHomeLocationName()|escape}
										</div>
									{/if}

									{if !empty($showUsernameField)}
										<div class="form-group propertyRow">
											<label for="username">{translate text="Username" isPublicFacing=true}</label>
											<input type="text" name="username" id="username" value="{$editableUsername|escape}" size="25" minlength="6" maxlength="25" class="form-control">
											<a id="usernameHelpButton" href="#" role="button" aria-controls="usernameHelp" aria-expanded="false"><i class="fa fa-question-circle" role="presentation"></i> {translate text="What is this?" isPublicFacing=true}</a>
											<div id="usernameHelp" style="display:none">
												<p>{translate text="A username is an optional feature. If you set one, your username will be your alias on hold slips and can also be used to log into your account in place of your card number.  A username can be set, reset or removed from the “My Preferences” section of your online account. Usernames must be between 6 and 25 characters (letters and number only, no special characters)." isPublicFacing=true}</p>
											</div>
										</div>
									{/if}
								</div>
							</div>
						</div>

						{if !empty($showEdsPreferences) || !empty($validEdsSorts) || !empty($validEbscohostSorts) || !empty($validSummonSorts)}
							<div class="panel" id="articlesAndDatabasesPreferencesPanel">
								<a data-toggle="collapse" href="#articlesAndDatabasesPreferencesPanelBody">
									<div class="panel-heading">
										<div class="panel-title">
											<h2>{translate text="Articles and Databases" isPublicFacing=true}</h2>
										</div>
									</div>
								</a>
								<div id="articlesAndDatabasesPreferencesPanelBody" class="panel-collapse collapse in">
									<div class="panel-body">
										{if !empty($validEdsSorts)}
											<div class="form-group propertyRow">
												<label for="defaultEdsSort" class="control-label">{translate text='Default Sort For New Searches' isPublicFacing=true}</label>&nbsp;
												<select name="defaultEdsSort" id="defaultEdsSort" class="form-control">
													{foreach from=$validEdsSorts key="sortValue" item="sortName"}
														<option value="{$sortValue}" {if $defaultEbscoEDSSort == $sortValue}selected{/if}>{translate text=$sortName isPublicFacing=true inAttribuge=true}</option>
													{/foreach}
												</select>
											</div>
										{/if}
										{if !empty($validEbscohostSorts)}
											<div class="form-group propertyRow">
												<label for="defaultEbscohostSort" class="control-label">{translate text='Default Sort For New Searches' isPublicFacing=true}</label>&nbsp;
												<select name="defaultEbscohostSort" id="defaultEbscohostSort" class="form-control">
													{foreach from=$validEbscohostSorts key="sortValue" item="sortName"}
														<option value="{$sortValue}" {if $defaultEbscoHostSort == $sortValue}selected{/if}>{translate text=$sortName isPublicFacing=true inAttribuge=true}</option>
													{/foreach}
												</select>
											</div>
										{/if}
										{if !empty($validSummonSorts)}
											<div class="form-group propertyRow">
												<label for="defaultSummonSort" class="control-label">{translate text='Default Sort For New Searches' isPublicFacing=true}</label>&nbsp;
												<select name="defaultSummonSort" id="defaultSummonSort" class="form-control">
													{foreach from=$validSummonSorts key="sortValue" item="sortName"}
														<option value="{$sortValue}" {if $defaultSummonSort == $sortValue}selected{/if}>{translate text=$sortName isPublicFacing=true inAttribuge=true}</option>
													{/foreach}
												</select>
											</div>
										{/if}
										{if !empty($showEdsPreferences)}
											<div class="form-group propertyRow">
												<label for="hideResearchStarters" class="control-label">{translate text='Hide Research Starters' isPublicFacing=true}</label>&nbsp;
												{if $edit == true}
													<input type="checkbox" class="form-control" name="hideResearchStarters" id="hideResearchStarters" {if $profile->hideResearchStarters==1}checked='checked'{/if} data-switch="">
												{else}
													&nbsp;{if $profile->hideResearchStarters==0} {translate text='No' isPublicFacing=true}{else} {translate text='Yes' isPublicFacing=true}{/if}
												{/if}
											</div>
										{/if}
									</div>
								</div>
							</div>
						{/if}

						{if array_key_exists('Community Engagement', $enabledModules)}
							<div class="panel" id="communityEngagementPreferencesPanel">
								<a data-toggle="collapse" href="#communityEngagementPreferencesPanelBody" class="active">
									<div class="panel-heading">
										<div class="panel-title">
											<h2>{translate text="Community Engagement" isPublicFacing=true}</h2>
										</div>
									</div>
								</a>
								<div id="communityEngagementPreferencesPanelBody" class="panel-collapse collapse in">
									<div class="panel-body">
										<div class="form-group propertyRow">
											<label for="campaignNotificationsByEmail" class="control-label">{translate text="Get Campaign Notifications By Email" isPublicFacing=true}</label>&nbsp;
											<input type="checkbox" class="form-control" name="campaignNotificationsByEmail" id="campaignNotificationsByEmail" {if $profile->campaignNotificationsByEmail==1}checked='checked'{/if} data-switch="">
										</div>
										{if $campaignLeaderboardDisplay == 'displayUser'}
											<div class="form-group propertyRow">
												<label for="optInToAllCampaignLeaderboards" class="control-label">{translate text="Opt in to All Leaderboards" isPublicFacing=true}</label>&nbsp;
												<input type="checkbox" class="form-control" name="optInToAllCampaignLeaderboards" id="optInToAllCampaignLeaderboards" {if $profile->optInToAllCampaignLeaderboards==1}checked='checked'{/if} data-switch="">
											</div>
										{/if}
									</div>
								</div>
							</div>
						{/if}

						{if count($allActiveThemes) > 1 || count($validLanguages) > 1}
							<div class="panel" id="displayPreferencesPanel">
								<a data-toggle="collapse" href="#displayPreferencesPanelBody" class="active">
									<div class="panel-heading">
										<div class="panel-title">
											<h2>
												{if count($validLanguages) > 1 && count($allActiveThemes) > 1}
													{translate text="Languages & Display" isPublicFacing=true}
												{elseif count($validLanguages) > 1}
													{translate text="Languages" isPublicFacing=true}
												{else}
													{translate text="Display" isPublicFacing=true}
												{/if}
											</h2>
										</div>
									</div>
								</a>
								<div id="displayPreferencesPanelBody" class="panel-collapse collapse in">
									<div class="panel-body">
										{if count($allActiveThemes) > 1}
											<div class="form-group propertyRow">
												<label for="preferredTheme" class="control-label">{translate text='Display Mode' isPublicFacing=true}</label>
												<select id="preferredTheme" name="preferredTheme" class="form-control">
													{foreach from=$allActiveThemes key=themeId item=themeName}
														<option value="{$themeId}"{if $profile->preferredTheme==$themeId} selected="selected"{/if}>
															{$themeName}
														</option>
													{/foreach}
												</select>
											</div>
										{/if}
										{if count($validLanguages) > 1}
											<div class="form-group propertyRow">
												<label for="profileLanguage" class="control-label">{translate text='Language to display catalog in' isPublicFacing=true}</label>
												<select id="profileLanguage" name="profileLanguage" class="form-control">
													{foreach from=$validLanguages key=languageCode item=language}
														<option value="{$languageCode}"{if $profile->interfaceLanguage==$languageCode} selected="selected"{/if}>
															{$language->displayName|escape}
														</option>
													{/foreach}
												</select>
											</div>
										{/if}
									</div>
								</div>
							</div>
						{/if}

						{if !empty($isAssociatedWithILS) && ((!empty($allowRememberPickupLocation) && count($pickupLocations) > 1) || !empty($showAlternateLibraryOptions) || !empty($allowRememberPickupLocation))}
							<div class="panel" id="holdPreferencesPanel">
								<a data-toggle="collapse" href="#holdPreferencesPanelBody" class="active">
									<div class="panel-heading">
										<div class="panel-title">
											<h2>{translate text="Holds" isPublicFacing=true}</h2>
										</div>
									</div>
								</a>
								<div id="holdPreferencesPanelBody" class="panel-collapse in">
									<div class="panel-body">
										{if !empty($allowRememberPickupLocation) && count($pickupLocations) > 1 && !empty($isAssociatedWithILS)}
											{* Allow editing the pickup location *}
											<div class="form-group propertyRow">
												<label for="pickupLocation" class="control-label">{translate text='Preferred Pickup Branch' isPublicFacing=true}</label>
												{if $edit == true && !empty($allowPickupLocationUpdates)}
													<select name="pickupLocation" id="pickupLocation" class="form-control" onchange="AspenDiscovery.Account.generateSublocationSelect();">
														{if count($pickupLocations) > 0}
															{foreach from=$pickupLocations item=location}
																{if is_object($location)}
																	<option value="{$location->locationId}" {if $location->locationId == $profile->pickupLocationId}selected="selected"{/if}>{$location->displayName|escape}</option>
																{else}
																	<option value="0">{$location}</option>
																{/if}
															{/foreach}
														{else}
															<option>placeholder</option>
														{/if}
													</select>
												{else}
													&nbsp;{$profile->getPickupLocationName()|escape}
												{/if}
											</div>
											<div id="pickupSublocationOptions" class="form-group propertyRow">
												{assign var=activePickupLocationId value=$profile->pickupLocationId}
												{if $edit == true && !empty($allowPickupLocationUpdates)}
													{if $activePickupLocationId > 0 && !empty($pickupSublocations.$activePickupLocationId) && count($pickupSublocations.$activePickupLocationId) > 1}
														{if $profile->pickupLocationId}
															<div id="sublocationSelectPlaceHolder">
																<label class="control-label" for="pickupSublocation">{translate text='Preferred Pickup Area' isPublicFacing=true}</label>
																<div class="controls">
																	<select name="pickupSublocation" id="pickupSublocation" class="form-control">
																		<option value="0default">{translate text='Please Select an Area' isPublicFacing=true}</option>
																		{foreach from=$pickupSublocations.$activePickupLocationId item=sublocation}
																			<option value="{$sublocation->id}" {if $sublocation->id == $profile->pickupSublocationId}selected="selected"{/if}>{$sublocation->name}</option>
																		{/foreach}
																	</select>
																</div>
															</div>
														{else}
															<div id="sublocationSelectPlaceHolder"></div>
														{/if}
													{else}
														<div id="sublocationSelectPlaceHolder"></div>
													{/if}
												{else}
													{$profile->getPickupSublocationName()|escape}
												{/if}
											</div>
										{/if}

										{if !empty($showAlternateLibraryOptions)  && !empty($isAssociatedWithILS)}
											{if count($locationList) > 2} {* First option is none *}
												<div class="form-group propertyRow">
													<label for="myLocation1" class="control-label">{translate text='Alternate Pickup Location 1' isPublicFacing=true}</label>
													{if $edit == true}
														{html_options name="myLocation1" id="myLocation1" class="form-control" options=$locationList selected=$profile->myLocation1Id}
													{else}
														&nbsp;{$profile->_myLocation1|escape}
													{/if}
												</div>
											{/if}
											{if count($locationList) > 3  && !empty($isAssociatedWithILS)} {* First option is none *}
												<div class="form-group propertyRow">
													<label for="myLocation2" class="control-label">{translate text='Alternate Pickup Location 2' isPublicFacing=true}</label>
													&nbsp;{if $edit == true}{html_options name="myLocation2" id="myLocation2" class="form-control" options=$locationList selected=$profile->myLocation2Id}{else}{$profile->_myLocation2|escape}{/if}
												</div>
											{/if}
										{/if}

										{if !empty($allowRememberPickupLocation)  && !empty($isAssociatedWithILS)}
											<div class="form-group propertyRow">
												<label for="rememberHoldPickupLocation" class="control-label">{translate text='Bypass pickup location prompt when placing holds' isPublicFacing=true}</label>&nbsp;
												{if $edit == true}
													<input type="checkbox" class="form-control" name="rememberHoldPickupLocation" id="rememberHoldPickupLocation" {if $profile->rememberHoldPickupLocation==1}checked='checked'{/if} data-switch="">
												{else}
													{if $profile->rememberHoldPickupLocation==0}{translate text="No" isPublicFacing=true}{else}{translate text="Yes" isPublicFacing=true}{/if}
												{/if}
											</div>
										{/if}
									</div>
								</div>
							</div>
						{/if}

						{if !empty($showRatings) && $showComments}
							<div class="panel" id="ratingsPreferencesPanel">
								<a data-toggle="collapse" href="#ratingsPreferencesPanelBody" class="active">
									<div class="panel-heading">
										<div class="panel-title">
											<h2>{translate text="Ratings" isPublicFacing=true}</h2>
										</div>
									</div>
								</a>
								<div id="ratingsPreferencesPanelBody" class="panel-collapse collapse in">
									<div class="panel-body">
										{if !empty($showRatings) && $showComments}
											<div class="form-group propertyRow">
												<label for="noPromptForUserReviews" class="control-label">{translate text='Do not prompt me for reviews after rating titles' isPublicFacing=true}</label>&nbsp;
												{if $edit == true}
													<input type="checkbox" class="form-control" name="noPromptForUserReviews" id="noPromptForUserReviews" {if $profile->noPromptForUserReviews==1}checked='checked'{/if} data-switch="">
												{else}
													{if $profile->noPromptForUserReviews==0} {translate text='No' isPublicFacing=true}{else} {translate text='Yes' isPublicFacing=true}{/if}
												{/if}
												<p class="help-block alert alert-info">
													<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span> {translate text="When you rate an item by clicking on the stars, you will be asked to review that item also. Selecting this option lets us know you don't want to give reviews after you have rated an item by clicking its stars." isPublicFacing=true}
												</p>
											</div>
										{/if}
									</div>
								</div>
							</div>
						{/if}

						<div class="panel" id="searchPreferencesPanel">
							<a data-toggle="collapse" href="#searchPreferencesPanelBody" class="active">
								<div class="panel-heading">
									<div class="panel-title">
										<h2>{translate text="Searching" isPublicFacing=true}</h2>
									</div>
								</div>
							</a>
							<div id="searchPreferencesPanelBody" class="panel-collapse collapse in">
								<div class="panel-body">
									{* Catalog Search *}
									<div class="form-group propertyRow">
										<label for="defaultCatalogSort" class="control-label">{translate text='Catalog Search Default Sort' isPublicFacing=true}</label>&nbsp;
										<select name="defaultCatalogSort" id="defaultCatalogSort" class="form-control">
											<option value="" {if empty($defaultCatalogSort)}selected{/if}>{translate text="Default based on search index" isPublicFacing=true inAttribuge=true}</option>
											{foreach from=$validCatalogSorts key="sortValue" item="sortName"}
												<option value="{$sortValue}" {if $defaultCatalogSort == $sortValue}selected{/if}>{translate text=$sortName isPublicFacing=true inAttribuge=true}</option>
											{/foreach}
										</select>
									</div>
									{if !empty($isAssociatedWithILS)}
										<div class="form-group propertyRow">
											<label for="disableCirculationActions" class="control-label">{translate text='Show Checkouts and Holds in Results' isPublicFacing=true}</label>&nbsp;
											{if $edit == true}
												<input type="checkbox" class="form-control" name="disableCirculationActions" id="disableCirculationActions" {if $profile->disableCirculationActions==0}checked='checked'{/if} data-switch="">
											{else}
												&nbsp;{if $profile->disableCirculationActions==1} {translate text='No' isPublicFacing=true}{else} {translate text='Yes' isPublicFacing=true}{/if}
											{/if}
										</div>
									{/if}
									<div class="form-group propertyRow" id="searchPreferenceLanguageGroup" {if $profile->interfaceLanguage=='en'}style="display:none"{/if}>
										<label for="searchPreferenceLanguage" class="control-label">{translate text="Prefer materials in %1%?" 1=$userLang->displayName|escape isPublicFacing=true}</label>
										<select name="searchPreferenceLanguage" id="searchPreferenceLanguage" class="form-control">
											<option value="0" {if $profile->searchPreferenceLanguage == 0}selected{/if}>{translate text="No, show interfiled with other languages" isPublicFacing=true}</option>
											<option value="1" {if $profile->searchPreferenceLanguage == 1}selected{/if}>{translate text="Yes, show above other languages" isPublicFacing=true}</option>
											<option value="2" {if $profile->searchPreferenceLanguage == 2}selected{/if}>{translate text="Yes, only show my preferred language" isPublicFacing=true}</option>
										</select>
									</div>

									{* Course Reserves *}
									{if !empty($validCourseReservesSorts)}
										<div class="form-group propertyRow">
											<label for="defaultCourseReservesSort" class="control-label">{translate text='Course Reserves Search Default Sort' isPublicFacing=true}</label>&nbsp;
											<select name="defaultCourseReservesSort" id="defaultCourseReservesSort" class="form-control">
												{foreach from=$validCourseReservesSorts key="sortValue" item="sortName"}
													<option value="{$sortValue}" {if $defaultCourseReservesSort == $sortValue}selected{/if}>{translate text=$sortName isPublicFacing=true inAttribuge=true}</option>
												{/foreach}
											</select>
										</div>
									{/if}

									{* Events *}
									{if !empty($validEventsSorts)}
										<div class="form-group propertyRow">
											<label for="defaultEventsSort" class="control-label">{translate text='Events Search Default Sort' isPublicFacing=true}</label>&nbsp;
											<select name="defaultEventsSort" id="defaultEventsSort" class="form-control">
												{foreach from=$validEventsSorts key="sortValue" item="sortName"}
													<option value="{$sortValue}" {if $defaultEventsSort == $sortValue}selected{/if}>{translate text=$sortName isPublicFacing=true inAttribuge=true}</option>
												{/foreach}
											</select>
										</div>
									{/if}

									{* Genealogy *}
									{if !empty($validGenealogySorts)}
										<div class="form-group propertyRow">
											<label for="defaultGenealogySort" class="control-label">{translate text='Genealogy Search Default Sort' isPublicFacing=true}</label>&nbsp;
											<select name="defaultGenealogySort" id="defaultGenealogySort" class="form-control">
												{foreach from=$validGenealogySorts key="sortValue" item="sortName"}
													<option value="{$sortValue}" {if $defaultGenealogySort == $sortValue}selected{/if}>{translate text=$sortName isPublicFacing=true inAttribuge=true}</option>
												{/foreach}
											</select>
										</div>
									{/if}

									{* Open Archives *}
									{if !empty($validOpenArchivesSorts)}
										<div class="form-group propertyRow">
											<label for="defaultOpenArchivesSort" class="control-label">{translate text='History & Archives Search Default Sort' isPublicFacing=true}</label>&nbsp;
											<select name="defaultOpenArchivesSort" id="defaultOpenArchivesSort" class="form-control">
												{foreach from=$validOpenArchivesSorts key="sortValue" item="sortName"}
													<option value="{$sortValue}" {if $defaultOpenArchivesSort == $sortValue}selected{/if}>{translate text=$sortName isPublicFacing=true inAttribuge=true}</option>
												{/foreach}
											</select>
										</div>
									{/if}

									{* User Lists *}
									{if !empty($validListsSorts)}
										<div class="form-group propertyRow">
											<label for="defaultListsSort" class="control-label">{translate text='List Search Default Sort' isPublicFacing=true}</label>&nbsp;
											<select name="defaultListsSort" id="defaultListsSort" class="form-control">
												{foreach from=$validListsSorts key="sortValue" item="sortName"}
													<option value="{$sortValue}" {if $defaultListsSort == $sortValue}selected{/if}>{translate text=$sortName isPublicFacing=true inAttribuge=true}</option>
												{/foreach}
											</select>
										</div>
									{/if}

									{if !empty($validSeriesSorts)}
										<div class="form-group propertyRow">
											<label for="defaultSeriesSort" class="control-label">{translate text='Series Search Default Sort' isPublicFacing=true}</label>&nbsp;
											<select name="defaultSeriesSort" id="defaultSeriesSort" class="form-control">
												{foreach from=$validSeriesSorts key="sortValue" item="sortName"}
													<option value="{$sortValue}" {if $defaultSeriesSort == $sortValue}selected{/if}>{translate text=$sortName isPublicFacing=true inAttribuge=true}</option>
												{/foreach}
											</select>
										</div>
									{/if}

									{if !empty($validWebsitesSorts)}
										<div class="form-group propertyRow">
											<label for="defaultWebsitesSort" class="control-label">{translate text='Website Search Default Sort' isPublicFacing=true}</label>&nbsp;
											<select name="defaultWebsitesSort" id="defaultWebsitesSort" class="form-control">
												{foreach from=$validWebsitesSorts key="sortValue" item="sortName"}
													<option value="{$sortValue}" {if $defaultWebsitesSort == $sortValue}selected{/if}>{translate text=$sortName isPublicFacing=true inAttribuge=true}</option>
												{/foreach}
											</select>
										</div>
									{/if}
								</div>
							</div>
						</div>
					</div>

					{if empty($offline) && $edit == true}
						<div class="form-group propertyRow">
							<button type="submit" name="updateMyPreferences" class="btn btn-sm btn-primary">{translate text="Update Preferences" isPublicFacing=true}</button>
						</div>
					{/if}
				</form>

				<script type="text/javascript">
					{* Initiate any checkbox with a data attribute set to data-switch=""  as a bootstrap switch *}
					{literal}
					$(function(){ $('input[type="checkbox"][data-switch]').bootstrapSwitch()});
					$("#usernameHelpButton").click(function() {
						var helpButton = $(this);
						if (helpButton.attr("aria-expanded") === "true") {
							$("#usernameHelp").css('display', 'none');
							$("#usernameHelpButton").attr("aria-expanded","false");
						}
						else if (helpButton.attr("aria-expanded") === "false") {
							$("#usernameHelp").css('display', 'block');
							$("#usernameHelpButton").attr("aria-expanded","true");
						}
						return false;
					})
					{/literal}
				</script>
			{/if}
		{else}
			<div class="page">
				{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
			</div>
		{/if}
	</div>
{/strip}
