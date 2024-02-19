{strip}
	<div class="row">
		<div class="col-xs-12">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>
	{if !empty($stopResults)}
		<div class="row">
			<div class="col-xs-12">
				<div class="alert alert-info">{$stopResults}</div>
			</div>
		</div>
	{/if}
	{if isset($runningProcesses)}
		<form action="" method="post" id='indexerInformationForm' class="form-inline">
			<div class="row">
				<div class="col-xs-12">
					<div class="alert alert-info">{translate text="This tool can be used to view information about the indexers running on the system." isAdminFacing=true}</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-1">
					<strong>{translate text='Select' isAdminFacing=true}</strong>
				</div>
				<div class="col-xs-2">
					<strong>{translate text="PID" isAdminFacing=true}</strong>
				</div>
				<div class="col-xs-3">
					<strong>{translate text="Name" isAdminFacing=true}</strong>
				</div>
				<div class="col-xs-2">
					<strong>{translate text="Start Time" isAdminFacing=true}</strong>
				</div>
				<div class="col-xs-2">
					<strong>{translate text="Notes" isAdminFacing=true}</strong>
				</div>
			</div>
			<div class="processBody striped">
				{foreach from=$runningProcesses item=process}
					<div class="row processRow">
						<div class="col-xs-1">
							{if !array_key_exists($process.pid,$processesToStop)}
								<input type="checkbox" class="selectedObject" name="selectedProcesses[{$process.pid}]" aria-label="Select Process {$process.pid}">
							{/if}
						</div>
						<div class="col-xs-2">
							{$process.pid}
						</div>
						<div class="col-xs-3">
							{$process.name}
						</div>
						<div class="col-xs-2">
							{$process.startTime}
						</div>
						<div class="col-xs-2">
							{if array_key_exists($process.pid,$processesToStop)}
								{assign var=pid value=$process.pid}
								{assign var=processToStop value=$processesToStop.$pid}
								{translate text="Process marked to be stopped at %1%" isAdminFacing=true 1=$processToStop->getFormattedDateSet()}
							{/if}
						</div>
					</div>
				{/foreach}
			</div>
			<div class="btn-group btn-group-sm">
				<button name="stopProcesses" type='submit' value='stopProcess' class="btn btn-sm btn-danger">{translate text='Stop Selected Processes' isAdminFacing=true}</button>
			</div>
		</form>
		<br/>
	{else}
		<p>{translate text="There are no running indexers" isAdminFacing=true}</p>
	{/if}
{/strip}