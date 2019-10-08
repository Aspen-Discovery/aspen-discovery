<div id="page-content" class="content">
	<br/>
	<div class="alert alert-info">{translate text='Select the Library Catalog you wish to use'}</div>
	<div id="selectLibraryMenu">
		<form id="selectLibrary" method="get" action="/MyAccount/SelectInterface" class="form">
			<div>
				<input type="hidden" name="gotoModule" value="{$gotoModule}"/>
				<input type="hidden" name="gotoAction" value="{$gotoAction}"/>
				{foreach from=$libraries item=libraryInfo}
					<div class="selectLibraryOption col-xs-12 col-sm-6 col-md-4">
						<label for="library{$libraryInfo.id}"><input type="radio" id="library{$libraryInfo.id}" name="library" value="{$libraryInfo.id}"/> {$libraryInfo.displayName}</label>
					</div>
				{/foreach}
				<div class="clearfix"></div>
				<div class="col-xs-12">
					<div class="selectLibraryOption">
						<label for="rememberThis"><input type="checkbox" name="rememberThis" checked="checked" id="rememberThis"> <b>Remember This</b></label>
					</div>
					<input type="submit" name="submit" value="Select Library Catalog" id="submitButton" class="btn btn-primary"/>
				</div>
				<div class="clearfix"></div>
			</div>
		</form>
	</div>
	<br/>
</div>