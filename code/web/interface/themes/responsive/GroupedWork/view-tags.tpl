{strip}
	{if $recordDriver}
	<div class="row">
		<div class="result-label col-md-3">{translate text='Tags'}:</div>
		<div class="col-md-9 result-value">
			{if $recordDriver->getTags()}
				{foreach from=$recordDriver->getTags() item=tag name=tagLoop}
					<a href="{$path}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a> ({$tag->cnt})
					{if $tag->userAddedThis}
						&nbsp;<a onclick="return VuFind.GroupedWork.removeTag('{$recordDriver->getPermanentId()|escape}', '{$tag->tag}');" class="btn btn-xs btn-danger">
							Delete
						</a>
					{/if}
					<br/>
				{/foreach}
			{else}
				{translate text='No Tags'}, {translate text='Be the first to tag this record'}!
			{/if}

			<br/>
			<div>
				<a href="#" onclick="return VuFind.GroupedWork.showTagForm(this, '{$recordDriver->getPermanentId()|escape}'); return false;" class="btn btn-sm btn-default">
					{translate text="Add Tag"}
				</a>
			</div>
		</div>

	</div>
	{/if}
{/strip}