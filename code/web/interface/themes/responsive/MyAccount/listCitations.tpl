<h1 id='listTitle'><a href="/MyAccount/MyList/{$favList->id}">{$favList->title|escape:"html"}</a></h1>

{if $favList->description}
	<div class="listDescription alignleft" id="listDescription">{$favList->description|escape}</div>
{/if}
<div id="listTopButtons" class="btn-toolbar">
	<div class="btn-group">
		<a value="viewList" id="FavEdit" class="btn btn-sm btn-info" href="/MyAccount/MyList/{$favList->id}">{translate text="Return to List" isPublicFacing=true}</a>
	</div>
</div>
<div class="alert alert-info">{translate text="Citations in %1% format." 1=$citationFormat isPublicFacing=true translateParameters=true}</div>
{if $citations}
	<div id="searchInfo">
		{foreach from=$citations item=citation}
			<div class="citation">
			{$citation}
			</div>
			<br />
		{/foreach}
	</div>
{else}
	{translate text='This list does not have any titles to build citations for.' isPublicFacing=true}
{/if}
<div class="alert alert-info">
	<p>{translate text="Citation formats are based on standards as of August 2021.  Citations contain only title, author, edition, publisher, and year published." isPublicFacing=true}</p>
	<p>{translate text="Citations should be used as a guideline and should be double checked for accuracy." isPublicFacing=true}</p>
	<p>{translate text="For titles that are available in multiple formats you can view more detailed citations by viewing the record for the specific format." isPublicFacing=true}</p>
</div>

