wp.domReady(() => {
	let queries = document.querySelectorAll('.ep-queries-debug');

	const copyBtn = document.querySelector('.copy-curl');
	const successMessage = document.querySelector('.ep-copy-button-wrapper__success');

	copyBtn.addEventListener('click', function (event) {
		const target = document.querySelector('.ep-queries-debug');
		const text = target.textContent;

		navigator.clipboard.writeText(text).then(function () {
			successMessage.style.display = 'inline-block';
			setTimeout(() => {
				successMessage.style.display = 'none';
			}, 3000);
			event.clearSelection();
		});
	});

	if (queries.length > 0) {
		queries = queries[0];

		queries.addEventListener('click', function (event) {
			let queryWrapper = event.target;

			while (event.currentTarget.contains(queryWrapper)) {
				if (queryWrapper.nodeName === 'LI') {
					if (event.target.className.match(/query-body-toggle/i)) {
						if (queryWrapper.className.match(/hide-query-body/i)) {
							queryWrapper.className = queryWrapper.className.replace(
								/hide-query-body/i,
								'',
							);
						} else {
							queryWrapper.className += ' hide-query-body';
						}
					}

					if (event.target.className.match(/query-result-toggle/i)) {
						if (queryWrapper.className.match(/hide-query-results/i)) {
							queryWrapper.className = queryWrapper.className.replace(
								/hide-query-results/i,
								'',
							);
						} else {
							queryWrapper.className += ' hide-query-results';
						}
					}

					if (event.target.className.match(/query-args-toggle/i)) {
						if (queryWrapper.className.match(/hide-query-args/i)) {
							queryWrapper.className = queryWrapper.className.replace(
								/hide-query-args/i,
								'',
							);
						} else {
							queryWrapper.className += ' hide-query-args';
						}
					}

					if (event.target.className.match(/query-headers-toggle/i)) {
						if (queryWrapper.className.match(/hide-query-headers/i)) {
							queryWrapper.className = queryWrapper.className.replace(
								/hide-query-headers/i,
								'',
							);
						} else {
							queryWrapper.className += ' hide-query-headers';
						}
					}

					if (event.target.className.match(/query-errors-toggle/i)) {
						if (queryWrapper.className.match(/hide-query-errors/i)) {
							queryWrapper.className = queryWrapper.className.replace(
								/hide-query-errors/i,
								'',
							);
						} else {
							queryWrapper.className += ' hide-query-errors';
						}
					}

					break;
				} else {
					queryWrapper = queryWrapper.parentNode;
				}
			}
		});
	}
});
