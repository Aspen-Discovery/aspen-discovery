{strip}
	{if $materialDescription}
		<div class="row">
			<div class="result-label col-sm-4">Material Description: </div>
			<div class="result-value col-sm-8">
				{$materialDescription}
			</div>
		</div>
	{/if}
	{if $materials}
		<div class="row">
			<div class="result-label col-sm-4">Materials: </div>
			<div class="result-value col-sm-8">
				{foreach from=$materials item="material"}
					{if $material.link}<a href="{$material.link}">{/if}
					{$material.label}{if $material.aatID} ({$material.aatID}){/if}
					{if $material.link}</a>{/if}<br/>
				{/foreach}
			</div>
		</div>
	{/if}
	{if $stylesAndPeriods}
		<div class="row">
			<div class="result-label col-sm-4">Style/Period: </div>
			<div class="result-value col-sm-8">
				{foreach from=$stylesAndPeriods item="styleAndPeriod"}
					{if $styleAndPeriod.link}<a href="{$styleAndPeriod.link}">{/if}
					{$styleAndPeriod.label}{if $styleAndPeriod.aatID} ({$styleAndPeriod.aatID}){/if}
					{if $styleAndPeriod.link}</a>{/if}<br/>
				{/foreach}
			</div>
		</div>
	{/if}
	{if $techniques}
		<div class="row">
			<div class="result-label col-sm-4">Techniques: </div>
			<div class="result-value col-sm-8">
				{foreach from=$techniques item="technique"}
					{if $technique.link}<a href="{$technique.link}">{/if}
					{$technique.label}{if $technique.aatID} ({$technique.aatID}){/if}
					{if $technique.link}</a>{/if}<br/>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $measurements}
		<div class="row">
			<div class="result-label col-sm-4">Measurements: </div>
			<div class="result-value col-sm-8">
				{foreach from=$measurements item="measurement"}
					{$measurement}<br/>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $installations}
		<div class="row">
			<div class="result-label col-sm-4">Installations: </div>
			<div class="result-value col-sm-8">
				{foreach from=$installations item="installation"}
					{$installation}<br/>
				{/foreach}
			</div>
		</div>
	{/if}
{/strip}