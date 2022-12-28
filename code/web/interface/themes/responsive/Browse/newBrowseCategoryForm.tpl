{strip}
<div>
	<div id="createBrowseCategoryComments">
		<p class="alert alert-info">
			{translate text="Please enter a name for the browse category to be created." isAdminFacing=true}
		</p>
	</div>
	<form method="post" name="createBrowseCategory" id="createBrowseCategory" action="/Browse/AJAX" class="form">
		<div>
			{if !empty($searchId)}
				<input type="hidden" name="searchId" value="{$searchId}" id="searchId">
			{elseif !empty($listId)}
				<input type="hidden" name="listId" value="{$listId}" id="listId">
			{elseif !empty($reserveId)}
				<input type="hidden" name="reserveId" value="{$reserveId}" id="reserveId">
			{/if}
			<input type="hidden" name="method" value="createBrowseCategory">
			<div class="form-group">
				<label for="categoryName" class="control-label">{translate text="New Category Name" isAdminFacing=true}</label>
				<input type="text" id="categoryName" name="categoryName" value="" class="form-control required">
			</div>
			{if !empty($property)} {* If data for Select tag is present, use the object editor template to build the <select> *}
			<div class="form-group">
				<label for="make-as-a-sub-category-ofSelect" class="control-label">{translate text="Add as a Sub-Category to (optional)" isAdminFacing=true} </label>
				{include file="DataObjectUtil/enum.tpl"} {* create select list *}
			</div>
			{/if}
			<div class="form-group">
				<label for="addToHomePage" class="control-label"><input type="checkbox" id="addToHomePage" name="addToHomePage" {if $user->browseAddToHome}checked="checked"{/if}>{translate text="Add to Home Page after creation (main categories only)" isAdminFacing=true}</label>
			</div>
		</div>
	</form>
</div>
{/strip}
<script type="text/javascript">
	{literal}
	$("#createBrowseCategory").validate({
		submitHandler: function(){
			AspenDiscovery.Browse.createBrowseCategory()
		}
	});
	{/literal}
</script>