# Coreの型確認と汎用reader

CoreはWordPress・ACFへ依存せず、取得済みのPHP値を扱います。木探索、型付きGetter、正規化規則、predicate、`Transform()`の詳しい契約は、正本である[TreeTypeReader README](../Internal/TreeTypeReader/README.md)を参照してください。

この文書では、入口の選び方とWpTypeSafetyが追加する機能だけを説明します。

## 入口を選ぶ

| 入力 | 入口 |
| --- | --- |
| 単独の値を変換せず確認する | `TypeCheck` |
| 単独の値を規則に従って正規化する | `ValueReader` |
| 辞書全体のインスタンス型を確認する | `DictCheck` |
| 配列内の値 | `ArrayGet` |
| publicインスタンスプロパティ | `ObjectGet` |
| 配列とオブジェクトが混在する木 | `TreeGet` |
| ACFフィールドの意味まで解釈する | `AcfGet` |

`TypeCheck`、`ValueReader`、`DictCheck`、各Getの基本動作はTreeTypeReaderと同じです。ACFの画像、参照、日時、選択肢などはCoreで推測せず、[AcfGet](acf.md)を使用します。

## 最小例

```php
use WpTypeSafety\Core\Check\ArrayGet;

$get = new ArrayGet($args ?? []);

$title = $get->String('title');
$count = $get->Int('count');
$items = $get->NestedList('items');
```

既定値はStrictで、PHP上の型が一致する値だけを受け入れます。数値文字列などを許可する場合だけmodeまたは`ValueRules`を指定します。

```php
use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Check\Data\NumericMode;

$get = new ArrayGet(['count' => '12'], NumericMode::Str);
$count = $get->Int('count'); // 12
```

## WpTypeSafetyが追加するCore Data Getter

WpTypeSafetyの`ArrayGet`、`ObjectGet`、`TreeGet`には、Core Dataをクラス名なしで取得するショートカットがあります。

| 単数 | リスト | 型 |
| --- | --- | --- |
| `ImgData()` | `ImgDataList()` | `ImgData` |
| `LinkData()` | `LinkDataList()` | `LinkData` |
| `ChoiceData()` | `ChoiceDataList()` | `ChoiceData` |
| `FileData()` | `FileDataList()` | `FileData` |
| `TimeData()` | `TimeDataList()` | `TimeData` |
| `DateData()` | `DateDataList()` | `DateData` |

```php
$image = $get->ImgData('image');
$links = $get->LinkDataList('links');
```

内部ではTreeTypeReaderの`Instance()`と`InstanceList()`へ委譲します。単数は型不一致時に`null`、リストは配列から型が一致する要素だけを返します。

## 規則を途中で切り替える

WpTypeSafetyの公開readerは`WithModes()`と`WithRules()`を提供します。新しいreaderを返し、元のreaderは変更しません。

```php
use WpTypeSafety\Core\Check\Data\NumericMode;

$numeric = $get->WithModes(NumericMode::Str);
```

`WithModes()`は規則を追加するのではなく、指定したmodeから作った規則へ置き換えます。引数なしならStrictへ戻ります。

## 案件固有Getter

readerを継承して、案件固有DataのGetterを追加できます。`Nested()`、`NestedPath()`、`Transform()`、`WithModes()`、`WithRules()`は派生型を維持します。

```php
use WpTypeSafety\Core\Check\ArrayGet;

final class SiteArrayGet extends ArrayGet
{
	public function Card(int|string $key): ?CardData
	{
		return $this->Instance($key, CardData::class);
	}
}
```

## 別種類のreaderの生成フック

配列からオブジェクトへ取得口の種類を切り替える`NestedObject()`などは、既定では標準の公開readerを返します。切替後も案件固有Getterが必要なら、protectedな生成フックを上書きします。

| 継承元 | フック | 適用される取得口 |
|---|---|---|
| `ArrayGet` | `createObjectReader(mixed $source): ObjectGet` | `NestedObject()`・`NestedObjectList()` |
| `ObjectGet` | `createArrayReader(mixed $source): ArrayGet` | `NestedArray()`・`NestedArrayList()` |
| `TreeGet` | 上記の両フック | 対応する`Nested*()`に加え`AsArrayGet()`・`AsObjectGet()` |

```php
use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Check\ObjectGet;

class SiteArrayGet extends ArrayGet
{
	protected function createObjectReader(mixed $source): ObjectGet
	{
		return new SiteObjectGet($source, $this->publicRulesSnapshot());
	}
}
```

