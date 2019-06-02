AspenDiscovery.Responsive = (function(){
	$(function(){
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
			AspenDiscovery.Responsive.isPrint = mql.matches;
		});
	}catch(err){
		//For now, just ignore this error.
	}

	window.onbeforeprint = function() {
		AspenDiscovery.Responsive.isPrint = true;
	};


	return {
		originalSidebarHeight: -1,
		adjustLayout: function(){
			// get resolution
			var resolutionX = document.documentElement.clientWidth;

			if (resolutionX >= 768 && !AspenDiscovery.Responsive.isPrint) {
				//Make the sidebar and main content the same size
				var mainContentElement = $("#main-content-with-sidebar");
				var sidebarContentElem = $("#sidebar-content");

				if (AspenDiscovery.Responsive.originalSidebarHeight == -1){
					AspenDiscovery.Responsive.originalSidebarHeight = sidebarContentElem.height();
				}
				//var heightToTest = Math.min(sidebarContentElem.height(), AspenDiscovery.Responsive.originalSidebarHeight);
				var heightToTest = sidebarContentElem.height();
				var maxHeight = Math.max(mainContentElement.height() + 15, heightToTest);
				if (mainContentElement.height() + 15 != maxHeight){
					mainContentElement.height(maxHeight);
				}
				if (sidebarContentElem.height() != maxHeight){
					sidebarContentElem.height(maxHeight);
				}
			}
		}
	};
}(AspenDiscovery.Responsive || {}));