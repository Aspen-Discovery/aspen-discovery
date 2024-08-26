{strip}
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
    <script src="/js/jsBarcode/JsBarcode.all.min.js"></script>
    <script>
        {literal}
        function makeBarcodes() {
            var input = document.getElementById("barcodeNumbers").value;
            var barcodeNumber = input.split("\n");
            var printdiv = document.getElementById("printish");
            var i=0;
            for (i=0;i<barcodeNumber.length;i++) {
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
                if (!barcodeNumber[i]) {
                } else {
                    var barcodeWidth = 12 / (barcodeNumber[i].length + 2);
                    if (barcodeWidth > 1.5) { barcodeWidth = 1.5 ; }
                    var svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
                    svg.setAttribute("id", "svg"+i);
                    labeldiv.appendChild(svg);
                    JsBarcode("#svg"+i, barcodeNumber[i], {
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
            // var hideMe = document.getElementById("formish");
            // hideMe.style.display = "none";
            // var showMe = document.getElementById("printish");
            // showMe.style.display = "block";

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
                break-inside: avoid-page !important;
                outline: 0px;
            }
            #footer-container
            , #header-wrapper
            , #horizontal-menu-bar-wrapper
            , #side-bar
            , #system-message-header
            , .breadcrumbs {
                display: none;
            }
            #formish {
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
            }
            .page {
                break-after: page !important;
                break-inside: avoid-page !important;
                height: 10.6in !important;
                width: 8.25in !important;
                margin-left: .2in !important;
                margin-top: .4in !important;
            }
        }

        @page {
            size: letter !important;
        }
    </style>
    <div id="formish">
        <h1>{translate text="Barcode Generator" isAdminFacing=true}"</h1>
        <p>Print barcodes for each number supplied on Avery 5160 labels</p>
        <p>Before printing labels, use your browser’s print preview options to </p>
        <ul>
            <li>set all header and footer fields to BLANK</li>
            <li>set all print margins to 0 (zero)</li>
            <li>set Scale to 100%; do NOT shrink to fit</li>
        </ul>
        <p>Try printing a test page on plain paper first. Hold it up to the light behind a sheet of labels to make sure the bar codes line up with the stickers.</p>

        <form id="args">
            Numbers to print as barcodes (each number on a new line):<br>
            <textarea rows="10" cols="20" name="barcodeNumbers" id="barcodeNumbers"></textarea>
            &nbsp;<input type="button" name="submitNumbers" value="Submit Numbers" class="btn btn-sm btn-primary" onclick="makeBarcodes(); return false;">
            &nbsp;<input type="button" name="printLabels" value="Print Labels" class="btn btn-sm btn-primary" onclick="window.print();" />
        </form>
    </div>
    <div id="printish">
    </div>
{/strip}