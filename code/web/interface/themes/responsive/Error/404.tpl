{strip}
	<h1>{translate text="Page Not Found" isPublicFacing=true}</h1>
	<p><strong>{translate text="We're sorry, but the page you are looking for can't be found." isPublicFacing=true}</strong></p>

	<ul>
		<li><a href="/Search/Home">{translate text="Browse the catalog" isPublicFacing=true}</a></li>
		<li>{translate text="Search the catalog" isPublicFacing=true}</li>
		{if !empty($homeLink)}
			<li><a href="{$homeLink}">{translate text="Visit the library's website" isPublicFacing=true}</a></li>
		{/if}
	</ul>
{/strip}