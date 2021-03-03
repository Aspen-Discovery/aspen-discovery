{strip}
	<table class="table table-striped table-condensed">
		<thead>
		<tr>
			{display_if_field_inconsistent array=$relatedRecords key="publicationDate" var=showRecordPublicationDate}
				<th>{translate text="Pub. Date"}</th>
			{/display_if_field_inconsistent}

			{assign var=showRecordEcontentSource value=false}
			{if in_array(strtolower($relatedManifestation->format), array('ebook', 'eaudiobook', 'emagazine', 'evideo'))}
				{assign var=showRecordEcontentSource value=true}
				<th>{translate text="Source"}</th>
			{/if}

			{display_if_field_inconsistent array=$relatedRecords key="edition" var=showRecordEdition}
				<th>{translate text="Edition"}</th>
			{/display_if_field_inconsistent}

			{display_if_field_inconsistent array=$relatedRecords key="publisher" var=showRecordPublisher}
				<th>{translate text="Publisher"}</th>
			{/display_if_field_inconsistent}

			{display_if_field_inconsistent array=$relatedRecords key="physical" var=showRecordPhysical}
				<th>{translate text="Phys Desc."}</th>
			{/display_if_field_inconsistent}

			{display_if_field_inconsistent array=$relatedRecords key="language" var=showRecordLanguage}
				<th>{translate text="Language"}</th>
			{/display_if_field_inconsistent}

			<th>{translate text="Availability"}</th>
			<th></th>
		</tr>
		</thead>
		{foreach from=$relatedRecords item=relatedRecord key=index}
			<tr{if !empty($promptAlternateEdition) && $index===0} class="danger"{/if}>
				{if $showRecordPublicationDate}
					<td><a href="{$relatedRecord->getUrl()}">{$relatedRecord->publicationDate}</a></td>
				{/if}
				{if $showRecordEcontentSource}
					<td><a href="{$relatedRecord->getUrl()}">{$relatedRecord->getEContentSource()}</a></td>
				{/if}
				{if $showRecordEdition}
					<td>{*<a href="{$relatedRecord->getUrl()}">*}{$relatedRecord->edition}{*</a>*}</td>
				{/if}
				{if $showRecordPublisher}
					<td><a href="{$relatedRecord->getUrl()}">{$relatedRecord->publisher}</a></td>
				{/if}
				{if $showRecordPhysical}
					<td><a href="{$relatedRecord->getUrl()}">{$relatedRecord->physical}</a></td>
				{/if}
				{if $showRecordLanguage}
					<td><a href="{$relatedRecord->getUrl()}">{implode subject=$relatedRecord->language glue="," translate=true}</a></td>
				{/if}
				<td>
					{include file='GroupedWork/statusIndicator.tpl' statusInformation=$relatedRecord->getStatusInformation() viewingIndividualRecord=1}
					{include file='GroupedWork/copySummary.tpl' summary=$relatedRecord->getItemSummary() totalCopies=$relatedRecord->getCopies() itemSummaryId=$relatedRecord->id recordViewUrl=$relatedRecord->getUrl()}
				</td>
				<td>
					<div class="btn-group btn-group-vertical btn-group-sm">
						<a href="{$relatedRecord->getUrl()}" class="btn btn-sm btn-info">{translate text="More Info"}</a>
						{foreach from=$relatedRecord->getActions() item=curAction}
							<a href="{if !empty($curAction.url)}{$curAction.url}{else}#{/if}" {if $curAction.onclick}onclick="{$curAction.onclick}"{/if} class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" {if !empty($curAction.alt)}title="{$curAction.alt}"{/if}>{$curAction.title|translate}</a>
						{/foreach}
					</div>
				</td>
			</tr>
		{/foreach}
	</table>
{/strip}