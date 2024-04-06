<?php namespace ProcessWire;

class Reactions extends WireData implements Module {

	/**
	 * Available reaction types
	 *
	 * @var array
	 */
	protected $reaction_types = [
		'like' => [
			'title' => 'Like it',
			'icon' => '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M720-120H280v-520l280-280 50 50q7 7 11.5 19t4.5 23v14l-44 174h258q32 0 56 24t24 56v80q0 7-2 15t-4 15L794-168q-9 20-30 34t-44 14Zm-360-80h360l120-280v-80H480l54-220-174 174v406Zm0-406v406-406Zm-80-34v80H160v360h120v80H80v-520h200Z"/></svg>',
			'sort' => 1,
		],
		'love' => [
			'title' => 'Love it',
			'icon' => '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="m480-120-58-52q-101-91-167-157T150-447.5Q111-500 95.5-544T80-634q0-94 63-157t157-63q52 0 99 22t81 62q34-40 81-62t99-22q94 0 157 63t63 157q0 46-15.5 90T810-447.5Q771-395 705-329T538-172l-58 52Zm0-108q96-86 158-147.5t98-107q36-45.5 50-81t14-70.5q0-60-40-100t-100-40q-47 0-87 26.5T518-680h-76q-15-41-55-67.5T300-774q-60 0-100 40t-40 100q0 35 14 70.5t50 81q36 45.5 98 107T480-228Zm0-273Z"/></svg>',
			'sort' => 2,
		],
		'haha' => [
			'title' => 'Haha!',
			'icon' => '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M480-260q68 0 123.5-38.5T684-400H276q25 63 80.5 101.5T480-260ZM312-520l44-42 42 42 42-42-84-86-86 86 42 42Zm250 0 42-42 44 42 42-42-86-86-84 86 42 42ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-400Zm0 320q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Z"/></svg>',
			'sort' => 3,
		],
	];

	/**
	 * Get module information
	 *
	 * @return array
	 */
	public static function getModuleInfo() {
		return [
			'title' => 'Reactions',
			'summary' => 'A module for collecting reactions on pages.',
			'version' => '0.0.1',
			'author' => 'Teppo Koivula',
			'href' => 'https://github.com/teppokoivula/reactions',
			'icon' => 'heart-o',
			'autoload' => true,
			'requires' => 'ProcessWire>=3.0.154',
		];
	}

	/**
	 * Initialize the module, add hooks
	 */
	public function init() {

		// get reaction types from site config (if available)
		$reaction_types = $this->config->reactions ? $this->config->reactions['reaction_types'] : null;
		if ($reaction_types) {
			$this->setReactionTypes($reaction_types);
		}

		$this->addHook('/reactions/save/', $this, 'hookSaveReaction');
	}

	/**
	 * Save reaction
	 *
	 * @param HookEvent $event
	 * @return string
	 */
	protected function hookSaveReaction(HookEvent $event): string {

		header('Content-Type: application/json');

		// get and validate input
		$page_id = $this->input->post('pageID', 'int');
		$new_value = $this->input->post('reaction', array_keys($this->reaction_types));
		$old_value = $this->input->post('previousReaction', array_keys($this->reaction_types));

		// if page ID or new value was not provided or was invalid, bail out
		if (empty($page_id) || empty($new_value) || !empty($old_value) && $new_value === $old_value) {
			http_response_code(400);
			return json_encode(['error' => $this->_('Invalid input')]);
		}

		// get and validate page
		$page = $this->pages->findOne('id=' . $page_id . ', include=hidden');
		if (!$page->id) {
			http_response_code(404);
			return json_encode(['error' => $this->_('Page not found')]);
		}

		// save reaction:
		// - if page has no reactions yet, insert a new row
		// - if page has reactions, increment the reaction type column
		$stmt = $this->database->prepare("INSERT INTO `reactions` (`pages_id`, `reaction_$new_value`) VALUES (?, 1) ON DUPLICATE KEY UPDATE `reaction_$new_value` = `reaction_$new_value` + 1");
		$stmt->execute([$page_id]);

		// decrement old reaction type column (if old value was provided)
		if ($old_value) {
			$stmt = $this->database->prepare("UPDATE `reactions` SET `reaction_$old_value` = `reaction_$old_value` - 1 WHERE `pages_id` = ? AND `reaction_$old_value` > 0");
			$stmt->execute([$page_id]);
		}

		// fetch updated reaction counts
		$stmt = $this->database->prepare("SELECT * FROM `reactions` WHERE `pages_id` = ?");
		$stmt->execute([$page_id]);
		$data = $stmt->fetch(\PDO::FETCH_ASSOC);
		$reactions = [];
		foreach ($data as $key => $value) {
			if (!array_key_exists(substr($key, 9), $this->reaction_types)) continue;
			$reactions[substr($key, 9)] = (int) $value;
		}

		return json_encode($reactions);
	}

