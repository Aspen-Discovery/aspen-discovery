{strip}
{if $lightbox}
<div onmouseup="this.style.cursor='default';" id="popupboxHeader" class="header">
	<a onclick="AspenDiscovery.closeLightbox(); return false;" href="">{translate text="close" isPublicFacing=true}</a>
	{translate text='Title Citation' isPublicFacing=true}
</div>
<div id="popupboxContent" class="content">
{/if}
{if $citationCount < 1}
	{translate text="No citations are available for this record" isPublicFacing=true}.
{else}
	<div style="text-align: left;">
		{if false && $ama}
			<b>{translate text="AMA Citation"}</b>
			<p style="width: 95%; padding-left: 25px; text-indent: -25px;">
				{include file=$ama}
			</p>
		{/if}

		{if $apa}
			<b>{translate text="APA Citation" isPublicFacing=true}</b> <span class="styleGuide"><a href="http://owl.english.purdue.edu/owl/resource/560/01/">({translate text="style guide" isPublicFacing=true})</a></span>
			<p style="width: 95%; padding-left: 25px; text-indent: -25px;">
				{include file=$apa}
			</p>
		{/if}

		{if $chicagoauthdate}
			<b>{translate text="Chicago / Turabian - Author Date Citation" isPublicFacing=true}</b> <span class="styleGuide"><a href="http://www.chicagomanualofstyle.org/tools_citationguide.html/">({translate text="style guide" isPublicFacing=true})</a></span>
			<p style="width: 95%; padding-left: 25px; text-indent: -25px;">
				{include file=$chicagoauthdate}
			</p>
		{/if}

		{if $chicagohumanities}
			<b>{translate text="Chicago / Turabian - Humanities Citation" isPublicFacing=true}</b> <span class="styleGuide"><a href="http://www.chicagomanualofstyle.org/tools_citationguide.html/">({translate text="style guide" isPublicFacing=true})</a></span>
			<p style="width: 95%; padding-left: 25px; text-indent: -25px;">
				{include file=$chicagohumanities}
			</p>
		{/if}

		{if $mla}
			<b>{translate text="MLA Citation" isPublicFacing=true}</b> <span class="styleGuide"><a href="http://owl.english.purdue.edu/owl/resource/747/01/">({translate text="style guide" isPublicFacing=true})</a></span>
			<p style="width: 95%; padding-left: 25px; text-indent: -25px;">
				{include file=$mla}
			</p>
		{/if}

	</div>
	<div class="alert alert-warning">
		<strong>{translate text="Note!" isPublicFacing=true}</strong> {translate text="Citation formats are based on standards as of August 2021.  Citations contain only title, author, edition, publisher, and year published. Citations should be used as a guideline and should be double checked for accuracy." isPublicFacing=true}
	</div>
{/if}
{if $lightbox}
</div>
{/if}
{/strip}