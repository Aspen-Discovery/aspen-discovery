{strip}

	{* Local Identifier *}
	{if count($identifier) > 0}
		<div class="row">
			<div class="result-label col-sm-4">Local Identifier{if count($identifier) > 1}s{/if}: </div>
			<div class="result-value col-sm-8">
				{implode subject=$identifier glue=', '}
			</div>
		</div>
	{/if}

	{* Physical Location *}
	{if !empty($physicalLocation)}
		<div class="row">
			<div class="result-label col-sm-4">Located at: </div>
			<div class="result-value col-sm-8">
				{foreach from=$physicalLocation item=location}
					{if $location}
						<div>{$location}</div>
					{/if}
				{/foreach}
			</div>
		</div>
	{/if}

	{* Shelf Locator *}
	{if !empty($shelfLocator)}
		<div class="row">
			<div class="result-label col-sm-4">Shelf Locator: </div>
			<div class="result-value col-sm-8">
				{foreach from=$shelfLocator item=location}
					{if $location}
						<div>{$location}</div>
					{/if}
				{/foreach}
			</div>
		</div>
	{/if}

	<div class="row">
		<div class="result-label col-sm-4">Item PID: </div>
		<div class="result-value col-sm-8">
			{$pid}
		</div>
	</div>

	{if $collectionInfo}
		<div class="row">
			<div class="result-label col-sm-4">Collection PID: </div>
			<div class="result-value col-sm-8">
				{foreach from=$collectionInfo item="collection"}
					<a href="{$collection.link}">{$collection.pid}</a> ({$collection.label})<br/>
				{/foreach}
			</div>
		</div>
	{/if}

	{* Names *}
	{if $familyName}
		<div class="row">
			<div class="result-label col-sm-4">Family Name: </div>
			<div class="result-value col-sm-8">
				{$familyName}
			</div>
		</div>
	{/if}
	{if $givenName}
		<div class="row">
			<div class="result-label col-sm-4">Given Name: </div>
			<div class="result-value col-sm-8">
				{$givenName}
			</div>
		</div>
	{/if}
	{if $middleName}
		<div class="row">
			<div class="result-label col-sm-4">Middle Name: </div>
			<div class="result-value col-sm-8">
				{$middleName}
			</div>
		</div>
	{/if}
	{if $maidenNames}
		<div class="row">
			<div class="result-label col-sm-4">Maiden Name{if count($maidenNames) > 1}s{/if}: </div>
			<div class="result-value col-sm-8">
				{implode subject=$maidenNames}
			</div>
		</div>
	{/if}

	{if $alternateNames}
		<div class="row">
			<div class="result-label col-sm-4">Alternate Name{if count($alternateNames) > 1}s{/if}: </div>
			<div class="result-value col-sm-8">
				{implode subject=$alternateNames}
			</div>
		</div>
	{/if}

	{* Migration information *}
	{if $migratedFileName}
		<div class="row">
			<div class="result-label col-sm-4">Migrated Filename: </div>
			<div class="result-value col-sm-8">
				{$migratedFileName}
			</div>
		</div>
	{/if}

	{if $migratedIdentifier}
		<div class="row">
			<div class="result-label col-sm-4">Migrated Identifier: </div>
			<div class="result-value col-sm-8">
				{$migratedIdentifier}
			</div>
		</div>
	{/if}

	{if $contextNotes}
		<div class="row">
			<div class="result-label col-sm-4">Migration Context Notes: </div>
			<div class="result-value col-sm-8">
				{$contextNotes}
			</div>
		</div>
	{/if}

	{if $relationshipNotes}
		<div class="row">
			<div class="result-label col-sm-4">Migration Relationship Notes: </div>
			<div class="result-value col-sm-8">
				{$relationshipNotes}
			</div>
		</div>
	{/if}

	{* Record Origin Info *}
	{if $recordOrigin}
		<div class="row">
			<div class="result-label col-sm-4">Entered By: </div>
			<div class="result-value col-sm-8">
				{$recordOrigin}
			</div>
		</div>
	{/if}
	{if $recordCreationDate}
		<div class="row">
			<div class="result-label col-sm-4">Entered On: </div>
			<div class="result-value col-sm-8">
				{$recordCreationDate}
			</div>
		</div>
	{/if}
	{if $recordChangeDate}
		<div class="row">
			<div class="result-label col-sm-4">Last Changed: </div>
			<div class="result-value col-sm-8">
				{$recordChangeDate}
			</div>
		</div>
	{/if}

{/strip}