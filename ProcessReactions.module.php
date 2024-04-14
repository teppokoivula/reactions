<?php namespace ProcessWire;

class ProcessReactions extends Process implements Module {

	/**
	 * Get module info
	 */
	public static function getModuleInfo() {
		return [
			'title' => 'Process Reactions',
			'summary' => 'A module for managing reactions on pages.',
			'version' => '0.0.2',
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

		$limit = 25;

		$reactions_for_pages = $reactions->getReactionsForAllPages([
			'limit' => $limit,
			'start' => $this->input->get->pageNum > 0 ? 0 + (($this->input->get->pageNum - 1) * $limit) : 0,
		]);

		// if we have no reactions yet, just display a message
		if (empty($reactions_for_pages['total'])) {
			return $this->_('No reactions yet.');
		}

		$reaction_types = $reactions->getReactionTypes();

		/** @var MarkupAdminDataTable */
		$table = $this->modules->get('MarkupAdminDataTable');
		$table->setEncodeEntities(false);
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
			$pager = "<ul class='uk-pagination'>";
			for ($i = 1; $i <= ceil($reactions_for_pages['total'] / $limit); $i++) {
				$pager .= "<li class='" . ($i == $this->input->get->pageNum ? 'uk-active' : '') . "'>";
				$pager .= "<a href='{$this->page->url}?pageNum=$i'>$i</a>";
				$pager .= "</li>";
			}
			$pager .= "</ul>";
		}

		return $table->render() . $pager;
	}

}
