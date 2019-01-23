{strip}
	<div class="col-xs-12">
		{* Search Navigation *}
		{include file="Archive/search-results-navigation.tpl"}
		<h2>
			{$title}
			{*{$title|escape} // plb 3/8/2017 not escaping because some titles use &amp; *}
		</h2>
		<div class="row">
			<div class="col-xs-4 col-sm-5 col-md-4 col-lg-3 text-center">
				<div class="main-project-image">
					<img src="{$medium_image}" class="img-responsive"/>
				</div>
			</div>
			<div id="main-content" class="col-xs-8 col-sm-7 col-md-8 col-lg-9">
				{if $genealogyData || $birthDate || $deathDate}
					{if $genealogyData->otherName}
						<div class='personDetail'><span class='result-label'>Other Names: </span><span class='personDetailValue'>{$genealogyData->otherName|escape}</span></div>
					{/if}
					{if $birthDate}
						<div class='personDetail'><span class='result-label'>Birth Date: </span><span class='personDetailValue'>{$birthDate}</span></div>
					{/if}
					{if $deathDate}
						<div class='personDetail'><span class='result-label'>Death Date: </span><span class='personDetailValue'>{$deathDate}</span></div>
					{/if}
					{if $genealogyData->ageAtDeath}
						<div class='personDetail'><span class='result-label'>Age at Death: </span><span class='personDetailValue'>{$genealogyData->ageAtDeath|escape}</span></div>
					{/if}
					{if $genealogyData->sex}
						<div class='personDetail'><span class='result-label'>Sex: </span><span class='personDetailValue'>{$genealogyData->sex|escape}</span></div>
					{/if}
					{if $genealogyData->race}
						<div class='personDetail'><span class='result-label'>Race: </span><span class='personDetailValue'>{$genealogyData->race|escape}</span></div>
					{/if}
					{if $genealogyData->causeOfDeath}
						<div class='personDetail'><span class='result-label'>Cause of Death: </span><span class='personDetailValue'>{$genealogyData->causeOfDeath|escape}</span></div>
					{/if}
				{/if}
			</div>
		</div>

		{include file="Archive/metadata.tpl"}
	</div>
{/strip}
<script type="text/javascript">
	$().ready(function(){ldelim}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
		{rdelim});
</script>