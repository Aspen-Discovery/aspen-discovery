AspenDiscovery.Authors = (function(){
    return{
        loadEnrichmentInfo: function(id){
            let url = Globals.path + "/Author/AJAX?method=getEnrichmentInfo&workId=" + id;
            $.getJSON(url, function(data){
                let similarAuthorsNovelist = data.similarAuthorsNovelist;
                if (similarAuthorsNovelist && similarAuthorsNovelist.length > 0){
                    $("#similar-authors-placeholder-sidebar").html(similarAuthorsNovelist);
                    $("#similar-authors").fadeIn();
                    $('#similar-authors [data-toggle="tooltip"]').tooltip();
                }
            });
        }
    };
}(AspenDiscovery.Authors));