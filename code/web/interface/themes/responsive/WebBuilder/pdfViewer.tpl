{strip}
<div id="pdfContainer">
	<div id="pdfContainerBody">
		<div id="pdfComponentBox">
			<object data="{$pdfPath}" id="view-pdf" type="application/pdf" width="100%" height="100%">
				<iframe src="{$pdfPath}" style="border: none;" width="100%" height="100%">
					{translate text="Your browser does not support the display of PDFs or the file is too large to display." isPublicFacing=true} <a href="{$pdfPath}" class="btn btn-primary">{translate text="Open the file" isPublicFacing=true}</a>
				</iframe>
			</object>
		</div>
	</div>
</div>
{/strip}