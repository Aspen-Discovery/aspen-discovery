{strip}
	<form enctype="multipart/form-data" name="previewRelatedCover" id="previewRelatedCover" method="post" action="/GroupedWork/AJAX">
		<input type="hidden" name="recordId" value="{$recordId}"/>
		<input type="hidden" name="groupedWorkId" value="{$groupedWorkId}"/>
		<input type="hidden" name="recordType" value="{$recordType}"/>
		<input type="hidden" name="method" value="previewRelatedCover"/>
		<div class="form-group">
			<!-- getBookcover from $recordId -->
			<div id="recordCover" class="text-center row">
				{literal}{/literal}
				<img alt="{translate text='Book Cover' inAttribute=true}" class="img-thumbnail" src="/bookcover.php?id={$recordId}&size=medium&type={$recordType}">
			</div>

		</div>
	</form>
	<script type="application/javascript">
		{literal}
		$("#previewRelatedCover").validate({
			submitHandler: function(){
				AspenDiscovery.GroupedWork.previewRelatedCover("{/literal}{$recordId}{literal}","{/literal}{$groupedWorkId}{literal}","{/literal}{$recordType}{literal}")
			}
		});
		$(document).on('change', ':file', function() {
			var input = $(this);
			var label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
			$("#selected-cover-label").val(label);
		});
		{/literal}
	</script>
{/strip}