{strip}
{if $error}
	<div class="alert alert-danger">{$error}</div>
{else}

<form id="materialsRequestUpdateForm" action="/MaterialsRequest/Update" method="post" class="form form-horizontal">
	{include file="MaterialsRequest/request-form-fields.tpl"}

</form>

<script type="text/javascript">
AspenDiscovery.MaterialsRequest.authorLabels = {$formatAuthorLabelsJSON};
AspenDiscovery.MaterialsRequest.specialFields = {$specialFieldFormatsJSON};
AspenDiscovery.MaterialsRequest.setFieldVisibility();
$("#materialsRequestForm").validate();
</script>

{/if}
{/strip}