{strip}
	<div class="col-xs-12">
		{* Display Title *}
		<h2>
			{$recordDriver->getTitle()|escape}
			{if $recordDriver->getFormats()}
				<br/><small>({implode subject=$recordDriver->getFormats() glue=", "})</small>
			{/if}
		</h2>

		<div class="row">
			<div id="main-content" class="col-tn-12">
				<div class="row">
					<div id="record-details-column" class="col-xs-12 col-sm-12 col-md-9">
						{include file="EBSCO/view-title-details.tpl"}
					</div>

					<div id="recordTools" class="col-xs-12 col-sm-6 col-md-3">
						<div class="btn-toolbar">
							<div class="btn-group btn-group-vertical btn-block">
								{* Options for the user to view online or download *}
								{foreach from=$summaryActions item=link}
									<a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick && strlen($link.onclick) > 0}onclick="{$link.onclick}"{/if} class="btn btn-sm btn-primary" target="_blank">{$link.title}</a>&nbsp;
								{/foreach}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		{if $recordDriver->hasFullText()}
			<div class="row">
				<div class="col-xs-12">
					{$recordDriver->getFullText()}
				</div>
			</div>
		{/if}

		<div class="row">
			{include file=$moreDetailsTemplate}
		</div>

		{* Show a link to EBSCO for now *}
		<div class="row">
			<div class="col-xs-12">
				<a href="{$recordDriver->getEbscoUrl()}" target="_blank" class="btn btn-sm btn-info">View in EBSCO EDS</a>
			</div>
		</div>

	</div>
{/strip}
