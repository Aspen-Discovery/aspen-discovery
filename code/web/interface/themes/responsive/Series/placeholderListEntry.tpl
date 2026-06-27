{strip}
	<div id="listEntry{$seriesMemberId}" class="resultsList listEntry" data-order="{$resultIndex}" data-list_entry_id="{$seriesMemberId}">
		<div class="row">
			{if !empty($listEditAllowed)}
				<div class="selectTitle col-xs-12 col-sm-1">
					<input type="checkbox" name="selected[{$seriesMemberId}]" class="titleSelect" id="selected{$seriesMemberId}">
				</div>
			{/if}
			{if !empty($showCovers)}
				<div class="col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
					{if $disableCoverArt != 1 && !empty($bookCoverUrl)}
						<div>
							<img src="{$bookCoverUrl}" class="listResultImage img-thumbnail{if $useOriginalCoverUrls} use-original-covers{/if} {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
						</div>
					{/if}
				</div>
			{/if}
			<div class="{if empty($showCovers)}col-xs-12{else}col-xs-9 col-sm-9 col-md-9 col-lg-10{/if}">
				<div class="row">
					<div class="col-xs-12">
						<span class="result-index">{$resultIndex})</span>&nbsp;
						{if !empty($placeholder['title'])}
						<span class="result-title">{$placeholder['title']}</span>
					</div>
				</div>
				{if !empty($placeholder['author'])}
					<div class="row">
						<div class="result-label col-tn-3 col-xs-3">{translate text="Author" isPublicFacing=true}</div>
						<div class="result-value col-tn-9 col-xs-9 notranslate">
							{if is_array($placeholder['author'])}
								{foreach from=$placeholder['author'] item=author}
									<a href='/Search/Results?lookfor={$author|escape:"url"}&searchIndex=Author'>{$author|highlight}</a> <br/>
								{/foreach}
							{else}
								<a href='/Search/Results?lookfor={$placeholder['author']|escape:"url"}&searchIndex=Author'>{$placeholder['author']|highlight}</a>
							{/if}
						</div>
					</div>
				{/if}
				{if !empty($placeholder['volume'])}
					<div class="row">
						<div class="result-label col-tn-3 col-xs-3">{translate text="Volume" isPublicFacing=true}</div>
						<div class="result-value col-tn-9 col-xs-9">{$placeholder['volume']|escape:"html"}</div>
					</div>
				{/if}
					{if !empty($placeholder['pubDate'])}
						<div class="row">
							<div class="result-label col-tn-3 col-xs-3">{translate text="Earliest Publication Date" isPublicFacing=true}</div>
							<div class="result-value col-tn-9 col-xs-9">{$placeholder['pubDate']|escape:"html"}</div>
						</div>
					{/if}
					{if !empty($placeholder['description'])}
						<br/>
						<div class="row">
							<div class="result-value col-sm-12">{$placeholder['description']|escape:"html"}</div>
						</div>
					{/if}
				{/if}
				{if !empty($placeholder['title'])}
					<div class="row">
						<div class="col-xs-12">
							{translate text="The library does not own this title" isPublicFacing=true}
						</div>
					</div>
				{else}
				<div class="row">
					<span class="result-title">{translate text="This entry no longer exists in the catalog" isPublicFacing=true}</span>
				</div>
				{/if}
			</div>

			<div class="col-xs-2 col-sm-2 col-md-2 col-lg-2 text-right">
				{if !empty($listEditAllowed)}
					<div class="btn-group-vertical" role="group">
						<a href="#"  onclick="return AspenDiscovery.Account.getEditListForm({$listEntryId}, {$listSelected}, {$listHasFiltersApplied})" class="btn btn-default">{translate text='Edit' isPublicFacing=true}</a>
						<a href="#" onclick="AspenDiscovery.confirm('Delete Title?', 'Are you sure you want to delete this?', 'Yes', 'No', true, 'AspenDiscovery.Lists.deleteEntryFromList({$listSelected}, {$listEntryId})', 'btn-danger');" class="btn btn-danger">{translate text='Delete' isPublicFacing=true}</a>
					</div>

				{/if}
			</div>
		</div>
	</div>
{/strip}
