
	<div class="page">
		{if $user->_web_note}
			<div class="row">
				<div id="web_note" class="alert alert-info text-center col-xs-12">{$user->_web_note}</div>
			</div>
		{/if}

		{if !empty($accountMessages)}
			{include file='systemMessages.tpl' messages=$accountMessages}
		{/if}

		<h1>{translate text="Your Searches" isPublicFacing=true}</h1>

        {if $numSavedSearches > 0 || $numRecentSearches > 0}
		<ul class="nav nav-tabs" role="tablist" id="searchHistoryTab">
            {if $numSavedSearches > 0}<li role="presentation"{if $tab=='saved'} class="active"{/if}><a href="#saved" aria-controls="saved" role="tab" data-toggle="tab">{translate text="Saved Searches" isPublicFacing=true} <span class="badge"><span class="saved-searches-count-placeholder">&nbsp;</span></span></a></li>{/if}
			{if $numRecentSearches > 0}<li role="presentation"{if $tab=='recent'} class="active"{/if}><a href="#recent" aria-controls="recent" role="tab" data-toggle="tab">{translate text="Recent Searches" isPublicFacing=true} <span class="badge"><span class="recent-searches-count-placeholder">&nbsp;</span></span></a></li>{/if}

		</ul>

		<div class="tab-content" id="searchHistory">
            {if $numSavedSearches > 0}
	            <div role="tabpanel" class="tab-pane{if $tab=='saved'} active{/if}" id="saved" aria-label="">
					<div id="savedSearchesPlaceholder">{translate text="Loading Saved Searches" isPublicFacing=true}</div>
					<div id="savedSearchesPagination"></div>
				</div>
            {/if}
            {if $numRecentSearches > 0}
				<div role="tabpanel" class="tab-pane{if $tab=='recent'} active{/if}" id="recent" aria-label="">
					<div id="recentSearchesPlaceholder">{translate text="Loading Recent Searches" isPublicFacing=true}</div>
					<div id="recentSearchesPagination"></div>
				</div>
			{/if}
		</div>
	        <script type="text/javascript">
                {literal}
		        $(document).ready(function() {
			        $("a[href='#saved']").on('show.bs.tab', function (e) {
				        AspenDiscovery.Account.loadSearchHistory('saved', {/literal}{$page}{literal}, {/literal}{$limit}{literal}, '{/literal}{$sort}{literal}');
			        });
			        $("a[href='#recent']").on('show.bs.tab', function (e) {
				        AspenDiscovery.Account.loadSearchHistory('recent', {/literal}{$page}{literal}, {/literal}{$limit}{literal}, 'id');
			        });
                    {/literal}{if $numSavedSearches > 0}{literal}
			        AspenDiscovery.Account.loadSearchHistory('saved', {/literal}{$page}{literal}, {/literal}{$limit}{literal}, '{/literal}{$sort}{literal}');
					 {/literal}{else}{literal}
                    AspenDiscovery.Account.loadSearchHistory('recent', {/literal}{$page}{literal}, {/literal}{$limit}{literal}, 'id');
			        {/literal}{/if}{literal}
		        });
                {/literal}
	        </script>

        {else}
            {translate text="There are currently no searches in your history." isPublicFacing=true}
		{/if}
	</div>

