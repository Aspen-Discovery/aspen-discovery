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
				<label for="categoryName" class="control-label">
					{translate text="Category Name" isAdminFacing=true}
					<a style="margin-right: .5em; margin-left: .25em" id="categoryNameTooltip" class="text-info" role="tooltip" tabindex="0" data-toggle="tooltip" data-placement="right" data-title="{translate text="Enter a descriptive name for the browse category that will be displayed to users." isAdminFacing=true inAttribute=true}">
						<i class="fas fa-question-circle"></i>
					</a>
					<span class="label label-danger" style="margin-right: .5em;">{translate text="Required" isAdminFacing=true}</span>
				</label>
				<input type="text" id="categoryName" name="categoryName" value="" class="form-control required" maxlength="100">
			</div>
			{if !empty($property)} {* If data for Select tag is present, use the object editor template to build the <select> *}
			<div class="form-group">
				<label for="make-as-a-sub-category-ofSelect" class="control-label">
					{translate text="Parent Category (Optional)" isAdminFacing=true}
					<a style="margin-right: .5em; margin-left: .25em" id="parentCategoryTooltip" class="text-info" role="tooltip" tabindex="0" data-toggle="tooltip" data-placement="right" data-title="{translate text="Select an existing category to make this new category a sub-category. Sub-categories appear under their parent category." isAdminFacing=true inAttribute=true}">
						<i class="fas fa-question-circle"></i>
					</a>
				</label>
				{include file="DataObjectUtil/enum.tpl"} {* create select list *}
			</div>
			{/if}
			<div class="form-group">
				<div class="checkbox" style="margin-bottom: 0">
					<label for="addToHomePage" class="control-label">
						<input type="checkbox" id="addToHomePage" name="addToHomePage" {if $user->browseAddToHome}checked="checked"{/if}>
						{if !empty($displayLibraryName)}
							{translate text="Display on %1% Home Page" 1=$displayLibraryName isAdminFacing=true}
						{else}
							{translate text="Display on Home Page" isAdminFacing=true}
						{/if}
					</label>
				</div>
				<span class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {translate text="Only applies to main categories, not sub-categories." isAdminFacing=true}</small></span>
			</div>
		</div>
	</form>
</div>
{/strip}
<script type="text/javascript">
	$('[data-toggle="tooltip"]').tooltip();

	{literal}
	$("#createBrowseCategory").validate({
		submitHandler: function(){
			AspenDiscovery.Browse.createBrowseCategory()
		}
	});
	{/literal}
</script>