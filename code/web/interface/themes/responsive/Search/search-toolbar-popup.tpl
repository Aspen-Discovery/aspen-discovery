{strip}
	<div class="form-horizontal">
		<div class="form-group">
		    <div class="col-xs-12">
		        <label for="results-sort" class="control-label">{translate text='Sort by' isPublicFacing=true}</label>
		    </div>
		    <div class="col-xs-12">
		        <select name="sort" onchange="document.location.href = this.options[this.selectedIndex].value;" class="form-control">
		            {foreach from=$sortList item=sortData key=sortLabel}
		                <option value="{$sortData.sortUrl|escape}"{if !empty($sortData.selected)} selected="selected"{/if}>{translate text=$sortData.desc isPublicFacing=true inAttribute=true}</option>
		            {/foreach}
		        </select>
		    </div>
		</div>
		<div id="hideSearchCoversSwitchModal" {if $displayMode != 'list'}style="display: none"{/if}>
		    <div class="form-group">
		        <div class="col-xs-12">
		            <div class="btn-group form-check form-switch" style="margin-right: 1rem;"  onclick="AspenDiscovery.Account.toggleShowCovers(!$('#hideCoversModal').is(':checked'))">
		              <input class="form-check-input" type="checkbox" id="hideCoversModal" {if $showCovers == 0}checked{/if} style="position: relative; top: 5px">
		              <label class="form-check-label" for="hideCoversModal">{translate text='Hide Covers' isPublicFacing=true}</label>
		            </div>
		        </div>
		    </div>
		</div>
		<div class="form-group">
		    <div class="col-xs-12">
		        <div class="btn-group btn-group-justified" data-toggle="buttons">
		            <label for="coversModal" title="Covers" class="btn btn-default{if $displayMode == 'covers'} active{/if}"><input onchange="AspenDiscovery.Searches.toggleDisplayMode(this.value)" type="radio" id="coversModal" value="covers" {if $displayMode == 'covers'}checked{/if}>
		                <i class='fas fa-th'></i> {translate text=Covers isPublicFacing=true}
		            </label>
		            <label for="listModal" title="Lists" class="btn btn-default{if $displayMode == 'list'} active{/if}"><input onchange="AspenDiscovery.Searches.toggleDisplayMode(this.value)" type="radio" id="listModal" value="list" {if $displayMode == 'list'}checked{/if}>
		                <i class='fas fa-list'></i> {translate text=List isPublicFacing=true}
		            </label>
		        </div>
		    </div>
		</div>
		{if $showSearchTools || ($loggedIn && count($userPermissions) > 0)}
			<div class="form-group">
			    <div class="col-xs-12">
				    {if !empty($showSearchTools)}
				        {if !empty($enableSavedSearches)}
				            {if !empty($savedSearch)}
				            	<a href="/MyAccount/SaveSearch?delete={$searchId}" class="btn btn-default btn-block">{translate text='Remove Saved Search' isPublicFacing=true}</a>
				            {else}
				            	<a href="#" onclick="return AspenDiscovery.Account.showSaveSearchForm('{$searchId}')" class="btn btn-default btn-block">{translate text='Save Search' isPublicFacing=true}</a>
				            {/if}
				        {/if}
			            <a href="#" onclick="return AspenDiscovery.Account.showEmailSearchForm();" class="btn btn-default btn-block">{translate text='Email this Search' isPublicFacing=true}</a>
			            <a href="{$rssLink|escape}" class="btn btn-default btn-block">{translate text='Get RSS Feed' isPublicFacing=true}</a>
			            <a href="{$excelLink|escape}" class="btn btn-default btn-block">{translate text='Export To Excel' isPublicFacing=true}</a>
		            {/if}
		            {if !empty($loggedIn) && (in_array('Administer All Collection Spotlights', $userPermissions) || in_array('Administer Library Collection Spotlights', $userPermissions))}
	                    <a href="#" onclick="return AspenDiscovery.CollectionSpotlights.createSpotlightFromSearch('{$searchId}')" class="btn btn-default btn-block">{translate text='Create Spotlight' isAdminFacing=true}</a>
	                {/if}
	                {if !empty($loggedIn) && (in_array('Administer All Browse Categories', $userPermissions) || in_array('Administer Library Browse Categories', $userPermissions))}
	                    <a href="#" onclick="return AspenDiscovery.Browse.addToHomePage('{$searchId}')" class="btn btn-default btn-block">{translate text='Add To Browse' isPublicFacing=true}</a>
	                {/if}
			    </div>
			</div>
		{/if}
	</div>
{/strip}