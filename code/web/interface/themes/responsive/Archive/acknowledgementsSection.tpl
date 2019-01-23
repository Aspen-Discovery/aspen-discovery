{strip}
	{foreach from=$productionTeam item=entity}
		<div class="relatedPerson row">
			<div class="col-tn-12">
				<a href='{$entity.link}'>
					{$entity.label}
				</a>
				{if $entity.role}
					&nbsp;({$entity.role})
				{/if}
				{if $entity.note}
					&nbsp;- {$entity.note}
				{/if}
			</div>
		</div>
	{/foreach}
{/strip}