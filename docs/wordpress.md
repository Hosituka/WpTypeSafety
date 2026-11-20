# WordPressの取得API

## 対象別の取得口

WordPressの値は、取得元と必要な戻り値に合わせて次の入口から読みます。

| 取得したいもの | 入口 | 主な操作 |
|---|---|---|
| 現在の表示対象・ループ投稿・ログインユーザー | `PostGet`・`TermGet`・`UserGet` | `Current()`・`Loop()`・`LoggedIn()` |
| 取得済みのWordPressオブジェクトをDataへ変換 | 各`*Data` | `From()` |
| ID・slug・path等から単体取得 | 対象別の`*Get` | `ById()`・`BySlug()`・`ByPath()`等 |
| 元のメインクエリの投稿・ページ情報 | `MainQueryGet` | 投稿一覧・ページ番号・総ページ数・投稿総数 |
| 投稿・ターム・ユーザー・コメントの一覧 | 各`*Query` | `Get()`・`From()` |
| 投稿・ターム・ユーザー・コメントのメタ | `MetaGet` | 型付きGetter・入れ子探索 |
| メニュー全体 | `MenuGet` | ID・slug・位置から取得 |
| メニュー項目 | `MenuNodeData::From` | セットアップ済み項目を変換 |
| 投稿タイプ・タクソノミーの登録定義 | `PostTypeGet`・`TaxonomyGet` | 登録名からの取得・Dataへの変換 |

`*Get`は対象ごとの単体取得・変換、`*Query`は一覧取得、`MetaGet`はメタ値の読み取りに使用します。

## 現在の表示対象・ループ投稿・ログインユーザー

WordPressのコンテキストからDataを取得する入口です。Dataへの変換には`From()`と同じ規則を使用します。

| API | 取得元 | 対象がない場合 |
|---|---|---|
| `PostGet::Current(string $dataClass = PostData::class)` | 元のメインクエリの表示対象 | 投稿でなければ`null` |
| `PostGet::Loop(string $dataClass = PostData::class)` | グローバル`$post` | `WP_Post`でなければ`null` |
| `TermGet::Current(string $dataClass = TermData::class)` | 元のメインクエリの表示対象 | タームでなければ`null` |
| `PostTypeGet::Current()` | 元のメインクエリの投稿詳細・投稿タイプアーカイブ | 対象外・未登録なら`null` |
| `UserGet::Current(int $avatarSize = 96, string $dataClass = WpUserData::class)` | 元のメインクエリの表示対象 | 有効なユーザーでなければ`null` |
| `UserGet::LoggedIn(int $avatarSize = 96, string $dataClass = WpUserData::class)` | `wp_get_current_user()` | 未ログインなら`null` |

`Current()`はクエリ確定後に使用します。メインクエリが存在しない場合は`null`です。`$wp_query`が独自クエリに置き換えられていても、`$wp_the_query`が指す表示対象を読みます。ターム一覧では`PostGet::Current()`は`null`で、`TermGet::Current()`が表示タームを返します。

会社案内ページ内で制作実績の独自ループを回す場合、`PostGet::Current()`は会社案内、`PostGet::Loop()`はループ中の制作実績です。`Loop()`はループの開始・進行・終了判定を行わず、呼び出し時点の`$post`を読みます。ループ外には古い値が残る場合があるため、ループ内や`setup_postdata()`で取得元を設定した場所で使用してください。

```php
use WpTypeSafety\Wp\Get\PostGet;
use WpTypeSafety\Wp\Get\UserGet;
use WpTypeSafety\Wp\Data\WpUserData;

$pageData = PostGet::Current();
$loopPostData = PostGet::Loop();
$loggedInUser = UserGet::LoggedIn(avatarSize: 64);
$archiveAuthor = UserGet::Current(avatarSize: 64);

// ProjectPostDataがPostDataを継承している場合。
$projectPost = PostGet::Current(dataClass: ProjectPostData::class);
```

派生Dataが必要な場合は、各取得口の`dataClass:`へ`class-string<T>`を指定します。取得口は指定クラスの`From()`を呼び、未知・別系統・abstractクラスは検索前に`InvalidArgumentException`とします。対象がなければ`From()`を呼ばず`null`、生成中の例外はそのまま伝播します。[Dataと派生クラス](data.md)も参照してください。

これらはWordPressの値をDataへ取り込むAPIで、ログイン処理・権限判定・投稿の公開可否による除外・HTML生成は行いません。グローバルな投稿やクエリの置き換え、ループの進行、結果の独自キャッシュも行いません。変換時のURL等の取得やWordPress自身のログインユーザー初期化は通常どおり発生します。

`UserGet::Current()`は著者アーカイブの表示対象、`LoggedIn()`は閲覧者のログインユーザーです。別の著者ページを開いても両者は同一とは限りません。`Current()`はログインユーザーへフォールバックしません。

## メインクエリの投稿一覧とページ情報

`MainQueryGet`は、クエリ確定後に元のメインクエリ`$wp_the_query`を読みます。`$wp_query`が独自クエリへ置き換えられていても対象は変わりません。追加クエリ・件数の再集計・グローバルの変更はしません。

```php
use WpTypeSafety\Wp\Get\MainQueryGet;

$currentPage = MainQueryGet::CurrentPageNumber();
$maxPageCount = MainQueryGet::MaxPageCount();
$foundPostCount = MainQueryGet::FoundPostCount();
```

`Current()`は実行済みのメインクエリを`PostQueryData`へ変換します。新しい投稿検索やループの進行は行いません。

