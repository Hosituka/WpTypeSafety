# TreeTypeReader

TreeTypeReaderは、不定形なPHP値の型確認・正規化と、配列・オブジェクトで構成された木構造の探索を担当するWpTypeSafety内部の汎用エンジンです。

WordPress・ACFには依存していません。WpTypeSafetyでは、Coreの公開Facadeと`MetaGet`がこのエンジンを利用します。

## 利用時の入口

通常の利用コードでは、`WpTypeSafety\Internal`を直接参照しません。次の公開Facadeを使用してください。

```php
use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Check\ObjectGet;
use WpTypeSafety\Core\Check\TreeGet;
use WpTypeSafety\Core\Check\TypeCheck;
use WpTypeSafety\Core\Check\ValueMode;
use WpTypeSafety\Core\Check\ValueReader;
use WpTypeSafety\Core\Check\ValueRules;
```

`WpTypeSafety\Internal\TreeTypeReader`は、WpTypeSafety内部の実装と派生取得口を接続するための名前空間です。一般利用者向けの安定した公開名前空間は`WpTypeSafety\Core\Check`です。

## 主な用途

| 取得口 | 用途 |
|---|---|
| `TypeCheck` | 変換せず、元から期待型である値だけを受け入れる |
| `ValueReader` | 許可した表現を単一の固定型へ正規化する |
| `ArrayGet` | ACFネイティブ値、テンプレートの`$args`、API配列を読む |
| `ObjectGet` | 外部クラスやプラグインのpublicインスタンスプロパティを読む |
| `TreeGet` | 配列とオブジェクトが混在する木構造を一つの取得口で読む |

通常のWordPressテーマでは、主に`ArrayGet`をデータ境界で使用します。`ObjectGet`・`TreeGet`は、外部APIやプラグインが返す構造をそのまま読む必要がある場合の選択肢です。

各取得口が公開APIで受け入れるキー型は次のとおりです。

| 取得口 | キー型 |
|---|---|
| `ArrayGet` | `int|string` |
| `ObjectGet` | `string` |
| `TreeGet` | `int|string`。objectノードでは文字列だけが存在し得る |

数字名のオブジェクトプロパティも`'0'`のような文字列で指定します。`ObjectGet`へ整数を渡すとPHPの引数型検査で`TypeError`になります。

## ACFネイティブ値と引数配列

ACFフィールドを画像・投稿・選択肢などの用途として解釈する場合は`AcfGet`を使用します。独自フィールドや未変換値をPHP配列として読む場合は、`Native()`の結果を`ArrayGet`へ渡せます。

```php
use WpTypeSafety\Core\Check\ArrayGet;

$nativeSettings = $postGet->Native('plugin_settings');
$settingsGet = new ArrayGet($nativeSettings);

$color = $settingsGet->String('color');
$columns = $settingsGet->Int('columns');
```

テンプレートパーツやブロックへ渡された引数も同じ取得口で確認できます。

```php
$argsGet = new ArrayGet($args);

$heading = $argsGet->String('heading');
$isVisible = $argsGet->Bool('is_visible');
$items = $argsGet->StringList('items');
```

型が既知のView間引数やDTOまで`ArrayGet`で包む必要はありません。型が不定形な境界で使用します。

## 戻り値の基本契約

| Getter | 成功時 | 欠損・型違い |
|---|---|---|
| `String()` | `string` | `''` |
| `Int()` | `int` | `null` |
| `Float()` | `float` | `null` |
| `Bool()` | `bool` | `null` |
| `Array()` | `array` | `[]` |
| `Instance()` | 指定クラス | `null` |
| `BackedEnum()` | 指定Enum | `null` |
| 各`*List()` | `list<T>` | `[]`または不正要素を除外 |

`false`・`0`・空文字列は、対応する型として有効な値です。真偽値変換だけで取得成功と失敗を判定しません。

## 欠損と明示的なnull

`Exists()`は、キーまたはプロパティの存在を確認します。値として`null`が保存されている場合と、取得対象自体が存在しない場合を区別します。

```php
$valuesGet = new ArrayGet([
	'nullable' => null,
]);

$valuesGet->Exists('nullable'); // true
$valuesGet->Exists('missing');  // false
```

存在する`null`はNormalizerへ渡されます。欠損時はNormalizerを呼ばず、Getterごとの既定値を返します。

## 値の正規化

既定値は`ValueMode::Strict`です。数値文字列やWordPress由来の真偽値表現を許可する場合だけ、必要なモードを明示します。

```php
use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Check\ValueMode;

$valuesGet = new ArrayGet(
	['count' => '12'],
	ValueMode::NumericStr,
);

$count = $valuesGet->Int('count'); // 12
```

複数の許容規則や独自Normalizerが必要な場合は`ValueRules`を使用します。生成済み取得口はRulesをcloneして保持するため、外部から後で変更しても挙動は変わりません。

## 木構造の探索

`Nested()`は、指定ノードを同じ種類の取得口で包み直します。`NestedPath()`は可変長引数で複数階層を移動します。

```php
$profileGet = $argsGet->NestedPath('user', 'profile');
$name = $profileGet->String('name');
```

`ArrayGet`は配列だけ、`ObjectGet`はオブジェクトだけを同型のまま探索します。配列とオブジェクトが階層ごとに切り替わる場合は`TreeGet`を使用します。

