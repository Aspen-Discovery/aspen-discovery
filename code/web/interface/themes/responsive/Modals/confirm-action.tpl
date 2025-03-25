<!-- Confirm Action Modal: A reusable modal for confirming potentially destructive actions -->
<div class="modal fade" id="confirmActionModal" tabindex="-1" role="dialog" aria-labelledby="confirmActionModalTitle">
	<div class="modal-dialog" role="document">
		<div class="modal-content">

			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title" id="confirmActionModalTitle">Confirm Action</h4>
			</div>

			<div class="modal-body">
				<p id="confirmActionModalPrompt">Are you sure you want to perform this action?</p>
				<p id="confirmActionModalDescription" class="text-warning" style="display: none;">
					<small></small>
				</p>
			</div>

			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-danger" id="confirmActionBtn">Confirm</button>
			</div>
		</div>
	</div>
</div>
