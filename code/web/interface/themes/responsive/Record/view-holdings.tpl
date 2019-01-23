{strip}
{if $offline}
	<div class="alert alert-warning">The circulation system is currently offline.  Holdings information is based on information from before the system went offline.</div>
{/if}
{* ils check & last checkin date *}
{if ($ils == 'Sierra' || $ils == 'Millennium')}
	{assign var=showLastCheckIn value=$hasLastCheckinData}
	{assign var=showVolume value=$hasVolume}
{else}
	{assign var=showLastCheckIn value=false}
	{assign var=showVolume value=false}
{/if}
{assign var=lastSection value=''}
{if $periodicalIssues}
	{include file='Record/issueSummaries.tpl' issueSummaries=$periodicalIssues}
{elseif isset($sections) && count($sections) > 0}
	{foreach from=$sections item=section}
		{if strlen($section.name) > 0 && count($sections) > 1}
			<div class="accordion-group">
				<div class="accordion-heading" id="holdings-header-{$section.name|replace:' ':'_'}">
					<a class='accordion-toggle' data-toggle="collapse" data-target="#holdings-section-{$section.name|replace:' ':'_'}">{$section.name}</a>
				</div>
		{/if}

		<div id="holdings-section-{$section.name|replace:' ':'_'}" class="accordion-body {if count($sections) > 1}collapse {if $section.sectionId <=5}in{/if}{/if}">
			<div class="accordion-inner">
				<div class="striped">
				{include file="Record/copiesTableHeader.tpl"}
				{foreach from=$section.holdings item=holding}
					{include file="Record/copiesTableRow.tpl"}
				{/foreach}
				</div>
			</div>
		</div>

		{if strlen($section.name) > 0 && count($sections) > 1}
			{* Close the group *}
			</div>
		{/if}
	{/foreach}
{else}
	No Copies Found
{/if}

{if !$show856LinksAsTab && count($links)}
	<div id="title_links">
		<div class="row">
			<div class="col-xs-12">
				<strong style="text-decoration: underline">Links</strong>
			</div>
		</div>
		{include file="Record/view-links.tpl"}
	</div>
{/if}

{/strip}