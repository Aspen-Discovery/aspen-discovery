{strip}
<div id="collectionSpotlight{$collectionSpotlight->id}" class="{if count($collectionSpotlight->lists) > 1}ui-tabs {/if}collectionSpotlight {$collectionSpotlight->style}">
	{if count($collectionSpotlight->lists) > 1}
		{if !isset($collectionSpotlight->listDisplayType) || $collectionSpotlight->listDisplayType == 'tabs'}
			{* Display Tabs *}
			<ul class="nav nav-tabs" role="tablist">
				{foreach from=$collectionSpotlight->lists item=list name=spotlightList}
					{assign var="active" value=$smarty.foreach.spotlightList.first}
					{if $list->displayFor == 'all' || ($list->displayFor == 'loggedIn' && $loggedIn) || ($list->displayFor == 'notLoggedIn' && !$loggedIn)}
					<li {if $active}class="active"{/if}>
						<a id="spotlightTab{$list->id}" href="#list-{$list->name|regex_replace:'/\W/':''|escape:url}" role="tab" data-toggle="tab" data-index="{$smarty.foreach.spotlightList.index}" data-carouselid="{$list->id}">{translate text=$list->name isPublicFacing=true isAdminEnteredData=true}</a>
					</li>
					{/if}
				{/foreach}
			</ul>
		{else}
			<div class="collectionSpotlightSelector">
				<select class="availableLists" id="availableLists{$collectionSpotlight->id}" onchange="changeSelectedList();return false;" aria-label="{translate text="Select a list to display" isPublicFacing=true inAttribute=true}">
					{foreach from=$collectionSpotlight->lists item=list}
					{if $list->displayFor == 'all' || ($list->displayFor == 'loggedIn' && $loggedIn) || ($list->displayFor == 'notLoggedIn' && !$loggedIn)}
					<option value="list-{$list->name|regex_replace:'/\W/':''|escape:url}">{translate text=$list->name isPublicFacing=true isAdminEnteredData=true inAttribute=true}</option>
					{/if}
					{/foreach}
				</select>
			</div>
		{/if}
	{/if}
	{* TODO: If showSpotlightTitle is on add the title here *}
	{if count($collectionSpotlight->lists) > 1}
		<div class="tab-content">
	{/if}
		{assign var="listIndex" value="0"}
		{foreach from=$collectionSpotlight->lists item=list name=spotlightList}
			{assign var="active" value=$smarty.foreach.spotlightList.first}
			{if $list->displayFor == 'all' || ($list->displayFor == 'loggedIn' && $loggedIn && $user->disableRecommendations == 0) || ($list->displayFor == 'notLoggedIn' && !$loggedIn)}
				{assign var="showViewMoreLink" value=$collectionSpotlight->showViewMoreLink}
				{assign var="showCollectionSpotlightTitle" value=$collectionSpotlight->showSpotlightTitle}
				{assign var="listIndex" value=$listIndex+1}
				{assign var="listName" value=$list->name|regex_replace:'/\W/':''|escape:url}
				{assign var="scrollerName" value="$listName"}
				{assign var="wrapperId" value="$listName"}
				{assign var="scrollerVariable" value="listScroller$listName"}
				{assign var="fullListLink" value=$list->fullListLink()}
				{assign var="scrollerTitle" value=$collectionSpotlight->name}

				{if count($collectionSpotlight->lists) == 1}
					{assign var="scrollerTitle" value=$collectionSpotlight->name}
				{/if}
				{if !isset($collectionSpotlight->listDisplayType) || $collectionSpotlight->listDisplayType == 'tabs'}
					{assign var="display" value="true"}
				{else}
					{if $listIndex == 1}
						{assign var="display" value="true"}
					{else}
						{assign var="display" value="false"}
					{/if}
				{/if}
				{if $collectionSpotlight->style == 'horizontal'}
					{include file='CollectionSpotlight/titleScroller.tpl'}
				{elseif $collectionSpotlight->style == 'horizontal-carousel'}
					{include file='CollectionSpotlight/horizontalCarousel.tpl'}
				{elseif $collectionSpotlight->style == 'vertical'}
					{include file='CollectionSpotlight/verticalTitleScroller.tpl'}
				{elseif $collectionSpotlight->style == 'single-with-next'}
					{include file='CollectionSpotlight/singleWithNextTitleSpotlight.tpl'}
				{elseif $collectionSpotlight->style == 'text-list'}
					{include file='CollectionSpotlight/textCollectionSpotlight.tpl'}
				{else}
					{include file='CollectionSpotlight/singleTitleSpotlight.tpl'}
				{/if}
			{/if}
		{/foreach}
	{if count($collectionSpotlight->lists) > 1}
		</div>
	{/if}

	{if $collectionSpotlight->style != 'horizontal-carousel'}
		<script type="text/javascript">
			{* Load title scrollers *}

			{foreach from=$collectionSpotlight->lists item=list}
				{if $list->displayFor == 'all' || ($list->displayFor == 'loggedIn' && $loggedIn) || ($list->displayFor == 'notLoggedIn' && !$loggedIn)}
					var listScroller{$list->name|regex_replace:'/\W/':''|escape:url};
				{/if}
			{/foreach}


			$(document).ready(function(){ldelim}
				{if count($collectionSpotlight->lists) > 1 && (!isset($collectionSpotlight->listDisplayType) || $collectionSpotlight->listDisplayType == 'tabs')}
				$('#collectionSpotlight{$collectionSpotlight->id} a[data-toggle="tab"]').on('shown.bs.tab', function (e) {ldelim}
					showList($(e.target).data('index'));
				{rdelim});
				{/if}

				{assign var=index value=0}
				{foreach from=$collectionSpotlight->lists item=list name=listLoop}
			        {assign var="listName" value=$list->name|regex_replace:'/\W/':''|escape:url}
					{if $list->displayFor == 'all' || ($list->displayFor == 'loggedIn' && $loggedIn) || ($list->displayFor == 'notLoggedIn' && !$loggedIn)}
						{if $index == 0}
							listScroller{$listName} = new TitleScroller('titleScroller{$listName}', '{$listName}', 'list{$listName}', {if $collectionSpotlight->autoRotate==1}true{else}false{/if}, '{$collectionSpotlight->style}');
							listScroller{$listName}.loadTitlesFrom('/Search/AJAX?method=getSpotlightTitles%26id={$list->id}%26scrollerName={$listName}%26coverSize={$collectionSpotlight->coverSize}%26showRatings={$collectionSpotlight->showRatings}%26numTitlesToShow={$collectionSpotlight->numTitlesToShow}{if $reload}%26reload=true{/if}', false);
						{/if}
						{assign var=index value=$index+1}
					{/if}
				{/foreach}
			{rdelim});

			$(window).bind('beforeunload', function(e) {ldelim}
				{if !isset($collectionSpotlight->listDisplayType) || $collectionSpotlight->listDisplayType == 'tabs'}

				{else}
					var availableListsSelector = $("#availableLists{$collectionSpotlight->id}");
					var availableLists = availableListsSelector[0];
					var selectedOption = availableLists.options[0];
					var selectedValue = selectedOption.value;
					availableListsSelector.val(selectedValue);
				{/if}
			{rdelim});

			function changeSelectedList(){ldelim}
				{*//Show the correct list*}
				var availableListsSelector = $("#availableLists{$collectionSpotlight->id}");
				var availableLists = availableListsSelector[0];
				var selectedOption = availableLists.options[availableLists.selectedIndex];

				var selectedList = selectedOption.value;
				$("#collectionSpotlight{$collectionSpotlight->id} .titleScroller.active").removeClass('active').hide();
				$("#" + selectedList).addClass('active').show();
				showList(availableLists.selectedIndex);
			{rdelim}

			function showList(listIndex){ldelim}
				{assign var=index value=0}
				{foreach from=$collectionSpotlight->lists item=list name=listLoop}
					{assign var="listName" value=$list->name|regex_replace:'/\W/':''|escape:url}
					{if $list->displayFor == 'all' || ($list->displayFor == 'loggedIn' && $loggedIn) || ($list->displayFor == 'notLoggedIn' && !$loggedIn)}
						{if $index == 0}
							if (listIndex === {$index}){ldelim}
								listScroller{$listName}.activateCurrentTitle();
							{rdelim}
						{else}
							else if (listIndex === {$index}){ldelim}
								if (listScroller{$listName} == null){ldelim}
									listScroller{$listName} = new TitleScroller('titleScroller{$listName}', '{$listName}', 'list{$listName}', {if $collectionSpotlight->autoRotate==1}true{else}false{/if}, '{$collectionSpotlight->style}');
									listScroller{$listName}.loadTitlesFrom('/Search/AJAX?method=getSpotlightTitles%26id={$list->id}%26scrollerName={$listName}%26coverSize={$collectionSpotlight->coverSize}%26showRatings={$collectionSpotlight->showRatings}%26numTitlesToShow={$collectionSpotlight->numTitlesToShow}{if $reload}%26reload=true{/if}', false);
								{rdelim}else{ldelim}
									listScroller{$listName}.activateCurrentTitle();
								{rdelim}
							{rdelim}
						{/if}
						{assign var=index value=$index+1}
					{/if}
				{/foreach}
			{rdelim}
		</script>
	{else}
		<script type="text/javascript">
			function changeSelectedList(){ldelim}
				{*//Show the correct list*}
				var availableListsSelector = $("#availableLists{$collectionSpotlight->id}");
				var availableLists = availableListsSelector[0];
				var selectedOption = availableLists.options[availableLists.selectedIndex];

				var selectedList = selectedOption.value;
				$("#collectionSpotlight{$collectionSpotlight->id} .titleScroller.active").removeClass('active').hide();
				$("#" + selectedList).addClass('active').show().jcarousel('reload');
			{rdelim}

			$(document).ready(function(){ldelim}
				{if count($collectionSpotlight->lists) > 1 && (!isset($collectionSpotlight->listDisplayType) || $collectionSpotlight->listDisplayType == 'tabs')}
				$('#collectionSpotlight{$collectionSpotlight->id} a[data-toggle="tab"]').on('shown.bs.tab', function (e) {ldelim}
					$('#collectionSpotlightCarousel' + $(e.target).data('carouselid')).jcarousel('reload');
				{rdelim});
				{/if}
			{rdelim});
		</script>
	{/if}
</div>
{/strip}
