{strip}
<div>
	<div id="createSpotlightComments">
		<p class="alert alert-info">
			{if count($existingCollectionSpotlights) > 0}
				You may either add this {$source} to an existing collection spotlight as a new tab, <br> or you may create a new spotlight to display this {$source} in.
			{else}
				Please enter a name for the spotlight to be created.
			{/if}
		</p>
	</div>
	<form method="post" name="addSpotlight" id="addSpotlight" action="/Admin/CreateCollectionSpotlight" class="form-horizontal">
		<input type="hidden" name="source" value="{$source}">
		<input type="hidden" name="id" value="{$id}">
		{if count($existingCollectionSpotlights) > 0}
			<div class="form-group">
				<label for="collectionSpotlightId" class="col-sm-4">Select a collection spotlight:</label>
				<div class="col-sm-8">
					<select id="collectionSpotlightId" name="collectionSpotlightId" class="form-control">
						<option value="-1">Create a new spotlight</option>
						{foreach from=$existingCollectionSpotlights item=spotlightName key=spotlightId}
							<option value="{$spotlightId}">{$spotlightName}</option>
						{/foreach}
					</select>
				</div>
			</div>
		{/if}
		<div class="form-group">
			<label for="spotlightName" class="col-sm-4">New Spotlight Name / New Tab Name:</label>
			<div class="col-sm-8">
				<input type="text" id="spotlightName" name="spotlightName" value="" class="form-control required">
			</div>
		</div>
	</form>
	<script type="text/javascript">
		$(function(){ldelim}
			$("#addSpotlight").validate();
		{rdelim});
	</script>
</div>
{/strip}