```php
$result = MainQueryGet::Current();
$posts = $result->Posts;

// 案件固有のPostData派生型も指定できます。
$caseResult = MainQueryGet::Current(
	dataClass: CaseData::class,
);
```

dataClassは`class-string<T>`（既定値PostData::class）で、各投稿を指定クラスのFromで生成します。各呼び出しでDataを生成し、独自キャッシュは行いません。`CurrentPageNumber`は既存の同名メソッドと同じ規則で取得します。`no_found_rows`が有効な場合、結果の`TotalCount`・`MaxPageCount`は`null`です。`fields: ids`等のID専用クエリは`PostQuery::From()`と同じく非対応です。

| メソッド | 戻り値 | 値がない場合 |
|---|---|---|
| `Current(string $dataClass = PostData::class)` | `PostQueryData<T>` | 投稿なしは空リスト、未集計の件数はnull |
| `CurrentPageNumber()` | `int` | 未指定・不正値は1 |
| `MaxPageCount()` | `int` | 未集計・空結果は0 |
| `FoundPostCount()` | `int` | 未集計・空結果は0 |

現在番号は通常`paged`、固定フロントページでは`page`から取得します。最新投稿を表示するフロントページは`paged`です。通常の投稿・固定ページの本文を`<!--nextpage-->`で分割した番号は一覧ページ番号として扱いません。[WordPress公式の説明](https://developer.wordpress.org/reference/functions/get_query_var/)

総ページ数・投稿総数はWordPressが保持している値を返します。集計省略時の0から実際の総数を推測しません。固定フロントページ内の独自一覧の総ページ数も対象外です。独自クエリの結果とページ情報は`PostQueryData`で扱ってください。メインクエリオブジェクトが存在しない段階で呼ぶと`LogicException`です。

## WordPressの値からデータクラスを生成する

対象別の取得口はWordPressの値から共通Dataを生成します。

| 取得口 | 入力 | 戻り値 | 変換できない場合 |
|---|---|---|---|
| `PostData::From()` | `WP_Post` | `static` | — |
| `PostGet::ById()` | 投稿ID | `?PostData` | `null` |
| `PostGet::ByPath()` | 親子のslug列・投稿タイプ | `?PostData` | `null` |
| `TermGet::ById()` | タームID・任意のtaxonomy | `?TermData` | `null` |
| `TermGet::BySlug()` | taxonomy・slug | `?TermData` | `null` |
| `UserGet::ById()` | ユーザーID | `?WpUserData` | `null` |
| `UserGet::BySlug()` | user_nicename | `?WpUserData` | `null` |
| `UserGet::ByEmail()` | メールアドレス | `?WpUserData` | `null` |
| `UserGet::ByLogin()` | ログイン名 | `?WpUserData` | `null` |
| `CommentGet::ById()` | コメントID | `?CommentData` | `null` |
| `CommentData::From()` | `WP_Comment` | `CommentData` | — |
| `WpUserData::From()` | `WP_User` | `?WpUserData` | `null` |
| `TermData::From()` | `WP_Term` | `?TermData` | `null` |
| `MenuNodeData::From()` | セットアップ済みのメニュー項目`WP_Post` | `?MenuNodeData` | `null` |
| `AttachmentGet::ImgFromId()` | 添付ID | `?ImgData` | `null` |
| `AttachmentGet::FileFromId()` | 添付ID | `?FileData` | `null` |

`From()`は既存オブジェクトの変換、`By*()`は識別子からの単体取得、`*Query::Get()`は条件による集合取得です。

単体取得のIDは0以下なら検索せず`null`です。未発見・WP側のエラー値・対象外の戻り値も`null`です。公開状態・承認状態・閲覧権限では除外しません。取得結果を表示してよいかは利用側で判断してください。

```php
<?php
use WpTypeSafety\Wp\Get\AttachmentGet;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Get\CommentGet;
use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Data\MenuNodeData;
use WpTypeSafety\Wp\Get\PostGet;
use WpTypeSafety\Wp\Get\TermGet;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Get\UserGet;

$postData = PostData::From($post);
$postDataById = PostGet::ById($postId); // ?PostData
$commentData = CommentData::From($comment);
$menuNodeData = MenuNodeData::From($menuItem);
$userData = WpUserData::From($user);
$termData = TermData::From($term);
$image = AttachmentGet::ImgFromId($attachmentId, 'medium_large');
$file = AttachmentGet::FileFromId($attachmentId);
```

### 識別子からの単体取得

```php
$about = PostGet::ByPath('company/about'); // postType省略時はpage
$staff = PostGet::ByPath('members/editor', postType: 'staff');
$category = TermGet::ById(34, taxonomy: 'category');
$term = TermGet::BySlug(taxonomy: 'category', slug: 'knowledge');
$author = UserGet::ById(12);
$bySlug = UserGet::BySlug('public-name');
$byEmail = UserGet::ByEmail('user@example.com');
$byLogin = UserGet::ByLogin('account-name');
$comment = CommentGet::ById(123);
```

`ByPath()`は親子関係を含むslug列です。前後の`/`は除去しますが、URL全体・クエリ・フラグメント・バックスラッシュは受け付けません。指定した投稿タイプだけを検索し、ネイティブ関数の添付ファイルへのフォールバックは使用しません。階層型・非階層型の投稿に使用でき、投稿タイプの空文字は`null`です。

`TermGet::ById()`はtaxonomy未指定（`null`）ならIDだけで取得し、指定時は返されたタームの所属も検証します。明示的な空文字は`null`です。既存の`BySlug($taxonomy, $slug)`の引数順は維持します。

ユーザーの`BySlug()`は`user_nicename`、`ByLogin()`はログイン名の検索で、相互のフォールバックは行いません。空の識別子は検索せず`null`を返します。

投稿・ターム・ユーザー・コメントの単体取得は、共通して`dataClass:`へ派生Dataのクラス名を指定できます。省略時は対象ごとの標準Dataです。

```php
$article = PostGet::ById(
	123,
	dataClass: ProjectPostData::class,
);
```

ユーザー・コメントの取得口では、`avatarSize:`と`dataClass:`をそれぞれ指定できます。取得失敗時はDataを生成せず`null`を返し、nullableな`From()`が変換できない場合も`null`です。生成中の例外はそのまま伝播し、指定クラスを静的に登録・保持することはありません。

全WordPress DataのProps取得・調整は`protected static getProps()`で行います。派生クラスは`parent::getProps()`の値を変更し、案件固有の追加値は`initialize()`で設定します。`MenuNodeData::From()`はセットアップ済みのメニュー項目を対象とし、構築済みの子ノードを第2引数で受け取ります。

`PostData`では`createRelatedPost()`を上書きすると、親・子・前後投稿に案件固有の派生型を使用できます。著者は`GetAuthor()`を直接上書きします。`TermData`の親・子には`createRelatedTerm()`、`CommentData`の親・子には`createRelatedComment()`を使います。`CommentData`のユーザーと投稿は`GetUser()`・`GetPost()`を直接上書きします。これらはData内部の関連取得に使う拡張点で、公開取得口の`dataClass`とは独立しています。

`PostData`・`TermData`・`CommentData`・`MenuNodeData`は関連Data型をジェネリクスとして保持します（PostPropsは値だけで型パラメーターを持ちません）。そのため、案件固有Dataの`@extends`へ関連型を指定すると、子・親・前後投稿・投稿者などを取得した後もPHPStanで派生型を維持できます。

```php
<?php
/** @extends PostData<ArticleData, ProjectUserData> */
final class ArticleData extends PostData
{
	protected function createRelatedPost(\WP_Post $post): ?self
	{
		return self::From($post);
	}

	public function GetAuthor(): ?ProjectUserData
	{
		$user = $this->AuthorId > 0 ? get_userdata($this->AuthorId) : null;
		return $user instanceof \WP_User ? ProjectUserData::From($user) : null;
	}

}

$article = ArticleData::From($post);
$child = $article->GetChildren()[0]; // ArticleData
$author = $article->GetAuthor();     // ProjectUserData|null
```

生成フックを上書きしなければ関連投稿は`PostData::From()`で生成する基底`PostData`です。所有者と別型の関連投稿やnull除外、内部の関連生成Closureを維持し、一律static::Fromには変えません。`@extends`には実際の関連型を指定してください。投稿Dataのコンストラクターはfinal protectedで、関連型の選択はフック側で行います。

```php
<?php
/** @extends PostData<PostData, \WpTypeSafety\Wp\Data\WpUserData> */
final class DisplayPostData extends PostData
{
	protected static function getProps(\WP_Post $post): \WpTypeSafety\Wp\Data\PostProps
	{
		$props = parent::getProps($post);
		$props->Title = '表示用：' . $props->Title;
		return $props;
	}
}

$postData = DisplayPostData::From($post);
```

`PostData`は投稿の`Status`・`PostTypeName`・`Slug`を保持します。`IsPublished()`は投稿状態が`publish`かを返し、`IsPubliclyViewable`はWordPressの`is_post_publicly_viewable()`による投稿状態と投稿タイプを合わせた結果です。`IsPasswordProtected`はパスワード文字列が設定されているかを表しますが、パスワード文字列自体や、現在の閲覧者が認証済みかどうかは保持しません。

SVG添付画像は、ローカルファイルの単位なし・`px`の`width`と`height`を寸法として優先します。両方を取得できない場合は、`viewBox`から比率を取得します。SVGにはラスター画像のサイズバリエーションがないため、指定したサイズ名にかかわらず元のURLと寸法または比率を持つ`ImgData`を返します。どちらも判定できないSVGは`null`です。

案件固有の投稿Dataも継承したfinal public static From(WP_Post): staticで生成します。final protectedコンストラクターは基底値のみ設定し、Fromがprotected initializeを呼びます。派生initializeはparent::initialize($post)を先に呼び、自身の宣言した追加値を設定します。親呼び忘れは規約・テストで扱い、Reflection等で親を自動呼び出ししません。Term/User/Comment/Menu/登録定義Dataにも同じ生成契約を適用します。

投稿取得口のPHPDocは`@template T of PostData`・`@param class-string<T> $dataClass`です。

| 取得口 | シグネチャ | 戻り値 |
|---|---|---|
| 単体ID | `PostGet::ById(int $postId, string $dataClass = PostData::class)` | `T|null` |
| slug列 | `PostGet::ByPath(string $path, string $postType = 'page', string $dataClass = PostData::class)` | `T|null` |
| 投稿クエリ | `PostQuery::Get(array $args = [], string $dataClass = PostData::class)` | `PostQueryData<T>` |
| 既存クエリ | `PostQuery::From(WP_Query $query, string $dataClass = PostData::class)` | `PostQueryData<T>` |

すべての投稿クラス指定は検索前に検証します。未知・PostData以外・abstractのクラスはInvalidArgumentExceptionであり、検索の未発見null・空リストとは区別します。生成中の例外は伝播します。メインクエリ未設定時のLogicException等、入口固有の既存契約も維持します。

## WordPressクエリからDataを取得する

`PostQuery`・`TermQuery`・`UserQuery`・`CommentQuery`は、WordPressネイティブのクエリ引数をそのまま受け取り、結果をWpTypeSafetyのDataへ変換する薄い接続口です。独自のクエリビルダーではないため、絞り込み・並び替え・メタクエリ等にはWordPressと同じ配列を使用します。

```php
<?php
use WpTypeSafety\Wp\Get\PostQuery;

$result = PostQuery::Get([
	'post_type' => 'staff',
	'post_status' => 'publish',
	'posts_per_page' => 12,
	'paged' => 2,
	'orderby' => 'menu_order',
	'order' => 'ASC',
]);

foreach ($result->Posts as $postData) {
	echo esc_html($postData->Title);
}

$totalCount = $result->TotalCount;
$maxPageCount = $result->MaxPageCount;
$currentPageNumber = $result->CurrentPageNumber;
```

案件固有の派生Dataを一覧へ保持する場合は、すべてのQueryで`dataClass:`を指定します。`PostQueryData`等の要素型も、指定した派生Dataとして静的解析できます。

```php
<?php
use WpTypeSafety\Wp\Get\PostQuery;

$result = PostQuery::Get(
	[
		'post_type' => 'cases',
		'posts_per_page' => 12,
	],
	dataClass: CaseData::class,
);

foreach ($result->Posts as $caseData) {
	// $caseDataはCaseDataとして推論されます。
}
```

各Queryの生成指定と結果は次のとおりです。`dataClass`は`Get()`と`From()`のどちらでも指定できます。

| Query | 生成指定引数 | 結果Data | 要素プロパティ | ページ情報 |
|---|---|---|---|---|
| `PostQuery` | `dataClass`（class-string、既定値PostData::class） | `PostQueryData<T>` | `Posts` | あり |
| `UserQuery` | `dataClass` | `UserQueryData` | `Users` | あり |
| `CommentQuery` | `dataClass` | `CommentQueryData` | `Comments` | あり |
| `TermQuery` | `dataClass` | `TermQueryData` | `Terms` | なし |

ユーザーとコメントでは、指定した`avatarSize`をData生成にも使用します。Queryの絞り込みはData生成ではなく、WordPressのクエリ引数で行ってください。`dataClass`省略時はWpTypeSafetyの標準Dataへ変換します。nullableな`From()`が変換できない結果はリストから除外します。

投稿・ユーザー・コメントのページ情報には次の規則があります。

| Queryと条件 | `TotalCount` | `MaxPageCount` |
|---|---|---|
| `PostQuery`・`UserQuery`、総件数を計算する設定 | WordPressが計算した総件数 | WordPressが計算したページ数 |
| `PostQuery`の`no_found_rows: true`、`UserQuery`の`count_total: false` | `null` | `null` |
| `CommentQuery`、`number`未指定 | 返された`Comments`の件数 | 結果があれば`1`、空なら`0` |
| `CommentQuery`、`number`指定かつ`no_found_rows: false` | WordPressが計算した総件数 | WordPressが計算したページ数 |
| `CommentQuery`、`number`指定かつ`no_found_rows: true` | `null` | `null` |

総件数を計算する設定で該当結果が0件なら、件数とページ数は`0`です。`CommentQuery`で`number`を指定しない場合は、WordPressと同じく件数制限のない取得として扱います。

| `CurrentPageNumber`の条件 | 値 |
|---|---|
| 通常 | 指定された`paged`を1以上へ正規化した値 |
| `offset`も指定 | 同じく`paged`から得た値。`offset`が取得位置に優先するため、実際の取得位置を表すページ番号とは限らない |

タームクエリは`TermQueryData`を返し、変換結果を`Terms`へ保持します。WordPressに共通のページ総数プロパティがないため、`TermQueryData`は`TotalCount`・`MaxPageCount`・`CurrentPageNumber`を持ちません。総件数取得のための追加クエリも自動実行しません。

```php
<?php
use WpTypeSafety\Wp\Get\TermQuery;

$result = TermQuery::Get([
	'taxonomy' => 'area',
	'hide_empty' => false,
	'orderby' => 'name',
]);

foreach ($result->Terms as $area) {
	echo esc_html($area->Name);
}
```

既存コードや他のクエリ引数生成処理が作ったネイティブクエリは、各クラスの`From()`で変換できます。

```php
<?php
$wpQuery = new WP_Query($args);
$result = PostQuery::From($wpQuery, dataClass: CaseData::class);
```

各取得口はDataへ変換するため、`fields => 'ids'`等のオブジェクトを返さない指定には対応しません。該当する指定を渡した場合は`InvalidArgumentException`を投げます。ID・件数だけが必要な処理ではWordPressネイティブのクエリを直接使用してください。

## WordPressメタを型付きで取得する

`MetaGet`は投稿・ターム・ユーザー・コメントの保存済みメタを固定型で取得します。WordPressオブジェクトまたは対応するDataは、`new MetaGet()`へ直接渡せます。整数だけを渡した場合は投稿IDとして扱います。読み取り専用であり、メタ値の追加・更新・削除は行いません。

```php
<?php
use WpTypeSafety\Wp\Get\MetaGet;

$postMetaGet = new MetaGet($post);
$termMetaGet = new MetaGet($term);
$userMetaGet = new MetaGet($user);
$commentMetaGet = new MetaGet($comment);

$subtitle = $postMetaGet->String('subtitle');
$viewCount = $postMetaGet->Int('view_count');
$settings = $postMetaGet->Array('plugin_settings');
$hasSubtitle = $postMetaGet->Exists('subtitle');
```

整数のタームID・ユーザーID・コメントIDは型を推測できないため、`FromTerm()`・`FromUser()`・`FromComment()`で対象を明示します。`FromPost()`も利用できます。

既定ではWordPressの保存表現に合わせ、`NumericMode::Str`・`BoolMode::TrueFalseStr`・`BoolMode::OneZeroStr`・`BoolMode::OneZeroInt`・`BoolMode::EmptyStrAsFalse`を合成した規則を使います。`on`・`off`・`yes`・`no`は既定では変換せず、必要な境界で対応するModeを明示します。元のPHP型だけを受け入れたい境界では`ValueRules::Strict()`を指定できます。

```php
<?php
use WpTypeSafety\Core\Check\Data\BoolMode;
use WpTypeSafety\Core\Check\Data\NumericMode;
use WpTypeSafety\Core\Check\ValueRules;

$strictMetaGet = new MetaGet($post, ValueRules::Strict());
```

`Exists()`は`metadata_exists()`を使うため、欠損したキーと、保存済みの空文字・`null`相当の値を区別できます。欠損時はNormalizerを呼ばず、`String()`は空文字、`Array()`と各`*List()`は空配列、それ以外の型付き取得口は`null`を返します。0以下の対象IDと空のメタキーも欠損として扱い、WordPress APIを呼びません。

`Native()`は単一値取得の結果を変換せず返し、欠損時は`null`です。`NativeList()`と型付きの各`*List()`は、同じキーで複数行保存されたメタ値を対象にします。単一行にシリアライズされた配列は`Array()`または`Nested()`で取得してください。型付きリストでは変換できない要素を除外し、0からキーを振り直します。

| 取得口 | 戻り値 | 欠損・型違い |
|---|---|---|
| `Exists()` | `bool` | 欠損は`false` |
| `Native()` | `mixed` | 欠損は`null` |
| `NativeList()` | `list<mixed>` | 欠損は空配列 |
| `String()` | `string` | 欠損・型違いは空文字 |
| `Int()`・`Float()`・`Bool()` | nullableな各PHP型 | 欠損・型違いは`null` |
| `Array()` | `array<array-key, mixed>` | 欠損・型違いは空配列 |
| `StringList()`・`IntList()`・`FloatList()`・`BoolList()` | 型付きリスト | 不正要素を除外。欠損は空配列 |
| `BackedEnum()` | `?BackedEnum` | 欠損・変換失敗は`null` |
| `BackedEnumList()` | `list<BackedEnum>` | 不正要素を除外。欠損は空配列 |
| `Post()`・`User()`・`Term()`・`Comment()` | 対応するnullableなData | IDをWpTypeSafetyのDataへ変換 |
| `WpPost()`・`WpUser()`・`WpTerm()`・`WpComment()` | 対応するnullableなWPオブジェクト | IDをWordPressネイティブ型へ変換 |
| `Nested()`・`NestedPath()` | `MetaGet` | 欠損地点以降の取得は各型の既定値 |

`Nested()`は同じ`MetaGet`を返し、取得済みの配列とオブジェクトが混在する保存値をCoreの`TreeGet`でたどります。`NestedPath()`はメタキー直下のパスまで一度に進みます。選択した値は投稿・ユーザー・ターム・コメントのDataへ変換したり、その対象のメタへ再び入ったりできます。

```php
<?php
$relationsGet = $postMetaGet->NestedPath('settings', 'relations');
$relatedPost = $relationsGet->Post('post_id');
$relatedWpPost = $relationsGet->WpPost('post_id');
$relatedPostMetaGet = $relationsGet->PostMeta('post_id');
$relatedSubtitle = $relatedPostMetaGet->String('subtitle');
```

`Post()`などの名前は、取得値をどのWordPress IDとして解釈するかを利用側が明示します。値の形から投稿・ユーザー等を推測しません。Data中心の処理には`Post()`等、WordPressやプラグインのAPIへ渡す場合は`WpPost()`等を使用します。ネイティブ型への変換はIDから現在のWPオブジェクトを取得する読み取り処理であり、DataやWordPressの値を書き換えません。`MetaGet::FromTree()`または配列・オブジェクトを渡す`new MetaGet()`から、取得済みのPHP木構造だけを入口にすることもできます。


### MetaGetで派生Dataを指定する

```php
$article = $relationsGet->Post('post_id', dataClass: ArticleData::class);
$user = $relationsGet->User('user_id', avatarSize: 96, dataClass: ProjectUser::class);
$term = $relationsGet->Term('term_id', taxonomy: 'category', dataClass: ProjectTerm::class);
$comment = $relationsGet->Comment('comment_id', avatarSize: 96, dataClass: ProjectComment::class);
```

4種類とも末尾のdataClassにclass-string<T>を指定し、T|nullを返します。省略時は標準Dataです。既存の位置引数、IDの解釈、Userのget_user_by経由の取得、Termのtaxonomy引数を維持します。クラスはメタ値・IDの読み取り前に検証し、未知・別系統・abstractはInvalidArgumentExceptionです。欠損はnull、nullableなFromのnullもそのまま返し、生成例外は伝播します。

FromTree・Nested・NestedPath・NestedList・Transformなど、MetaGet型を維持するReaderでも同じAPIを使用できます。NestedArray・NestedObject等はCore Readerを返すため対象外です。WpPost等のネイティブ取得、PostMeta等のメタReader取得は変更していません。

`WithModes()`・`WithRules()`は、同じ取得元を保持した新しい取得口を返します。元の`MetaGet`の判定基準は変更しません。渡した`ValueRules`は生成時にcloneされ、同じ規則は`Nested()`後と再進入先の`MetaGet`にも継承されます。

Coreの各Getterと同様に、`MetaGet`の最終的な値を返すGetterにも省略可能な`predicate`を指定できます。predicateにはWordPress向けの`ValueRules`で正規化できた値だけが渡され、単数Getterで条件に合わなければ既定値、List系Getterでは条件に合う要素だけを返します。欠損したメタ値ではpredicateを実行しません。

```php
$positiveCount = $postMetaGet->Int(
	'view_count',
	predicate: static fn(int $value): bool => $value > 0,
);

$publishedStatuses = $postMetaGet->BackedEnumList(
	'statuses',
	Status::class,
	predicate: static fn(Status $value): bool => $value === Status::Published,
);
```

## 投稿の本文とターム

`PostData::RawContent`は未加工の`post_content`です。`HasContent()`は、この値に空白以外の文字があるかを判定します。描画用HTMLが必要な場合は`GetRenderedContent()`を使用します。

```php
use WpTypeSafety\Wp\Get\PostGet;

$postData = \WpTypeSafety\Wp\Data\PostData::From($post);
$rawContent = $postData->RawContent;
echo $postData->GetRenderedContent();
$terms = $postData->GetTerms('category');
```

GetRenderedContent(): stringは、内部Adapterでget_the_content()・the_contentフィルター・]]>の置換を行い、保持したRawContentから描画用HTMLを返します。出力・キャッシュは行いません。現在のIdに対応する投稿が取得できない場合は空文字です。getPropsで値を調整したDataでも標準処理を実行します。

