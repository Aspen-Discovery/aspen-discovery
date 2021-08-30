<div id="page-content" class="content">
	<div id="main-content">
		<h1>{translate text="Enter a new purchase suggestion" isPublicFacing=true}</h1>
		<div id="materialsRequest">
			<div class="materialsRequestExplanation alert alert-info">
				{if empty($newMaterialsRequestSummary)}
					{translate text="<p>Please fill out this form to make a purchase suggestion. You will receive an email when the library processes your suggestion.</p><p>Only certain fields are required, but the more information you enter the easier it will be for the librarians to find the title you're requesting. The Notes field can be used to provide any additional information.</p>" isPublicFacing=true}
				{else}
				    {translate text=$newMaterialsRequestSummary isPublicFacing=true isAdminEnteredData=true}
				{/if}
			</div>
			<div id="materialsRequestFormContainer">
				{$materialsRequestForm}
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	$("#materialsRequestForm").validate();
</script>