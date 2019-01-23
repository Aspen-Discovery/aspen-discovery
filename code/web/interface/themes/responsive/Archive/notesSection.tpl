{strip}
	{foreach from=$notes item=note}
		<div class="row">
			{if count($notes) > 1}
				<div class="result-label col-sm-4">{$note.label}</div>
				<div class="result-value col-sm-8">
					{$note.body}
				</div>
			{else}
				<div class="result-value col-sm-12">
					{$note.body}
				</div>
			{/if}
		</div>
	{/foreach}
{/strip}