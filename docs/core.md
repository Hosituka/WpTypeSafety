# Coreの型確認と配列・オブジェクト取得

CoreはWordPress・ACFへ依存せず、取得済みのPHP値だけを扱います。入力に応じて次の入口を選びます。

| 入力 | 入口 |
|---|---|
| 単独の値 | `TypeCheck` |
| 文字列キーと全要素の型をまとめて確認する辞書 | `DictCheck` |
| 配列内の値 | `ArrayGet` |
| objectのpublicインスタンスプロパティ | `ObjectGet` |
| ACFのフィールド種別を解釈して正規化したい値 | `AcfGet` |

`DictCheck`はキーごとの取得口ではなく、辞書全体が一つの型契約を満たすか確認する機能です。`ArrayGet`と`ObjectGet`は既定で厳密な型確認を行い、必要な境界だけ数値の正規化規則へ切り替えられます。ACFのフィールド種別や返却形式まで解釈する必要がある場合はCoreではなく`AcfGet`を使用します。

## 取得値の型確認：TypeCheck

`TypeCheck`は渡された値を変換せず、期待するPHPの型であればそのまま返します。型違い・nullなら常にnullです。例えば数値文字列`'4'`は`Int()`では受け入れません。正しい値の`0`、`false`、空文字、空配列は保持します。

```php
<?php
use WpTypeSafety\Core\Check\TypeCheck;

$count = TypeCheck::Int($args['count'] ?? null) ?? 4;
$title = TypeCheck::String($args['title'] ?? null) ?? '無題';
$enabled = TypeCheck::Bool($args['enabled'] ?? null) ?? false;
```

ACFのNumberフィールドは数値文字列を返すことがあるため、`TypeCheck::Int(get_field(...))`ではなく`AcfGet::Int()`または`AcfGet::Float()`を使用します。

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
use WpTypeSafety\Core\Check\ArrayGet;

$postGet = new AcfGet($post);
$acfGet = new ArrayGet($postGet->Array('outer'));
$title = $acfGet->Nested('middle')->Nested('inner')->String('title');
```

`ArrayGet`はテンプレートパーツの`$args`や取得済みの連想配列からキーを取り出します。コンストラクターには`mixed`を渡せます。配列以外は空配列として扱うため、`get_field()`の戻り値やリピーターの各行をそのまま渡せます。既定の`ValueMode::Strict`では型判定を`TypeCheck`と同じ基準で行い、値を変換しません。文字列は空文字、配列は空配列、それ以外はnullを型違い・未指定時に返します。`Instance()`と`InstanceList()`は案件固有データクラスにも使用できます。`*List()`は受け入れられない要素を除外して0からキーを振り直し、すべての要素が除外された場合は空配列を返します。

数値がPHPの数値型と数値文字列の間で揺れる境界では、第2引数に`ValueMode::NumericStr`を指定できます。

```php
<?php
use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Check\ValueMode;

$resultGet = new ArrayGet([
	'count' => '12',
	'ratio' => '1.5',
], ValueMode::NumericStr);

$count = $resultGet->Int('count');   // 12
$ratio = $resultGet->Float('ratio'); // 1.5
```

`NumericStr`の`Int()`は、int、整数として正確に表現できるfloat、符号と数字だけからなるPHP整数範囲内の文字列を受け入れます。小数文字列、指数表記、空白付き文字列、範囲外の整数は受け入れません。`Float()`はPHPの`is_numeric()`で数値と判定できる値をPHPのfloat変換規則に従って正規化します。元の数値文字列の完全な精度保存や有限値であることまでは保証せず、巨大な数値文字列は`INF`になる場合があります。`String()`と`Bool()`はこのモードでも厳密なままです。対応する`IntList()`と`FloatList()`も各要素へ同じ規則を適用します。

`ValueMode::BoolStr`は、元からboolである値に加えて、文字列`'true'`・`'false'`・`'1'`・`'0'`を`Bool()`と`BoolList()`で受け入れます。整数の`1`・`0`や、`'yes'`・`'on'`等の文字列は変換しません。

`ValueMode::BoolInt`は、元からboolである値に加えて、整数`1`を`true`、整数`0`を`false`として`Bool()`と`BoolList()`で受け入れます。文字列`'1'`・`'0'`や、それ以外の整数は変換しません。

`ValueMode::EmptyStrAsFalse`は、元からboolである値に加えて空文字を`false`として受け入れます。真偽値を表す文字列や整数の変換とは別の規則であり、必要に応じて`BoolStr`・`BoolInt`と合成します。欠損値の`null`は`false`へ変換しません。

同じ取得範囲で複数の追加規則を使う場合は、`ValueRules::FromModes()`で合成します。合成は常にStrictな規則から始まり、各モードが担当するNormalizerだけを変更します。`Strict`の指定は何も追加しないため、途中または最後に指定しても、それまでの追加規則を解除しません。同じモードを複数回指定しても受け入れる値は変わりません。

```php
<?php
use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Check\ValueMode;
use WpTypeSafety\Core\Check\ValueRules;

