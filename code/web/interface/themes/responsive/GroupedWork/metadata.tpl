{strip}
	<meta property="title" content="{$recordDriver->getTitle()|removeTrailingPunctuation|escape}">
	<meta property="og:description" content="{$recordDriver->getDescriptionFast()|strip_tags|escape}">
	<meta property="DC.Creator" content="{$recordDriver->getPrimaryAuthor()|strip_tags|escape}">
{/strip}