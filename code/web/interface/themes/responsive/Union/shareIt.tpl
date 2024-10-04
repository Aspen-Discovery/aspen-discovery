{foreach from=$shareItResults item=shareItResult name="recordLoop"}
	<div class='result'>
		<div class='resultsList row'>

			<div class="col-xs-12">
				<div class="row">
					<div class="col-xs-12">
						<span class="result-index">{$smarty.foreach.recordLoop.iteration})</span>&nbsp;
						<a href="{$shareItResult.link}" class="result-title notranslate">
							{if !$shareItResult.title|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$shareItResult.title|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						</a>
					</div>
				</div>

				{if !empty($shareItResult.author)}
					<div class="row">
						<div class="result-label col-tn-3"> {translate text='Author' isPublicFacing=true}</div>
						<div class="col-tn-9 result-value">{$shareItResult.author|escape}</div>
					</div>
				{/if}

				{if !empty($shareItResult.format)}
					<div class="row">
						<div class="result-label col-tn-3">{translate text='Format' isPublicFacing=true}</div>
						<div class="col-tn-9 result-value">{$shareItResult.format|escape}</div>
					</div>
				{/if}
			</div>
		</div>
	</div>
{/foreach}