$rules = ValueRules::FromModes(
	ValueMode::NumericStr,
	ValueMode::BoolStr,
	ValueMode::BoolInt,
	ValueMode::EmptyStrAsFalse,
);

$resultGet = new ArrayGet($result, $rules);
$count = $resultGet->Int('count');
$enabled = $resultGet->Bool('enabled');
```

複数のモードが同じNormalizerへ規則を追加する場合も、既存の判定を包んで許可条件を増やすため、指定順によって一方の規則が失われることはありません。

backed Enumは、`BackedEnum()`で文字列・整数のbacking valueから対応するcaseへ正規化できます。値がすでに指定したEnumのインスタンスなら、そのまま返します。欠損、backing型の不一致、定義されていない値では`null`を返します。`BackedEnumList()`は配列内の変換できない要素を除外し、すべて除外された場合や欠損時は空配列を返します。

```php
<?php
use WpTypeSafety\Core\Check\ArrayGet;

enum PublishStatus: string
{
	case Draft = 'draft';
	case Published = 'published';
}

$postGet = new ArrayGet([
	'status' => 'published',
	'allowed_statuses' => ['draft', 'invalid', 'published'],
]);

$status = $postGet->BackedEnum('status', PublishStatus::class);
$allowedStatuses = $postGet->BackedEnumList('allowed_statuses', PublishStatus::class);
```

int-backed Enumのbacking valueは、既定の`Strict`ではintだけを受け入れます。数値文字列も受け入れる場合は`NumericStr`を指定します。unbacked Enumの既存インスタンスを確認するだけなら、従来どおり`Instance()`・`InstanceList()`を使用します。`ObjectGet`にも同じ`BackedEnum()`・`BackedEnumList()`があります。

一部の基準だけを案件固有に差し替える場合は、`ValueRules::FromMode()`で基準を作り、publicなNormalizerを上書きして渡します。

```php
<?php
use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Check\ValueMode;
use WpTypeSafety\Core\Check\ValueRules;

$rules = ValueRules::FromMode(ValueMode::Strict);
$rules->BoolNormalizer = static function (mixed $value): ?bool {
	return match ($value) {
		'enabled' => true,
		'disabled' => false,
		default => null,
	};
};

$settingsGet = new ArrayGet($settings, $rules);
```

欠損したキーまたはプロパティではNormalizerを呼ばず、Accessor既定の失敗値を返します。一方、存在する値として明示された`null`と、リスト内に存在する`null`要素はNormalizerへ渡します。標準Rulesは`null`を受け入れませんが、カスタムNormalizerでは意味のある値へ変換できます。

```php
<?php
use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Check\TypeCheck;
use WpTypeSafety\Core\Check\ValueMode;
use WpTypeSafety\Core\Check\ValueRules;

$rules = ValueRules::FromMode(ValueMode::Strict);
$rules->IntNormalizer = static fn(mixed $value): ?int => $value === null
	? 0
	: TypeCheck::Int($value);

$missingGet = new ArrayGet([], $rules);
$nullGet = new ArrayGet(['count' => null], $rules);

