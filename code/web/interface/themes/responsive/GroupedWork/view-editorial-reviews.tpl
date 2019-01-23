{strip}
	{foreach from=$editorialReviews item=editorialReview}
		<div class='review'>
		{if $editorialReview->title}
			<h4 class='reviewSource'>{$editorialReview->title}</h4>
		{/if}
		<div>
			<p class="reviewContent">{$editorialReview->review}</p>
			<div class='reviewCopyright'><small>{$editorialReview->source}</small></div>
		</div>
	{foreachelse}
		<p>No reviews currently exist.</p>
	{/foreach}

	{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles))}
		<div>
			<a class="btn btn-sm" href='{$path}/EditorialReview/Edit?recordId={$id}'>Add Editorial Review</a>
		</div>
	{/if}
{/strip}