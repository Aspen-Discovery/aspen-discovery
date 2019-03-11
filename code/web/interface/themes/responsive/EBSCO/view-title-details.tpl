{strip}

	{* Display more information about the title*}
	{if $recordDriver->getAuthor()}
		<div class="row">
			<div class="result-label col-md-3">Author: </div>
			<div class="col-md-9 result-value">
				<a href='{$path}/Author/Home?author="{$recordDriver->getAuthor()|escape:"url"}"'>{$recordDriver->getAuthor()|highlight}</a>
			</div>
		</div>
	{/if}

	{if $showPublicationDetails && $recordDriver->getPublicationDetails()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Published'}:</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getPublicationDetails() glue=", "}
			</div>
		</div>
	{/if}

	{if $showPhysicalDespriptions && $recordDriver->getPhysicalDescriptions()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Physical Description'}:</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getPhysicalDescriptions() glue=", "}
			</div>
		</div>
	{/if}

	{if $showFormats}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Format'}:</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getFormats() glue=", "}
			</div>
		</div>
	{/if}

	{if $showEditions && $recordDriver->getEditions()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Edition'}:</div>
			<div class="col-md-9 result-value">
				{implode subject=$recordDriver->getEditions() glue=", "}
			</div>
		</div>
	{/if}

	{if $recordDriver->getAllSubjectHeadings()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Subjects'}:</div>
			<div class="col-md-9 result-value">
				{foreach from=$recordDriver->getAllSubjectHeadings() item=subject}
					<div>
					{$subject}
					</div>
				{/foreach}
			</div>
		</div>
	{/if}
{/strip}