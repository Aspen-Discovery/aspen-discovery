{strip}
<form class="form-horizontal" id="save-to-list-form">
	<div>
		<input type="hidden" name="submit" value="1">
		<input type="hidden" name="sourceId" id="sourceId" value="{$sourceId|escape}">
		<input type="hidden" name="source" id="source" value="{$source|escape}">

		{* Check to see if we should dispaly add to reading history *}
		{if $enableAddToReadingHistory}
			<ul class="nav nav-tabs" role="tablist" id="addToListTypeTabs">
				<li role="presentation" class="active"><a href="#addToList" aria-controls="all" role="tab" data-toggle="tab" onclick="$('#saveToListButton').show();$('#saveToReadingHistoryButton').hide();">{translate text="List" isPublicFacing=true}</a></li>
				<li role="presentation"><a href="#addToReadingHistory" aria-controls="ils" role="tab" data-toggle="tab" onclick="$('#saveToListButton').hide();$('#saveToReadingHistoryButton').show();">{translate text="Reading History" isPublicFacing=true}</a></li>
			</ul>
			<div class="tab-content" id="addToListType">
				<div role="tabpanel" class="tab-pane active" id="addToList" aria-label="Add to List Panel">
		{/if}
					{if !empty($containingLists)}
						<p>
						{translate text='This item is already part of the following list/lists' isPublicFacing=true}<br>
						{foreach from=$containingLists item="list"}
							<a href="/MyAccount/MyList/{$list.id}">{$list.title|escape:"html"}</a><br>
						{/foreach}
						</p>
					{/if}

					{* Only display the list drop-down if the user has lists that do not contain
					 this item OR if they have no lists at all and need to create a default list *}
					{if (!empty($nonContainingLists) || (empty($containingLists) && empty($nonContainingLists))) }
						{assign var="showLists" value="true"}
					{/if}

					{if !empty($showLists)}
						<div class="form-group">
							<label for="addToList-list" class="col-sm-3">{translate text='Choose a List' isPublicFacing=true}</label>
							<div class="col-sm-9">
								<select name="list" id="addToList-list" class="form-control form-control-sm">
									{foreach from=$nonContainingLists item="list"}
										<option value="{$list.id}" {if !empty($list.selected)}selected{/if}>{$list.title|escape:"html"}</option>
									{foreachelse}
										<option value="">{translate text='My Favorites' isPublicFacing=true}</option>
									{/foreach}
								</select>
								<div style="margin-top: 6px; text-align: center;">
									<div>{translate text='or' isPublicFacing=true}</div>
									<div style="margin-top: 6px;">
										<button class="btn btn-sm btn-default" onclick="return AspenDiscovery.Account.showCreateListForm('{$source|escape:"url"}', '{$sourceId|escape:"url"}')">{translate text="Create a New List" isPublicFacing=true}</button>
									</div>
								</div>
							</div>
						</div>
					{else}
						<button class="btn btn-sm btn-default" onclick="return AspenDiscovery.Account.showCreateListForm('{$source|escape:"url"}', '{$sourceId|escape:"url"}')">{translate text="Create a New List" isPublicFacing=true}</button>
					{/if}

					{if !empty($showLists) && $enableListDescriptions}
						<div class="form-group">
							<label for="addToList-notes" class="col-sm-3">{translate text='Add a Note' isPublicFacing=true}</label>
							<div class="col-sm-9">
								<textarea name="notes" rows="3" cols="50" class="form-control" id="addToList-notes"></textarea>
							</div>
						</div>
					{/if}
		{if $enableAddToReadingHistory}
				</div>
				<div role="tabpanel" class="tab-pane" id="addToReadingHistory" aria-label="Add to Reading History Panel">
					<div class="form-group">
						<label for="addToReadingHistory-date" class="col-sm-3">{translate text='Date Read' isPublicFacing=true}</label>
						<div class="col-sm-9">
							<select name="monthRead" type="month" class="form-control" id="addToReadingHistory-month">
								<option value="1" {if $curMonth==1}selected{/if}>{translate text="January" isPublicFacing=1 inAttribute=true}</option>
								<option value="2" {if $curMonth==2}selected{/if}>{translate text="February" isPublicFacing=1 inAttribute=true}</option>
								<option value="3" {if $curMonth==3}selected{/if}>{translate text="March" isPublicFacing=1 inAttribute=true}</option>
								<option value="4" {if $curMonth==4}selected{/if}>{translate text="April" isPublicFacing=1 inAttribute=true}</option>
								<option value="5" {if $curMonth==5}selected{/if}>{translate text="May" isPublicFacing=1 inAttribute=true}</option>
								<option value="6" {if $curMonth==6}selected{/if}>{translate text="June" isPublicFacing=1 inAttribute=true}</option>
								<option value="7" {if $curMonth==7}selected{/if}>{translate text="July" isPublicFacing=1 inAttribute=true}</option>
								<option value="8" {if $curMonth==8}selected{/if}>{translate text="August" isPublicFacing=1 inAttribute=true}</option>
								<option value="9" {if $curMonth==9}selected{/if}>{translate text="September" isPublicFacing=1 inAttribute=true}</option>
								<option value="10" {if $curMonth==10}selected{/if}>{translate text="October" isPublicFacing=1 inAttribute=true}</option>
								<option value="11" {if $curMonth==11}selected{/if}>{translate text="November" isPublicFacing=1 inAttribute=true}</option>
								<option value="12" {if $curMonth==12}selected{/if}>{translate text="December" isPublicFacing=1 inAttribute=true}</option>
							</select>
							<input name="yearRead" type="number" min="1900" max="{$curYear}" value="{$curYear}" class="form-control" id="addToReadingHistory-year"/>
						</div>
					</div>
				</div>
			</div>
		{/if}
	</div>
</form>
{/strip}