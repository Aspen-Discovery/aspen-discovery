{strip}
	<div id="listEntry{$listEntryId}" class="resultsList listEntry" data-order="{$resultIndex}" data-list_entry_id="{$listEntryId}">
		{* Because colons give css & jquery trouble the Ids from Islandora have : replaced with _ *}
		<div class="row" id="record{$summId|escape:"url"}">
			{if $listEditAllowed}
				<div class="selectTitle col-xs-12 col-sm-1">
					<input type="checkbox" name="selected[{$listEntryId}]" class="titleSelect" id="selected{$listEntryId}">
				</div>
			{/if}
			{if $showCovers}
			<div class="col-xs-12 col-sm-3 col-md-3 col-lg-2 text-center">
				{if $disableCoverArt != 1}
					<a href="{$summUrl}">
						<img src="{$bookCoverUrlMedium}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image' inAttribute=true}">
					</a>
				{/if}
			</div>
			{/if}

			<div class="{if !$showCovers}col-xs-9 col-sm-9 col-md-9 col-lg-10{elseif $listEditAllowed}col-xs-6 col-sm-6 col-md-6 col-lg-7{else}col-xs-6 col-sm-6 col-md-6 col-lg-8{/if}">				<div class="row">
					<div class="col-xs-12">
						<span class="result-index">{$resultIndex})</span>&nbsp;
						<a href="{$summUrl}" class="result-title notranslate">{if !$summTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}</a>
					</div>
				</div>

				{if $summAuthor}
					<div class="row">
						<div class="result-label col-xs-3">Author: </div>
						<div class="col-xs-9 result-value  notranslate">
							{if is_array($summAuthor)}
								{foreach from=$summAuthor item=author}
									<a href='/Author/Home?author="{$author|escape:"url"}"'>{$author|highlight}</a>
								{/foreach}
							{else}
								<a href='/Author/Home?author="{$summAuthor|escape:"url"}"'>{$summAuthor|highlight}</a>
							{/if}
						</div>
					</div>
				{/if}

				{if $listEntryNotes}
					<div class="row">
						<div class="result-label col-md-3">Notes: </div>
						<div class="user-list-entry-note result-value col-md-9">
							{$listEntryNotes}
						</div>
					</div>
				{/if}

				{if $summPublisher}
					<div class="row">
						<div class="result-label col-xs-3">Publisher: </div>
						<div class="col-xs-9 result-value">
							{$summPublisher}
						</div>
					</div>
				{/if}

				{if $summFormat}
					<div class="row">
						<div class="result-label col-xs-3">Format: </div>
						<div class="col-xs-9 result-value">
							{$summFormat}
						</div>
					</div>
				{/if}

				{if $summPubDate}
					<div class="row">
						<div class="result-label col-xs-3">Pub. Date: </div>
						<div class="col-xs-9 result-value">
							{$summPubDate|escape}
						</div>
					</div>
				{/if}

				{if $summSnippets}
					{foreach from=$summSnippets item=snippet}
						<div class="row">
							<div class="result-label col-xs-3">{translate text=$snippet.caption}: </div>
							<div class="col-xs-9 result-value">
								{if !empty($snippet.snippet)}<span class="quotestart">&#8220;</span>...{$snippet.snippet|highlight}...<span class="quoteend">&#8221;</span><br />{/if}
							</div>
						</div>
					{/foreach}
				{/if}

				<div class="row well-small">
					<div class="col-xs-12 result-value" id="descriptionValue{$summId|escape}">{$summDescription|highlight|truncate_html:450:"..."}</div>
				</div>

				<div class="row">
					<div class="col-xs-12">
						{include file='Archive/result-tools-horizontal.tpl'}
					</div>
				</div>

			</div>

			<div class="col-xs-2 col-sm-2 col-md-2 col-lg-2 text-right">
				{if $listEditAllowed}
					<div class="btn-group-vertical" role="group">
						{if $userSort && $resultIndex != '1'}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.Lists.changeWeight('{$listEntryId}', 'up');" title="{translate text="Move Up"}">&#x25B2;</span>{/if}
						<a href="/MyAccount/Edit?listEntryId={$listEntryId|escape:"url"}{if !is_null($listSelected)}&amp;listId={$listSelected|escape:"url"}{/if}" class="btn btn-default">{translate text='Edit'}</a>
						{* Use a different delete URL if we're removing from a specific list or the overall favorites: *}
						<a href="/MyAccount/MyList/{$listSelected|escape:"url"}?delete={$listEntryId|escape:"url"}" onclick="return confirm('Are you sure you want to delete this?');" class="btn btn-danger">{translate text='Delete'}</a>
						{if $userSort && ($resultIndex != $listEntryCount)}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.Lists.changeWeight('{$listEntryId}', 'down');" title="{translate text="Move Down"}">&#x25BC;</span>{/if}
					</div>
				{/if}
			</div>
		</div>
	</div>
{/strip}