{strip}
	<h1 class="notranslate">
		{$library->displayName}
	</h1>


	<div class="row">
		<div class="result-label col-sm-4">{translate text='Branches' isPublicFacing=true}</div>
		<div class="col-sm-8 result-value">
			<ul>
				{foreach from=$branches item=branch}
					<li><a href="{$branch.link}">{$branch.name}</a></li>
				{/foreach}
			</ul>
		</div>
	</div>
{/strip}