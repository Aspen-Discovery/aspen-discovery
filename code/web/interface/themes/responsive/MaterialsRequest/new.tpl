<div id="page-content" class="content">
	<div id="main-content">
		<h2>{translate text='Materials Request'}</h2>
		<div id="materialsRequest">
			<div class="materialsRequestExplanation alert alert-info">
				{if empty($newMaterialsRequestSummary)}
				If you cannot find a title in our catalog, you can request the title via this form.
				Please enter as much information as possible so we can find the exact title you are looking for.
				For example, if you are looking for a specific season of a TV show, please include that information.
				{else}
					{$newMaterialsRequestSummary}
				{/if}
			</div>
			<form id="materialsRequestForm" action="{$path}/MaterialsRequest/Submit" method="post" class="form form-horizontal" role="form">
				{include file="MaterialsRequest/request-form-fields.tpl"}

				<div class="materialsRequestLoggedInFields" {if !$loggedIn}style="display:none"{/if}>
					<div id="copyright" style="display: none">
						<p class="alert alert-warning">
						WARNING CONCERNING COPYRIGHT RESTRICTIONS The copyright law of the United States (Title 17, United States Code) governs the making of photocopies or other reproductions of copyrighted material. Under certain conditions specified in the law, libraries and archives are authorized to furnish a photocopy or other reproduction. One of these specified conditions is that the photocopy or reproduction is not to be used for any purpose other than private study, scholarship, or research. If a user makes a request for, or later uses, a photocopy or reproduction for purposes in excess of fair use, that user may be liable for copyright infringement. This institution reserves the right to refuse to accept a copying order if, in its judgment, fulfillment of the order would involve violation of copyright law.
						</p>
					</div>
					<div id="copyrightAgreement" class="formatSpecificField articleField col-sm-9 col-sm-offset-3">
						<label for="acceptCopyrightYes"><input type="radio" name="acceptCopyright" class="required" id="acceptCopyrightYes" value="1">Accept</label>
						<label for="acceptCopyrightNo"><input type="radio" name="acceptCopyright" id="acceptCopyrightNo" value="1">Decline</label>
					</div>
					<div class="col-sm-9 col-sm-offset-3">
						<input type="submit" value="Submit {translate text='Materials_Request_alt'}" class="btn btn-primary">
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">
	VuFind.MaterialsRequest.authorLabels = {$formatAuthorLabelsJSON};
	VuFind.MaterialsRequest.specialFields = {$specialFieldFormatsJSON};
	VuFind.MaterialsRequest.setFieldVisibility();
	$("#materialsRequestForm").validate();
</script>