現在のWordPress投稿を複製し、本文・タイトル・抜粋・著者ID・親ID・状態・投稿タイプ・スラッグ・保持した日時をDataの値へ置き換えます。パスワードなどDataが保持しない描画用情報は現在の投稿を使います。完全なWordPress状態のスナップショットではありません。投稿用グローバル変数は一時的に切り替え、正常終了・例外時とも復元します。WordPressのクエリ初期化後に利用してください。setup_postdata()失敗はLogicException、不正なフィルター戻り値はUnexpectedValueExceptionです。

これは任意の投稿に対してメインクエリ全体を再現するAPIではありません。`is_singular()`等のクエリ条件・閲覧権限は変更せず、`the_post`や描画フィルターが起こす任意の副作用も巻き戻しません。パスワード保護・ページ分割等はWordPressの本文取得処理に従います。通常のテンプレートと同じく、本文描画時に登録されるブロックのCSS等の配信タイミングにも注意してください。生成HTMLの一括エスケープはせず、出力するかの判断はテンプレートで行います。

`GetTerms(string $taxonomy, string $dataClass = TermData::class): array`はTermGet::FromPostと同じ内部処理でlist<T>を返します。毎回取得し、無効なId・空タクソノミー・タームなし・取得エラー時は空配列です。生成する派生クラスを指定できます。nullableなFromの結果は除外します。

