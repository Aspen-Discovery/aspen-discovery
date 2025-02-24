<div class="row">
	<div class="col-xs-12">
		<div class="row">
			{if !empty({$logo})}
				<div class="col-sm-4">
					<a href="{$url}" target="_blank" aria-label="{$title|escape} ({translate text='opens in new window' isPublicFacing=true})">
						<img class="img-responsive img-thumbnail" src="{$logo}" alt="{$title|escape}">
					</a>
				</div>
			<div class="col-sm-8">
			{else}
			<div class="col-sm-12">
			{/if}
				<h2>{$title}</h2>
					{if isset($description)}
						{$description}
					{/if}
				<a href="{$url}" class="btn btn-primary" target="_blank" aria-label="{translate text='Open Resource' isPublicFacing=true} ({translate text='opens in new window' isPublicFacing=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="Open Resource" isPublicFacing=true}</a>
			</div>
		</div>
	</div>
</div>
