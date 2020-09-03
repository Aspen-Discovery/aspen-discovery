{strip}
{if count($appearsOnLists) > 0}
	<div class="row">
		<div class="result-label col-tn-3">
			{if count($appearsOnLists) > 1}
				{translate text="Appears on these lists"}
			{else}
				{translate text="Appears on list"}
			{/if}
		</div>
		<div class="result-value col-tn-8">
			{assign var=showMoreLists value=false}
			{if count($appearsOnLists) >= 5}
				{assign var=showMoreLists value=true}
			{/if}
			{foreach from=$appearsOnLists item=appearsOnList name=loop}
				<a href="{$appearsOnList.link}">{$appearsOnList.title}</a><br/>
				{if !empty($showMoreLists) && $smarty.foreach.loop.iteration == 3}
					<a onclick="$('#moreLists_{$recordDriver->getPermanentId()}').show();$('#moreListsLink_{$recordDriver->getPermanentId()}').hide();" id="moreListsLink_{$recordDriver->getPermanentId()}">{translate text="More Lists..."}</a>
					<div id="moreLists_{$recordDriver->getPermanentId()}" style="display:none">
				{/if}
			{/foreach}
			{if !empty($showMoreLists)}
				</div>
			{/if}
		</div>
	</div>
{/if}
{/strip}