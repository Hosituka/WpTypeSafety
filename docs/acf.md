# ACFの取得API

## 目次

- [標準取得口の一覧](#標準取得口の一覧)
- [数値フィールドの取得](#数値フィールドの取得)
- [ACFの取得先の指定](#acfの取得先の指定)
- [投稿・ターム・ユーザーの複数取得](#投稿タームユーザーの複数取得)
- [ACF画像・ファイルの取得](#acf画像ファイルの取得)
- [日付・日時・時刻の取得](#日付日時時刻の取得)
- [選択系フィールド](#ラジオボタングループチェックボックスの取得口)
- [案件固有フィールドへの拡張](#案件固有フィールドへの拡張)
- [リピーター行の取得](#リピーター行の取得)
- [フレキシブルコンテンツの取得](#フレキシブルコンテンツの取得)
- [ACFブロックの描画情報](#acfブロックの描画情報)

## 標準取得口の一覧

`AcfGetTerm`・`AcfGetUser`は`AcfGet`を継承しているため、同じ取得口を利用できます。型違い・未入力時は、単数Dataとnullableな値が`null`、文字列が空文字、リストと配列が空配列です。

| 取得口 | 戻り値 | 用途・契約 |
|---|---|---|
| `Text()` | `string` | 文字列。未入力・型違いは空文字 |
| `Number()` | `?float` | 整数・float・数値文字列をfloatへ正規化。数値以外は`null` |
| `Int()` | `?int` | 整数・整数相当のfloat・整数形式の数値文字列をintへ正規化。それ以外は`null` |
| `Bool()` | `?bool` | 真偽値。型違いは`null` |
| `Link()` | `?LinkData` | ACFリンク配列 |
| `Url()` | `?string` | HTTP(S)の絶対URL |
| `Img()` | `?ImgData` | ID・寸法を取得できる画像 |
| `ImgUrl()` | `?string` | URL返却形式の画像 |
| `Gallery()` | `list<ImgData>` | 画像情報を持つギャラリー |
| `GalleryUrl()` | `list<string>` | URL返却形式のギャラリー |
| `File()` | `?FileData` | ID・ファイルサイズを取得できるファイル |
| `FileUrl()` | `?string` | URL返却形式のファイル |
| `Date()` | `?DateTimeImmutable` | Date Picker。時刻は00:00:00 |
| `DateTime()` | `?DateTimeImmutable` | Date Time Picker |
| `Time()` | `?TimeData` | Time Picker。日付を含まない時刻 |
| `Post()`・`Posts()` | `?PostData`・`list<PostData>` | 投稿オブジェクト・関連フィールド |
| `Term()`・`Terms()` | `?TermData`・`list<TermData>` | タクソノミーフィールド |
| `User()`・`Users()` | `?WpUserData`・`list<WpUserData>` | ユーザーフィールド |
| `Nested()` | `AcfGet` | グループなどの子配列へ進む |
| `Array()` | `array<mixed>` | 生配列。要素型は保証しない |
| `Repeater()` | `list<AcfGet>` | リピーターの各行 |
| `Flexible()` | `list<FlexibleNode>` | レイアウト名と行の取得口 |

ラジオ・ボタングループ・チェックボックス・セレクトは、ACFの返り値設定に対応する専用取得口を使います。詳細は各節を参照してください。

## ACF文字列の未入力時の扱い

`AcfGet->Text()`・`AcfGetUser->Text()`・`AcfGetTerm->Text()`は、文字列以外・空文字・空白だけの値を空文字として返します。フォールバック値を渡す引数は設けず、用途ごとの代替値は呼び出し側の`?:`で指定します。

```php
<?php
$title = (new AcfGet($post))->Text('title') ?: '代替タイトル';
$label = (new AcfGet($row))->Text('label') ?: '代替ラベル';
$color = $termGet->Text('display_color') ?: 'var(--black)';
```

`?:`は文字列`'0'`も代替値の対象にします。この文言取得の運用ではそれを許容し、数値として扱う値は数値用の取得口で取得します。`Text()`自体が`'0'`を空文字へ変換するわけではありません。`ArrayGet::String()`もフォールバック引数は設けず、呼び出し側で`?:`を使います。

## 数値フィールドの取得

ACFのNumberフィールドはWordPressのメタ値を基にするため、`get_field()`から数値文字列が返ることがあります。小数を含む数値として扱う場合は`Number()`、整数に限定する場合は`Int()`を使用します。

```php
<?php
$price = $postGet->Number('price'); // "12.5"も12.5へ正規化
$count = $postGet->Int('count');    // "12"も12へ正規化
```

`Number()`はPHPの`is_numeric()`に従うため、前後に空白がある数値文字列や指数表記も受け入れて`float`へ変換します。`Int()`は整数、整数として正確に表現できるfloat、符号と数字だけで構成された整数形式の文字列を受け入れます。小数文字列・指数表記・空白を含む文字列・PHPの整数範囲外は`null`です。`TypeCheck::Int()`は変換を行わず、最初から`int`である値だけを通すCore APIです。

## ACFの取得先の指定

グループ内の値を読む場合は、`Nested()`で子の`AcfGet`を取得できます。配列そのものが必要な場合は`Array()`を使います。

```php
<?php
$postGet = new AcfGet($post);
$firstViewGet = $postGet->Nested('first_view');
$label = $firstViewGet->Text('label');
$title = $postGet->Nested('outer')->Nested('inner')->Text('title');
```

`Nested()`は呼び出した時点の`Array()`の結果を保持します。未設定・配列以外の値は空の取得元になり、続く`Text()`は空文字、`Img()`はnullを返します。投稿・オプションページ・ユーザー・タームを取得元にしたACFグループでは、整形済み配列に加えて未整形値と子フィールド定義も保持するため、グループ内でも`Date()`・`DateTime()`・`Time()`を利用できます。`AcfGetUser`・`AcfGetTerm`からも使え、戻り値は`AcfGet`です。

単一選択のACFユーザーフィールド・タクソノミーフィールドが参照する先のACF値を読むときは、`UserFields()`・`TermFields()`を使えます。内部では既存の`User()`・`Term()`で対象を取得し、`AcfGetUser`・`AcfGetTerm`へ渡します。未選択・型違い・取得失敗なら、空の取得元を返します。`UserFields()`はアバター画像を取得しません。アバター画像のサイズは、`User()`などで得た`WpUserData`の`GetAvatar()`を呼ぶときに決まります。`AcfGet`・`AcfGetUser`・`AcfGetTerm`のいずれからも呼べます。

```php
<?php
$postGet = new AcfGet($post);
$authorName = $postGet->UserFields('author')->Text('profile_name');
$termColor = $postGet->TermFields('category')->Text('display_color');
$groupAuthorName = $postGet->Nested('settings')->UserFields('author')->Text('profile_name');
```

`Nested()`は同じ取得結果内の配列へ進み、`UserFields()`・`TermFields()`は参照先のユーザー・タームへ取得対象を切り替えます。これらは単一値用です。ACFフィールドで複数選択を許可した場合の配列は対象にせず、空の取得元になります。投稿の標準投稿者や通常のタクソノミー割り当てを読むメソッドでもありません。

## 投稿・ターム・ユーザーの複数取得

複数選択を許可した投稿オブジェクト・関連・タクソノミー・ユーザーフィールドには、`Posts()`・`Terms()`・`Users()`を使います。`AcfGet`・`AcfGetUser`・`AcfGetTerm`および`Nested()`で利用できます。

```php
<?php
$relatedPosts = $postGet->Posts('related_posts');
$categories = $postGet->Terms('categories');
$members = $postGet->Users('members', avatarSize: 160);
```

戻り値はそれぞれ`list<PostData>`・`list<TermData>`・`list<WpUserData>`です。ACFの返却形式がオブジェクトまたはIDのどちらでも変換し、未入力・配列以外は`[]`、取得・変換できない要素は除外します。入力順と重複は維持します。`Users()`の`avatarSize`は各`WpUserData`へ設定するアバター取得用Resolverに渡されます。

単数用の`Post()`・`Term()`・`User()`へ複数値を渡しても、先頭要素を推測して返すことはありません。同様に、複数用メソッドへ単一値を渡した場合も空配列です。投稿の標準タームや投稿者を取得するAPIではなく、指定したACFフィールドの値だけを対象にします。

`AcfGet`のコンストラクターは`WP_Post | PostData | int | string | array | null`を受け取ります。投稿オブジェクト・DTO（派生クラスを含む）はIDに変換し、文字列と`null`はそのままACFへ渡します。配列の場合は、その配列内のキーを読みます。

```php
<?php
(new AcfGet($post))->Text('title');          // WP_Post
(new AcfGet($postData))->Text('title');      // PostData・案件用の派生DTO
(new AcfGet($postData->PostId))->Text('title');
(new AcfGet('site_options'))->Array('settings'); // オプションページの取得先ID
(new AcfGet())->Text('title');                // null：ACFの現在のコンテキストを使う
$group = (new AcfGet($post))->Array('settings');
(new AcfGet($group))->Text('label');          // 取得済みの配列内のキーを読む
```

`Repeater()`の各行はすでに`AcfGet`です。包み直さず、そのまま取得メソッドを呼びます。
外部から渡された生配列の場合のみ`new AcfGet($row)`を使います。

## ACF画像・ファイルの取得

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$fields = new AcfGet();
$image = $fields->Img('hero');      // 寸法がある場合はImgData、取得できなければnull
$imageUrl = $fields->ImgUrl('logo'); // URL返却形式の画像は?string
$fileUrl = $fields->FileUrl('document'); // URL返却形式のファイルは?string
```

`AcfGetUser`・`AcfGetTerm`は対象のユーザー・タームをコンストラクターへ渡し、その対象のACFフィールドを取得します。URL返却形式の画像には`ImgUrl()`、ファイルには`FileUrl()`を使います。

ギャラリーのURL返却形式には`GalleryUrl($fieldName): list<string>`を使用します。`AcfGet`・`AcfGetUser`・`AcfGetTerm`およびNestedに対応します。未入力・配列以外は`[]`、不正URL・ID・画像情報配列は除外します。有効なHTTP(S)絶対URLの順序と重複を保持し、エスケープは出力側で行います。画像情報が必要な場合は従来の`Gallery()`を使います。

画像URL専用の入口として`AcfGet->ImgUrl()`、`AcfGetUser->ImgUrl()`、`AcfGetTerm->ImgUrl()`があります。URL返却形式の画像は`ImgData`に詰めず、寸法を取得できる画像だけを`ImgData`として返します。URLが未設定・不正なら`null`です。

HTTP(S) URLの検証では、日本語ドメインを`intl`拡張のUTS #46処理で検証します。検証後も取得口が返す値は入力された元のURLです。`intl`拡張を利用できない環境では、日本語ドメインは`null`になります。日本語を含むパス・クエリ・フラグメントは`intl`の有無にかかわらず検証できます。相対URLとHTTP(S)以外のスキームは対象外です。

SVG添付画像は、ローカルファイルのルート`svg`要素にある単位なしまたは`px`の`width`・`height`属性を寸法として優先し、HTMLの同名属性へ使える整数として`ImgData`へ保持します。両方を取得できない場合は、`viewBox`の幅・高さを比率として使用します。どちらからも寸法または比率を取得できないSVGは`null`です。外部URLのSVGを取得して解析することはありません。

SVGにはWordPressのラスター画像サイズがないため、`Img()`へ`thumbnail`等のサイズを指定しても元のSVG URLと寸法または比率を返します。`ImgUrl()`はURLだけを返す取得口なので、レイアウトシフト対策に必要な寸法や比率は提供しません。

ファイルURL専用の入口として`AcfGet->FileUrl()`、`AcfGetUser->FileUrl()`、`AcfGetTerm->FileUrl()`があります。URL返却形式のファイルは`FileData`に詰めず、IDとファイルサイズを取得できる場合だけ`FileData`として返します。URLが未設定・不正なら`null`です。

## 日付・日時・時刻の取得

ACFのReturn Formatに依存せず扱う場合は、時間系フィールド専用の取得口を使用します。

| 取得口 | ACFフィールドタイプ | 戻り値 | ACFの未整形形式 |
|---|---|---|---|
| `Date()` | Date Picker | `?DateTimeImmutable` | `Ymd` |
| `DateTime()` | Date Time Picker | `?DateTimeImmutable` | `Y-m-d H:i:s` |
| `Time()` | Time Picker | `?TimeData` | `H:i:s` |

```php
<?php
$eventDate = $postGet->Date('event_date');
$eventStart = $postGet->DateTime('event_start');
$openingTime = $postGet->Time('opening_time');

echo esc_html($eventDate?->format('Y-m-d') ?? '');
echo esc_html($eventStart?->format('Y-m-d H:i') ?? '');
echo esc_html($openingTime?->Format('H:i') ?? '');
```

`Date()`は時刻を`00:00:00`に固定し、`Date()`と`DateTime()`はWordPressのサイトタイムゾーンを持つ`DateTimeImmutable`を返します。`TimeData`は日付やタイムゾーンを補わず、`Hour`・`Minute`・`Second`を保持します。`Format()`の既定形式は`H:i:s`で、`Format('H:i')`のようにPHPの日時フォーマットを指定できます。未入力、異なるフィールドタイプ、存在しない日付・時刻、解析失敗は`null`です。

専用取得口は`get_field_object()`から未整形値を取得するため、管理画面のReturn Formatを変更しても戻り値の契約は変わりません。Return Formatどおりの表示文字列が必要な場合は、従来どおり`Text()`を使用できます。

時間系取得口は、投稿・ID・オプションページ・ユーザー・タームなど、ACFの取得先を直接指定した場合と、そこからACFグループをたどる`Nested()`に対応します。多段の`Nested()`でも未整形値と子フィールド定義を引き継ぎます。外部の取得済み配列を渡した`new AcfGet($array)`、その配列から作る`Nested()`、`Repeater()`の各行ではフィールド定義と未整形値を復元できないため、`null`を返します。

## ラジオ・ボタングループ・チェックボックスの取得口

返り値型を固定するため、ACFの返り値設定に応じて取得メソッドを使い分けます。`AcfGet`・`AcfGetUser`・`AcfGetTerm`すべてで利用できます。

| 取得メソッド | ACFの返り値設定 | 戻り値 |
|---|---|---|
| `RadioValue('position')` | 値（`value`） | `?string` |
| `RadioLabel('position')` | ラベル（`label`） | `?string` |
| `Radio('position')` | 両方（`array`） | `?ChoiceData` |
| `ButtonGroupValue('alignment')` | 値（`value`） | `?string` |
| `ButtonGroupLabel('alignment')` | ラベル（`label`） | `?string` |
| `ButtonGroup('alignment')` | 両方（`array`） | `?ChoiceData` |
| `CheckboxValues('items')` | 値（`value`） | `list<string>` |
| `CheckboxLabels('items')` | ラベル（`label`） | `list<string>` |
| `Checkbox('items')` | 両方（`array`） | `list<ChoiceData>` |

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$acfGet = new AcfGet();
$position = $acfGet->RadioValue('position') ?? 'left';
$label = $acfGet->RadioLabel('display_label');
$choice = $acfGet->Radio('both_choice');
$alignment = $acfGet->ButtonGroupValue('alignment') ?? 'left';
$values = $acfGet->CheckboxValues('items');
```

投稿・ユーザー・タームなどから直接取得するときは`get_field_object()`でフィールド型と`return_format`を確認します。ラジオ以外に`Radio*()`、ボタングループ以外に`ButtonGroup*()`、チェックボックス以外に`Checkbox*()`を使った場合や、返り値設定と取得口が一致しない場合は、文字列・DTOの取得口では`null`、複数選択の取得口では空配列です。ボタングループはラジオと同じ単一選択の戻り値契約ですが、ACFフィールドタイプ`button_group`を独立して検証します。両方形式から値だけを取りたい場合は`ButtonGroup('field')?->Value`のようにDTOを参照してください。ラベルから値を逆引きするなどの変換は行いません。

`Radio()`・`Checkbox()`は値とラベルがそろった選択肢だけを`ChoiceData`にします。片側が欠損・空文字・型違いの場合はDTOを作りません。文字列または整数の選択肢は文字列として扱い、`0`・`'0'`は保持します。未選択の空文字・false・nullや、配列などの不正な単一値は`null`です。チェックボックスはリスト配列を対象にし、型違いの要素を除いて選択順の連番リストを返します。

取得済み配列を渡す`new AcfGet($row)`・`Nested()`では、フィールド定義を参照できません。文字列を値と解釈するかラベルと解釈するかは呼び出し元がメソッドで指定します。形状の検証は行いますが、文字列の意味まで検証することはできません。`Radio()`は`['value' => ..., 'label' => ...]`、`Checkbox()`はそのリストが必要です。Value・Labelの取得口へ両方形式の配列を渡しても、文字列への変換は行いません。

## セレクトの取得口

単一選択と複数選択を区別し、ACFの返り値設定に一致する取得口を使います。
`AcfGet`・`AcfGetUser`・`AcfGetTerm`で利用できます。

| 取得メソッド | 複数選択 | 返り値設定 | 戻り値 |
|---|---|---|---|
| `SelectValue('type')` | 無効 | 値 | `?string` |
| `SelectLabel('type')` | 無効 | ラベル | `?string` |
| `Select('type')` | 無効 | 両方 | `?ChoiceData` |
| `SelectValues('types')` | 有効 | 値 | `list<string>` |
| `SelectLabels('types')` | 有効 | ラベル | `list<string>` |
| `SelectChoices('types')` | 有効 | 両方 | `list<ChoiceData>` |

直接取得では`get_field_object()`でフィールド型が`select`であること、
`multiple`と`return_format`が合うことを確認します。不一致・欠損時は単一取得で`null`、
複数取得で空配列です。整数の選択肢は文字列化し、`0`を保持します。
複数取得はリスト配列のみを受け入れ、不正な要素を除去します。

取得済み配列や`Nested()`はフィールド定義を参照できないため、形状のみ検証します。
例えばSNSのリピーター行は`(new AcfGet($row))->SelectValue('type')`で取得できます。
値とラベルの逆引きや、単一・複数間の自動変換は行いません。

検証は`php tests/select-accessors.php`で実行できます（APIスタブ、DB接続なし）。

## URLフィールドの取得

`Url('url')`は`?string`を返します。`AcfGet`・`AcfGetUser`・`AcfGetTerm`、
取得済み配列・`Nested()`で利用できます。
`ImgUrl()`・`FileUrl()`と同じHTTP(S)の絶対URL検証を使い、
未入力・型違い・不正URL・相対パス・`javascript:`・`mailto:`などは`null`です。
値は変換・エスケープせず返すため、描画時には`esc_url($url)`を使用してください。
Select取得口と異なり、フィールド型の照合は行わず、取得した値がURLとして妥当かを検証します。
検証は`php tests/url-accessors.php`で実行できます（APIスタブ、DB接続なし）。

```php
<?php
$url = $rowGet->Url('url');
if ($url !== null) {
	// テンプレートでhrefへesc_urlして出力する。
}
```

## ターム・ユーザー未選択時

`AcfGetTerm`・`AcfGetUser`は明示的な`null`を受け取れます。
`AcfGetTerm`・`AcfGetUser`は`AcfGet`を継承しています。
`new AcfGetTerm(null)`・`new AcfGetUser(null)`は空配列を親の取得元として使い、
対象IDの組み立てやACFの取得関数を呼ばず、既存の取得口ごとの空値を返します。
`Text()`は空文字、`Array()`・`Repeater()`・複数選択は空配列、
画像・URL・単一選択などは`null`です。`Nested()`も空の取得元を引き継ぎます。
現在の投稿へのフォールバックは行いません。
有効なターム・ユーザーを渡すと、保持しているAdapterが参照先IDを組み立てます。
Get系のstaticな初期登録は不要です。
`new AcfGet(null)`は従来どおり現在の投稿を参照し、この変更の対象外です。
検証は`php tests/nullable-subjects.php`で実行できます。

## 案件固有フィールドへの拡張

`AcfGet`は継承でき、派生クラスからだけ使用できる`protected raw()`を提供します。
標準アクセサーにないACFE・自作フィールドなどは、生値を案件固有の型へ変換する公開メソッドとして追加できます。

```php
<?php
final class ProjectAcfGet extends AcfGet
{
	public function Price(string $fieldName): ?PriceData
	{
		return PriceData::FromMixed($this->raw($fieldName));
	}
}

$postGet = new ProjectAcfGet($postId);
$price = $postGet->Price('price');
```

`raw()`は未検証の生値を返すため、派生クラスの公開メソッドで型確認・正規化してから返してください。
利用側テンプレートから直接呼ぶことはできません。

`Nested()`・`Repeater()`・`FlexibleNode::Get`が生成する子取得口は、呼び出し元の派生型とコンストラクターで注入された依存・Adapterを引き継ぎます。内部では`protected createChild()`が現在の取得口を複製し、取得元だけを子配列へ差し替えます。通常は派生クラス側で子生成処理を書く必要はありません。複製時に追加の初期化が必要な派生型だけ、`createChild()`をオーバーライドしてください。

```php
<?php
$projectGet = new ProjectAcfGet($postId);
$groupGet = $projectGet->Nested('settings'); // ProjectAcfGet

foreach ($projectGet->Repeater('items') as $rowGet) {
	$price = $rowGet->Price('price'); // 派生クラスのメソッドを利用可能
}
```

## 生配列の取得

`Group()`は用途を明確にするため`Array()`へ改名しました（旧名は削除）。
グループ・リピーターなど、取得値が配列なら加工せず返し、未設定・配列以外は`[]`です。
返り値は`array<mixed>`で、キー・要素の型は保証しません。
各フィールドを型付きで読む場合は`Nested()`を使います。
`Repeater()`は基底型から呼ぶと`list<AcfGet>`、派生型から呼ぶと同じ派生型のリストを返します。生の行配列が必要な場合は`Array()`を使います。
検証は`php tests/array-accessors.php`で実行できます。

## リピーター行の取得

`Repeater('items')`は呼び出し元と同じ`AcfGet`派生型のリストを返します。
`AcfGet`・`AcfGetUser`・`AcfGetTerm`で共通です。
未入力・配列以外の取得値は`[]`、配列以外の行は除外し、
残った行を入力順の連番リストにします。空の行配列は空の`AcfGet`として保持します。
各行は取得済み配列を保持するため、フィールド定義の照合は行えません。
行内の`Nested()`・`Repeater()`も利用でき、読み取り時点の値を保持します。

```php
<?php
foreach ($postGet->Repeater('items') as $rowGet) {
	$text = $rowGet->Text('text');
}
$rawRows = $postGet->Array('items'); // 生配列が必要な場合
```

旧コードの`new AcfGet($row)`や`is_array($row)`による包み直しは削除し、
コールバックの行引数は`AcfGet`型へ変更してください。
`count()`・`array_slice()`など外側の配列操作は従来どおり利用できます。
検証は`php tests/repeater-rows.php`で実行できます。

## フレキシブルコンテンツの取得

`Flexible('contents')`は`list<FlexibleNode>`を返します。`FlexibleNode`は、ACFが各行の`acf_fc_layout`へ保存するレイアウト名を`Layout`、同じ行の取得口を`Get`として保持します。`Get`は呼び出し元と同じ`AcfGet`派生型です。`AcfGet`・`AcfGetUser`・`AcfGetTerm`で共通です。

```php
<?php
foreach ($postGet->Flexible('contents') as $node) {
	match ($node->Layout) {
		'image_section' => ImageSectionView::Render($node->Get),
		'text_section' => TextSectionView::Render($node->Get),
		default => null,
	};
}
```

内部では`Repeater()`と同じ行配列の正規化を利用します。`acf_fc_layout`が未入力・空文字・文字列以外の行は除外します。`Get`は取得済み行配列を保持する取得口であり、行内のフィールドを`Text()`・`Img()`・`Nested()`・`Repeater()`・`Flexible()`などで取得できます。

`FlexibleNode`はreadonlyですが、利用側から`new FlexibleNode(Layout: ..., Get: ...)`で直接構築できます。検証は`php tests/flexible-content.php`で実行できます。

## ACFブロックの描画情報

```php
<?php
use WpTypeSafety\Acf\Get\AcfDataFactory;

$blockData = AcfDataFactory::BlockFromAcf($block, isPreview: $is_preview ?? false);
$withoutImg = $blockData->HasStyle('without-img');
$isPreview = $blockData->IsPreview;
$wrapperAttributes = $blockData->GetWrapperAttributes(className: 'staff-card');
```

`Name`・`Id`・`Anchor`・`Classes`・`IsPreview`をreadonlyで保持します。`Classes`は`$block['className']`を空白で分割し重複を除いたリストで、WordPressが出力時に生成するクラスは含めません。`HasClass()`・`HasStyle()`は完全一致で判定します。`HasStyle('default')`は明示された`is-style-default`にだけ一致し、スタイル未選択をdefaultと推測しません。

Factoryの`bool $isPreview = false`引数でプレビュー状態を明示します。`$block`内の値や`is_admin()`から推測しません。`AcfDataFactory`は`Acf/Internal/BlockAdapter::FromAcf()`へ委譲し、Internalが文字列項目の欠損・型違いを空文字、クラスの欠損・型違いを空配列へ正規化して`new AcfBlockData(...)`で生成します。静的登録は不要です。元配列や周辺状態が変化しても、生成済みインスタンスの値は変更されません。

PostDataと同様に、正規化済みの値を使って直接生成することもできます。Dataのコンストラクターは生の`$block`配列ではなく、名前付き引数を受け取ります。

```php
<?php
use WpTypeSafety\Acf\Data\AcfBlockData;

$blockData = new AcfBlockData(
	Name: 'acf/staff-card',
	Id: 'block_example',
	Anchor: '',
	Classes: ['is-style-without-img'],
	IsPreview: $is_preview ?? false,
);
```

Data単独での生成・プロパティ参照・クラス判定にWordPress・ACF・Internal・`autoload.php`は不要です。`Classes`の要素を含む正規化済み入力の用意は、直接生成する呼び出し元の責務です。生成用・取得用処理をDataへ静的登録する方式は使いません。

`GetWrapperAttributes(string $className = ''): string`は、コンストラクターの任意引数`wrapperAttributesResolver`（`Closure(string): string`）へ処理を委譲します。原則DataにHTML生成を持たせませんが、このラッパー属性取得のみ、注入した描画処理への委譲を例外として許可します。Data自身はWordPress関数・エスケープ処理・プレビュー分岐を持ちません。Resolver未指定でも直接構築できますが、属性取得を呼ぶと`LogicException`になります。

Factoryは、プレビューなら基本クラスと生成時のClassesを結合・重複除去して`class`属性をエスケープする処理、フロントなら`get_block_wrapper_attributes(['class' => $className])`へ委譲する処理を注入します。プレビューの内側へアンカーを重複付与せず、フロントのクラス・アンカー・スタイルはWordPressの描画コンテキストへ任せます。戻り値はエスケープ済みなので、`<div <?= $wrapperAttributes ?>>`のようにそのまま出力してください。二重エスケープはしません。フロントでは対象ブロックの描画中に呼んでください。

独自に直接生成する場合は、`wrapperAttributesResolver: static fn (string $className): string => ...`のように描画処理を渡せます。処理は属性取得時に毎回呼ばれ、コンストラクターでは実行しません。独自Resolverもエスケープ済みの属性文字列を返す責務を持ちます。ACFフィールドの取得・その他のHTML描画は引き続きテンプレート側に残します。

旧`new AcfBlockData($block, isPreview: ...)`は、`AcfDataFactory::BlockFromAcf($block, isPreview: ...)`へ置き換えてください。この実装方針はルートの`AGENTS.md`に固定しています。Get系APIもstaticなClosure登録を必要としません。
