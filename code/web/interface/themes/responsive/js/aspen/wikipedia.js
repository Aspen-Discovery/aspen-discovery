AspenDiscovery.Wikipedia = (function(){
	return{
		getWikipediaArticle: function(articleName){
			var url = Globals.path + "/Author/AJAX?method=getWikipediaData&articleName=" + articleName;
			$.getJSON(url, function(data){
				if (data.success) {
					// noinspection JSUnresolvedVariable
					$("#wikipedia_placeholder").html(data.formatted_article).fadeIn();
				}
			});
		}
	};
}(AspenDiscovery.Wikipedia));