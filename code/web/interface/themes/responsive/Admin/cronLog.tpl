{strip}
	<div id="main-content" class="col-md-12">
		<h1>{translate text='Cron Log' isAdminFacing=true}</h1>
		
		<div class="adminTableRegion fixed-height-table">
			<table class="adminTable table table-condensed table-hover table-condensed smallText table-sticky" aria-label="Cron Log">
				<thead>
					<tr><th>{translate text='Id' isAdminFacing=true}</th><th>{translate text='Started' isAdminFacing=true}</th><th>{translate text='Finished' isAdminFacing=true}</th><th>{translate text='Elapsed' isAdminFacing=true}</th><th>{translate text='Processes Run' isAdminFacing=true}</th><th>{translate text='Num Errors' isAdminFacing=true}</th><th>{translate text='Had Errors?' isAdminFacing=true}</th><th>{translate text='Notes' isAdminFacing=true}</th></tr>
				</thead>
				<tbody>
					{foreach from=$logEntries item=logEntry}
						<tr{if $logEntry->getHadErrors()} class="danger"{/if}>
							<td><a href="#" class="accordion-toggle collapsed" id="cronEntry{$logEntry->id}" onclick="AspenDiscovery.Admin.toggleCronProcessInfo('{$logEntry->id}');return false;">{$logEntry->id}</a></td>
							<td>{$logEntry->startTime|date_format:"%D %T"}</td>
							<td>{$logEntry->endTime|date_format:"%D %T"}</td>
							<td>{$logEntry->getElapsedTime()}</td>
							<td>{$logEntry->getNumProcesses()}</td>
							<td>{$logEntry->numErrors}</td>
							<td>{if $logEntry->getHadErrors()}{translate text='Yes' isAdminFacing=true}{else}{translate text='No' isAdminFacing=true}{/if}</td>
							<td><a href="#" onclick="return AspenDiscovery.Admin.showCronNotes('{$logEntry->id}');">{translate text='Show Notes' isAdminFacing=true}</a></td>
						</tr>
						<tr class="logEntryProcessDetails" id="processInfo{$logEntry->id}" style="display:none">
							<td colspan="7">
								<table class="logEntryProcessDetails table table-striped table-condensed">
									<thead>
										<tr><th>{translate text='Process Name' isAdminFacing=true}</th><th>{translate text='Started' isAdminFacing=true}</th><th>{translate text='End Time' isAdminFacing=true}</th><th>{translate text='Elapsed' isAdminFacing=true}</th><th>{translate text='Updates' isAdminFacing=true}</th><th>{translate text='Skipped' isAdminFacing=true}</th><th>{translate text='Errors' isAdminFacing=true}</th><th>{translate text='Notes' isAdminFacing=true}</th></tr>
									</thead>
									<tbody>
									{foreach from=$logEntry->processes() item=process}
										<tr>
											<td>{$process->processName}</td>
											<td>{$process->startTime|date_format:"%D %T"}</td>
											<td>{$process->endTime|date_format:"%D %T"}</td>
											<td>{$process->getElapsedTime()}</td>
											<td>{$process->numUpdates}</td>
											<td>{$process->numSkipped}</td>
											<td>{$process->numErrors}</td>
											<td><a href="#" onclick="return AspenDiscovery.Admin.showCronProcessNotes('{$process->id}');">{translate text='Show Notes' isAdminFacing=true}</a></td>
										</tr>
									{/foreach}
									</tbody>
								</table>
							</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>

		{if !empty($pageLinks.all)}<div class="text-center">{$pageLinks.all}</div>{/if}
	</div>
{/strip}
