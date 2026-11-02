{strip}
	<div class="row">
		{foreach from=$mediaFiles item=mediaFile}
			<div class="col-xs-6 col-sm-4 col-md-3 text-center">
				<a href="{$mediaFile.url|escape}" target="_blank" rel="noopener" title="{$mediaFile.title|escape}" class="thumbnail">
					{if !empty($mediaFile.thumbnailUrl)}
						<img src="{$mediaFile.thumbnailUrl|escape}" alt="{$mediaFile.title|default:$mediaFile.format|escape}" loading="lazy">
					{else}
						<span class="label label-default">{translate text=$mediaFile.format isPublicFacing=true}</span>
					{/if}
					{if !empty($mediaFile.title)}
						<div class="caption small">{$mediaFile.title|escape}</div>
					{/if}
				</a>
			</div>
		{/foreach}
	</div>
{/strip}
