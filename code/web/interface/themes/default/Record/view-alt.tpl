<div id="page-content" class="content">

	<div class="record">
		<a href="{$path}/{$activeRecordProfileModule}/{$id|escape:"url"}/Home" class="backtosearch">&laquo; {translate text="Back to Record"}</a>

		{if $pageTitle}<h1>{$pageTitle}</h1>{/if}
		{include file="Record/$subTemplate"}

	</div>

</div>
