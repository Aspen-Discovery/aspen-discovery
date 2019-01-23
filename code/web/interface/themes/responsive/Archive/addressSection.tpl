{strip}
	{foreach from=$linkedAddresses item=entity}
		<div class="row">
			<div class="result-label col-sm-4">
				{$entity.role|translate|ucwords} {$entity.note}:
			</div>
			<div class="result-value col-sm-8">
				<a href='{$entity.link}'>
					{$entity.label}
				</a>
			</div>
		</div>
	{/foreach}

	{foreach from=$unlinkedAddresses item="unlinkedEntity"}
		{if $unlinkedEntity.type == 'place'}
			<div class="row">
				<div class="result-label col-sm-4">
					{$unlinkedEntity.role|translate|ucwords} {$unlinkedEntity.note}:
				</div>
				<div class="result-value col-sm-8">
					{$unlinkedEntity.label}
				</div>
			</div>
		{/if}
	{/foreach}
{/strip}