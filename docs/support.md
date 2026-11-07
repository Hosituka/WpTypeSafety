# テーマ開発の補助機能

## 補助機能の一覧

`Support/`は、テーマ開発中に不便だと感じた処理をまとめた場所です。WordPress・ACFへの依存の有無で分類したディレクトリではありません。現在はHTML出力用の`Esc`と、ブロックエディター用アセットの読み込みを登録する`EnqueueAssets`があります。必要なものだけ利用できます。

利用時の前提は機能ごとに異なります。`Esc`の呼び出しにはWordPressの`wp_kses()`が必要です。

`EnqueueAssets::OnBlockEditor()`は、投稿編集画面またはサイトエディターでのみコールバックを実行します。WordPressの`add_action()`・`get_current_screen()`に依存します。

```php
<?php
use WpTypeSafety\Support\EnqueueAssets;

EnqueueAssets::OnBlockEditor(static function (): void {
	wp_enqueue_script('theme-block-editor');
});
```

## テンプレートとHTML出力の補助

```php
<?php
use WpTypeSafety\Support\Esc;

echo Esc::WithBr($description);
```

`Esc::WithBr()`は`<br>`、`WithSmall()`は`<small>`、`WithStrong()`は`<strong>`だけを許可します。
