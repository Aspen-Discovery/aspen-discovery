{strip}
	<div class="col-xs-12">
		<h1>{$title}</h1>
		<div id="videoContainer">
			<video width="100%" id="player" controls>
				<source src="{$videoPath}" type="video/mp4">
                {translate text="Your browser does not support the display of Videos or the file (%1%) is too large to display." 1=$fileSize  isPublicFacing=true}
				<a class="btn btn-primary" href="{$videoPath}">{translate text="Open the file" isPublicFacing=true}</a>
			</video>
		</div>
	</div>
{/strip}