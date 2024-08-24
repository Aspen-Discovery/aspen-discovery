<!--
barcodeGenerator.tpl
Provided a list of numbers, print barcodes for each number on Avery 5160 labels
James Staub
Nashville Public Library

Borrowing from
JsBarcode http://lindell.me/JsBarcode
Boulder Information Services https://boulderinformationservices.wordpress.com/2011/08/25/print-avery-labels-using-css-and-html/

20170817 v.1 Avery 5160
-->
{strip}
    <script src="/js/jsBarcode/JsBarcode.all.min.js"></script>
    <script>
        {literal}
        function makeBarcodes() {
            var input = document.getElementById("patronids").value;
            var patron = input.split("\n");
            var printdiv = document.getElementById("printish");
            var i=0;
            for (i=0;i<patron.length;i++) {
                if ((i+1) % 30 == 1) {
                    var pagediv = document.createElement("div");
                    pagediv.setAttribute("id", "page"+i/30);
                    pagediv.setAttribute("class", "page");
                    printdiv.appendChild(pagediv);
                }
                var labeldiv = document.createElement("div");
                labeldiv.setAttribute("id", "label"+i);
                labeldiv.setAttribute("class", "avery5160");
                pagediv.appendChild(labeldiv);
                if (!patron[i]) {
                } else {
// TO DO: VALIDATE PATRON IDS
                    var barcodeWidth = 12 / (patron[i].length + 2);
                    if (barcodeWidth > 1.5) { barcodeWidth = 1.5 ; }
                    var svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
                    svg.setAttribute("id", "svg"+i);
                    labeldiv.appendChild(svg);
                    JsBarcode("#svg"+i, patron[i], {
                        format: "code39",
                        displayValue: true,
                        font: "akagi-pro, Helvetica, Arial, sans-serif",
                        fontSize: 14,
                        textAlign: "center",
                        textMargin: 2,
                        textPosition: "bottom",
                        height: 40,
                        margin: 0,
                        width: barcodeWidth
                    });
                }
            }
            var hideMe = document.getElementById("formish");
            hideMe.style.display = "none";
            var showMe = document.getElementById("printish");
            showMe.style.display = "block";

        }
        {/literal}
    </script>
    <style>
        .avery5160 {
            /* Avery 5160 labels */
            width: 2.625in !important;
            height: 1in !important;
            margin: 0in .125in 0in 0in !important;
            padding: .212in 0 0 .475in !important;
            float: left;
            display: inline-block;
            text-align: left;
            overflow: hidden;
            outline: 1px dotted;  /*outline doesn't occupy space like border does */
        }

        @media print {
            .avery5160 {
                outline: 0px;
            }
            #footer-container, #header-wrapper,#horizontal-menu-bar-wrapper,#side-bar,#system-message-header,.breadcrumbs {
                display: none;
            }
            .container
            , #main-content-with-sidebar
            , #main-content
            , #printish {
                clear: both !important;
                left: 0px !important;
                margin: 0 !important;;
                padding: 0 !important;
                height: 10.625in !important;
                width: 8.25in !important;
            }
            .page {
                page-break-after: always !important;
            }
            #reportFrontMatter {
                display: none;
            }
        }

        @page {
            size: 8.5in 11in !important;
            /*	margin: .375in .125in .375in !important; */
            margin: .375in .125in 0in !important;
        }
    </style>
    <div id="printish">
    </div>

    <div id="formish">
        <h1>MNPS/NPL patron barcode generator</h1>
        <p>Before printing labels, use your browser’s print preview options to </p>
        <ul>
            <li>set all header and footer fields to BLANK</li>
            <li>set all print margins to 0 (zero)</li>
            <li>set Scale to 100%; do NOT shrink to fit</li>
        </ul>
        <p>Try printing a test page on plain paper first. Hold it up to the light behind a sheet of labels to make sure the bar codes line up with the stickers.</p>

        <form id="args" onsubmit="makeBarcodes(); return false;">
            Patron ID:<br>
            <textarea rows="10" cols="15" name="patronids" id="patronids"></textarea>
            <br>
            <input type="submit" value="SUBMIT">
        </form>
    </div>

{/strip}