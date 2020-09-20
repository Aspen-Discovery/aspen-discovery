{strip}
	<div id="page-content" class="col-xs-12">
	{if $importResults}
		<h1>
			Congratulations, we imported {$importResults.totalTitles} title{if $importResults.totalTitles !=1}s{/if} from {$importResults.totalLists} list{if $importResults.totalLists != 1}s{/if}.
		</h1>
	{else}
		<h1>
			Sorry your lists could not be imported
		</h1>
	{/if}
	{if !empty($importResults.errors)}
		<div class="errors">We were not able to import the following titles. You can search the catalog for these titles to re-add them to your lists.<br />
			<ul>
				{foreach from=$importResults.errors item=error}
					<li>{$error}</li>
				{/foreach}
			</ul>
		</div>
	{/if}
	</div>
{/strip}