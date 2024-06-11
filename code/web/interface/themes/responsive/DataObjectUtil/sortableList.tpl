{strip}
<ol class="list-unstyled" id="sortable">
	{foreach from=$property.values item=propertyName key=propertyValue}
		<li draggable="true" style="cursor:grab" data-value='{$propertyValue}'><i class="fas fa-grip-vertical"></i> {$propertyName|escape}</li>
	{/foreach}
</ol>
<input type='hidden' name='{$propName}' id='{$propName}' value='{$propValue|escape}'>

<script type="text/javascript">
	$(document).ready(function(){
		refreshListOptions();
		const sortableList =
			document.getElementById("sortable");
		let draggedItem = null;

		function refreshListValue(){
			let listOptions = [];
			$( "#sortable li" ).each(function( index ) {
				listOptions.push($(this).text().trim().replace('|',''));
			});
			let text = listOptions.join('|');
			$("#{$propName}").val(text.trim());
		}

		function refreshListOptions(drag){
			let value = $("#{$propName}").val();
			let optionsFromText = value.split("|");
			optionsFromText.forEach(textOption => {
				$('#sortable').children().each(function () {
					if(textOption.trim() == $(this).data().value){
						$('#sortable').append($(this));
					}
				});
			});
		}

		sortableList.addEventListener(
			"dragstart",
			(e) => {
				draggedItem = e.target;
				setTimeout(() => {
					e.target.style.display =
						"none";
				}, 0);
			});

		sortableList.addEventListener(
			"dragend",
			(e) => {
				setTimeout(() => {
					e.target.style.display = "";
					draggedItem = null;
					refreshListValue();
				}, 0);
			});

		sortableList.addEventListener(
			"dragover",
			(e) => {
				e.preventDefault();
				const afterElement =
					getDragAfterElement(
						sortableList,
						e.clientY);
				const currentElement =
					document.querySelector(
						".dragging");
				if (afterElement == null) {
					sortableList.appendChild(
						draggedItem
					);
				} else {
					sortableList.insertBefore(
						draggedItem,
						afterElement
					);
				}
			});

		const getDragAfterElement = (
			container, y
		) => {
			const draggableElements = [
				...container.querySelectorAll(
					"li:not(.dragging)"
				),
			];

			return draggableElements.reduce(
				(closest, child) => {
					const box =
						child.getBoundingClientRect();
					const offset =
						y - box.top - box.height / 2;
					if (
						offset < 0 &&
						offset > closest.offset) {
						return {
							offset: offset,
							element: child,
						};
					} else {
						return closest;
					}
				}, {
					offset: Number.NEGATIVE_INFINITY,
				}
			).element;
		};
	});
</script>