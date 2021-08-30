{strip}
	<div id="page-content" class="col-xs-12">
	{if $importResults}
		<h1>
			{if $importResults.totalTitles == 1 && $importResults.totalLists == 1}
				{translate text="Congratulations, we imported 1 title from 1 list." isPublicFacing=true}
			{elseif $importResults.totalTitles == 1}
				{translate text="Congratulations, we imported 1 title from %1% lists." 1=$importResults.totalLists isPublicFacing=true}
			{elseif $importResults.totalLists == 1}
				{translate text="Congratulations, we imported %1% titles from 1 list." 1=$importResults.totalTitles isPublicFacing=true}
			{else}
				{translate text="Congratulations, we imported %1% titles from %2% lists." 1=$importResults.totalTitles 2=$importResults.totalLists isPublicFacing=true}
			{/if}
		</h1>
	{else}
		<h1>
			{translate text="Sorry your lists could not be imported" isPublicFacing=true}
		</h1>
	{/if}
	{if !empty($importResults.errors)}
		<div class="errors">{translate text="We were not able to import the following titles. You can search the catalog for these titles to re-add them to your lists." isPublicFacing=true}<br />
			<ul>
				{foreach from=$importResults.errors item=error}
					<li>{$error}</li>
				{/foreach}
			</ul>
		</div>
	{/if}
	</div>
{/strip}