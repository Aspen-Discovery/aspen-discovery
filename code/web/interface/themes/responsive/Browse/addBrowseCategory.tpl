{strip}
<div>
	<div id="createBrowseCategoryComments">
		<p class="alert alert-info">
			{translate text="Would you like to update an existing browse category or create a new one?"}
		</p>
	</div>

	<div>
		<button href="#" class="btn btn-default" onclick="return AspenDiscovery.Browse.getUpdateBrowseCategoryForm('{$searchId}')">{translate text="Update Existing"}</button> <button href="#" class="btn btn-default" onclick="return AspenDiscovery.Browse.getNewBrowseCategoryForm('{$searchId}')">{translate text="Create New"}</button>
	</div>
</div>
{/strip}