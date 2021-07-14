{strip}
<div id="pdfContainer">
    <div id="pdfContainerBody">
        <div id="pdfComponentBox">
            <object data="{$pdfPath}" id="view-pdf" type="application/pdf" width="100%" height="100%">
                <iframe src="{$pdfPath}" style="border: none;" width="100%" height="100%">
                    Your browser does not support the display of PDFs or the file is too large to display.  Click <a href="{$pdfPath}">here</a> to open the file.
                </iframe>
            </object>
        </div>
    </div>
</div>
{/strip}