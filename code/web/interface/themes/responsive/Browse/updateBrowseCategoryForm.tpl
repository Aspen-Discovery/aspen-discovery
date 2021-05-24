{strip}
<div>
	<form method="post" name="updateBrowseCategory" id="updateBrowseCategory" action="/Browse/AJAX" class="form">
		<div>
			{if $searchId}
				<input type="hidden" name="searchId" value="{$searchId}" id="searchId">
			{else}
				<input type="hidden" name="listId" value="{$listId}" id="listId">
			{/if}
			<input type="hidden" name="method" value="updateBrowseCategory">
			<div class="form-group">
				<label for="update-browse-categorySelect" class="control-label">Select a browse category: </label>
				<select class="form-control" name="updateBrowseCategorySelect" id="updateBrowseCategorySelect">
					<option value="null"></option>
					{foreach from=$browseCategories item="browseCategory" key="resultIndex"}
							<option value="{$browseCategory->textId}">{$browseCategory->label}</option>
					{/foreach}
				</select>
			</div>
		</div>
	</form>
</div>
{/strip}
<script type="text/javascript">
	{literal}
	$("#updateBrowseCategory").validate({
		submitHandler: function(){
			AspenDiscovery.Browse.updateBrowseCategory()
		}
	});
	{/literal}
</script>