`SiteObjectGet`は案件側が定義する`ObjectGet`の派生クラスです。フックには対象の値が渡され、`publicRulesSnapshot()`で現在の規則のコピーを取得できます。フックはReaderの生成だけを担当し、要素の型判定・リスト走査・連番化は親側で行います。単数の`Nested*()`は欠損・型違いでもフックを呼び、既定のReaderが空の取得口へ正規化します。リストは適合要素ごとに1回だけ呼び、`As*()`は元のコンテナ型が一致するときだけ呼びます。案件側も空Readerへのフォールバックと規則の引き継ぎを維持してください。

同じ種類への`Nested()`・`NestedList()`等は`createChild()`を使い、別種類のReader生成フックは使いません。`MetaGet`も公開`TreeGet`の生成処理を継承します。メタ取得の解決と子へ進んだときのメタ状態解除は`MetaGet`が担当します。

### 切替先の静的型を指定する

汎用実装で宣言した型パラメーターを、公開Facadeも引き継ぎます。`ArrayGet<TObjectReader>`・`ObjectGet<TArrayReader>`・`TreeGet<TArrayReader, TObjectReader>`で切替先を指定できます。型引数の既定値は各領域の標準readerなので、通常利用で指定する必要はありません。同型への`Nested()`等は引き続き`static`で派生型を維持します。

```php
/** @extends ArrayGet<SiteObjectGet> */
class SiteArrayGet extends ArrayGet
{
	/** @return SiteObjectGet */
	protected function createObjectReader(mixed $source): ObjectGet
	{
		return new SiteObjectGet($source, $this->publicRulesSnapshot());
	}
}

/** @extends ObjectGet<SiteArrayGet> */
class SiteObjectGet extends ObjectGet
{
	/** @return SiteArrayGet */
	protected function createArrayReader(mixed $source): ArrayGet
	{
		return new SiteArrayGet($source, $this->publicRulesSnapshot());
	}
}

$siteGet = new SiteArrayGet($args);
$objectGet = $siteGet->NestedObject('profile'); // SiteObjectGet
$objectGets = $siteGet->NestedObjectList('profiles'); // list<SiteObjectGet>
$arrayGet = $objectGet->NestedArray('details'); // SiteArrayGet
```

上の2つのコード例は独立した例で、同じクラスを重複定義するものではありません。`TreeGet`の案件版は`@extends TreeGet<SiteArrayGet, SiteObjectGet>`を指定して両方の生成フックを上書きします。これにより`AsArrayGet()`・`AsObjectGet()`のnullableな戻り値にも型引数が反映されます。`Nested*()`の公開メソッドを型指定だけのために上書きする必要はありません。

PHPDocの型引数は生成処理を変更しません。標準reader以外を型引数に指定した派生クラスは、対応する生成フックを必ず上書きし、宣言した型のreaderを返してください。PHPDocだけの指定は実行時の型保証にはなりません。型パラメーターは戻り値専用の共変型として宣言しています。

静的型の回帰テストは`tests/phpstan/reader-types.php`へ分離しています。PHPStanを開発環境へ導入している場合は次で実行できます。WordPress・ACFの起動は不要です。

```powershell
vendor\bin\phpstan.bat analyse -c tests/phpstan/readers.neon
```

設定は対象6クラスと型アサーションをレベル8で解析し、実行時テストの案件版reader宣言は`scanFiles`で参照するだけにしています。通常の`tests/*.php`の実行とは別に検証してください。PHPStanそのものはライブラリの実行時依存には追加しません。

## 責務の境界

Coreが行うのは、PHP値の存在確認、型確認、明示した規則による正規化です。次は対象外です。

- ACFフィールドタイプやreturn formatの解釈
- WordPress IDからオブジェクトを取得する処理
- URLや画像などの意味的な変換
- 入力スキーマ全体のvalidation
- HTML出力のescaping

これらは[ACF](acf.md)、[WordPress](wordpress.md)、案件固有validation、WordPressのescaping関数へ分けてください。

## TreeTypeReader READMEの同期

`Internal/TreeTypeReader/README.md`は、独立プロジェクト`TreeTypeReader/README.md`を正本とする同期コピーです。手作業で内容を分岐させず、次を実行して更新します。

```powershell
.\tools\SyncTreeTypeReaderReadme.ps1 -SourceProject 'C:\path\to\TreeTypeReader'
```
