{strip}
	{if $tagList}
		<div class="panel">
			<a data-toggle="collapse" data-parent="#account-link-accordion" href="#myTagsPanel">
				<div class="panel-heading">
					<div class="panel-title collapsed">
						My Tags
					</div>
				</div>
			</a>
			<div id="myTagsPanel" class="panel-collapse collapse">
				<div class="panel-collapse">
					<div class="panel-body">
						{foreach from=$tagList item=tag}
							<div class="myAccountLink">
								<a href='{$path}/Search/Results?lookfor={$tag->tag|escape:"url"}&amp;basicType=tag'>{$tag->tag|escape:"html"}</a> ({$tag->cnt})&nbsp;
								<a href='#' onclick="return VuFind.Account.removeTag('{$tag->tag}');">
									<span class="glyphicon glyphicon-remove-circle" title="Delete Tag">&nbsp;</span>
								</a>
							</div>
						{/foreach}
					</div>
				</div>
			</div>
		</div>
	{/if}
{/strip}