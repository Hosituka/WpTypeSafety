# Coreの型確認と配列・オブジェクト取得

CoreはWordPress・ACFへ依存せず、取得済みのPHP値だけを扱います。入力に応じて次の入口を選びます。

| 入力 | 入口 |
|---|---|
| 単独の値 | `TypeCheck` |
| 文字列キーと全要素の型をまとめて確認する辞書 | `DictCheck` |
| 配列内の値 | `ArrayGet` |
| objectのpublicインスタンスプロパティ | `ObjectGet` |
| ACFのフィールド種別を解釈して正規化したい値 | `AcfGet` |

`DictCheck`はキーごとの取得口ではなく、辞書全体が一つの型契約を満たすか確認する機能です。ACF固有の正規化が必要な場合はCoreではなく`AcfGet`を使用します。

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

`String()`、`Int()`、`Float()`、`Bool()`、`Array()`、`Instance($value, SomeClass::class)`を用意しています。`StringList()`、`IntList()`、`FloatList()`、`BoolList()`、`InstanceList($value, SomeClass::class)`は、入力が配列なら型違いの要素を除いて0からキーを振り直し、配列以外ならnullを返します。配列に合う要素がない場合は空配列です。`Array()`は要素を検証せず、連想配列のキーも保持します。

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
$items = $argsGet->InstanceList('items', MyCardData::class);
```

頻出するCore Dataには、クラス名を毎回渡さずに取得できる専用アクセサーがあります。

| 単数取得 | 複数取得 | 戻り値 |
|---|---|---|
| `ImgData()` | `ImgDataList()` | `?ImgData`・`list<ImgData>` |
| `LinkData()` | `LinkDataList()` | `?LinkData`・`list<LinkData>` |
| `ChoiceData()` | `ChoiceDataList()` | `?ChoiceData`・`list<ChoiceData>` |
| `FileData()` | `FileDataList()` | `?FileData`・`list<FileData>` |
| `TimeData()` | `TimeDataList()` | `?TimeData`・`list<TimeData>` |

単数取得は欠損・型違いで`null`、リスト取得は配列以外で空配列です。リスト取得では型違いの要素を除外し、0からキーを振り直します。案件固有DataやWp・Acf領域のクラスには、汎用の`Instance()`・`InstanceList()`を使用します。

取得済みのACFグループも同じ方法でたどれます。

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$postGet = new AcfGet($post);
$acfGet = new ArrayGet($postGet->Array('outer'));
$title = $acfGet->Nested('middle')->Nested('inner')->String('title');
```

`ArrayGet`はテンプレートパーツの`$args`や取得済みの連想配列からキーを取り出し、型判定を`TypeCheck`へ委ねます。コンストラクターには`mixed`を渡せます。配列以外は空配列として扱うため、`get_field()`の戻り値やリピーターの各行をそのまま渡せます。型変換やDTO生成は行いません。文字列は空文字、配列は空配列、それ以外はnullを型違い・未指定時に返します。`Instance()`と`InstanceList()`は案件固有データクラスにも使用できます。`*List()`は型違いの要素を除外して0からキーを振り直します。`Nested()`は単一の子配列を`ArrayGet`で包み、`NestedList()`は配列である各要素を`list<ArrayGet>`として返します。`NestedObject()`はobjectである単一の値を`ObjectGet`で包み、`NestedObjectList()`はobjectである各要素を`list<ObjectGet>`として返します。いずれもキーがない・値が対象の型でない場合は空の取得結果を返します。

WordPress専用の`PostData()`・`TermData()`・`WpUserData()`・`MenuNodeData()`と、それらの`*List()`は持ちません。`$argsGet->Instance('post', PostData::class)`、`$argsGet->InstanceList('posts', PostData::class)`のように型を指定します。これによりCoreからWp・Acfへの依存をなくしています。

```php
<?php
$fieldsGet = new ArrayGet(get_fields($postId));

$heading = $fieldsGet->Nested('hero')->String('heading');

foreach ($fieldsGet->NestedList('faq') as $rowGet) {
	$question = $rowGet->String('question');
	$answer = $rowGet->String('answer');
}
```

ACFの`get_fields()`は、フィールド名をキー、整形済みのフィールド値を値とする配列、またはフィールドがない場合に`false`を返します。`ArrayGet`は配列以外を空配列として扱うため、その戻り値を直接渡せます。Groupは`Nested()`、Repeaterなど配列行のリストは`NestedList()`でたどれます。

`NestedList()`はACFのフィールドタイプを解釈する機能ではありません。一般的な配列のうち、配列である要素だけを`ArrayGet`で包みます。ACFが整形した画像配列を`ImgData`へ変換したり、日付文字列を`DateTimeImmutable`へ変換したりはしません。ACFの型変換やフィールド定義の引き継ぎが必要な場合は`AcfGet::Repeater()`を使用します。フィールド名も文字列のままなので、`get_fields()`との組み合わせで保証するのは取得後のPHP型です。

同じPHPファイル内で取得から表示まで完結する場合は、`ArrayGet`を使わずDTOを直接参照して構いません。

## オブジェクトのpublicプロパティからの型付き取得

`ObjectGet`は、外部APIやプラグイン等が返すオブジェクトのpublicインスタンスプロパティを読み、型判定を`TypeCheck`へ委ねます。コンストラクターには`mixed`を渡せ、オブジェクト以外は空の取得元として扱います。

```php
<?php
use WpTypeSafety\Core\Check\ObjectGet;

$result = (object) [
	'count' => 12,
	'profile' => (object) [
		'name' => 'Sample',
	],
];

$resultGet = new ObjectGet($result);
$count = $resultGet->Int('count');
$profileGet = $resultGet->Nested('profile');
$name = $profileGet->String('name');
```

値はコンストラクター時に配列へ変換せず、アクセサーを呼ぶたびに同じオブジェクトの現在値を読みます。`ObjectGet`生成後のプロパティ追加・変更・削除は次の取得へ反映されますが、元変数へ別のオブジェクトを代入しても取得対象は変わりません。

対象は、初期化済みのpublicインスタンスプロパティ、`stdClass`のプロパティ、既存の動的プロパティ、public readonlyプロパティです。private・protected・static・未初期化プロパティ、存在しないプロパティ、`__get()`だけで提供される値、getterメソッドは対象外です。PHP 8.4以降のpublicなgetフックは通常のプロパティ読み取りとして実行し、フックが投げた例外はそのまま伝播します。

`Nested()`はobjectである単一プロパティを`ObjectGet`で包み、`NestedList()`は配列内のobjectだけを`list<ObjectGet>`として返します。`NestedArray()`はarrayである単一プロパティを`ArrayGet`で包み、`NestedArrayList()`は配列内のarrayだけを`list<ArrayGet>`として返します。これにより、配列とオブジェクトが交互に現れる木構造でも取得口を切り替えながらたどれます。プリミティブ値、型付きリスト、`Instance()`、Core Data用アクセサーの戻り値規則は`ArrayGet`と同じです。型変換、バリデーション、HTMLエスケープは行いません。

```php
<?php
$responseGet = new ObjectGet($response);
$settingsGet = $responseGet->NestedArray('settings');
$ownerGet = $settingsGet->NestedObject('owner');
$ownerName = $ownerGet->String('name');
```

WordPressやプラグインの値が整数と数値文字列の間で揺れる場合、`Int()`は数値文字列を変換せず`null`を返します。揺れを正規化する必要がある場合は、対象に対応するGet・Factory・Adapterを使用します。通常の型付きDataや正式なgetterがあるクラスでは、それらを直接使うことを優先します。
