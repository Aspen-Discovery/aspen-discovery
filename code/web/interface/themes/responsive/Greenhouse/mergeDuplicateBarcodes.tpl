{strip}
	<div class="row">
		<div class="col-xs-12">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>
	{if isset($barcodesWithDuplicates)}
		<div class="row">
			<div class="col-xs-12">
				<h2>{translate text="Barcodes with Duplicates" isAdminFacing=true}</h2>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-3">
				{translate text="Barcodes" isAdminFacing=true}
			</div>
			<div class="col-xs-3">
				{translate text="Usernames" isAdminFacing=true}
			</div>
			<div class="col-xs-3">
				{translate text="Old User Id" isAdminFacing=true}
			</div>
			<div class="col-xs-3">
				{translate text="New User Id" isAdminFacing=true}
			</div>
		</div>
		{foreach from=$barcodesWithDuplicates item=barcodeInfo}
			<div class="row">
				<div class="col-xs-3">
					{$barcodeInfo.cat_username}
				</div>
				<div class="col-xs-3">
					{$barcodeInfo.usernames}
				</div>
				<div class="col-xs-3">
					{$barcodeInfo.oldUserId}
				</div>
				<div class="col-xs-3">
					{$barcodeInfo.newUserId}
				</div>
			</div>
		{/foreach}
	{/if}
	{if isset($mergeResults)}
		<div class="row">
			<div class="col-xs-12">
				<h2>{translate text="Merge Results" isAdminFacing=true}</h2>
			</div>
			<div class="col-xs-12">
				<dl>
					<dt>{translate text="Num Users Updated with New Username" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numUsersUpdated}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Users Updated and Merged" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numUsersMerged}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Lists Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numListsMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Reading History Entries Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numReadingHistoryEntriesMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Ratings & Reviews Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numRatingsReviewsMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Roles Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numRolesMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Don't Show Again Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numNotInterestedMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Linked Primary Users Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numLinkedPrimaryUsersMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Linked Users Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numLinkedUsersMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Saved Searches Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numSavedSearchesMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num System Message Dismissals Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numSystemMessageDismissalsMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Placard Dismissals Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numPlacardDismissalsMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Materials Request Created By Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numMaterialsRequestsAssignmentsMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num User Messages Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numUserMessagesMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num User Payments Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numUserPaymentsMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num User Staff Settings Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numUserStaffSettingsMoved}</dd>
				</dl>
			</div>
			{if count($mergeResults.errors) > 0}
				<div class="col-xs-12">
					<h2>Errors</h2>
				</div>
				<div class="col-xs-12">
					{foreach from=$mergeResults.errors item=error}
						<div class="alert alert-danger">
							{$error}
						</div>
					{/foreach}
				</div>
			{/if}
		</div>
	{else}
		<div class="row">
			<div class="col-xs-12">
				<div class="alert alert-info">{translate text="This tool can be used to merge barcodes that have more than one user in the database for it." isAdminFacing=true}</div>
			</div>
		</div>
		{if !empty($setupErrors)}
			<div class="row">
				<div class="col-xs-12">
					{foreach from=$setupErrors item=setupError}
						<div class="alert alert-danger">
							{$setupError}
						</div>
					{/foreach}
				</div>
			</div>
		{else}
			<form id='importForm' method="post" role="form" onsubmit="setFormSubmitting();" aria-label="{translate text="Merge Users With Duplicate Barcodes" isAdminFacing=true inAttribute=true}">
				<div class='editor'>
					<div class="row">
						<div class="col-xs-12">
							<div class="form-group">
								<button type="submit" name="submit" value="mergeUsersWithDuplicateBarcodes" class="btn btn-primary">{translate text="Merge Users With Duplicate Barcodes" isAdminFacing=true}</button>
							</div>
						</div>
					</div>
				</div>
			</form>
		{/if}
	{/if}
{/strip}