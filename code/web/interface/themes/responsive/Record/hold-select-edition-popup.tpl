{strip}
	<div class="alert alert-info">{translate text="This edition is currently checked out. Are you interested in requesting a different edition that may be available faster?" isPublicFacing=true}</div>
	{include file="GroupedWork/relatedRecords.tpl" relatedRecords=$relatedManifestation->getRelatedRecords() relatedManifestation=$relatedManifestation inPopUp=true promptAlternateEdition=true}
{/strip}