取得処理は内部Adapterへ委譲します。動作を変更する場合はメソッドをオーバーライドします。戻り値がlist<TermData>でなければUnexpectedValueExceptionです。HTML出力用のRenderContent()は設けていません。

## WordPressのターム

単一タームをタクソノミー名とスラッグで指定する場合は`TermGet::BySlug()`を使用します。

```php
use WpTypeSafety\Wp\Get\TermGet;

$term = TermGet::BySlug(
	taxonomy: 'category',
	slug: 'knowledge',
);

$name = $term?->Name ?? '';
$url = $term?->Url ?? '';
```

戻り値はTermData|nullです。投稿0件でも取得し、空指定・未登録・該当なし・変換失敗はnullです。第3引数dataClassに派生クラスを指定できます。未知・別系統・abstractクラスは検索前にInvalidArgumentException、生成例外はそのまま伝播します。タクソノミー登録後に呼び出してください。

`TermGet`は投稿のタームを`TermData`のリストで返します。`Roots()`・`Deepest()`・`Shallowest()`はターム階層を調べます。取得失敗や該当なしは空配列です。

```php
<?php
use WpTypeSafety\Wp\Get\TermGet;
use WpTypeSafety\Wp\Data\TermData;

$terms = TermGet::FromPost($postId, 'category');
$roots = TermGet::Roots($terms, dataClass: TermData::class);
$children = $roots !== [] ? $roots[0]->GetChildren() : [];
```

