{if $error}
	<div class="alert alert-danger">{$error}</div>
{else}

<form id="materialsRequestUpdateForm" action="/MaterialsRequest/Update" method="post" class="form form-horizontal">
	{include file="MaterialsRequest/request-form-fields.tpl"}

</form>

<script type="text/javascript">
VuFind.MaterialsRequest.authorLabels = {$formatAuthorLabelsJSON};
VuFind.MaterialsRequest.specialFields = {$specialFieldFormatsJSON};
VuFind.MaterialsRequest.setFieldVisibility();
$("#materialsRequestForm").validate();
</script>

{/if}