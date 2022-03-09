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
			<b>{translate text="AMA Citation" isPublicFacing=true}</b>
			<p style="width: 95%; padding-left: 25px; text-indent: -25px;">
				{include file=$ama}
			</p>
		{/if}

		{if $apa}
			<b>{translate text="APA Citation, 7th Edition" isPublicFacing=true}</b> {if $showCitationStyleGuides}<span class="styleGuide"><a href="https://owl.purdue.edu/owl/research_and_citation/apa_style/apa_formatting_and_style_guide/general_format.html" target="_blank">({translate text="style guide" isPublicFacing=true})</a></span>{/if}
			<p style="width: 95%; padding-left: 25px; text-indent: -25px;">
				{include file=$apa}
			</p>
		{/if}

		{if $chicagoauthdate}
			<b>{translate text="Chicago / Turabian - Author Date Citation, 17th Edition" isPublicFacing=true}</b> {if $showCitationStyleGuides}<span class="styleGuide"><a href="https://www.chicagomanualofstyle.org/tools_citationguide/citation-guide-2.html" target="_blank">({translate text="style guide" isPublicFacing=true})</a></span>{/if}
			<p style="width: 95%; padding-left: 25px; text-indent: -25px;">
				{include file=$chicagoauthdate}
			</p>
		{/if}

		{if $chicagohumanities}
			<b>{translate text="Chicago / Turabian - Humanities (Notes and Bibliography) Citation, 17th Edition" isPublicFacing=true}</b> {if $showCitationStyleGuides}<span class="styleGuide"><a href="https://www.chicagomanualofstyle.org/tools_citationguide/citation-guide-1.html" target="_blank">({translate text="style guide" isPublicFacing=true})</a></span>{/if}
			<p style="width: 95%; padding-left: 25px; text-indent: -25px;">
				{include file=$chicagohumanities}
			</p>
		{/if}

		{if $mla}
			<b>{translate text="MLA Citation, 9th Edition" isPublicFacing=true}</b> {if $showCitationStyleGuides}<span class="styleGuide"><a href="https://owl.purdue.edu/owl/research_and_citation/mla_style/mla_formatting_and_style_guide/mla_general_format.html" target="_blank">({translate text="style guide" isPublicFacing=true})</a></span>{/if}
			<p style="width: 95%; padding-left: 25px; text-indent: -25px;">
				{include file=$mla}
			</p>
		{/if}

	</div>
	<div class="alert alert-warning">
		<strong>{translate text="Note!" isPublicFacing=true}</strong> {translate text="Citations contain only title, author, edition, publisher, and year published. Citations should be used as a guideline and should be double checked for accuracy. Citation formats are based on standards as of August 2021." isPublicFacing=true}
	</div>
{/if}
{if $lightbox}
</div>
{/if}
{/strip}