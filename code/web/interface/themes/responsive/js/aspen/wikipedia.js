AspenDiscovery.Wikipedia = (() => {
	return {
		getWikipediaArticle(articleName) {
			const url = `${Globals.path}/Author/AJAX?method=getWikipediaData&articleName=${encodeURIComponent(articleName)}`;
			$.getJSON(url)
			.done((data) => {
				const { success, formatted_article, debugMessage } = data || {};
				const $placeholder = $("#wikipedia_placeholder");
				if (success && formatted_article) {
					$placeholder.html(formatted_article).fadeIn();
				} else if (debugMessage) {
					$placeholder.append(
						'<div ' + 'class="smallText text-muted" style="font-style:italic">' +
						debugMessage +
						'</div>'
					).fadeIn();
				}
			})
			.fail((jqXHR, textStatus) => {
				$("#wikipedia_placeholder")
					.html(`<div class="alert alert-danger">Failed to load article: ${textStatus}</div>`)
					.fadeIn();
			});
		}
	};
})();