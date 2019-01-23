{strip}
	<div id="main-content">
		<h1>{$shortPageTitle}</h1>
		<a class="btn btn-sm btn-default" href='/Admin/TranslationMaps?objectAction=list'>Return to List</a>
		<p>
			{foreach from=$translationMapValues item=translationMapValue}
				{$translationMapValue->value} = {$translationMapValue->translation}<br/>
			{/foreach}
		</p>
	</div>
{/strip}