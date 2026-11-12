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

配列からオブジェクトへ取得口の種類を切り替える`NestedObject()`などは標準の取得口を返します。切替後も案件固有Getterが必要なら、派生クラス側で対応するメソッドを上書きしてください。

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
