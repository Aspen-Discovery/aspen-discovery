{strip}
{if !empty($offline)}
	<div class="alert alert-warning">{translate text="The circulation system is currently offline.  Holdings information is based on information from before the system went offline." isPublicFacing=true}</div>
{/if}
{* ils check & last checkin date *}
{assign var=showVolume value=$hasVolume}
{assign var=lastSection value=''}
{if !empty($periodicalIssues)}
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
					<div class="accordion-inner ">
						<table class="table table-striped">
                            {assign var=hiddenCopy value=false}
							{include file="Record/copiesTableHeader.tpl"}
							{foreach from=$section.holdings item=holding name=tableLoop}
								{if $smarty.foreach.tableLoop.iteration > 5}
									{assign var=hiddenCopy value=true}
								{/if}

								{include file="Record/copiesTableRow.tpl"}
							{/foreach}
						</table>
					</div>
					{if count($section.holdings) > 5}
						<a onclick="$(this).remove();$('.hiddenCopy').show()" role="button" class="btn btn-default btn-sm" style="cursor: pointer;">{translate text="Show All Copies" isPublicFacing=true}</a>
					{/if}
				</div>

		{if strlen($section.name) > 0 && count($sections) > 1}
			{* Close the group *}
			</div>
		{/if}
	{/foreach}
{else}
	{translate text="No Copies Found" isPublicFacing=true}
{/if}

{if empty($show856LinksAsTab) && count($links)}
	<div id="title_links">
		<div class="row">
			<div class="col-xs-12">
				<strong style="text-decoration: underline">{translate text="Links" isPublicFacing=true}</strong>
			</div>
		</div>
		{include file="Record/view-links.tpl"}
	</div>
{/if}

{/strip}