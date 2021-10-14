{strip}
<div class="col-xs-12">
	<h1>{$title}</h1>
	<div id="pdfContainer">
		<div id="pdfContainerBody">
			<div id="pdfComponentBox">
				<object data="{$pdfPath}" id="view-pdf" type="application/pdf" width="100%" height="100%">
					<iframe src="{$pdfPath}" style="border: none;" width="100%" height="100%">
						{translate text="Your browser does not support the display of PDFs or the file (%1%) is too large to display." 1=$fileSize  isPublicFacing=true}
						<a class="btn btn-primary" href="{$pdfPath}">{translate text="Open the file" isPublicFacing=true}</a>
					</iframe>
				</object>
			</div>
		</div>
	</div>
{/strip}