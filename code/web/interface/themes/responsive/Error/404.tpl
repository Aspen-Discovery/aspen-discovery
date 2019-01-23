{strip}
	<h1>Page Not Found</h1>
	<p><strong>We're sorry, but the page you are looking for can't be found.</strong></p>
	{if $homeLink}
		<p>Try <a href="/Search/Home">browsing the catalog</a>, searching the catalog, or visiting the <a href="{$homeLink}">library's website</a>.</p>
	{else}
		<p>Try <a href="/Search/Home">browsing the catalog</a> or searching the catalog.</p>
	{/if}
{/strip}