VuFind.Responsive = (function(){
	$(function(){
		// Attach Responsive Actions to window resizing
		/*$(window).resize(function(){
			VuFind.Responsive.adjustLayout();
		});

		try{
			var mainContentElement = $("#main-content-with-sidebar");
			mainContentElement.resize(function(){
				console.log("main content resized");
				VuFind.Responsive.adjustLayout();
			});
			var sidebarContentElem = $("#sidebar-content");
			sidebarContentElem.resize(function(){
				console.log("sidebar resized ");
				VuFind.Responsive.adjustLayout();
			});
			console.log("resize listeners setup ");
		}catch(err){
			console.log("Could not setup resize sensors " + err);
			//Ignore errors if main content doesn't exist
		}

		$().ready(
			function(){
				VuFind.Responsive.adjustLayout();
			}
		);*/

		// auto adjust the height of the search box
		// (Only side bar search box for now)
		$('#lookfor', '#home-page-search').on( 'keyup', function (event ){
			$(this).height( 0 );
			if (this.scrollHeight < 32){
				$(this).height( 18 );
			}else{
				$(this).height( this.scrollHeight );
			}
		}).keyup(); //This keyup triggers the resize

		$('#lookfor').on( 'keydown', function (event ){
			if (event.which == 13 || event.which == 10){
				event.preventDefault();
				event.stopPropagation();
				$("#searchForm").submit();
				return false;
			}
		}).on( 'keypress', function (event ){
			if (event.which == 13 || event.which == 10){
				event.preventDefault();
				event.stopPropagation();
				return false;
			}
		})
	});

	try{
		var mediaQueryList = window.matchMedia('print');
		mediaQueryList.addListener(function(mql) {
			VuFind.Responsive.isPrint = mql.matches;
			//VuFind.Responsive.adjustLayout();
			//console.log("The site is now print? " + VuFind.Responsive.isPrint);
		});
	}catch(err){
		//For now, just ignore this error.
	}

	window.onbeforeprint = function() {
		VuFind.Responsive.isPrint = true;
		//VuFind.Responsive.adjustLayout();
	};


	return {
		originalSidebarHeight: -1,
		adjustLayout: function(){
			// get resolution
			var resolutionX = document.documentElement.clientWidth;

			if (resolutionX >= 768 && !VuFind.Responsive.isPrint) {
				//Make the sidebar and main content the same size
				var mainContentElement = $("#main-content-with-sidebar");
				var sidebarContentElem = $("#sidebar-content");

				if (VuFind.Responsive.originalSidebarHeight == -1){
					VuFind.Responsive.originalSidebarHeight = sidebarContentElem.height();
				}
				//var heightToTest = Math.min(sidebarContentElem.height(), VuFind.Responsive.originalSidebarHeight);
				var heightToTest = sidebarContentElem.height();
				var maxHeight = Math.max(mainContentElement.height() + 15, heightToTest);
				if (mainContentElement.height() + 15 != maxHeight){
					mainContentElement.height(maxHeight);
				}
				if (sidebarContentElem.height() != maxHeight){
					sidebarContentElem.height(maxHeight);
				}

				//var xsContentInsertionPointElement = $("#xs-main-content-insertion-point");
				//var mainContent;
			//	// @screen-sm-min screen resolution set in \vufind\web\interface\themes\responsive\css\bootstrap\less\variables.less
			//
			//	//move content from main-content-with-sidebar to xs-main-content-insertion-point
			//	mainContent = mainContentElement.html();
			//	if (mainContent && mainContent.length){
			//		xsContentInsertionPointElement.html(mainContent);
			//		mainContentElement.html("");
			//		VuFind.initCarousels();
			//	}
			//}else{
			//	//Sm or better resolution
			//	mainContent = xsContentInsertionPointElement.html();
			//	if (mainContent && mainContent.length){
			//		mainContentElement.html(mainContent);
			//		xsContentInsertionPointElement.html("");
			//		VuFind.initCarousels();
			//	}
			}
		}
	};
}(VuFind.Responsive || {}));