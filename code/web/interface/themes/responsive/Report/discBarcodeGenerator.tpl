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
        // EAN8.js
        // Transform a 7-digit number into an EAN-8-encoded string of 0s and 1s
        // James Staub
        // Nashville Public Library
        // 20150402
        // Borrowing very heavily from JsBarcode
        function EAN8(EANnumber){
            this.EANnumber = EANnumber+"0";
            this.valid = function(){
                return valid(this.EANnumber);
            };
            this.encoded = function (){
                if(valid(this.EANnumber)){
                    return createUPC(this.EANnumber);
                }
                return "error";
            }
            //The L (left) type of encoding
            var Lbinary = {
                0: "0001101",
                1: "0011001",
                2: "0010011",
                3: "0111101",
                4: "0100011",
                5: "0110001",
                6: "0101111",
                7: "0111011",
                8: "0110111",
                9: "0001011"}
            //The R (right) type of encoding
            var Rbinary = {
                0: "1110010",
                1: "1100110",
                2: "1101100",
                3: "1000010",
                4: "1011100",
                5: "1001110",
                6: "1010000",
                7: "1000100",
                8: "1001000",
                9: "1110100"}
            //The start bits
            var startBin = "101";
            //The end bits
            var endBin = "101";
            //The middle bits
            var middleBin = "01010";
            //Regexp to test if the EAN8 code is correct formatted
            var regexp = /^[0-9]{8}$/;
            //Create the binary representation of the EAN8 code
            //number needs to be a string
            function createUPC(number){
                //Create the return variable
                var result = "";
                //Get the number to be encoded on the left side of the EAN code
                var leftSide = number.substring(0,4);
                //Get the number to be encoded on the right side of the EAN code
                var rightSide = number.substring(4);
                //Add the start bits
                result += startBin;
                //Add the left side
                result += encode(leftSide,"LLLL");
                //Add the middle bits
                result += middleBin;
                //Add the right side
                result += encode(rightSide,"RRRR");
                //Add the end bits
                result += endBin;
                return result;
            }
            //Convert a numberarray to the representing
            function encode(number,struct){
                //Create the variable that should be returned at the end of the function
                var result = "";
                //Loop all the numbers
                for(var i = 0;i<number.length;i++){
                    //Using the L, G or R encoding and add it to the returning variable
                    if(struct[i]==="L"){
                        result += Lbinary[number[i]];
                    }
                    else if(struct[i]==="R"){
                        result += Rbinary[number[i]];
                    }
                }
                return result;
            }
            //Calulate the checksum digit
            function checksum(number){
                var result = 0;
                for(var i=0;i<7;i+=2){result+=parseInt(number[i])*3}
                for(var i=1;i<7;i+=2){result+=parseInt(number[i])}
                return ((10 - (result % 10)) % 10);
            }
            function valid(number){
                if(number.search(regexp)===-1){
                    return false;
                }
                else{
                    number = number.substring(0,7)
                    number += checksum(number);
                    return number;
                }
            }
        }

        {/literal}
    </script>
    <script>
{literal}
        // hub.js
        // James Staub
        // Nashville Public Library
        // 20150402
        hub = function(svgid, content, libraryName) {
        // OPTIONS
            options = {
            // MEDIABANK LABEL: diameterLabel=7/4in=126px; diameterBarcode=23/16in=103.5px, centerQuiet=3/4in=54px, text=1/8in=9px; paddingLabel=1/8in=9px
            // BAYSCAN LABEL: diameter=1.5"=108px, centerquiet=.75"=54
            // padding unknown=.25"=18px
            diameterLabel: 1.75,
            //              paddingLabel: .125,
            paddingLabel: 0,
            diameterBarcode: 1.4375,
            centerQuiet: .75,
            fontSize: 6/72,
            format: "EAN8",
            displayValue: true,
            fontFamily: "Lucida Sans",
            //              fontFamily: "monospace",
            backgroundColor:"#fff",
            lineColor:"#000",
            libraryName: libraryName
        };
        options.fontSize = parseFloat(options.fontSize);
        // ENCODE THE NUMERAL in EAN-8 BINARY
        var encoder = new window[options.format](content);
        var binary = encoder.encoded();
        // GIVE THE SVG HEIGHT AND WIDTH AND SET INCHES AS THE DEFAULT UNIT
        var svg = document.getElementById(svgid);
        svg.setAttribute("height", options.diameterLabel + options.paddingLabel + "in");
        svg.setAttribute("width", options.diameterLabel + options.paddingLabel + "in");
        svg.setAttribute("viewBox", "0,0," + (options.diameterLabel + options.paddingLabel) + "," + (options.diameterLabel + options.paddingLabel));
        // DRAW THE LIBRARY NAME ON TOP
        var defs = document.createElementNS("http://www.w3.org/2000/svg", "defs");
        svg.appendChild(defs);
        var pathTop = document.createElementNS("http://www.w3.org/2000/svg", "path");
        pathTop.setAttribute("id", "pathTop");
        pathTop.setAttribute("d","M " + (options.paddingLabel/2 + options.fontSize*1.5) + "," + (options.diameterLabel/2 + options.paddingLabel/2) + " A " + (options.diameterLabel/2 - options.fontSize*1.5) + "," + (options.diameterLabel/2 - options.fontSize*1.5) + " 1 0,1 " + (options.diameterLabel - options.paddingLabel/2 - options.fontSize*1.5) + "," + (options.diameterLabel/2 - options.paddingLabel/2));
        pathTop.setAttribute("stroke", options.lineColor);
        //      pathTop.setAttribute("stroke-width", 1);
        defs.appendChild(pathTop);
        var textTop = document.createElementNS("http://www.w3.org/2000/svg", "text");
        textTop.setAttribute("font-size", options.fontSize);
        textTop.setAttribute("text-anchor", "middle");
        svg.appendChild(textTop);
        var textPathTop = document.createElementNS("http://www.w3.org/2000/svg", "textPath");
        textPathTop.setAttributeNS("http://www.w3.org/1999/xlink", "xlink:href","#pathTop");
        textPathTop.setAttribute("startOffset", "50%");
        textPathTop.setAttribute("font-family", options.fontFamily);
        textPathTop.textContent = options.libraryName;
        textTop.appendChild(textPathTop);
        // DRAW THE BARCODE NUMERAL ON BOTTOM
        var pathBottom = document.createElementNS("http://www.w3.org/2000/svg", "path");
        pathBottom.setAttribute("id", "pathBottom");
        pathBottom.setAttribute("d","M " + (options.paddingLabel/2 + options.fontSize*.75) + "," + (options.diameterLabel/2 + options.paddingLabel/2) + " A " + (options.diameterLabel/2 - options.fontSize*.75) + "," + (options.diameterLabel/2 - options.fontSize*.75) + " 1 0,0 " + (options.diameterLabel - options.paddingLabel/2 - options.fontSize*.75) + "," + (options.diameterLabel/2 - options.paddingLabel/2));
        pathBottom.setAttribute("stroke", options.lineColor);
        defs.appendChild(pathBottom);
        var textBottom = document.createElementNS("http://www.w3.org/2000/svg", "text");
        textBottom.setAttribute("font-size", options.fontSize);
        textBottom.setAttribute("text-anchor", "middle");
        svg.appendChild(textBottom);
        var textPathBottom = document.createElementNS("http://www.w3.org/2000/svg", "textPath");
        textPathBottom.setAttributeNS("http://www.w3.org/1999/xlink", "xlink:href","#pathBottom");
        textPathBottom.setAttribute("dominant-baseline", "central");
        textPathBottom.setAttribute("startOffset", "50%");
        textPathBottom.setAttribute("font-family", options.fontFamily);
        textPathBottom.textContent = content;
        textBottom.appendChild(textPathBottom);
// DRAW THE BARCODE NUMERAL THROUGH THE CENTER
        var textCenter = document.createElementNS("http://www.w3.org/2000/svg", "text");
        textCenter.setAttribute("font-size", options.centerQuiet/7);
        textCenter.setAttribute("x", ((options.diameterLabel + options.paddingLabel)/2));
        textCenter.setAttribute("y", ((options.diameterLabel + options.paddingLabel)/2 + options.centerQuiet/7/2));
        textCenter.setAttribute("font-family", options.fontFamily);
        textCenter.setAttribute("text-anchor", "middle");
        textCenter.textContent = content;
        svg.appendChild(textCenter);
// DRAW THE CIRCULAR BARCODE
        var r = options.centerQuiet/2;
        var strokeWidth = ((options.diameterBarcode - options.centerQuiet)/2) / 67; //67 bits for the EAN8, ? 3 extra for padding outer buffer
        for(var i=0;i<binary.length;i++){
            r += strokeWidth;
            if(binary[i] === "1"){
                var circle = document.createElementNS("http://www.w3.org/2000/svg", 'circle'); //Create a circle in SVG's namespace
                circle.setAttribute("cx", (options.diameterLabel + options.paddingLabel)/2);
                circle.setAttribute("cy", (options.diameterLabel + options.paddingLabel)/2);
                circle.setAttribute("r", r);
                circle.setAttribute("stroke", options.lineColor);
                circle.setAttribute("stroke-width", strokeWidth);
                circle.setAttribute("fill-opacity", 0);
                svg.appendChild(circle);
            }
        }
    }
{/literal}
    </script>
    <script>
{literal}
        $(document).ready(function() {
            $("#barcodeGenerator").validate({
                rules: {
                    start: {
                        required: true,
                        number: true,
                        min: 1000000,
                        max: 9999999
                    },
                    count: {
                        required: true,
                        number: true,
                        min: 1
                    }
                },
                submitHandler: function(form) {
                    makeBarcodes();
                    return false; // Prevent actual form submission
                }
            });
        });

        function makeBarcodes() {
            var libraryName = document.getElementById("libraryName").value;
            var start = document.getElementById("start").value;
            start = +start;
            var count = document.getElementById("count").value;
            count = +count;
            var printdiv = document.getElementById("printish");
            for (i=start; i<start+count; i++) {
                var svgdiv = document.createElement("div");
                printdiv.appendChild(svgdiv);
                var svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
                svg.setAttribute("id", "svg"+i);
                svgdiv.appendChild(svg);
                hub(svg.id,i,libraryName);
            }
        }
{/literal}
    </script>
    <style>
        @media print {
            #footer-container, #header-wrapper, #horizontal-menu-bar-wrapper, #side-bar, #system-message-header, .breadcrumbs {
                display: none;
            }

            #formish {
                display: none;
            }
        }
    </style>
    <div id="formish">
        <h1>{translate text="Disc Barcode Generator" isAdminFacing=true}</h1>
        <p>Print disc hub circular EAN-8 barcodes for each number supplied</p>
        <form id="barcodeGenerator">
            <label for="libraryName">Library Name: </label> <input type="text" name="libraryName" id="libraryName" value="{$librarySystemName|escape}">
            <br><label for="start">Start barcode: </label> <input type="number" name="start" id="start" min="1000000" max="9999999">
            <br><label for="count">How many: </label> <input type="number" name="count" id="count" min="1" value="10">
            <br><input type="submit" name="submit" value="Submit" class="btn btn-sm btn-primary">
            &nbsp;<input type="button" name="printLabels" value="Print Labels" class="btn btn-sm btn-primary" onclick="{literal} window.print(); {/literal}" />
        </form>
    </div>
    <div id="printish">
    </div>
{/strip}