{strip}
{if count($appearsOnLists) > 0}
	<div class="row">
		<div class="result-label col-tn-3">
			{if count($appearsOnLists) > 1}
				{translate text="Appears on these lists" isPublicFacing=true}
			{else}
				{translate text="Appears on list" isPublicFacing=true}
			{/if}
		</div>
		<div class="result-value col-tn-8">
			{assign var=showMoreLists value="0"}
			{if count($appearsOnLists) >= 5}
				{assign var=showMoreLists value="1"}
			{/if}
			{foreach from=$appearsOnLists item=appearsOnList name=loop}
				<a href="{$appearsOnList.link}">{$appearsOnList.title}</a><br/>
				{if ($showMoreLists == "1") && $smarty.foreach.loop.iteration == 3}
					<a onclick="$('#moreLists_{$recordDriver->getPermanentId()}').show();$('#moreListsLink_{$recordDriver->getPermanentId()}').hide();" id="moreListsLink_{$recordDriver->getPermanentId()}">{translate text="More Lists..." isPublicFacing=true}</a>
					<div id="moreLists_{$recordDriver->getPermanentId()}" style="display:none">
				{/if}
			{/foreach}
			{if $showMoreLists == "1"}
				</div>
			{/if}
		</div>
	</div>
{/if}
{/strip}