`GetChildren()`は呼び出し時にWordPressから直下の子を取得します。

`TermGet::FromPost()`は`WP_Post | PostData | int`を受け取ります。`ArticlePostData`などの派生DTOも渡せます。オブジェクトから投稿IDを取り出して取得し、DTOの内容を更新したり作り直したりはしません。

案件固有の派生`TermData`を使用する場合は、`dataClass:`を指定します。`Deepest()`と`Shallowest()`は入力されたDataと同じインスタンスを選別するため派生型を維持します。`Roots()`だけは`dataClass`が必須です。親なしも含めて現在のWordPress階層からルートを取得し、指定クラスで新しく生成します。入力型と生成型は別でよく、結果は指定型の`list<T>`だけになります。入力のインスタンスや編集済みPropsは引き継ぎません。

```php
<?php
$termClass = AreaData::class;
$areas = TermGet::FromPost(
	$post,
	'area',
	dataClass: $termClass,
);
$roots = TermGet::Roots(
	$areas,
	dataClass: $termClass,
);
$deepest = TermGet::Deepest($areas);
```

`Roots()`は指定クラスのnullableな`From()`が`null`を返した要素を除外します。生成中の例外はそのまま伝播します。

`Deepest()`・`Shallowest()`は各タームの祖先数を深さとして比較します。同じ深さのタームが複数ある場合はすべて入力順で返し、空入力は空配列です。`Roots()`は各タームの最上位祖先を取得し、同じタクソノミーとIDの重複を除きます。取得・変換失敗、参照切れなどでルートを解決できない要素は除外し、入力Dataへフォールバックしません。生成例外は伝播します。

