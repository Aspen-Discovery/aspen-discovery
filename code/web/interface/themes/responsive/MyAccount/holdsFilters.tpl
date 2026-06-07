{if is_array($filterOptions)}
	<div id="holdsFilterDisplayHorizontal">
		<div class="row row-no-gutters horizontalFilterSelector" id="holdsFilterRow">
				<div class="col-xs-12">
					<div class="slider-container" role="region" id="slider-holds-filter">
					<button type="button" class="slider-button slider-button-prev btn btn-default" id="slider-prev-holds-filter" aria-label="{translate text="Previous Filter" isPublicFacing=true inAttribute=true}"><i class="fas fa-chevron-left"></i></button>
					<div class="slider-wrapper" role="listbox" aria-activedescendant="slide-holds-filter-0">
						{foreach from=$filterOptions item=filterOption key=filterKey}
							<div role="option" tabindex="0" class="slider-slide horizontal-filter-select" data-filterKey="{$filterKey}" data-filter="{$filterKey}">
								<div class="form-group">
									<fieldset>
										<legend>{$filterOption.label}</legend>
										<select
												name="HoldFilter_{$filterKey}[]"
												id="HoldFilter_{$filterKey}"
												class="multipleSelect"
												aria-label="{translate text='Filter by %1%' 1=$filterOption.label isPublicFacing=true inAttribute=true}"
                                                {if $filterOption.type === "multiselect"}multiple="multiple"{/if}
										>
                                            {foreach from=$filterOption.options item=optionVal key=optionKey}
												<option value="{$optionKey}"{if isset($filterOption.selected) && in_array($optionKey, $filterOption.selected)} selected="selected"{/if}>{$optionVal}</option>
                                            {/foreach}
										</select>
									</fieldset>
								</div>
							</div>
			        {/foreach}
						<div role="option" tabindex="0" class="slider-slide horizontal-filter-button">
							<button type="button" style="margin-top: .5em;" class="btn btn-primary" id="applyHoldsFilters">Apply Filters</button>
						</div>
						<div role="option" tabindex="0" class="slider-slide horizontal-filter-button">
							<button type="button" style="margin-top: .5em;" class="btn btn-default" id="clearHoldsFilters">Clear Filters</button>
						</div>
				</div>
				<button type="button" class="slider-button slider-button-next btn btn-default" id="slider-next-hold-filter" aria-label="{translate text="Next Filter" isPublicFacing=true inAttribute=true}"><i class="fas fa-chevron-right"></i></button>
					</div>
					</div>
				<script>
					$(document).ready(function(){ldelim}
						AspenDiscovery.Account.initializeHorizontalHoldFiltersSwipers('holds-filter');
                        {rdelim});
				</script>
		</div>
	</div>
	<script type="text/javascript">
        {literal}
		$(document).ready(function() {
			const $filterTiles = $('#holdsFilterRow .horizontal-filter-select');
			let activeFilterIndex = -1;
			$('.multipleSelect').multipleSelect({
				container: '#holdsFilterRow',
				onOpen: function() {
					setTimeout(function() {
						const $openDrop = $('.ms-drop:visible').last();
						if (!$openDrop.length) return;

						const $firstInput = $openDrop.find('li:not(.disabled) input:not(:disabled)').first();
						if ($firstInput.length) $firstInput.focus();
					}, 0);
				}
			});
			$('#holdsFilterRow').on('keydown', '.ms-choice', function(e) {
				if (e.key !== 'Enter' && e.key !== ' ' && e.key !== 'ArrowDown') return;

				e.preventDefault();

				const $tile = $(this).closest('.horizontal-filter-select');
				activeFilterIndex = $filterTiles.index($tile);

				const $select = $tile.find('select.multipleSelect').first();
				if (!$select.length) return;

				$select.multipleSelect('open');
			});
			$(document).on('keydown.holdsFilterTabFlow', function(e) {
				if (e.key !== 'Tab') return;

				const $openDrop = $('.ms-drop:visible').last();
				if (!$openDrop.length) return;

				if (activeFilterIndex < 0) return;

				const nextIndex = e.shiftKey ? activeFilterIndex - 1 : activeFilterIndex + 1;

				if (nextIndex < 0 || nextIndex >= $filterTiles.length) {
					activeFilterIndex = -1;
					return;
				}

				e.preventDefault();
				e.stopPropagation();

				const $currentTile = $filterTiles.eq(activeFilterIndex);
				const $currentSelect = $currentTile.find('select.multipleSelect').first();
				if ($currentSelect.length) $currentSelect.multipleSelect('close');

				activeFilterIndex = nextIndex;

				const $targetChoice = $filterTiles.eq(activeFilterIndex).find('.ms-choice').first();
				if ($targetChoice.length) $targetChoice.focus();
			});
			$(document).on('click', function(evt) {
				if (!$(evt.target).closest('#holdsFilterRow').length) {
					activeFilterIndex = -1;
				}
			});
			$('#applyHoldsFilters').on('click', function() {
				let filters = {};
				$('#holdsFilterRow select').each(function() {
					let key = $(this).attr('id').replace('HoldFilter_', '');
					filters[key] = $(this).val() || [];
				});
				AspenDiscovery.Account.loadHolds('all', $('#availableHoldSort_{/literal}{$source}{literal} option:selected').val(), $('#unavailableHoldSort_{/literal}{$source}{literal} option:selected').val(), null, null, filters);
			});
			$('#clearHoldsFilters').on('click', function() {
				AspenDiscovery.Account.loadHolds('all', $('#availableHoldSort_{/literal}{$source}{literal} option:selected').val(), $('#unavailableHoldSort_{/literal}{$source}{literal} option:selected').val());
			});
		});
        {/literal}
	</script>
{/if}