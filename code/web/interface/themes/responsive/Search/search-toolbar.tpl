{strip}
	{* User's viewing mode toggle switch *}
	<div class="row visible-md visible-lg">{* browse styling replicated here *}
		<div class="col-md-4">
			{if !empty($recordCount)}
				{* <span class="sidebar-label">
					   <label for="results-sort">{translate text='Sort'}</label></span> *}
				<label for="results-sort" style="margin-right: .5rem">{translate text='Sort by' isPublicFacing=true}</label>
				<select id="results-sort" name="sort" onchange="document.location.href = this.options[this.selectedIndex].value;" class="input-medium">
					{foreach from=$sortList item=sortData key=sortLabel}
						<option value="{$sortData.sortUrl|escape}"{if !empty($sortData.selected)} selected="selected"{/if}>{translate text=$sortData.desc isPublicFacing=true inAttribute=true}</option>
					{/foreach}
				</select>
			{/if}
		</div>
		<div class="col-md-8">
			<div id="selected-browse-label">
				<div class="btn-toolbar" role="toolbar" aria-label="Search Tools" style="justify-content: flex-end">
		            <div class="btn-group form-check form-switch" id="hideSearchCoversSwitch" style="margin-right: 1rem; {if $displayMode != 'list'}display: none{/if}"  onclick="AspenDiscovery.Account.toggleShowCovers(!$('#hideCovers').is(':checked'))">
                      <input class="form-check-input" type="checkbox" id="hideCovers" {if $showCovers == false}checked{/if} style="position: relative; top: 5px">
                      <label class="form-check-label" for="hideCovers">{translate text='Hide Covers' isPublicFacing=true}</label>
                    </div>
		            {if $showSearchTools || ($loggedIn && count($userPermissions) > 0)}
			            <div class="btn-group btn-group-sm">
			                <button data-toggle="dropdown" class="btn btn-sm btn-default dropdown-toggle" type="button" id="dropdownSearchToolsBtn"><i class="fas fa-toolbox"></i> {translate text='Search Tools' isPublicFacing=true} <span class="caret"></span></button>
			                <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownSearchToolsBtn">
			                    {if !empty($showSearchTools)}
									{if empty($offline) || $enableEContentWhileOffline}
										{if !empty($enableSavedSearches)}
											{if !empty($savedSearch)}
												<li><a href="/MyAccount/SaveSearch?delete={$searchId}">{translate text='Remove Saved Search' isPublicFacing=true}</a></li>
											{else}
												<li><a href="#" onclick="return AspenDiscovery.Account.showSaveSearchForm('{$searchId}')">{translate text='Save Search' isPublicFacing=true}</a></li>
											{/if}
										{/if}
										<li><a href="#" onclick="return AspenDiscovery.Account.ajaxLightbox('/Search/AJAX?method=getEmailForm', true);">{translate text='Email this Search' isPublicFacing=true}</a></li>
									{/if}
								{if !empty($rssLink)}
									<li><a href="{$rssLink|escape}">{translate text='Get RSS Feed' isPublicFacing=true}</a></li>
								{/if}
			                    {if !empty($excelLink)}<li><a href="{$excelLink|escape}">{translate text='Export To CSV' isPublicFacing=true}</a></li>{/if}
                                {if !empty($risLink)}<li><a href="{$risLink|escape}">{translate text="Export To RIS" isPublicFacing=true}</a></li>{/if}
			                    {/if}
			                    {if !empty($loggedIn) && (in_array('Administer All Collection Spotlights', $userPermissions) || in_array('Administer Library Collection Spotlights', $userPermissions))}
	                                 <li><a href="#" onclick="return AspenDiscovery.CollectionSpotlights.createSpotlightFromSearch('{$searchId}')">{translate text='Create Spotlight' isAdminFacing=true}</a></li>
	                            {/if}
	                            {if !empty($loggedIn) && (in_array('Administer All Browse Categories', $userPermissions) || in_array('Administer Library Browse Categories', $userPermissions) || in_array('Administer Selected Browse Category Groups', $userPermissions))}
	                                 <li><a href="#" onclick="return AspenDiscovery.Browse.addToHomePage('{$searchId}')">{translate text='Add To Browse' isPublicFacing=true}</a></li>
	                            {/if}
			                </ul>
			            </div>
	                {/if}
				</div>
			</div>
		</div>
	</div>
	<div class="row visible-sm visible-xs">
		<div class="col-sm-12">
            <button type="button" class="btn btn-default btn-sm" onclick="return AspenDiscovery.Account.showSearchToolbar('{$displayMode}', '{$showCovers}', '{if !empty($rssLink)}{$rssLink|escape}{/if}', '{if !empty($excelLink)}{$excelLink|escape}{/if}', '{if !empty($risLink)}{$risLink|escape}{/if}', '{$searchId}', [{foreach from=$sortList item=sortData key=sortLabel}{ldelim}'desc': '{$sortData.desc}','selected': '{$sortData.selected}', 'sortUrl': '{$sortData.sortUrl|escape}'{rdelim},{/foreach}]);">
              <i class="fas fa-toolbox"></i> {translate text='Search Tools' isPublicFacing=true}
            </button>
		</div>
	</div>
{/strip}