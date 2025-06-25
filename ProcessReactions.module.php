<?php namespace ProcessWire;

class ProcessReactions extends Process implements Module {

	/**
	 * Get module info
	 */
	public static function getModuleInfo() {
		return [
			'title' => 'Process Reactions',
			'summary' => 'A module for managing reactions on pages.',
			'version' => '0.0.3',
			'author' => 'Teppo Koivula',
			'href' => 'https://github.com/teppokoivula/reactions',
			'icon' => 'heart-o',
			'page' => [
				'name' => 'reactions',
				'parent' => 'setup',
				'title' => __('Reactions'),
			],
			'permission' => 'process-reactions',
			'requires' => 'ProcessWire>=3.0.154',
		];
	}

	public function ___execute() {

		/** @var Reactions */
		$reactions = $this->modules->get('Reactions');

		$reaction_types = $reactions->getReactionTypes();

		$sort = $this->input->get('sort', array_merge([
			'page',
			'page.title',
			'total',
		], array_keys($reaction_types)));
		$sort_dir = $this->input->get('sortDir', ['asc', 'desc']) ?? ($sort == 'page.title' ? 'asc' : 'desc');

		$limit = 25;

		$reactions_for_pages = $reactions->getReactionsForAllPages(array_filter([
			'sort' => $sort,
			'sort_dir' => $sort_dir,
			'limit' => $limit,
			'start' => $this->input->get->pageNum > 0 ? 0 + (($this->input->get->pageNum - 1) * $limit) : 0,
		]));

		// if we have no reactions yet, just display a message
		if (empty($reactions_for_pages['total'])) {
			return $this->_('No reactions yet.');
		}

		/** @var MarkupAdminDataTable */
		$table = $this->modules->get('MarkupAdminDataTable');
		$table->setEncodeEntities(false);
		$table->setSortable(false);
		$table->addClass('process-reactions-table');
		foreach ($reaction_types as $reaction_type) {
			$columns = [
				'page' => $this->_('Page'),
			];
			foreach ($reaction_types as $reaction_type => $reaction_type_data) {
				$columns[$reaction_type] = $reaction_type_data['title'];
			}
			$columns['_total_reactions'] = $this->_('Total');
			$table->headerRow($columns);
		}

		foreach ($reactions_for_pages['reactions'] as $page_id => $page_reactions) {
			$page_object = $this->pages->get($page_id);
			if (!$page_object->id) continue;
			$row = [
				'page' => '<a href="' . $page_object->url . '" target="_blank">'
					. $page_object->title
					. '</a>',
			];
			foreach ($reaction_types as $reaction_type => $reaction_type_data) {
				$row[$reaction_type] = $page_reactions[$reaction_type] ?? 0;
			}
			$row['_total_reactions'] = array_sum($row);
			$table->row(array_values($row));
		}

		$pager = '';
		if ($reactions_for_pages['total'] > $limit) {
			$pager_params = [];
			if ($sort) {
				$pager_params['sort'] = $sort;
				$pager_params['sortDir'] = $sort_dir;
			}
			$pager = "<ul class='uk-pagination'>";
			for ($i = 1; $i <= ceil($reactions_for_pages['total'] / $limit); $i++) {
				$pager .= "<li class='" . ($i == $this->input->get->pageNum ? 'uk-active' : '') . "'>";
				$pager .= "<a href='{$this->page->url}?pageNum={$i}" . (empty($pager_params) ? '' : '&' . http_build_query($pager_params)) . "'>{$i}</a>";
				$pager .= "</li>";
			}
			$pager .= "</ul>";
		}

		foreach ($reaction_types as $key => $reaction_type) {
			$reaction_types[$key]['key'] = $key;
		}

		// add script tag for general utilities
		$script = "<script>

		// store reaction types and current sort state in JavaScript variables
		const reactionTypes = " . json_encode(array_values($reaction_types)) . ";
		const currentSort = '" . $sort . "';
		const currentSortDir = '" . $sort_dir . "';

		document.querySelectorAll('.process-reactions-table thead th').forEach((header, index) => {

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
		</script>";

		// add action links
		$actions = [
			'<a href="' . $this->page->url . 'csv?' . $this->input->queryString() . '" class="uk-align-right uk-margin-auto-left uk-margin-remove-bottom">'
				. $this->_('Download CSV')
				. '<span uk-icon="icon: download"></span>'
				. '</a>',
		];

		return $table->render() . $pager . "<div class='uk-margin-top'>"
			. implode(' ', $actions)
			. "</div>"
			. $script;
	}

	public function ___executeCSV() {

		/** @var Reactions */
		$reactions = $this->modules->get('Reactions');

		$reaction_types = $reactions->getReactionTypes();

		$sort = $this->input->get('sort', array_merge([
			'page',
			'page.title',
			'created',
			'updated',
			'total',
		], array_keys($reaction_types)));
		$sort_dir = $this->input->get('sortDir', ['asc', 'desc']) ?? ($sort == 'page.title' ? 'asc' : 'desc');

		// header row
		$row = [
			$this->_('Page'),
		];
		foreach ($reaction_types as $reaction_type => $reaction_type_data) {
			$row[] = $reaction_type_data['title'];
		}
		$row[] = $this->_('Total');
		$rows = [$row];

		$reactions_for_pages = $reactions->getReactionsForAllPages(array_filter([
			'sort' => $sort,
			'sort_dir' => $sort_dir,
		]));

		// loop through reactions and prepare CSV rows
		foreach ($reactions_for_pages['reactions'] as $page_id => $page_reactions) {

			// get page object
			$page_object = $this->pages->get($page_id);
			if (!$page_object->id) continue;

			// add reaction data to CSV row
			$row = [];
			foreach ($reaction_types as $reaction_type => $reaction_type_data) {
				$row[] = $page_reactions[$reaction_type] ?? 0;
			}
			$row[] = array_sum($row);

			// prepend page title to the row
			array_unshift($row, $page_object->title);

			$rows[] = $row;
		}

		// set headers for CSV download
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=reactions-' . date('Ymd-His') . '.csv');
		header('Pragma: no-cache');
		header('Expires: 0');

		// open file pointer and write BOM + header row
		$fp = fopen('php://output', 'w');
		fputs($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
		foreach ($rows as $row) {
			fputcsv($fp, $row, ';');
		}
		fclose($fp);
		exit;
	}

}