	/**
	 * Set reaction types
	 *
	 * @param array $reaction_types
	 */
	public function setReactionTypes(array $reaction_types) {

		// validate reaction type names (need to be valid column names)
		foreach ($reaction_types as $reaction_type => $reaction) {
			if (!preg_match('/^[a-z0-9_]+$/', $reaction_type)) {
				throw new WireException(sprintf(
					$this->_('Invalid reaction type name: %s')
				), $reaction_type);
			}
		}

		// make sure that reaction types have a sort order set
		$sort = 0;
		uasort($reaction_types, function($a, $b) {
			return ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0);
		});
		foreach ($reaction_types as $reaction_type => $reaction) {
			++$sort;
			if (!isset($reaction['sort'])) {
				$reaction_types[$reaction_type]['sort'] = $sort;
			}
		}

		$this->reaction_types = $reaction_types;

		// get reaction types from module config
		$data = $this->modules->getConfig('Reactions');
		$configured_reaction_types = $data['reaction_types'] ?? [];

		// if keys for configured reaction types differ from the ones set in the module, update database table and then save the new config
		// note: order of keys doesn't really matter, so we're only checking if there are any differences
		if (array_diff(array_keys($reaction_types), array_keys($configured_reaction_types)) || array_diff(array_keys($configured_reaction_types), array_keys($reaction_types))) {
			$this->updateDatabaseTable();
			$data['reaction_types'] = $reaction_types;
			$this->modules->saveConfig('Reactions', $data);
		}

	}

	/**
	 * Get reaction types
	 *
	 * @return array
	 */
	public function getReactionTypes(): array {
		return $this->reaction_types;
	}

	/**
	 * Update database table with reaction type columns
	 */
	public function updateDatabaseTable() {

		$updated = false;

		// add reaction type columns that are not yet in the table
		foreach ($this->reaction_types as $reaction_type => $reaction) {
			if (!$this->database->columnExists('reactions', 'reaction_' . $reaction_type)) {
				$this->database->exec("ALTER TABLE `reactions` ADD COLUMN `reaction_$reaction_type` INT(11) NOT NULL DEFAULT 0");
				$updated = true;
			}
		}

		// remove reaction type columns that are no longer in use
		foreach ($this->database->getColumns('reactions') as $column) {
			if (strpos($column, 'reaction_') !== 0) continue;
			$reaction_type = substr($column, 9);
			if (!isset($this->reaction_types[$reaction_type])) {
				$this->database->exec("ALTER TABLE `reactions` DROP COLUMN `$column`");
				$updated = true;
			}
		}

		if ($updated) {
			$this->message("Updated table 'reactions' with configured reaction types");
		}
	}

	/**
	 * Get reaction count for a page
	 *
	 * @param Page $page
	 * @return array
	 */
	public function getReactions(Page $page): array {
		$stmt = $this->database->prepare("SELECT * FROM `reactions` WHERE `pages_id` = ?");
		$stmt->execute([$page->id]);
		$data = $stmt->fetch(\PDO::FETCH_ASSOC);
		$reactions = array_fill_keys(array_keys($this->reaction_types), 0);
		foreach ($data as $key => $value) {
			if (!isset($reactions[substr($key, 9)])) continue;
			$reactions[substr($key, 9)] = (int) $value;
		}
		return $reactions;
	}

	/**
	 * Get reaction counts for all pages
	 *
	 * @param array $options
	 * @return array
	 */
	public function getReactionsForAllPages(array $options = []): array {
		$stmt = $this->database->query("
		SELECT * FROM `reactions`
		" . (isset($options['limit']) ? "LIMIT " . (int) $options['limit'] : "") . "
		" . (isset($options['start']) ? "OFFSET " . (int) $options['start'] : "") . "
		");
		$data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		$reactions = [];
		foreach ($data as $row) {
			$page_id = (int) $row['pages_id'];
			$reactions[$page_id] = array_fill_keys(array_keys($this->reaction_types), 0);
			foreach ($row as $key => $value) {
				if (!isset($reactions[$page_id][substr($key, 9)])) continue;
				$reactions[$page_id][substr($key, 9)] = (int) $value;
			}
		}
		return [
			'reactions' => $reactions,
			'limit' => $options['limit'] ?? null,
			'total' => !empty($options['limit']) ? $this->database->query("SELECT COUNT(*) FROM `reactions`")->fetchColumn() : count($reactions),
		];
	}

	/**
	 * Render buttons for reactions
	 *
	 * @param null|Page $page Optional, current page is used by default
	 * @return string
	 */
	public function renderReactionButtons(?Page $page = null): string {

		// render buttons
		$buttons = [];
		uasort($this->reaction_types, function($a, $b) {
			return ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0);
		});
		foreach ($this->reaction_types as $reaction_type => $reaction_data) {

			// gotsta have an icon or a title
			if (empty($reaction_data['icon']) && !strlen($reaction_data['title'])) continue;

			$button = "";

			if (!empty($reaction_data['icon'])) {
				if (strpos($reaction_data['icon'], 'fa') === 0) {
					$button .= '<i class="reactions-buttons__icon ' . $this->sanitizer->entities1($reaction_data['icon']) . '"></i>';
				} else if (strpos($reaction_data['icon'], '<') === 0) {
					// markup, e.g. SVG icon or image
					$button .= $reaction_data['icon'];
				} else {
					$button .= '<span class="reaction-buttons__icon">' . $this->sanitizer->entities1($reaction_data['icon']) . '</span>';
				}
			}

			if (strlen($reaction_data['title'])) {
				$button .= (!empty($reaction_data['icon']) ? ' ' : '')
					. '<span class="reaction-buttons__label">' . $this->sanitizer->entities1($reaction_data['title']) . '</span>';
			}

			$buttons[] = sprintf('<button class="reaction-buttons__button" data-page-id="%d" data-reaction="%s">%s</button>', $page ? $page->id : $this->page->id, $reaction_type, $button);
		}

		return sprintf('<div class="reaction-buttons">%s</div>', implode('', $buttons));
	}

	/**
	 * Tasks to run when installing the module
	 */
	public function ___install() {
		$sql = "
		CREATE TABLE IF NOT EXISTS `reactions` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`pages_id` int(11) NOT NULL,
			`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			UNIQUE KEY `pages_id` (`pages_id`)
		) ENGINE=" . $this->config->dbEngine . " DEFAULT CHARSET=" . $this->config->dbCharset . ";
		";
		$this->database->exec($sql);
		$this->message("Created table 'reactions'");
		if (!empty($this->reaction_types)) {
			$this->updateDatabaseTable();
		}
	}

	/**
	 * Tasks to run when uninstalling the module
	 */
	public function ___uninstall() {
		$this->database->exec("DROP TABLE IF EXISTS `reactions`");
	}
}
