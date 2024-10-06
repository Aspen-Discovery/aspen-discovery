{strip}
<form name="selectHoldCandidateForm" id="selectHoldCandidateForm" method="get" action="/MaterialsRequest/AJAX">
	<input type="hidden" name="method" value="selectHoldCandidate"/>
	<input type="hidden" name="requestId" value="{$requestId}"/>
	<div class="form-group">
		<div class="input-group">
			<table class="table table-responsive table-condensed">
				<thead>
					<tr>
						<th></th>
						<th>{translate text="Source" isAdminFacing=true}</th>
						<th>{translate text="ID" isAdminFacing=true}</th>
						<th>{translate text="Cover" isAdminFacing=true}</th>
						<th>{translate text="Title" isAdminFacing=true}</th>
						<th>{translate text="Author" isAdminFacing=true}</th>
						<th>{translate text="Format" isAdminFacing=true}</th>
						<th>{translate text="Link" isAdminFacing=true}</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$holdCandidates item=holdCandidate}
						<tr>
							<td><input type="radio" name="holdCandidateId" value="{$holdCandidate->id}" id="holdCandidateId{$holdCandidate->id}" aria-label="{translate text='Select %1% %2%' 1=$holdCandidate->source 2=$holdCandidate->sourceId inAttribute=true}" alt="{translate text='Select %1% %2%' 1=$holdCandidate->source 2=$holdCandidate->sourceId inAttribute=true}"></td>
							<td>{$holdCandidate->source}</td>
							<td>{$holdCandidate->sourceId}</td>
							<td>{if !empty($holdCandidate->getBookcoverUrl())}<img src="{$holdCandidate->getBookcoverUrl()}">{/if}</td>
							<td>{$holdCandidate->getTitle()}</td>
							<td>{$holdCandidate->getAuthor()}</td>
							<td>{$holdCandidate->getFormat()}</td>
							<td>{if !empty($holdCandidate->getLink())}<a href="{$holdCandidate->getLink()}" target="_blank">{translate text="View" isAdminFacing=true}</a>{/if}</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
</form>
{/strip}