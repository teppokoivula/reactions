Reactions module for ProcessWire
--------------------------------

Reactions is a module for collecting reactions on pages from site users/visitors. This module is currently at a very early stage, so please test carefully before using it on a live site.

## Getting started

1. Configure available reaction options using `$config->reactions`, e.g. placing something like this in your site/config.php file:

```
$config->reactions = [
	'reaction_types' => [
		'like' => [
			'title' => 'Like it',
			'icon' => '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M720-120H280v-520l280-280 50 50q7 7 11.5 19t4.5 23v14l-44 174h258q32 0 56 24t24 56v80q0 7-2 15t-4 15L794-168q-9 20-30 34t-44 14Zm-360-80h360l120-280v-80H480l54-220-174 174v406Zm0-406v406-406Zm-80-34v80H160v360h120v80H80v-520h200Z"/></svg>',
			// optional attributes, either as an associative array or as a string, e.g.:
			// 'attrs' => [
			// 	'data-some-attr' => 'value',
			// ],
			// 'attrs' => 'data-attr="value"',
		],
		'love' => [
			'title' => 'Love it',
			'icon' => '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="m480-120-58-52q-101-91-167-157T150-447.5Q111-500 95.5-544T80-634q0-94 63-157t157-63q52 0 99 22t81 62q34-40 81-62t99-22q94 0 157 63t63 157q0 46-15.5 90T810-447.5Q771-395 705-329T538-172l-58 52Zm0-108q96-86 158-147.5t98-107q36-45.5 50-81t14-70.5q0-60-40-100t-100-40q-47 0-87 26.5T518-680h-76q-15-41-55-67.5T300-774q-60 0-100 40t-40 100q0 35 14 70.5t50 81q36 45.5 98 107T480-228Zm0-273Z"/></svg>',
		],
		'haha' => [
			'title' => 'Haha!',
			'icon' => '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M480-260q68 0 123.5-38.5T684-400H276q25 63 80.5 101.5T480-260ZM312-520l44-42 42 42 42-42-84-86-86 86 42 42Zm250 0 42-42 44 42 42-42-86-86-84 86 42 42ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-400Zm0 320q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Z"/></svg>',
		],
	],
];
```

2. Install the module

3. Add your own scripts and styles, or use the ones bundled with the module:

```
<link rel="stylesheet" href="<?= $config->urls->Reactions ?>styles/reaction-buttons.css">
<script src="<?= $config->urls->Reactions ?>scripts/reaction-buttons.js"></script>
```

4. Call the render method in where you'd like the reaction buttons to show up:

```
<?= $modules->get('Reactions')->renderReactionButtons() ?>
```

5. If you'd like to view the reactions in admin, install Process Reactions

Process Reactions adds a "Reactions" page under Setup in the admin. Viewing said page requires the "process-reactions" permission.
