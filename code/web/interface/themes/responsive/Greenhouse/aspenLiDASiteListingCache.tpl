{strip}
	<div class="row">
		<div class="col-xs-12 col-md-9">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>

    <div id="main-content" class="col-md-12">
        <form class="row">
            <div class="form-group col-xs-12">
                <label for="lastUpdateOfListings" class="control-label">{translate text="Last Update of Site Listings" isAdminFacing=true}</label>
                <input name="lastUpdateOfListings" type="text" class="form-control valid" value="{$lastUpdatedCache|date_format:"%D %T"}" readonly>
            </div>
            <div class="form-group col-xs-12">
                <input type="hidden" id="refreshData" name="refreshData" value="true">
                <button class="btn btn-primary" type="submit"><i class="fas fa-sync-alt"></i> {translate text="Refresh Data" isAdminFacing=true}</button>
            </div>
        </form>
       {$siteListingCache}
    </div>
{/strip}