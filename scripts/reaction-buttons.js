document.addEventListener('DOMContentLoaded', () => {

	const reactionButtons = document.querySelectorAll('.reaction-buttons__button');
	if (!reactionButtons.length) return;

	// get the reactions from the local storage
	const reactions = JSON.parse(localStorage.getItem('reactions')) || {};

	reactionButtons.forEach(button => {

		// if the reaction is stored in the local storage, add the active class to the button
		const pageID = button.getAttribute('data-page-id');
		if (pageID && pageID in reactions && reactions[pageID] === button.getAttribute('data-reaction')) {
			button.classList.add('reaction-buttons__button--active');
		}

		button.addEventListener('click', event => {

			event.preventDefault();

			// bail out early if the button is disabled or already active
			if (button.disabled) return;
			if (button.classList.contains('reaction-buttons__button--active')) return;

			// get and validate the reaction and pageID attributes
			const reaction = button.getAttribute('data-reaction');
			const pageID = button.getAttribute('data-page-id');
			if (!reaction || !pageID) return;

			// disable all reaction buttons while the request is being processed
			reactionButtons.forEach(reactionButton => {
				reactionButton.disabled = true;
			});

			// construct new form data object based on the clicked reaction button
			const formData = new FormData();
			formData.append('reaction', reaction);
			formData.append('pageID', pageID);

			// add the previous reaction to the form data if there was one
			const previousReactionButton = document.querySelector('.reaction-buttons__button--active');
			if (previousReactionButton) {
				formData.append('previousReaction', previousReactionButton.getAttribute('data-reaction'));
			}

			// add the saving class to the clicked reaction button
			button.classList.add('reaction-buttons__button--saving');

			fetch('/reactions/save/', {
				method: 'POST',
				body: formData,
			})
				.then(response => response.json())
				.then(data => {

					// remove the saving class and add the active class to the clicked reaction button
					button.classList.remove('reaction-buttons__button--saving');
					button.classList.add('reaction-buttons__button--active');

					// remove the active class from the previous reaction button, in case there was one
					if (previousReactionButton) {
						previousReactionButton.classList.remove('reaction-buttons__button--active');
					}

					// store the reaction in the local storage
					let reactions = JSON.parse(localStorage.getItem('reactions')) || {};
					reactions[pageID] = reaction;
					localStorage.setItem('reactions', JSON.stringify(reactions));

					// enable all reaction buttons after the request has been processed
					reactionButtons.forEach(reactionButton => {
						reactionButton.disabled = false;
					});

					// trigger an event to notify other scripts that the reactions have been updated
					document.dispatchEvent(new CustomEvent('reactions-updated', {
						detail: {
							pageID: pageID,
							reaction: reaction,
						},
					}, { bubbles: true }));
				})
				.catch((error) => {

					console.error('Error:', error);

					// remove the saving class from the clicked reaction button
					button.classList.remove('reaction-buttons__button--saving');

					// enable all reaction buttons after the request has been processed
					reactionButtons.forEach(reactionButton => {
						reactionButton.disabled = false;
					});
				});
		});
	});
});