## WordPressメニュー

クラシックメニューを取得する `MenuGet`、セットアップ済み項目を変換する `MenuNodeData::From`、取得済みツリーを探索する `MenuData` に分けています。Navigationブロックは対象外です。

```php
<?php
use WpTypeSafety\Wp\Get\MenuGet;
use WpTypeSafety\Wp\Data\MenuData;

$menu = MenuGet::ByLocation('primary');
$byId = MenuGet::ById(12);
$bySlug = MenuGet::BySlug('footer-menu');

if ($menu !== null) {
	echo esc_html($menu->Name);
	foreach ($menu->Nodes as $node) {
		echo esc_html($node->Title);
	}
	$current = $menu->FindCurrent();
	$pageNode = $menu->FindByPageId(123);
	$path = $menu->PathToId(456);
}
```

`BySlug()` はslugのみで検索し、名前やIDへフォールバックしません。`ByLocation()` は登録済みかつ割当済みの位置を対象とします。不正・未発見・項目取得失敗は `null`、存在する空メニューは `Nodes = []` の `MenuData` を返します。

`MenuData::From(WP_Term)`はnav_menuの定義と関連項目を取得し、ツリーを構築します。純粋なメモリ内変換ではありません。定義はId・Name・Slug・Description、ルート項目はNodesです。

