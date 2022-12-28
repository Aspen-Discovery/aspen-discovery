{strip}
	{if !empty($subjects)}
		<div class="row">
			<div class="col-md-9 result-value">
				{foreach from=$subjects item=subject name=loop}
					<a href="/Search/Results?lookfor=%22{$subject|escape:"url"}%22&amp;searchIndex=Subject">{$subject|escape}</a>
					<br/>
				{/foreach}
			</div>
		</div>
	{/if}

{/strip}