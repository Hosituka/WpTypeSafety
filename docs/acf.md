# ACFの取得API

## 参照先のフィールド取得口

ACFの参照先を`AcfGet`として読むメソッドは、`PostFields()`・`PostsFields()`・`TermFields()`・`TermsFields()`・`UserFields()`・`UsersFields()`です。

WordPressネイティブオブジェクトを取得する`WpPost()`・`WpTerm()`・`WpUser()`とは用途が異なります。WordPress層の`PostGet`等のクラスは、WordPress値の取得・Dataへの変換を担います。

## 目次

目的から探す場合は、次の入口を参照してください。

- 文字列・数値を取得したい：[標準取得口の一覧](#標準取得口の一覧)・[数値フィールドの取得](#数値フィールドの取得)
- 画像・ファイル・Icon Pickerを取得したい：[ACF画像・ファイルの取得](#acf画像ファイルの取得)・[Icon Pickerの取得](#icon-pickerの取得)
- Group・Repeater・Flexible Contentをたどりたい：[生配列の取得](#生配列の取得)・[リピーター行の取得](#リピーター行の取得)・[フレキシブルコンテンツの取得](#フレキシブルコンテンツの取得)
- 投稿・ユーザー・ターム参照を扱いたい：[ACFの取得先の指定](#acfの取得先の指定)・[投稿・ターム・ユーザーの複数取得](#投稿タームユーザーの複数取得)
- 選択系フィールドを扱いたい：[ラジオ・ボタングループ・チェックボックスの取得口](#ラジオボタングループチェックボックスの取得口)・[セレクトの取得口](#セレクトの取得口)
- 独自フィールドへ対応したい：[案件固有フィールドへの拡張](#案件固有フィールドへの拡張)
- ACFブロックを扱いたい：[ACFブロックの描画情報](#acfブロックの描画情報)

## 標準取得口の一覧

投稿・ユーザー・ターム・ACF参照先ID・取得済み配列は、すべて`AcfGet`から同じ取得口を利用できます。型違い・未入力時は、単数Dataとnullableな値が`null`、文字列が空文字、リストと配列が空配列です。

| 取得口 | 戻り値 | 用途・契約 |
|---|---|---|
| `Text()` | `string` | 文字列。未入力・型違いは空文字 |
| `Float()` | `?float` | 整数・float・数値文字列をfloatへ正規化。数値以外は`null` |
| `Int()` | `?int` | 整数・整数相当のfloat・整数形式の数値文字列をintへ正規化。それ以外は`null` |
| `Bool()` | `?bool` | 真偽値、整数・文字列の`1`・`0`、文字列の`true`・`false`を真偽値へ正規化。型違いは`null` |
| `Link()` | `?LinkData` | ACFリンク配列 |
| `Url()` | `?string` | HTTP(S)の絶対URL |
| `Img()` | `?ImgData` | ID・寸法を取得できる画像 |
| `ImgUrl()` | `?string` | URL返却形式の画像 |
| `ImgId()` | `?int` | ID・画像配列から正の添付IDを抽出 |
| `IconClass()` | `?string` | Icon PickerのDashiconクラス名 |
| `IconImg()` | `?ImgData` | Icon Pickerのメディアライブラリ画像 |
| `IconId()` | `?int` | Icon Pickerのメディアライブラリ添付ID |
| `IconUrl()` | `?string` | Icon PickerのURLタブ |
| `Gallery()` | `list<ImgData>` | 画像情報を持つギャラリー |
| `GalleryUrl()` | `list<string>` | URL返却形式のギャラリー |
| `GalleryIds()` | `list<int>` | ID・画像配列のリストから添付IDを抽出 |
| `File()` | `?FileData` | ID・ファイルサイズを取得できるファイル |
| `FileUrl()` | `?string` | URL返却形式のファイル |
| `FileId()` | `?int` | ID・ファイル配列から正の添付IDを抽出 |
| `Date()` | `?DateData` | Date Picker。時刻とタイムゾーンを含まない日付 |
| `DateTime()` | `?DateTimeImmutable` | Date Time Picker |
| `Time()` | `?TimeData` | Time Picker。日付を含まない時刻 |
| `Post()`・`Posts()` | `?PostData`・`list<PostData>` | 投稿オブジェクト・関連フィールド |
| `Term()`・`Terms()` | `?TermData`・`list<TermData>` | タクソノミーフィールド |
| `User()`・`Users()` | `?WpUserData`・`list<WpUserData>` | ユーザーフィールド |
| `WpPost()`・`WpPosts()` | `?WP_Post`・`list<WP_Post>` | 投稿参照をWordPressネイティブ型で取得 |
| `WpTerm()`・`WpTerms()` | `?WP_Term`・`list<WP_Term>` | ターム参照をWordPressネイティブ型で取得 |
| `WpUser()`・`WpUsers()` | `?WP_User`・`list<WP_User>` | ユーザー参照をWordPressネイティブ型で取得 |
| `PostFields()`・`UserFields()`・`TermFields()` | 呼び出し元と同じ`AcfGet`派生型 | 単一参照先のACFフィールド取得口へ切り替える |
| `PostsFields()`・`UsersFields()`・`TermsFields()` | `list<呼び出し元と同じAcfGet派生型>` | 複数参照先それぞれのACFフィールド取得口を返す |
| `Native()` | `mixed` | 通常のACF戻り値をWpTypeSafetyで変換せず返す |
| `Nested()` | `AcfGet` | グループなどの子配列へ進む |
| `NestedPath()` | `AcfGet` | Groupだけで構成された複数階層をまとめて進む |
| `Array()` | `array<mixed>` | 生配列。要素型は保証しない |
| `Repeater()` | `list<AcfGet>` | リピーターの各行 |
| `Flexible()` | `list<FlexibleNode>` | レイアウト名と行の取得口 |

ラジオ・ボタングループ・チェックボックス・セレクトは、ACFの返り値設定に対応する専用取得口を使います。詳細は各節を参照してください。

## 添付IDをWordPressの描画APIへ渡す

`ImgId()`・`FileId()`は正の整数・数字だけの文字列・配列の`ID`または`id`を`?int`へ正規化します。先頭の0は許可しますが、PHPの整数範囲を超える値、0・負数・float・真偽値・URLだけの値は`null`です。配列では`ID`を優先し、欠損または`null`の場合だけ`id`を使用します。

`GalleryIds()`は同じ規則で各要素を変換し、不正な要素を除外して入力順と重複を維持した連番リストを返します。未入力・配列以外・全件不一致は`[]`です。いずれもURLからのID逆引き、添付の実在・メディア種類・ファイルの存在確認は行いません。`ImgId()`でIDが取得できても、画像として描画できる保証ではありません。

```php
<?php
$postGet = new AcfGet($post);
$photoId = $postGet->ImgId('photo');

if ($photoId !== null) {
	echo wp_get_attachment_image($photoId, 'medium_large', false, ['class' => 'photo']);
}

$galleryIds = $postGet->GalleryIds('gallery');
$fileId = $postGet->FileId('document');
```

HTML生成は利用側で行います。IDだけを取得する際に`ImgData`・`FileData`の生成や寸法取得は行いません。URL返却形式のフィールドからもIDを必要とする場合は、ACFの返却形式をIDまたは配列に設定してください。

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

代表的な入力の変換結果は次のとおりです。

| 入力例 | `Float()` | `Int()` |
|---|---|---|
| `12` | `12.0` | `12` |
| `'12'` | `12.0` | `12` |
| `'12.5'` | `12.5` | `null` |
| `'1e3'` | `1000.0` | `null` |
| 非数値文字列 | `null` | `null` |

`Float()`はPHPの`is_numeric()`に従うため、前後に空白がある数値文字列や指数表記も受け入れます。`Int()`は整数、整数として正確に表現できるfloat、前後のASCII空白を除いて符号と数字だけで構成された整数形式の文字列を受け入れます。数字の途中に空白がある文字列・PHPの整数範囲外は`null`です。`TypeCheck::Int()`は変換を行わず、最初から`int`である値だけを通すCore APIです。

## ACFの取得先の指定

グループ内の値を読む場合は、`Nested()`で子の`AcfGet`を取得できます。配列そのものが必要な場合は`Array()`を使います。

```php
<?php
$postGet = new AcfGet($post);
$firstViewGet = $postGet->Nested('first_view');
$label = $firstViewGet->Text('label');
$title = $postGet->NestedPath('outer', 'inner')->Text('title');
```

`NestedPath()`は指定したフィールド名ごとに`Nested()`を順番に呼び出す省略形です。Groupだけで構成された経路を対象とし、RepeaterやFlexible Contentの行を自動選択しません。

`Nested()`と`NestedPath()`は呼び出した時点の`Array()`の結果を保持します。未設定・配列以外の値は空の取得元になり、続く`Text()`は空文字、`Img()`はnullを返します。投稿・オプションページ・ユーザー・タームを取得元にしたACFグループでは、整形済み配列に加えて未整形値と子フィールド定義も保持するため、グループ内でも`Date()`・`DateTime()`・`Time()`を利用できます。戻り値は呼び出し元と同じ`AcfGet`派生型です。

単一選択のACF投稿オブジェクト・関連・ユーザー・タクソノミーフィールドが参照する先のACF値を読むときは、`PostFields()`・`UserFields()`・`TermFields()`を使えます。内部では既存の`Post()`・`User()`・`Term()`で対象を取得し、同じ派生型の子取得口へ取得元だけを差し替えます。未選択・型違い・取得失敗なら、空の取得元を返します。`UserFields()`はアバター画像を取得しません。アバター画像のサイズは、`User()`などで得た`WpUserData`の`GetAvatar()`を呼ぶときに決まります。

```php
<?php
$postGet = new AcfGet($post);
$relatedSubtitle = $postGet->PostFields('related_post')->Text('subtitle');
$authorName = $postGet->UserFields('author')->Text('profile_name');
$termColor = $postGet->TermFields('category')->Text('display_color');
$groupAuthorName = $postGet->Nested('settings')->UserFields('author')->Text('profile_name');
```

`Nested()`は同じ取得結果内の配列へ進み、`PostFields()`・`UserFields()`・`TermFields()`は参照先の投稿・ユーザー・タームへ取得対象を切り替えます。これらは単一値用です。ACFフィールドで複数選択を許可した場合の配列は対象にせず、空の取得元になります。投稿の標準投稿者や通常のタクソノミー割り当てを読むメソッドでもありません。

## 投稿・ターム・ユーザーの複数取得

参照フィールドでは、必要な戻り値に合わせて取得口を選びます。表の`Post`部分は、タームでは`Term`、ユーザーでは`User`に置き換えられます。

| やりたいこと | 単数 | 複数 | 投稿の場合の戻り値 |
|---|---|---|---|
| WpTypeSafetyのDataへ変換する | `Post()` | `Posts()` | `?PostData`・`list<PostData>` |
| WordPressネイティブ型へ変換する | `WpPost()` | `WpPosts()` | `?WP_Post`・`list<WP_Post>` |
| 参照先のACFを読む | `PostFields()` | `PostsFields()` | `AcfGet`・`list<AcfGet>` |
| ACFの値を変換せず取得する | `Native()` | — | `mixed` |

複数選択を許可した投稿オブジェクト・関連・タクソノミー・ユーザーフィールドには、`Posts()`・`Terms()`・`Users()`を使います。`AcfGet`および`Nested()`で利用できます。

```php
<?php
$relatedPosts = $postGet->Posts('related_posts');
$categories = $postGet->Terms('categories');
$members = $postGet->Users('members', avatarSize: 160);
```

戻り値はそれぞれ`list<PostData>`・`list<TermData>`・`list<WpUserData>`です。ACFの返却形式がオブジェクトまたはIDのどちらでも変換し、未入力・配列以外は`[]`、取得・変換できない要素は除外します。入力順と重複は維持します。`Users()`の`avatarSize`は各`WpUserData`のアバター取得時の既定サイズとして保持されます。

案件固有の投稿Dataは単数・複数とも`dataClass`で指定します。`Post(string $fieldName, string $dataClass = PostData::class): ?PostData`・`Posts(string $fieldName, string $dataClass = PostData::class): array`のPHPDocは`@template T of PostData`・`@param class-string<T> $dataClass`と戻り値`T|null`・`list<T>`です。参照値をWP_Postへ正規化してから指定クラスのFromを呼びます。ターム・ユーザーもdataClassで指定し、正規化したWP_Term・WP_Userを指定クラスのFromへ渡します。

```php
<?php
$featuredCase = $postGet->Post(
	'featured_case',
	dataClass: CaseData::class,
);
$relatedCases = $postGet->Posts(
	'related_cases',
	dataClass: CaseData::class,
);
$areas = $postGet->Terms(
	'areas',
	dataClass: AreaData::class,
);
$members = $postGet->Users(
	'members',
	dataClass: StaffUserData::class,
);
```

指定クラスはフィールド取得前に検証し、未知・別系統・abstractクラスはInvalidArgumentExceptionです。単数の未選択・変換失敗はnull、複数は要素を除外します。入力順・重複は維持します。PostのFromは非nullable、Term/UserのFromはnullableです。生成例外は伝播します。UserにはavatarSizeも渡します。クラス未指定時は共通Dataを返します。

WordPressやプラグインのAPIへネイティブオブジェクトを渡す場合は、`WpPost()`・`WpTerm()`・`WpUser()`と、それぞれの複数形を使用できます。DataとWordPressネイティブ型は、利用先に合わせて選べます。

```php
<?php
$wpPost = $postGet->WpPost('related_post');       // ?WP_Post
$wpPosts = $postGet->WpPosts('related_posts');    // list<WP_Post>
$wpTerm = $postGet->WpTerm('category');           // ?WP_Term
$wpUser = $postGet->WpUser('member');             // ?WP_User
```

これらはACFの返却形式がIDまたは対応するWPオブジェクトのどちらでも、記載したネイティブ型へ正規化します。`Native()`はACFの値を変換せず返すため、返却形式によって型が変わります。固定されたWP型が必要な場合は`WpPost()`等を使用してください。

各参照先のACFフィールドへ進む場合は、`PostsFields()`・`TermsFields()`・`UsersFields()`を使います。戻り値は`list<AcfGet>`で、派生`AcfGet`から呼んだ場合はリスト内でも同じ派生型とAdapterを維持します。既存の複数取得メソッドで変換できた対象だけを子取得口にするため、入力順・重複・不正要素除外の契約も同じです。

```php
<?php
$relatedPostGets = $postGet->PostsFields('related_posts');

foreach ($relatedPostGets as $relatedPostGet) {
	$subtitle = $relatedPostGet->Text('subtitle');
}
```

単数用の`Post()`・`Term()`・`User()`へ複数値を渡しても、先頭要素を推測して返すことはありません。同様に、複数用メソッドへ単一値を渡した場合も空配列です。投稿の標準タームや投稿者を取得するAPIではなく、指定したACFフィールドの値だけを対象にします。

`AcfGet`の取得元には次の値を指定できます。

| 渡す値 | 解釈 |
|---|---|
| `WP_Post`・`PostData`・`int` | 投稿ID |
| `WP_User`・`WpUserData` | ACFのユーザー参照ID |
| `WP_Term`・`TermData` | ACFのターム参照ID |
| `string` | オプションページなどの取得先ID |
| `array` | 取得済み配列内のキー |
| `null` | ACFの現在のコンテキスト |

派生Dataも対応する基底Dataと同じように扱います。

```php
<?php
$postGet = new AcfGet($post);                    // WP_Post
$postDataGet = new AcfGet($postData);            // PostData・案件用の派生DTO
$userGet = new AcfGet($user);                    // WP_User・WpUserData
$termGet = new AcfGet($term);                    // WP_Term・TermData
$postIdGet = new AcfGet($postData->Id);
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

必要な情報量に合わせて取得口を選びます。

| ACFフィールド | Dataで取得 | URLだけ取得 |
|---|---|---|
| 画像 | `Img()` | `ImgUrl()` |
| ギャラリー | `Gallery()` | `GalleryUrl()` |
| ファイル | `File()` | `FileUrl()` |

```php
<?php
use WpTypeSafety\Acf\Get\AcfGet;

$fields = new AcfGet();
$image = $fields->Img('hero');      // 寸法がある場合はImgData、取得できなければnull
$imageUrl = $fields->ImgUrl('logo'); // URL返却形式の画像は?string
$fileUrl = $fields->FileUrl('document'); // URL返却形式のファイルは?string
```

対象のユーザー・タームを`AcfGet`のコンストラクターへ渡すと、その対象のACFフィールドを取得します。URL返却形式の画像には`ImgUrl()`、ファイルには`FileUrl()`を使います。

ギャラリーのURL返却形式には`GalleryUrl($fieldName): list<string>`を使用します。`AcfGet`および`Nested()`に対応します。未入力・配列以外は`[]`、不正URL・ID・画像情報配列は除外します。有効なHTTP(S)絶対URLの順序と重複を保持し、エスケープは出力側で行います。画像情報が必要な場合は`Gallery()`を使います。

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
| `IconId()` | Media Library | `?int` |
| `IconUrl()` | URL | `?string` |

```php
<?php
$postGet = new AcfGet($post);
$iconClass = $postGet->IconClass('menu_icon');
$iconImg = $postGet->IconImg('menu_icon', 'medium');
$iconId = $postGet->IconId('menu_icon');
$iconUrl = $postGet->IconUrl('menu_icon');
```

`IconClass()`は`dashicons-admin-home`のような選択されたアイコン固有のクラス名だけを返します。HTMLへ出力するときは、WordPress共通の`dashicons`クラスを呼び出し側で追加します。フロント画面でDashiconsのCSSがまだ読み込まれていない場合は、テーマまたはプラグイン側で`wp_enqueue_style('dashicons')`を行います。

```php
<?php if ($iconClass !== null): ?>
	<span class="dashicons <?= esc_attr($iconClass) ?>" aria-hidden="true"></span>
<?php endif; ?>
```

`IconImg()`はMedia Libraryで選ばれた添付画像を既存の`ImgData`へ変換します。第2引数には`Img()`と同じ画像サイズを指定でき、SVGも既存のSVG寸法取得仕様に従います。`IconUrl()`はURLタブのHTTP(S)絶対URLだけを返します。属性へ出力するときのエスケープは呼び出し側で行います。

`IconId()`はMedia Libraryの未整形IDまたは画像配列内の`ID`・`id`を正の整数へ正規化します。Dashicons・URL・独自タブは`null`です。`ImgId()`と同じく、添付の実在・メディア種類は検証せず、URLからのID逆引きや寸法取得も行いません。戻り値は利用側で`wp_get_attachment_image()`などへ渡せます。

投稿・ID・ユーザー・ターム・オプションページ等を取得元にした場合は、Icon PickerのReturn Formatにかかわらず、ACFの未整形値から選択タブを判定します。`Nested()`・`Repeater()`・`Flexible()`も未整形値と子フィールド定義を引き継ぐため、同じ4つの取得口を利用できます。

外部で取得済みの配列を`AcfGet`へ渡す場合は、`['type' => 'dashicons|media_library|url', 'value' => ...]`というIcon Pickerの配列形式を維持してください。文字列だけの場合はDashiconクラス名とHTTP(S) URLを判別できますが、Media Library由来のURL文字列から添付画像IDや元のタブを復元することはできません。

ACFのフィルターで追加した独自タブは、標準の3取得口では推測して変換しません。`Native()`で値を取得し、案件固有の派生`AcfGet`で型確認・正規化してください。

## 日付・日時・時刻の取得

ACFのReturn Formatに依存せず扱う場合は、時間系フィールド専用の取得口を使用します。

| 取得口 | ACFフィールドタイプ | 戻り値 | ACFの未整形形式 | 外部の取得済み配列から作った取得口 | 取得できない場合 |
|---|---|---|---|---|---|
| `Date()` | Date Picker | `?DateData` | `Ymd` | `null` | `null` |
| `DateTime()` | Date Time Picker | `?DateTimeImmutable` | `Y-m-d H:i:s` | `null` | `null` |
| `Time()` | Time Picker | `?TimeData` | `H:i:s` | `null` | `null` |

```php
<?php
$eventDate = $postGet->Date('event_date');
$eventStart = $postGet->DateTime('event_start');
$openingTime = $postGet->Time('opening_time');

echo esc_html($eventDate?->Format('Y-m-d') ?? '');
echo esc_html($eventStart?->format('Y-m-d H:i') ?? '');
echo esc_html($openingTime?->Format('H:i') ?? '');
```

`Date()`は時刻とタイムゾーンを補わず、`Year`・`Month`・`Day`を持つ`DateData`を返します。`DateTime()`はWordPressのサイトタイムゾーンを持つ`DateTimeImmutable`、`Time()`は日付とタイムゾーンを含まない`TimeData`を返します。`DateData::Format()`の既定形式は`Y-m-d`、`TimeData::Format()`は`H:i:s`で、どちらもPHPの日時フォーマットを指定できます。表の「取得できない場合」には未入力、異なるフィールドタイプ、存在しない日付・時刻、解析失敗を含みます。

専用取得口は`get_field_object()`から未整形値を取得するため、管理画面のReturn Formatを変更しても戻り値の契約は変わりません。Return Formatどおりの表示文字列が必要な場合は、`Text()`を使用できます。

時間系取得口は、投稿・ID・オプションページ・ユーザー・タームなどACFの取得先を直接指定した場合と、そこからたどる`Nested()`・`Repeater()`・`Flexible()`に対応します。グループ内のリピーター、リピーター行内のグループなど、複数種類を組み合わせた入れ子でも利用できます。外部の取得済み配列ではフィールド定義と未整形値を復元できないため、子取得口でも表のとおり`null`です。

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

## URLフィールドの取得

`Url('url')`は`?string`を返します。`AcfGet`、
取得済み配列・`Nested()`で利用できます。
`ImgUrl()`・`FileUrl()`と同じHTTP(S)の絶対URL検証を使い、
未入力・型違い・不正URL・相対パス・`javascript:`・`mailto:`などは`null`です。
値は変換・エスケープせず返すため、描画時には`esc_url($url)`を使用してください。
Select取得口と異なり、フィールド型の照合は行わず、取得した値がURLとして妥当かを検証します。

```php
<?php
$url = $rowGet->Url('url');
if ($url !== null) {
	// テンプレートでhrefへesc_urlして出力する。
}
```

## 参照先未選択時

`PostFields()`・`UserFields()`・`TermFields()`で参照フィールドが未選択・型違い・取得失敗の場合は、空配列を子取得口の取得元として使います。`PostsFields()`・`UsersFields()`・`TermsFields()`の場合は空のリストを返します。
対象IDの組み立てやACFの取得関数を呼ばず、既存の取得口ごとの空値を返します。
`Text()`は空文字、`Array()`・`Repeater()`・複数選択は空配列、
画像・URL・単一選択などは`null`です。`Nested()`も空の取得元を引き継ぎます。
現在の投稿へのフォールバックは行いません。
有効なターム・ユーザーも取得元に指定できます。nullableな変数を直接渡す場合、`null`は現在の投稿を意味するため、空の取得元にしたい場合は`new AcfGet($subject ?? [])`と明示してください。

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

`Nested()`・`Repeater()`・`FlexibleNode::Get`が生成する子取得口でも、呼び出し元の派生型と追加したメソッドを利用できます。独自の初期化が必要な派生型では、子取得口の生成処理を調整してください。詳しくは[内部構造](architecture.md#getとadapter)を参照してください。

```php
<?php
$projectGet = new ProjectAcfGet($postId);
$groupGet = $projectGet->Nested('settings'); // ProjectAcfGet

foreach ($projectGet->Repeater('items') as $rowGet) {
	$price = $rowGet->Price('price'); // 派生クラスのメソッドを利用可能
}
```

## 生配列の取得

`Array()`はグループ・リピーターなどの取得値が配列なら加工せず返し、未設定・配列以外は`[]`を返します。
返り値は`array<mixed>`で、キー・要素の型は保証しません。
各フィールドを型付きで読む場合は`Nested()`を使います。
`Repeater()`は基底型から呼ぶと`list<AcfGet>`、派生型から呼ぶと同じ派生型のリストを返します。生の行配列が必要な場合は`Array()`を使います。

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

`Repeater()`の各行は`AcfGet`型として扱えます。戻り値はリストなので、`count()`・`array_slice()`などの配列操作も利用できます。

## フレキシブルコンテンツの取得

`Flexible('contents')`は`list<FlexibleNode>`を返します。`FlexibleNode`は、ACFが各行の`acf_fc_layout`へ保存するレイアウト名を`Layout`、同じ行の取得口を`Get`として保持します。`Get`は呼び出し元と同じ`AcfGet`派生型です。

```php
<?php
foreach ($postGet->Flexible('contents') as $node) {
	$templateSlug = match ($node->Layout) {
		'image_section' => 'template-parts/flexible/image-section',
		'text_section' => 'template-parts/flexible/text-section',
		default => null,
	};
	if ($templateSlug === null) {
		continue;
	}

	get_template_part($templateSlug, null, ['rowGet' => $node->Get]);
}
```

各テンプレートパーツでは`$args['rowGet']`を`AcfGet`として使います。レイアウト名とテンプレートパスの対応を明示しているため、未対応のレイアウトは読み込みません。

内部では`Repeater()`と同じ行配列の正規化を利用します。`acf_fc_layout`が未入力・空文字・文字列以外の行は除外します。`Get`は取得済み行配列を保持する取得口であり、行内のフィールドを`Text()`・`Img()`・`Nested()`・`Repeater()`・`Flexible()`などで取得できます。投稿・IDなどACFの取得先から呼んだ場合は、該当レイアウトの未整形値と子フィールド定義も引き継ぐため、時間系取得口も利用できます。

`FlexibleNode`はreadonlyですが、利用側から`new FlexibleNode(Layout: ..., Get: ...)`で直接構築できます。

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

Factoryの`bool $isPreview = false`引数でプレビュー状態を明示します。`$block`内の値や`is_admin()`から推測しません。文字列項目の欠損・型違いは空文字、クラスの欠損・型違いは空配列へ正規化されます。元配列や周辺状態が変化しても、生成済みインスタンスの値は変更されません。

AcfBlockDataは正規化済みの値を使って直接生成することもできます。WordPress Dataの外部new禁止はAcfBlockDataへ適用しません。Dataのコンストラクターは生の`$block`配列ではなく、名前付き引数を受け取ります。

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

Data単独での生成・プロパティ参照・クラス判定にWordPress・ACFは不要です。`Classes`の要素を含む正規化済み入力の用意は、直接生成する呼び出し元の責務です。

`GetWrapperAttributes(string $className = ''): string`は、コンストラクターの任意引数`wrapperAttributesResolver`（`Closure(string): string`）を使います。Resolver未指定でも直接構築できますが、属性取得を呼ぶと`LogicException`になります。

Factoryから生成した場合、プレビューでは基本クラスと生成時のClassesが`class`属性に反映され、フロントではWordPressのブロック属性が使われます。プレビューの内側へアンカーを重複付与せず、フロントのクラス・アンカー・スタイルはWordPressの描画コンテキストへ任せます。戻り値はエスケープ済みなので、`<div <?= $wrapperAttributes ?>>`のようにそのまま出力してください。二重エスケープはしません。フロントでは対象ブロックの描画中に呼んでください。

独自に直接生成する場合は、`wrapperAttributesResolver: static fn (string $className): string => ...`のように描画処理を渡せます。処理は属性取得時に毎回呼ばれ、コンストラクターでは実行しません。独自Resolverもエスケープ済みの属性文字列を返す責務を持ちます。ACFフィールドの取得・その他のHTML描画はテンプレート側で行います。