### 案件固有のノード

MenuDataのサブクラスがノードクラスを決めます。取得口にはメニューDataのクラスを渡します。

```php
use WpTypeSafety\Wp\Data\MenuData;
use WpTypeSafety\Wp\Data\MenuNodeData;

/** @extends MenuNodeData<ProjectNode> */
class ProjectNode extends MenuNodeData {}
/** @extends MenuData<ProjectNode> */
class ProjectMenu extends MenuData
{
	protected static function getNodeClass(): string
	{
		return ProjectNode::class;
	}
}
$menu = MenuGet::ByLocation('primary', dataClass: ProjectMenu::class);
```

MenuData<TNode>とMenuNodeData<TChild>の型指定を実装と揃えてください。子から親の順でFromを呼びます。getPropsがnullを返したノードは子孫ごと除外し、子をルートへ昇格しません。ノードクラスも取得前に検証し、生成例外は伝播します。

MenuNodeData::Fromはnav_menu_item以外や未セットアップの項目ではnullです。wp_setup_nav_menu_itemによる暗黙の補完はしません。

### 取得済みツリーの探索

| メソッド | 条件 | 該当なし |
|---|---|---|
| `FindCurrent()` | 取得時点のWP判定で `IsCurrent = true` | `null` |
| `FindById($menuItemId)` | メニュー項目自身のID | `null` |
| `FindByPageId($pageId)` | 固定ページを参照する項目 | `null` |
| `FindByObject($type, $object, $objectId)` | リンク先の種別・名前・ID | `null` |
| `FindByUrl($url)` | URL完全一致 | `null` |
| `PathToId($menuItemId)` | 項目IDまでのパス | `[]` |
| `PathToObject($type, $object, $objectId)` | リンク先までのパス | `[]` |
| `PathToUrl($url)` | URLまでのパス | `[]` |

すべて保持済みのツリーを深さ優先で探索し、複数一致なら最初の1件を返します。パスにはルートと対象自身を含みます。探索時にWordPressへ再問い合わせしません。URLの末尾スラッシュ・クエリ・フラグメント等の自動正規化は行いません。

### 現在位置の意味とスナップショット

`FindCurrent()`は「現在のページから所属メニューを特定するAPI」ではなく、「取得したメニュー内に現在のページを直接指す項目があるか」を探索します。

メニュー取得時にWordPressの `_wp_menu_item_classes_by_context()` で現在位置を判定し、`MenuNodeData::IsCurrent` に保持します。クエリ確定後に取得してください。クエリ未設定時は現在項目なしです。祖先しか存在しない場合は祖先へフォールバックせず `null` を返します。単独の `MenuNodeData::From()` は渡された項目の `current` をコピーするだけで、現在位置を計算しません。

取得後にWPコンテキストが変わっても既存Dataの判定は変わりません。新しい状態が必要なら `MenuGet` で再取得します。ライブラリ独自の静的ツリーキャッシュは持たず、各取得呼び出しで新しいスナップショットを作ります。繰り返し使う場合は取得済みの `MenuData` を渡してください。

メニュー全体は`MenuGet::ByLocation()`・`ById()`・`BySlug()`で取得し、`FindBy*()`・`PathTo*()`は取得した`MenuData`から呼び出します。

## WordPressのコメント

`CommentData`は匿名コメントと登録ユーザーのコメントを同じ型で表します。`AuthorName`・`WebsiteUrl`（コメント投稿者のWebサイト）はどちらでも参照でき、`AuthorUserId`が0より大きい場合だけ`HasUser()`がtrueになります。`GetUser()`は対応する`WpUserData`を返し、匿名コメント・ユーザー取得失敗では`null`です。投稿者メールアドレスは公開プロパティに保持しません。

