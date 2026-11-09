# ACFの取得API

## 目次

目的から探す場合は、次の入口を参照してください。

- 文字列・数値を取得したい：[標準取得口の一覧](#標準取得口の一覧)・[数値フィールドの取得](#数値フィールドの取得)
- 画像・ファイル・Icon Pickerを取得したい：[ACF画像・ファイルの取得](#acf画像ファイルの取得)・[Icon Pickerの取得](#icon-pickerの取得)
- Group・Repeater・Flexible Contentをたどりたい：[生配列の取得](#生配列の取得)・[リピーター行の取得](#リピーター行の取得)・[フレキシブルコンテンツの取得](#フレキシブルコンテンツの取得)
- 投稿・ユーザー・ターム参照を扱いたい：[ACFの取得先の指定](#acfの取得先の指定)・[投稿・ターム・ユーザーの複数取得](#投稿タームユーザーの複数取得)
- 選択系フィールドを扱いたい：[ラジオ・ボタングループ・チェックボックスの取得口](#ラジオボタングループチェックボックスの取得口)・[セレクトの取得口](#セレクトの取得口)
- 独自フィールドへ対応したい：[案件固有フィールドへの拡張](#案件固有フィールドへの拡張)
- ACFブロックを扱いたい：[ACFブロックの描画情報](#acfブロックの描画情報)

APIの分類から探す場合は、次の目次を利用できます。

- [標準取得口の一覧](#標準取得口の一覧)
- [数値フィールドの取得](#数値フィールドの取得)
- [ACFの取得先の指定](#acfの取得先の指定)
- [投稿・ターム・ユーザーの複数取得](#投稿タームユーザーの複数取得)
- [ACF画像・ファイルの取得](#acf画像ファイルの取得)
- [Icon Pickerの取得](#icon-pickerの取得)
- [日付・日時・時刻の取得](#日付日時時刻の取得)
- [選択系フィールド](#ラジオボタングループチェックボックスの取得口)
- [案件固有フィールドへの拡張](#案件固有フィールドへの拡張)
- [リピーター行の取得](#リピーター行の取得)
- [フレキシブルコンテンツの取得](#フレキシブルコンテンツの取得)
- [ACFブロックの描画情報](#acfブロックの描画情報)

## 標準取得口の一覧

投稿・ユーザー・ターム・ACF参照先ID・取得済み配列は、すべて`AcfGet`から同じ取得口を利用できます。型違い・未入力時は、単数Dataとnullableな値が`null`、文字列が空文字、リストと配列が空配列です。

| 取得口 | 戻り値 | 用途・契約 |
|---|---|---|
| `Text()` | `string` | 文字列。未入力・型違いは空文字 |
| `Float()` | `?float` | 整数・float・数値文字列をfloatへ正規化。数値以外は`null` |
| `Int()` | `?int` | 整数・整数相当のfloat・整数形式の数値文字列をintへ正規化。それ以外は`null` |
| `Bool()` | `?bool` | 真偽値。型違いは`null` |
| `Link()` | `?LinkData` | ACFリンク配列 |
| `Url()` | `?string` | HTTP(S)の絶対URL |
| `Img()` | `?ImgData` | ID・寸法を取得できる画像 |
| `ImgUrl()` | `?string` | URL返却形式の画像 |
| `IconClass()` | `?string` | Icon PickerのDashiconクラス名 |
| `IconImg()` | `?ImgData` | Icon Pickerのメディアライブラリ画像 |
| `IconUrl()` | `?string` | Icon PickerのURLタブ |
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
| `PostGet()`・`UserGet()`・`TermGet()` | 呼び出し元と同じ`AcfGet`派生型 | 単一参照先のACFフィールド取得口へ切り替える |
| `PostGets()`・`UserGets()`・`TermGets()` | `list<呼び出し元と同じAcfGet派生型>` | 複数参照先それぞれのACFフィールド取得口を返す |
| `Native()` | `mixed` | 通常のACF戻り値をWpTypeSafetyで変換せず返す |
| `Nested()` | `AcfGet` | グループなどの子配列へ進む |
| `Array()` | `array<mixed>` | 生配列。要素型は保証しない |
| `Repeater()` | `list<AcfGet>` | リピーターの各行 |
| `Flexible()` | `list<FlexibleNode>` | レイアウト名と行の取得口 |

ラジオ・ボタングループ・チェックボックス・セレクトは、ACFの返り値設定に対応する専用取得口を使います。詳細は各節を参照してください。

## ACF文字列の未入力時の扱い

`AcfGet->Text()`は、文字列以外・空文字・空白だけの値を空文字として返します。フォールバック値を渡す引数は設けず、用途ごとの代替値は呼び出し側の`?:`で指定します。

```php
<?php
$postGet = new AcfGet($post);
$rowGet = new AcfGet($row);

$title = $postGet->Text('title') ?: '代替タイトル';
$label = $rowGet->Text('label') ?: '代替ラベル';
$color = $termGet->Text('display_color') ?: 'var(--black)';
```

`?:`は文字列`'0'`も代替値の対象にします。この文言取得の運用ではそれを許容し、数値として扱う値は数値用の取得口で取得します。`Text()`自体が`'0'`を空文字へ変換するわけではありません。`ArrayGet::String()`もフォールバック引数は設けず、呼び出し側で`?:`を使います。

## 数値フィールドの取得

ACFのNumberフィールドはWordPressのメタ値を基にするため、`get_field()`から数値文字列が返ることがあります。小数を含む数値として扱う場合は`Float()`、整数に限定する場合は`Int()`を使用します。

```php
<?php
$price = $postGet->Float('price'); // "12.5"も12.5へ正規化
$count = $postGet->Int('count');    // "12"も12へ正規化
```

`Float()`はPHPの`is_numeric()`に従うため、前後に空白がある数値文字列や指数表記も受け入れて`float`へ変換します。`Int()`は整数、整数として正確に表現できるfloat、符号と数字だけで構成された整数形式の文字列を受け入れます。小数文字列・指数表記・空白を含む文字列・PHPの整数範囲外は`null`です。`TypeCheck::Int()`は変換を行わず、最初から`int`である値だけを通すCore APIです。

## ACFの取得先の指定

グループ内の値を読む場合は、`Nested()`で子の`AcfGet`を取得できます。配列そのものが必要な場合は`Array()`を使います。

```php
<?php
$postGet = new AcfGet($post);
$firstViewGet = $postGet->Nested('first_view');
$label = $firstViewGet->Text('label');
$title = $postGet->Nested('outer')->Nested('inner')->Text('title');
```

`Nested()`は呼び出した時点の`Array()`の結果を保持します。未設定・配列以外の値は空の取得元になり、続く`Text()`は空文字、`Img()`はnullを返します。投稿・オプションページ・ユーザー・タームを取得元にしたACFグループでは、整形済み配列に加えて未整形値と子フィールド定義も保持するため、グループ内でも`Date()`・`DateTime()`・`Time()`を利用できます。戻り値は呼び出し元と同じ`AcfGet`派生型です。

単一選択のACF投稿オブジェクト・関連・ユーザー・タクソノミーフィールドが参照する先のACF値を読むときは、`PostGet()`・`UserGet()`・`TermGet()`を使えます。内部では既存の`Post()`・`User()`・`Term()`で対象を取得し、同じ派生型の子取得口へ取得元だけを差し替えます。未選択・型違い・取得失敗なら、空の取得元を返します。`UserGet()`はアバター画像を取得しません。アバター画像のサイズは、`User()`などで得た`WpUserData`の`GetAvatar()`を呼ぶときに決まります。

```php
<?php
$postGet = new AcfGet($post);
$relatedSubtitle = $postGet->PostGet('related_post')->Text('subtitle');
$authorName = $postGet->UserGet('author')->Text('profile_name');
$termColor = $postGet->TermGet('category')->Text('display_color');
$groupAuthorName = $postGet->Nested('settings')->UserGet('author')->Text('profile_name');
```

`Nested()`は同じ取得結果内の配列へ進み、`PostGet()`・`UserGet()`・`TermGet()`は参照先の投稿・ユーザー・タームへ取得対象を切り替えます。これらは単一値用です。ACFフィールドで複数選択を許可した場合の配列は対象にせず、空の取得元になります。投稿の標準投稿者や通常のタクソノミー割り当てを読むメソッドでもありません。

## 投稿・ターム・ユーザーの複数取得

複数選択を許可した投稿オブジェクト・関連・タクソノミー・ユーザーフィールドには、`Posts()`・`Terms()`・`Users()`を使います。`AcfGet`および`Nested()`で利用できます。

```php
<?php
$relatedPosts = $postGet->Posts('related_posts');
$categories = $postGet->Terms('categories');
$members = $postGet->Users('members', avatarSize: 160);
```

戻り値はそれぞれ`list<PostData>`・`list<TermData>`・`list<WpUserData>`です。ACFの返却形式がオブジェクトまたはIDのどちらでも変換し、未入力・配列以外は`[]`、取得・変換できない要素は除外します。入力順と重複は維持します。`Users()`の`avatarSize`は各`WpUserData`へ設定するアバター取得用Resolverに渡されます。

各参照先のACFフィールドへ進む場合は、`PostGets()`・`TermGets()`・`UserGets()`を使います。戻り値は`list<AcfGet>`で、派生`AcfGet`から呼んだ場合はリスト内でも同じ派生型とAdapterを維持します。既存の複数取得メソッドで変換できた対象だけを子取得口にするため、入力順・重複・不正要素除外の契約も同じです。

```php
<?php
$relatedPostGets = $postGet->PostGets('related_posts');

foreach ($relatedPostGets as $relatedPostGet) {
	$subtitle = $relatedPostGet->Text('subtitle');
}
```

単数用の`Post()`・`Term()`・`User()`へ複数値を渡しても、先頭要素を推測して返すことはありません。同様に、複数用メソッドへ単一値を渡した場合も空配列です。投稿の標準タームや投稿者を取得するAPIではなく、指定したACFフィールドの値だけを対象にします。

`AcfGet`のコンストラクターは`WP_Post | PostData | WP_User | WpUserData | WP_Term | TermData | int | string | array | null`を受け取ります。投稿オブジェクト・DTO（派生クラスを含む）は投稿ID、ユーザーとタームはACFの参照先IDへ変換します。文字列と`null`はそのままACFへ渡し、配列の場合はその配列内のキーを読みます。

```php
<?php
$postGet = new AcfGet($post);                    // WP_Post
$postDataGet = new AcfGet($postData);            // PostData・案件用の派生DTO
$userGet = new AcfGet($user);                    // WP_User・WpUserData
$termGet = new AcfGet($term);                    // WP_Term・TermData
$postIdGet = new AcfGet($postData->PostId);
$optionGet = new AcfGet('site_options');          // オプションページの取得先ID
$currentPostGet = new AcfGet();                   // null：ACFの現在のコンテキストを使う

$postGet->Text('title');
$postDataGet->Text('title');
$userGet->Text('profile_name');
$termGet->Text('display_color');
$postIdGet->Text('title');
$optionGet->Array('settings');
$currentPostGet->Text('title');

$group = $postGet->Array('settings');
$groupGet = new AcfGet($group);
$groupGet->Text('label');                         // 取得済みの配列内のキーを読む
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

対象のユーザー・タームを`AcfGet`のコンストラクターへ渡すと、その対象のACFフィールドを取得します。URL返却形式の画像には`ImgUrl()`、ファイルには`FileUrl()`を使います。

ギャラリーのURL返却形式には`GalleryUrl($fieldName): list<string>`を使用します。`AcfGet`および`Nested()`に対応します。未入力・配列以外は`[]`、不正URL・ID・画像情報配列は除外します。有効なHTTP(S)絶対URLの順序と重複を保持し、エスケープは出力側で行います。画像情報が必要な場合は従来の`Gallery()`を使います。

画像URL専用の入口として`AcfGet->ImgUrl()`があります。URL返却形式の画像は`ImgData`に詰めず、寸法を取得できる画像だけを`ImgData`として返します。URLが未設定・不正なら`null`です。

HTTP(S) URLの検証では、日本語ドメインを`intl`拡張のUTS #46処理で検証します。検証後も取得口が返す値は入力された元のURLです。`intl`拡張を利用できない環境では、日本語ドメインは`null`になります。日本語を含むパス・クエリ・フラグメントは`intl`の有無にかかわらず検証できます。相対URLとHTTP(S)以外のスキームは対象外です。

SVG添付画像は、ローカルファイルのルート`svg`要素にある単位なしまたは`px`の`width`・`height`属性を寸法として優先し、HTMLの同名属性へ使える整数として`ImgData`へ保持します。両方を取得できない場合は、`viewBox`の幅・高さを比率として使用します。どちらからも寸法または比率を取得できないSVGは`null`です。外部URLのSVGを取得して解析することはありません。

SVGにはWordPressのラスター画像サイズがないため、`Img()`へ`thumbnail`等のサイズを指定しても元のSVG URLと寸法または比率を返します。`ImgUrl()`はURLだけを返す取得口なので、レイアウトシフト対策に必要な寸法や比率は提供しません。

ファイルURL専用の入口として`AcfGet->FileUrl()`があります。URL返却形式のファイルは`FileData`に詰めず、IDとファイルサイズを取得できる場合だけ`FileData`として返します。URLが未設定・不正なら`null`です。

## Icon Pickerの取得

ACF 6.3以降のIcon Pickerは、選択したタブに対応する取得口を使います。未入力、別のタブ、別のフィールドタイプ、不正値の場合はいずれも`null`です。

| 取得口 | 対象タブ | 戻り値 |
|---|---|---|
| `IconClass()` | Dashicons | `?string` |
| `IconImg()` | Media Library | `?ImgData` |
| `IconUrl()` | URL | `?string` |

```php
<?php
$postGet = new AcfGet($post);
$iconClass = $postGet->IconClass('menu_icon');
$iconImg = $postGet->IconImg('menu_icon', 'medium');
$iconUrl = $postGet->IconUrl('menu_icon');
```

`IconClass()`は`dashicons-admin-home`のような選択されたアイコン固有のクラス名だけを返します。HTMLへ出力するときは、WordPress共通の`dashicons`クラスを呼び出し側で追加します。フロント画面でDashiconsのCSSがまだ読み込まれていない場合は、テーマまたはプラグイン側で`wp_enqueue_style('dashicons')`を行います。

```php
<?php if ($iconClass !== null): ?>
	<span class="dashicons <?= esc_attr($iconClass) ?>" aria-hidden="true"></span>
<?php endif; ?>
```

`IconImg()`はMedia Libraryで選ばれた添付画像を既存の`ImgData`へ変換します。第2引数には`Img()`と同じ画像サイズを指定でき、SVGも既存のSVG寸法取得仕様に従います。`IconUrl()`はURLタブのHTTP(S)絶対URLだけを返します。属性へ出力するときのエスケープは呼び出し側で行います。

投稿・ID・ユーザー・ターム・オプションページ等を取得元にした場合は、Icon PickerのReturn Formatにかかわらず、ACFの未整形値から選択タブを判定します。`Nested()`・`Repeater()`・`Flexible()`も未整形値と子フィールド定義を引き継ぐため、同じ3つの取得口を利用できます。

外部で取得済みの配列を`AcfGet`へ渡す場合は、`['type' => 'dashicons|media_library|url', 'value' => ...]`というIcon Pickerの配列形式を維持してください。文字列だけの場合はDashiconクラス名とHTTP(S) URLを判別できますが、Media Library由来のURL文字列から添付画像IDや元のタブを復元することはできません。

ACFのフィルターで追加した独自タブは、標準の3取得口では推測して変換しません。`Native()`で値を取得し、案件固有の派生`AcfGet`で型確認・正規化してください。

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

時間系取得口は、投稿・ID・オプションページ・ユーザー・タームなどACFの取得先を直接指定した場合と、そこからたどる`Nested()`・`Repeater()`・`Flexible()`に対応します。グループ内のリピーター、リピーター行内のグループなど、複数種類を組み合わせた入れ子でも未整形値と子フィールド定義を引き継ぎます。外部の取得済み配列を渡した`new AcfGet($array)`と、その配列から作る子取得口ではフィールド定義と未整形値を復元できないため、時間系取得口は`null`を返します。

## ラジオ・ボタングループ・チェックボックスの取得口

返り値型を固定するため、ACFの返り値設定に応じて取得メソッドを使い分けます。すべて`AcfGet`で利用できます。

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
`AcfGet`で利用できます。

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
例えばSNSのリピーター行は、取得元が分かる名前で包んでから値を取得できます。

```php
<?php
$rowGet = new AcfGet($row);
$type = $rowGet->SelectValue('type');
```

値とラベルの逆引きや、単一・複数間の自動変換は行いません。

検証は`php tests/select-accessors.php`で実行できます（APIスタブ、DB接続なし）。

## URLフィールドの取得

`Url('url')`は`?string`を返します。`AcfGet`、
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

## 参照先未選択時

`PostGet()`・`UserGet()`・`TermGet()`で参照フィールドが未選択・型違い・取得失敗の場合は、空配列を子取得口の取得元として使います。`PostGets()`・`UserGets()`・`TermGets()`の場合は空のリストを返します。
対象IDの組み立てやACFの取得関数を呼ばず、既存の取得口ごとの空値を返します。
`Text()`は空文字、`Array()`・`Repeater()`・複数選択は空配列、
画像・URL・単一選択などは`null`です。`Nested()`も空の取得元を引き継ぎます。
現在の投稿へのフォールバックは行いません。
有効なターム・ユーザーを`AcfGet`へ渡すと、保持しているAdapterが参照先IDを組み立てます。nullableな変数を直接渡す場合、`null`は現在の投稿を意味するため、空の取得元にしたい場合は`new AcfGet($subject ?? [])`と明示してください。
Get系のstaticな初期登録は不要です。
`new AcfGet(null)`は従来どおり現在の投稿を参照し、この変更の対象外です。
検証は`php tests/nullable-subjects.php`で実行できます。

## 案件固有フィールドへの拡張

`Native()`は、WpTypeSafetyで型確認・正規化していないACFネイティブ値を`mixed`で返します。投稿・ユーザー・ターム・オプションページ等を取得元にした場合は、通常の`get_field($fieldName, $postId)`と同じACF整形済み値です。取得済み配列を取得元にした場合は、その配列内の値をそのまま返します。

```php
<?php
$nativeValue = $postGet->Native('custom_field');
```

`Native()`は`get_field()`の全引数を再公開する完全互換ラッパーではありません。ACF未整形値やACFネイティブのHTMLエスケープ等が必要な場合は、`get_field()`を直接使用してください。WordPressのメタテーブルへ直接アクセスするAPIでもありません。

`Nested()`・`Repeater()`・`FlexibleNode::Get`でも同じメソッドを利用できます。子取得口はACFがすでに整形した配列のスナップショットを保持するため、その値をWpTypeSafetyで追加変換せず返します。

標準アクセサーにないACFE・自作フィールドなどは、`Native()`の戻り値を案件固有の型へ変換する公開メソッドとして追加できます。

```php
<?php
final class ProjectAcfGet extends AcfGet
{
	public function Price(string $fieldName): ?PriceData
	{
		return PriceData::FromMixed($this->Native($fieldName));
	}
}

$postGet = new ProjectAcfGet($postId);
$price = $postGet->Price('price');
```

`Native()`は公開取得口ですが、戻り値型は`mixed`です。通常のテンプレートでは用途別の型付き取得口を優先し、未対応フィールドの調査や案件固有の取得メソッドを実装するときの拡張口として使用してください。型確認・正規化・描画時のエスケープは自動では行いません。

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
`AcfGet`とその派生型で共通です。
未入力・配列以外の取得値は`[]`、配列以外の行は除外し、
残った行を入力順の連番リストにします。空の行配列は空の`AcfGet`として保持します。
投稿・IDなどACFの取得先から呼んだ場合、各行は整形済み値に加えて未整形値と子フィールド定義を引き継ぐため、`Date()`・`DateTime()`・`Time()`も利用できます。外部の取得済み配列から作った行では時間系取得口は`null`です。
行内の`Nested()`・`Repeater()`・`Flexible()`も利用でき、読み取り時点の値と利用可能なACFコンテキストを引き継ぎます。

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

`Flexible('contents')`は`list<FlexibleNode>`を返します。`FlexibleNode`は、ACFが各行の`acf_fc_layout`へ保存するレイアウト名を`Layout`、同じ行の取得口を`Get`として保持します。`Get`は呼び出し元と同じ`AcfGet`派生型です。

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

内部では`Repeater()`と同じ行配列の正規化を利用します。`acf_fc_layout`が未入力・空文字・文字列以外の行は除外します。`Get`は取得済み行配列を保持する取得口であり、行内のフィールドを`Text()`・`Img()`・`Nested()`・`Repeater()`・`Flexible()`などで取得できます。投稿・IDなどACFの取得先から呼んだ場合は、該当レイアウトの未整形値と子フィールド定義も引き継ぐため、時間系取得口も利用できます。

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