$missing = $missingGet->Int('count'); // null。Normalizerを呼ばない
$count = $nullGet->Int('count');      // 0。明示的なnullを正規化
```

NormalizerプロパティのPHPDocは、PHPStanやPsalm等の静的解析でClosureの引数・戻り値型を検査するための契約です。PHPはClosure代入時にそのシグネチャを検査しませんが、実行結果は`NormalizeString(): ?string`・`NormalizeInt(): ?int`・`NormalizeFloat(): ?float`・`NormalizeBool(): ?bool`のPHP戻り値型でも検査されます。不正な型を返すNormalizerは呼び出し時に`TypeError`になります。カスタムNormalizerを利用するコードでは静的解析の併用を推奨します。

`ArrayGet`と`ObjectGet`は受け取ったRulesを複製して保持します。そのため、生成後に元のRulesのNormalizerプロパティを書き換えても既存の取得口は変化しません。`Nested()`・`NestedList()`・`NestedObject()`・`NestedObjectList()`で生成される子取得口には、保持している同じ基準のスナップショットが引き継がれます。Closureが外部の可変オブジェクトをキャプチャしている場合、そのオブジェクトの状態まではcloneで固定しません。`Array()`・`Instance()`・Core Data用アクセサーはRulesの対象外で、従来どおり元のPHP型を確認します。

チェーンの途中から判定基準を切り替える場合は、`WithMode()`または`WithRules()`を使用します。どちらも同じ取得元を持つ新しい取得口を返し、呼び出し元の取得口は変更しません。切り替え後に生成したNested系の子取得口には、新しく選んだ基準が引き継がれます。

```php
<?php
use WpTypeSafety\Core\Check\ValueMode;

$count = $fieldsGet
	->Nested('acf_values')
	->WithMode(ValueMode::NumericStr)
	->Int('count');

$strictValue = $fieldsGet
	->WithMode(ValueMode::NumericStr)
	->Nested('external')
	->WithMode(ValueMode::Strict)
	->Int('strict_value');
```

`Nested()`は単一の子配列を`ArrayGet`で包み、`NestedList()`は配列である各要素を`list<ArrayGet>`として返します。`NestedObject()`はobjectである単一の値を`ObjectGet`で包み、`NestedObjectList()`はobjectである各要素を`list<ObjectGet>`として返します。いずれもキーがない・値が対象の型でない場合は空の取得結果を返します。

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

`NestedList()`はACFのフィールドタイプを解釈する機能ではありません。一般的な配列のうち、配列である要素だけを`ArrayGet`で包みます。数値モードや独自Rulesを指定しても、ACFが整形した画像配列を`ImgData`へ変換したり、日付文字列を`DateTimeImmutable`へ変換したりはしません。ACFの型変換やフィールド定義の引き継ぎが必要な場合は`AcfGet::Repeater()`を使用します。フィールド名も文字列のままなので、`get_fields()`との組み合わせで保証するのは取得後のPHP型です。

同じPHPファイル内で取得から表示まで完結する場合は、`ArrayGet`を使わずDTOを直接参照して構いません。

## オブジェクトのpublicプロパティからの型付き取得

`ObjectGet`は、外部APIやプラグイン等が返すオブジェクトのpublicインスタンスプロパティを読みます。コンストラクターには`mixed`を渡せ、オブジェクト以外は空の取得元として扱います。第2引数には`ArrayGet`と同じ`ValueMode`または`ValueRules`を指定できます。

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

`Nested()`はobjectである単一プロパティを`ObjectGet`で包み、`NestedList()`は配列内のobjectだけを`list<ObjectGet>`として返します。`NestedArray()`はarrayである単一プロパティを`ArrayGet`で包み、`NestedArrayList()`は配列内のarrayだけを`list<ArrayGet>`として返します。これにより、配列とオブジェクトが交互に現れる木構造でも取得口を切り替えながらたどれます。ValueMode・Rulesと、プリミティブ値・型付きリスト・`Instance()`・Core Data用アクセサーの戻り値規則は`ArrayGet`と同じです。HTMLエスケープや値の意味に関するバリデーションは行いません。

```php
<?php
$responseGet = new ObjectGet($response);
$settingsGet = $responseGet->NestedArray('settings');
$ownerGet = $settingsGet->NestedObject('owner');
$ownerName = $ownerGet->String('name');
```

WordPressやプラグインの値が整数と数値文字列の間で揺れるだけなら、`ValueMode::NumericStr`を指定できます。フィールド種別、ID参照、日時、画像等の意味まで解釈する場合は、対象に対応するGet・Factory・Adapterを使用します。通常の型付きDataや正式なgetterがあるクラスでは、それらを直接使うことを優先します。
