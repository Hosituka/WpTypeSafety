# Coreの型確認と配列取得

## 取得値の型確認：TypeCheck

`TypeCheck`は渡された値を変換せず、期待するPHPの型であればそのまま返します。型違い・nullなら常にnullです。例えば数値文字列`'4'`は`Int()`では受け入れません。正しい値の`0`、`false`、空文字、空配列は保持します。

```php
<?php
use WpTypeSafety\Core\Check\TypeCheck;

$count = TypeCheck::Int($args['count'] ?? null) ?? 4;
$title = TypeCheck::String($args['title'] ?? null) ?? '無題';
$enabled = TypeCheck::Bool($args['enabled'] ?? null) ?? false;
```

ACFのNumberフィールドは数値文字列を返すことがあるため、`TypeCheck::Int(get_field(...))`ではなく`AcfGet::Int()`または`AcfGet::Number()`を使用します。

`String()`、`Int()`、`Float()`、`Bool()`、`Array()`、`Instance($value, SomeClass::class)`を用意しています。`StringArray()`、`IntArray()`、`FloatArray()`、`BoolArray()`、`InstanceArray($value, SomeClass::class)`は、入力が配列なら型違いの要素を除いて0からキーを振り直し、配列以外ならnullを返します。配列に合う要素がない場合は空配列です。`Array()`は要素を検証しません。

## 連想配列の型確認：DictCheck

`DictCheck`は文字列キーの連想配列を確認し、元のキーを保って返します。`Instances($value, SomeClass::class)`は`array<string, SomeClass>`、`InstanceLists($value, SomeClass::class)`は`array<string, list<SomeClass>>`を確認します。入力が配列でない場合や、キー・値・リスト内の要素に型違いが一つでもある場合は、部分的な結果を返さず`null`にします。空の辞書と空のリストは有効です。PHPでは数字だけの文字列キーが整数キーへ変わるため、そのキーは受け入れません。

```php
<?php
use WpTypeSafety\Core\Check\DictCheck;
use WpTypeSafety\Wp\Data\TermData;

$termsByTaxonomy = DictCheck::InstanceLists($rawTerms, TermData::class);
if ($termsByTaxonomy !== null) {
	$categoryTerms = $termsByTaxonomy['category'] ?? [];
}
```

## 連想配列からの型付き取得

```php
<?php
use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Data\ImgData;

$argsGet = new ArrayGet($args);
$title = $argsGet->String('title') ?: '代替タイトル';
$image = $argsGet->ImgData('image');
$items = $argsGet->InstanceArray('items', MyCardData::class);
```

頻出するCore Dataには、クラス名を毎回渡さずに取得できる専用アクセサーがあります。

| 単数取得 | 複数取得 | 戻り値 |
|---|---|---|
| `ImgData()` | `ImgDataArray()` | `?ImgData`・`list<ImgData>` |
| `LinkData()` | `LinkDataArray()` | `?LinkData`・`list<LinkData>` |
| `ChoiceData()` | `ChoiceDataArray()` | `?ChoiceData`・`list<ChoiceData>` |
| `FileData()` | `FileDataArray()` | `?FileData`・`list<FileData>` |
| `TimeData()` | `TimeDataArray()` | `?TimeData`・`list<TimeData>` |

単数取得は欠損・型違いで`null`、複数取得は配列以外で空配列です。複数取得では型違いの要素を除外し、0からキーを振り直します。案件固有DataやWp・Acf領域のクラスには、汎用の`Instance()`・`InstanceArray()`を使用します。

取得済みのACFグループも同じ方法でたどれます。

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$postGet = new AcfGet($post);
$acfGet = new ArrayGet($postGet->Array('outer'));
$title = $acfGet->Nested('middle')->Nested('inner')->String('title');
```

`ArrayGet`はテンプレートパーツの`$args`や取得済みの連想配列からキーを取り出し、型判定を`TypeCheck`へ委ねます。コンストラクターには`mixed`を渡せます。配列以外は空配列として扱うため、`get_field()`の戻り値やリピーターの各行をそのまま渡せます。型変換やDTO生成は行いません。文字列は空文字、配列は空配列、それ以外はnullを型違い・未指定時に返します。`Instance()`と`InstanceArray()`は案件固有データクラスにも使用できます。`*Array()`は型違いの要素を除外して0からキーを振り直します。`Nested()`は単一の子配列を`ArrayGet`で包み、`NestedArray()`は配列である各要素を`list<ArrayGet>`として返します。どちらもキーがない・値が対象の配列でない場合は空の取得結果を返します。

WordPress専用の`PostData()`・`TermData()`・`WpUserData()`・`MenuNodeData()`と、それらの`*Array()`は持ちません。`$argsGet->Instance('post', PostData::class)`、`$argsGet->InstanceArray('posts', PostData::class)`のように型を指定します。これによりCoreからWp・Acfへの依存をなくしています。

```php
<?php
$fieldsGet = new ArrayGet(get_fields($postId));

$heading = $fieldsGet->Nested('hero')->String('heading');

foreach ($fieldsGet->NestedArray('faq') as $rowGet) {
	$question = $rowGet->String('question');
	$answer = $rowGet->String('answer');
}
```

ACFの`get_fields()`は、フィールド名をキー、整形済みのフィールド値を値とする配列、またはフィールドがない場合に`false`を返します。`ArrayGet`は配列以外を空配列として扱うため、その戻り値を直接渡せます。Groupは`Nested()`、Repeaterなど配列行のリストは`NestedArray()`でたどれます。

`NestedArray()`はACFのフィールドタイプを解釈する機能ではありません。一般的な配列のうち、配列である要素だけを`ArrayGet`で包みます。ACFが整形した画像配列を`ImgData`へ変換したり、日付文字列を`DateTimeImmutable`へ変換したりはしません。ACFの型変換やフィールド定義の引き継ぎが必要な場合は`AcfGet::Repeater()`を使用します。フィールド名も文字列のままなので、`get_fields()`との組み合わせで保証するのは取得後のPHP型です。

同じPHPファイル内で取得から表示まで完結する場合は、`ArrayGet`を使わずDTOを直接参照して構いません。
