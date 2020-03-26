{strip}
	{if $placard->link}
		<a href="{$placard->link}" target="_blank" class="placard-link">
	{/if}
	<div class="placard row">
		<div class="col-xs-12">

			{if !empty($placard->image)}
				<img src="/files/original/{$placard->image}" class="placard-image" alt="{$placard->title}">
			{/if}
			{if !empty($placard->body)}
				<span class="placard-body">
					{$placard->body}
				</span>
			{/if}

			{if !empty($placard->css)}
				<style type="text/css">{$placard->css}</style>
			{/if}
		</div>
	</div>
	{if $placard->link}
		</a>
	{/if}
{/strip}