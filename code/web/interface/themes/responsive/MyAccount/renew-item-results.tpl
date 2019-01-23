{strip}
<div class="contents">
	{if $renewResults.success}
		<div class="alert alert-success">{$renewResults.message}</div>
	{else}
		<div class='alert alert-danger'>{$renewResults.message}</div>
	{/if}
</div>


{* old version of this form in Record. Didn't find any other references to it. plb 1-28-2015
<div id='renew_results'>
	<div class='hold_result_title header'>
		Renewal Results
		<a href="#" onclick='hideLightbox();return false;' class="closeIcon">Close <img src="{$path}/images/silk/cancel.png" alt="close" /></a>
	</div>
	<div class = "content">
		<ol>
		{foreach from=$renew_results item=renewalResult}
			<li class='{if $renewalResult.success == true}renewPassed{else}renewFailed{/if}'>{$renewalResult.title} - {$renewalResult.message}</li>
		{/foreach}
		</ol>
	</div>
</div>
*}
{/strip}