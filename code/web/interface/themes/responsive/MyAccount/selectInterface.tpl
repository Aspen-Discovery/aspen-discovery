<div id="page-content" class="content">
	<br/>
	<div class="alert alert-info">{translate text='Select the Library Catalog you wish to use' isPublicFacing=true}</div>
	<div id="selectLibraryMenu">
		<form id="selectLibrary" method="get" action="/MyAccount/SelectInterface" class="form">
			<div id="selectLibraryOptions" class="row">
				<input type="hidden" name="gotoModule" value="{$gotoModule}"/>
				<input type="hidden" name="gotoAction" value="{$gotoAction}"/>
				{foreach from=$libraries key=libraryKey item=libraryInfo}
					<div class="selectLibraryOption col-tn-12">
						<label for="library{$libraryKey}"><input type="radio" id="library{$libraryKey}" name="library" value="{$libraryKey}"/> {$libraryInfo.displayName}</label>
					</div>
				{/foreach}
			</div>
			<div class="row">
				<div class="col-xs-12">
					{if !$noRememberThis}
						<div class="selectLibraryOption">
							<label for="rememberThis"><input type="checkbox" name="rememberThis" id="rememberThis"> <b>{translate text="Remember This" isPublicFacing=true}</b></label>
						</div>
					{/if}
					<input type="submit" name="submit" value="{translate text="Select Library Catalog" isPublicFacing=true inAttribute=true}" id="submitButton" class="btn btn-primary"/>
				</div>
			</div>
		</form>
	</div>
	<br/>
</div>