```php
<?php
$commentData = CommentData::From($comment);

echo esc_html($commentData->AuthorName);
$user = $commentData->GetUser();
$avatar = $commentData->GetAvatar(64);
```

`GetPost()`・`GetParent()`・`GetChildren()`は関連する投稿・親コメント・直下の子コメントを呼び出し時に取得します。`GetChildren()`の第1引数はWordPressのコメントクエリへ渡す状態（既定値`approve`）、第2引数は並び順（既定値`ASC`）です。

案件固有DataはCommentDataのサブクラスでgetProps・initializeを上書きし、Subclass::From($comment, $avatarSize)またはCommentGetのdataClassで生成します。親・子コメントは`createRelatedComment(WP_Comment): ?CommentData`、ユーザーと投稿は`GetUser()`・`GetPost()`を上書きします。

GetAvatar()は現在のコメントを複製し、保持済み著者情報を反映して取得します。ゲストのメールアドレス等は現在のコメントを使い、削除済みならnullです。サイズ省略時はPropsのAvatarSizeまたは96、最小1です。詳細は[Dataと派生クラス](data.md)を参照してください。

## 投稿タイプ・タクソノミーの登録定義

`PostTypeGet::Current()`は、元のメインクエリの投稿・固定ページ・CPT詳細、または投稿タイプアーカイブから`PostTypeData`を取得します。通常の投稿一覧（ブログトップ）・検索・カテゴリー・ターム・著者ページは対象外で`null`です。メインクエリ未設定・登録解除済みでも`null`を返し、ループ中の投稿やクエリ条件から推測しません。`PostData::GetPostType()`が保持する投稿の種類を取得するのに対し、こちらは表示中のページを基準にします。

`PostTypeData`は投稿タイプの定義、`TaxonomyData`はタクソノミーの定義を表します。投稿1件を表す`PostData`、ターム1件を表す`TermData`とは別の型です。

```php
<?php
use WpTypeSafety\Wp\Get\PostTypeGet;
use WpTypeSafety\Wp\Data\PostTypeData;
use WpTypeSafety\Wp\Get\TaxonomyGet;
use WpTypeSafety\Wp\Data\TaxonomyData;

// 投稿タイプ・タクソノミーの登録完了後に呼び出す。
$postTypeData = PostTypeGet::ByName('cases');
$currentPostType = PostTypeGet::Current();
$taxonomyData = TaxonomyGet::ByName('area');

$archiveUrl = $postTypeData?->ArchiveUrl;
$taxonomyNames = $postTypeData?->GetTaxonomyNames() ?? [];
$isCasesTaxonomy = $taxonomyData?->HasObjectType('cases') ?? false;
```

| 検索API | 入力 | 戻り値 | ネイティブ変換 |
|---|---|---|---|
| PostTypeGet::ByName | 登録投稿タイプ名・dataClass | TPostTypeまたはnull | PostTypeData::From(WP_Post_Type) |
| TaxonomyGet::ByName | 登録タクソノミー名・dataClass | TTaxonomyまたはnull | TaxonomyData::From(WP_Taxonomy) |

`ByName()`は登録名で検索し、未登録・空文字なら`null`です。`dataClass:`で派生Dataを指定できます。不正クラスは検索前に`InvalidArgumentException`です。取得済みの定義は`Data::From()`で変換し、Propsは派生`getProps()`で調整します。登録・更新は行いません。

共通プロパティは`Name`・`Label`・`SingularLabel`・`Description`・`IsPublic`・`IsPubliclyQueryable`・`IsHierarchical`です。文字列以外の表示名・説明は空文字、欠損または空の単数形ラベルは`Label`へフォールバックします。公開設定は登録上のbool値であり、現在の閲覧者の権限や記事単位の閲覧可否を保証しません。

`PostTypeData`は追加で`HasArchive: bool`・`ArchiveUrl: string`を保持します。`HasArchive`は登録設定`has_archive`をboolへ変換した値です。URLは生成時に`get_post_type_archive_link()`から取得し、HTTP(S)絶対URL以外・取得失敗は空文字です。標準投稿`post`は`HasArchive`がfalseでも投稿一覧URLを持つ場合があるため、URLの有無は`ArchiveUrl !== ''`で判断してください。

`GetTaxonomyNames(): list<string>`は保持したNameで内部Adapterへ委譲し、get_object_taxonomies()から現在の関連名を取得します。文字列以外・空文字を除外して再採番し、空のName・不正な取得結果は[]です。変更する場合はGetTaxonomyNames()をオーバーライドします。

`TaxonomyData`は`ObjectTypes: list<string>`を生成時点のスナップショットとして保持します。取得口は不正な要素・空文字を除外して連番化します。`HasObjectType(string $postType): bool`は登録名の完全一致で判定し、WordPress APIを呼びません。タクソノミー全体の`Url`や投稿・ターム取得メソッドは提供せず、タームURLは`TermData`、一覧取得は`*Query`で扱います。

DataはPropsの値をreadonlyプロパティへコピーします。Propsの後続の変更は既存Dataへ反映されません。直接構築した値の正規化は呼び出し側の責任です。`PostData::$PostTypeName`と`TermData::$TaxonomyName`は文字列です。登録定義はそれぞれ`GetPostType()`・`GetTaxonomy()`で必要になった時点で取得できます。
