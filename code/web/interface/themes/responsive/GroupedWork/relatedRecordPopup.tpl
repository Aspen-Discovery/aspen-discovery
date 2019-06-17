{strip}
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">×</button>
		<h3 id="modal-title">Related Records</h3>
	</div>
	<div class="modal-body">
		{include file="GroupedWork/relatedRecords.tpl" inPopUp=true}
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" id="modalClose">{translate text=Close}</button>
	</div>
{/strip}