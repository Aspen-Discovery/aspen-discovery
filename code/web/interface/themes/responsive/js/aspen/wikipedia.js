AspenDiscovery.Wikipedia = (() => {
	return {
		getWikipediaArticle(articleName) {
			const url = `${Globals.path}/Author/AJAX?method=getWikipediaData&articleName=${encodeURIComponent(articleName)}`;
			$.getJSON(url)
			.done((data) => {
				const { success, formatted_article, debugMessage } = data || {};
				const $placeholder = $("#wikipedia_placeholder");
				if (success && formatted_article) {
					$placeholder.html(formatted_article);
				}
				if (debugMessage) {
					$placeholder.append(
						'<div class="smallText text-muted" style="font-style:italic">' +
						debugMessage +
						'</div>'
					);
				}
				if ((success && formatted_article) || debugMessage) {
					$placeholder.fadeIn();
				}
			})
			.fail((jqXHR, textStatus) => {
				$("#wikipedia_placeholder")
					.html(`<div class="alert alert-danger">${__('Failed to load article:')} ${textStatus}</div>`)
					.fadeIn();
			});
		}
	};
})();