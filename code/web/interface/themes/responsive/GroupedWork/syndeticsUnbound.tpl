{strip}
	<div id="syndetics_unbound"></div>
	<script src="https://unbound.syndetics.com/syndeticsunbound/connector/initiator.php?a_id={$unboundAccountNumber}{if !empty($unboundInstanceNumber)}&i_id={$unboundInstanceNumber}{/if}" type="text/javascript"></script>
	<script type="text/javascript">
		var su_session = LibraryThingConnector.runUnboundWithMetadata({ldelim}
			"title":"{$recordDriver->getTitle()|escape:'javascript'}",
			"author":"{$recordDriver->getPrimaryAuthor()|escape:'javascript'}",
			"isbn":"{$recordDriver->getCleanISBN()}",
			"upc":"{$recordDriver->getCleanUPC()}",
			"id":"{$recordDriver->getPermanentId()}",
			"unbound_container_id":"#syndetics_unbound",
			"sectionTitle":"{translate text="See More from Syndetics Unbound" inAttribute=true isPublicFacing=true}",
			"buttonTitle":"{translate text="Explore" inAttribute=true isPublicFacing=true}"
		{rdelim});
		unboundLoaded = function() {ldelim}
			var numEnrichments = LibraryThingConnector.numberOfEnhancementsShown();
			if( numEnrichments === 0 ) {ldelim}
				$("#syndeticsUnboundPanel").hide();
			{rdelim}
		{rdelim}
	</script>
{/strip}
