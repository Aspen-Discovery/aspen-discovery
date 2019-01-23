{strip}
	{foreach from=$academicRecords item=academicRecord}

			{if $academicRecord.academicPosition}
				<div class="row">
					<div class="result-label col-sm-12">
					{$academicRecord.academicPosition.title}
						{if $academicRecord.academicPosition.employer}
							&nbsp;{if $academicRecord.academicPosition.employer.link}<a href="{$academicRecord.academicPosition.employer.link}">{/if}{$academicRecord.academicPosition.employer.label}{if $academicRecord.academicPosition.employer.link}</a>{/if}
						{/if}
						{if $academicRecord.academicPosition.startDate || $academicRecord.academicPosition.endDate}
							&nbsp;({$academicRecord.academicPosition.startDate} - {$academicRecord.academicPosition.endDate})
						{/if}
					</div>
				</div>
			{/if}

			{if $academicRecord.researchInterests}
				<div class="row">
					<div class="result-label col-sm-4">
						Research Interests:
					</div>
					<div class="result-value col-sm-8">
						{implode subject=$academicRecord.researchInterests}
					</div>
				</div>
			{/if}
			{if $academicRecord.cvLink}
				<div class="row">
					<div class="result-value col-sm-8">
						<a href="{$academicRecord.cvLink}">Curriculum Vitae</a>
					</div>
				</div>
			{/if}
			{if $academicRecord.honorsAwards}
				<div class="row">
					<div class="result-label col-sm-4">
						Honors and Awards:
					</div>
					<div class="result-value col-sm-8">
						{implode subject=$academicRecord.honorsAwards}
					</div>
				</div>
			{/if}
			{if count($academicRecord.publications)}
				{foreach from=$academicRecord.publications item=publication}

					<div class="row">
						<div class="result-label col-sm-4">
							Published in:
						</div>
						<div class="result-value col-sm-8">
							{if $publication.link}<a href='{$publication.link}'>{/if}
								{$publication.label}
							{if $publication.link}</a>{/if}
						</div>
					</div>
				{/foreach}
			{/if}
			{if count($academicRecord.education)}
				{foreach from=$academicRecord.education item=education}

					<div class="row">
						<div class="result-label col-sm-4">
							Degree:
						</div>
						<div class="result-value col-sm-8">
							{if $education.degreeName}{$education.degreeName}{/if}
							{if $education.degreeGrantor}
								{if $education.degreeName}&nbsp;from&nbsp;{/if}
								{if $education.degreeGrantor.link}<a href='{$education.degreeGrantor.link}'>{/if}
								{$education.degreeGrantor.label}
								{if $education.degreeGrantor.link}</a>{/if}
							{/if}
							{if $education.graduationDate} {$education.graduationDate}{/if}
						</div>
					</div>
				{/foreach}
			{/if}
						{*
			{if $degreeName}
				<div class="row">
					<div class="result-label col-sm-12">Degree Name: </div>
					<div class="result-value col-sm-8">
						{$degreeName}
					</div>
				</div>
			{/if}
			{if $graduationDate}
				<div class="row">
					<div class="result-label col-sm-4">Graduation Date: </div>
					<div class="result-value col-sm-8">
						{$graduationDate}
					</div>
				</div>
			{/if}
			{foreach from=$educationPeople item="educationPerson"}
				<div class="row">
					<div class="result-label col-sm-4">
						{$educationPerson.role}:
					</div>
					<div class="result-value col-sm-8">
						{if $educationPerson.link}
							<a href='{$educationPerson.link}'>
								{$educationPerson.label}
							</a>
						{else}
							{$educationPerson.label}
						{/if}
					</div>
				</div>
			{/foreach}
		*}
		<br/>
	{/foreach}
{/strip}