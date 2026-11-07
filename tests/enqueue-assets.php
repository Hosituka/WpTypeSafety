<?php
declare(strict_types=1);

use WpTypeSafety\Support\EnqueueAssets;

/** @var array<string, list<callable>> $actions */
$actions = [];
$currentScreen = null;

function add_action(string $hookName, callable $callback): void
{
	global $actions;
	$actions[$hookName][] = $callback;
}

function get_current_screen(): mixed
{
	global $currentScreen;
	return $currentScreen;
}

require_once __DIR__ . '/../Support/EnqueueAssets.php';

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
	if (! $ok) {
		throw new RuntimeException($label);
	}
	$checks++;
};
$runs = 0;
EnqueueAssets::OnBlockEditor(static function () use (&$runs): void {
	$runs++;
});

$check(isset($actions['enqueue_block_editor_assets'][0]), 'registers block editor hook');
$callback = $actions['enqueue_block_editor_assets'][0];

$callback();
$check($runs === 0, 'ignores missing screen');

$currentScreen = new class(false, 'post') {
	public function __construct(
		private readonly bool $isBlockEditor,
		public readonly string $base,
	) {}

	public function is_block_editor(): bool
	{
		return $this->isBlockEditor;
	}
};
$callback();
$check($runs === 0, 'ignores non-block editor');

$currentScreen = new class(true, 'widgets') {
	public function __construct(
		private readonly bool $isBlockEditor,
		public readonly string $base,
	) {}

	public function is_block_editor(): bool
	{
		return $this->isBlockEditor;
	}
};
$callback();
$check($runs === 0, 'ignores unsupported block editor screen');

foreach (['post', 'site-editor'] as $base) {
	$currentScreen = new class(true, $base) {
		public function __construct(
			private readonly bool $isBlockEditor,
			public readonly string $base,
		) {}

		public function is_block_editor(): bool
		{
			return $this->isBlockEditor;
		}
	};
	$callback();
}
$check($runs === 2, 'runs on post and site editor screens');

echo "Enqueue assets: {$checks} checks passed.\n";
