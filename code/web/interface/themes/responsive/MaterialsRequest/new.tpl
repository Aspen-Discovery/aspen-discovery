<div id="page-content" class="content">
	<div id="main-content">
		<h1>{translate text='Materials Request' isPublicFacing=true}</h1>
		<div id="materialsRequest">
			{if !empty($offline)}
				<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
			{elseif !$displayMaterialsRequest}
				<div class="alert alert-warning">{translate text="The materials request system is not currently available.  Please check back later." isPublicFacing=true}</div>
			{else}
				<div class="materialsRequestExplanation alert alert-info">
					{if empty($newMaterialsRequestSummary)}
						{translate text='If you cannot find a title in our catalog, you can request the title via this form. Please enter as much information as possible so we can find the exact title you are looking for. For example, if you are looking for a specific season of a TV show, please include that information.' isPublicFacing=true}
					{else}
						{translate text=$newMaterialsRequestSummary isPublicFacing=true isAdminEnteredData=true}
					{/if}
				</div>
				<form id="materialsRequestForm" action="/MaterialsRequest/Submit" method="post" class="form form-horizontal" role="form">
					{include file="MaterialsRequest/request-form-fields.tpl"}

					<div class="materialsRequestLoggedInFields" {if empty($loggedIn)}style="display:none"{/if}>
						<div id="copyright" style="display: none">
							<p class="alert alert-warning">
							{translate text='WARNING CONCERNING COPYRIGHT RESTRICTIONS The copyright law of the United States (Title 17, United States Code) governs the making of photocopies or other reproductions of copyrighted material. Under certain conditions specified in the law, libraries and archives are authorized to furnish a photocopy or other reproduction. One of these specified conditions is that the photocopy or reproduction is not to be used for any purpose other than private study, scholarship, or research. If a user makes a request for, or later uses, a photocopy or reproduction for purposes in excess of fair use, that user may be liable for copyright infringement. This institution reserves the right to refuse to accept a copying order if, in its judgment, fulfillment of the order would involve violation of copyright law.' isPublicFacing=true}
							</p>
						</div>
						<div id="copyrightAgreement" class="formatSpecificField articleField col-sm-9 col-sm-offset-3">
							<label for="acceptCopyrightYes"><input type="radio" name="acceptCopyright" class="required" id="acceptCopyrightYes" value="1">{translate text="Accept" isPublicFacing=true}</label>
							<label for="acceptCopyrightNo"><input type="radio" name="acceptCopyright" id="acceptCopyrightNo" value="1">{translate text="Decline" isPublicFacing=true}</label>
						</div>
						<div class="col-sm-9 col-sm-offset-3">
							<input type="submit" value="{translate text='Submit Materials Request' inAttribute=true isPublicFacing=true}" class="btn btn-primary">
						</div>
					</div>
				</form>
			{/if}
		</div>
	</div>
</div>
<script type="text/javascript">
	AspenDiscovery.MaterialsRequest.authorLabels = {$formatAuthorLabelsJSON};
	AspenDiscovery.MaterialsRequest.specialFields = {$specialFieldFormatsJSON};
	AspenDiscovery.MaterialsRequest.setFieldVisibility();
	$("#materialsRequestForm").validate();
</script>