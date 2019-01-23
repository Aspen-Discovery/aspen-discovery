{strip}
<div class="searchform">
	<form method="get" action="{$path}/Union/Search" id="searchForm" class="search">
		<div>
			<input type="hidden" name="searchSource" value="genealogy" />
			<input id="lookfor" placeholder="Search Name / Keyword" type="search" name="lookfor" size="30" value="{$lookfor|escape:"html"}" title="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term."/>
			&nbsp;by&nbsp;
			<select name="genealogyType" id="genealogySearchTypes">
			{foreach from=$genealogySearchTypes item=searchDesc key=searchVal}
				<option value="{$searchVal}"{if $searchIndex == $searchVal} selected="selected"{/if}>{translate text=$searchDesc}</option>
			{/foreach}
			</select>

			<input type="submit" name="submit" id='searchBarFind' value="{translate text="Find"}" />
		</div>
	</form>
</div>
{/strip}
