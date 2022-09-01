<div id="page-content" class="content">
	<div id="main-content">
		<h1>{translate text='Materials Request' isPublicFacing=true}</h1>
		<div id="materialsRequest">
			{if $error}
				<div class="alert alert-warning"><strong>{$error}</strong></div>
			{else}
				<div class="materialsRequestExplanation alert alert-info">
					{if empty($newMaterialsRequestSummary)}
						{translate text='If you cannot find a title in our catalog, you can request the title via this form. Please enter as much information as possible so we can find the exact title you are looking for. For example, if you are looking for a specific season of a TV show, please include that information.' isPublicFacing=true}
					{else}
						{translate text=$newMaterialsRequestSummary isPublicFacing=true isAdminEnteredData=true}
					{/if}
				</div>
				{$vdxForm}
			{/if}
		</div>
	</div>
</div>