```php
$responseGet = new TreeGet($response);
$itemGet = $responseGet->NestedPath('results', 0, 'item');
$title = $itemGet->String('title');
```

コンテナ型を明示的に切り替える場合は、`NestedArray()`・`NestedObject()`・`AsArrayGet()`・`AsObjectGet()`を使用します。

## List Getter

List Getterは配列全体を失敗扱いにせず、期待型へ正規化できない要素だけを除外します。結果は0から再採番した`list<T>`です。

```php
$valuesGet = new ArrayGet(
	['numbers' => ['1', 'invalid', 2]],
	ValueMode::NumericStr,
);

$numbers = $valuesGet->IntList('numbers'); // [1, 2]
```

すべての要素が不正な場合と、対象キーが欠損した場合は空配列を返します。

## predicate

Getterの`predicate`には、型確認・正規化に成功した値だけが渡されます。単一Getterでは条件不一致時に既定値を返し、List Getterでは条件に合わない要素を除外します。

```php
$age = $argsGet->Int(
	'age',
	predicate: static fn(int $value): bool => $value >= 18,
);

$positiveNumbers = $argsGet->IntList(
	'numbers',
	predicate: static fn(int $value): bool => $value > 0,
);
```

predicate内の例外は捕捉せず、そのまま呼び出し元へ伝播します。

## Transform

`Transform()`は指定ノードの生値をコールバックへ渡し、変換結果を新しい探索対象としてチェーンを継続します。

```php
$payloadGet = $argsGet->Transform(
	'payload',
	static fn(mixed $value): mixed =>
		is_string($value) ? json_decode($value, true) : null,
);

$title = $payloadGet->String('title');
```

欠損時はコールバックを実行せず、空の取得対象を返します。明示的に保存された`null`はコールバックへ渡します。コールバックの例外はそのまま伝播し、元の取得口は変更しません。

## 取得口の継承

公開Facadeの`ValueReader`・`ArrayGet`・`ObjectGet`・`TreeGet`は継承でき、案件固有Data用のGetterを追加できます。

```php
final class SiteArrayGet extends ArrayGet
{
	public function Post(int|string $key): ?PostData
	{
		return $this->Instance($key, PostData::class);
	}
}
```

`Nested()`・`NestedPath()`・`Transform()`・`WithMode()`・`WithRules()`と、同型要素を返す`NestedList()`は派生型を維持します。`NestedObject()`など別の取得口へ切り替えるメソッドは標準の公開Facadeを返します。

## 内部拡張契約

ここからはWpTypeSafety本体または将来の独立パッケージ化に向けた内部契約です。通常のテーマコードでは使用しません。

TreeTypeReader固有の取得口・型確認・正規化クラスは、役割を横断する`Check`階層を設けず、モジュール直下へ配置します。

```text
Internal/TreeTypeReader/
├─ ArrayGet.php
├─ ObjectGet.php
├─ TreeGet.php
├─ TreeTypeReader.php
├─ TypeCheck.php
├─ DictCheck.php
├─ ValueReader.php
├─ ValueRules.php
├─ ValueMode.php
├─ Data/
│  └─ ResolvedValue.php
└─ Internal/
   ├─ ValueResolver.php
   └─ ValuePredicate.php
```

`Data`には探索状態や取得結果を表す不変データを置きます。現在の`ResolvedValue`に加え、将来、厳密な取得結果・失敗理由・失敗したパスを表すDataを追加できる領域です。`Internal`には値解決やpredicate適用など、外部へ公開しないアルゴリズムだけを置きます。

`TreeTypeReader`は型付きGetterの処理本体をprotectedメソッドとして共有します。`ArrayGet`・`ObjectGet`・`TreeGet`は、取得元に合ったキー型を持つ薄い公開Getterからその共通処理を呼び出します。これにより処理の重複を抑えつつ、`ObjectGet`の公開シグネチャを`string`に限定しています。

派生取得口は、少なくとも次の2つを実装します。

```php
abstract protected function resolveValue(
	int|string $segment,
): ResolvedValue;

abstract protected function createChild(
	mixed $source,
): static;
```

`WpTypeSafety\Internal\TreeTypeReader\Data\ResolvedValue`は、欠損と明示的な`null`を区別します。

```php
new ResolvedValue(
	Exists: true,
	Value: null,
);
```

List Getterだけ取得方式が異なる取得元は、`resolveListValue()`を上書きします。

```php
protected function resolveListValue(
	int|string $segment,
): ResolvedValue {
	return $this->resolveValue($segment);
}
```

通常の配列・オブジェクト木では、単一GetterとList Getterは同じノードを解決します。`MetaGet`では、メタルートの単一Getterが`get_metadata(..., true)`相当、List Getterが`get_metadata(..., false)`相当を使用します。どちらも返すのは1つの`ResolvedValue`であり、List側は`Value`へ配列を保持します。

`ValueResolver`と`ValuePredicate`はアルゴリズムの実装詳細です。Core・Wp・Acfの派生取得口から直接参照せず、`TreeTypeReader`のprotected契約を通して利用します。

## 関連文書

- 公開Core APIの詳細：[docs/core.md](../../docs/core.md)
- WordPressメタとの接続：[docs/wordpress.md](../../docs/wordpress.md)
- ライブラリ全体の内部構造：[docs/architecture.md](../../docs/architecture.md)
