document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('.process-reactions-table thead th').forEach((header, index) => {

		// get config variables
		const reactionTypes = ProcessWire.config.ProcessReactions.reactionTypes || [];
		const currentSort = ProcessWire.config.ProcessReactions.currentSort || null;
		const currentSortDir = ProcessWire.config.ProcessReactions.currentSortDir || 'asc';

		// add data attributes and classes to headers
		header.setAttribute('data-reaction-type', index === 0 ? 'page.title' : (index <= reactionTypes.length ? reactionTypes[index - 1].key : 'total'));
		header.classList.add('uk-link');

		// if we are currently sorting by this header, add a sorting icon
		if (header.getAttribute('data-reaction-type') === currentSort) {
			header.appendChild(document.createElement('span'));
			if (currentSort === 'page.title' && currentSortDir === 'desc' || currentSort !== 'page.title' && currentSortDir === 'asc') {
				header.querySelector('span').setAttribute('uk-icon', 'icon: chevron-up');
			} else {
				header.querySelector('span').setAttribute('uk-icon', 'icon: chevron-down');
			}
			header.querySelector('span').classList.add('uk-margin-small-left');
		}

		// add click event listener to headers
		header.addEventListener('click', () => {

			// if already sorting, bail out early
			if (header.hasAttribute('data-sorting') || document.querySelector('.process-reactions-table thead th[data-sorting]')) {
				return;
			}

			// mark state of header as sorting
			header.setAttribute('data-sorting', 'true');

			// add spinner icon
			const existingIcon = header.querySelector('span[uk-icon]');
			if (existingIcon) {
				existingIcon.remove();
			}
			header.appendChild(document.createElement('span'));
			header.querySelector('span').classList.add('uk-margin-small-left');
			header.querySelector('span').setAttribute('uk-spinner', 'ratio: 0.5');

			// update the URL with new sort parameters
			const url = new URL(window.location.href);
			const reactionType = header.getAttribute('data-reaction-type');
			const currentSort = url.searchParams.get('sort') || null;
			if (url.searchParams.has('sort')) {
				url.searchParams.set('sort', reactionType);
			} else {
				url.searchParams.append('sort', reactionType);
			}
			if (currentSort == reactionType && url.searchParams.has('sortDir')) {
				url.searchParams.set('sortDir', currentSortDir === 'asc' ? 'desc' : 'asc');
			} else {
				url.searchParams.delete('sortDir');
				url.searchParams.append('sortDir', reactionType === 'page.title' ? 'asc' : 'desc');
			}

			// reload the page with new sort parameters
			window.location.href = url.toString();
		});
	});
});
