# WordPressの取得API

## 対象別の取得口

WordPressの値は、取得元と必要な戻り値に合わせて次の入口から読みます。

| 取得したいもの | 入口 | 主な操作 |
|---|---|---|
| 現在の表示対象・ループ投稿・ログインユーザー | `PostGet`・`TermGet`・`UserGet` | `Current()`・`Loop()`・`LoggedIn()` |
| 取得済みのWordPressオブジェクト・IDをDataへ変換 | 対象別の`*Get` | `From()`・`FromId()`・`Props()` |
| 元のメインクエリの投稿・ページ情報 | `MainQueryGet` | 投稿一覧・ページ番号・総ページ数・投稿総数 |
| 投稿・ターム・ユーザー・コメントの一覧 | 各`*Query` | `Get()`・`From()` |
| 投稿・ターム・ユーザー・コメントのメタ | `MetaGet` | 型付きGetter・入れ子探索 |
| メニューのツリー | `MenuNodeGet` | ツリー取得・ノード検索 |
| 投稿タイプ・タクソノミーの登録定義 | `PostTypeGet`・`TaxonomyGet` | 登録名からの取得・Dataへの変換 |

`*Get`は対象ごとの単体取得・変換、`*Query`は一覧取得、`MetaGet`はメタ値の読み取りに使用します。

## 現在の表示対象・ループ投稿・ログインユーザー

WordPressのコンテキストからDataを取得する入口です。Dataへの変換には`From()`と同じ規則を使用します。

| API | 取得元 | 対象がない場合 |
|---|---|---|
| `PostGet::Current(?Closure $postFactory = null)` | 元のメインクエリの表示対象 | 投稿でなければ`null` |
| `PostGet::Loop(?Closure $postFactory = null)` | グローバル`$post` | `WP_Post`でなければ`null` |
| `TermGet::Current(?Closure $termFactory = null)` | 元のメインクエリの表示対象 | タームでなければ`null` |
| `PostTypeGet::Current()` | 元のメインクエリの投稿詳細・投稿タイプアーカイブ | 対象外・未登録なら`null` |
| `UserGet::Current(int $avatarSize = 96, ?Closure $userFactory = null)` | 元のメインクエリの表示対象 | 有効なユーザーでなければ`null` |
| `UserGet::LoggedIn(int $avatarSize = 96, ?Closure $userFactory = null)` | `wp_get_current_user()` | 未ログインなら`null` |

`Current()`はクエリ確定後に使用します。メインクエリが存在しない場合は`null`です。`$wp_query`が独自クエリに置き換えられていても、`$wp_the_query`が指す表示対象を読みます。ターム一覧では`PostGet::Current()`は`null`で、`TermGet::Current()`が表示タームを返します。

会社案内ページ内で制作実績の独自ループを回す場合、`PostGet::Current()`は会社案内、`PostGet::Loop()`はループ中の制作実績です。`Loop()`はループの開始・進行・終了判定を行わず、呼び出し時点の`$post`を読みます。ループ外には古い値が残る場合があるため、ループ内や`setup_postdata()`で取得元を設定した場所で使用してください。

```php
use WpTypeSafety\Wp\Get\PostGet;
use WpTypeSafety\Wp\Get\UserGet;

$pageData = PostGet::Current();
$loopPostData = PostGet::Loop();
$loggedInUser = UserGet::LoggedIn(avatarSize: 64);
$archiveAuthor = UserGet::Current(avatarSize: 64);

// 案件固有Dataを返す生成クロージャーも指定できる。
$projectPost = PostGet::Current(
	postFactory: static fn (WP_Post $post): ProjectPostData =>
		new ProjectPostData(PostGet::Props($post)),
);
$projectUser = UserGet::LoggedIn(
	avatarSize: 64,
	userFactory: static fn (WP_User $user, int $avatarSize): ProjectUserData =>
		new ProjectUserData(UserGet::Props($user, $avatarSize)),
);
```

生成クロージャーの戻り値は投稿では`PostData`、タームでは`TermData`、ユーザーでは`WpUserData`またはその派生型です。PHPDocのジェネリクスで派生型を維持します。対象がない場合はクロージャーを呼びません。対象がある場合は一度だけ呼び、`null`や別の型を返すと`UnexpectedValueException`、クロージャー内部の例外はそのまま伝播します。`userFactory`にはユーザーと指定済みのアバターサイズを渡します。

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
	postFactory: CaseData::FromWpPost(...),
);
```

Factoryの引数は`WP_Post`、戻り値は`PostData`またはその派生型です。`null`は認めません。各呼び出しでDataを生成し、独自キャッシュは行いません。`CurrentPageNumber`は既存の同名メソッドと同じ規則で取得します。`no_found_rows`が有効な場合、結果の`TotalCount`・`MaxPageCount`は`null`です。`fields: ids`等のID専用クエリは`PostQuery::From()`と同じく非対応です。

| メソッド | 戻り値 | 値がない場合 |
|---|---|---|
| `Current($postFactory = null)` | `PostQueryData<TPost>` | 投稿なしは空リスト、未集計の件数はnull |
| `CurrentPageNumber()` | `int` | 未指定・不正値は1 |
| `MaxPageCount()` | `int` | 未集計・空結果は0 |
| `FoundPostCount()` | `int` | 未集計・空結果は0 |

現在番号は通常`paged`、固定フロントページでは`page`から取得します。最新投稿を表示するフロントページは`paged`です。通常の投稿・固定ページの本文を`<!--nextpage-->`で分割した番号は一覧ページ番号として扱いません。[WordPress公式の説明](https://developer.wordpress.org/reference/functions/get_query_var/)

総ページ数・投稿総数はWordPressが保持している値を返します。集計省略時の0から実際の総数を推測しません。固定フロントページ内の独自一覧の総ページ数も対象外です。独自クエリの結果とページ情報は`PostQueryData`で扱ってください。メインクエリオブジェクトが存在しない段階で呼ぶと`LogicException`です。

## WordPressの値からデータクラスを生成する

対象別の取得口はWordPressの値から共通Dataを生成します。

| 取得口 | 入力 | 戻り値 | 変換できない場合 |
|---|---|---|---|
| `PostGet::From()` | `WP_Post` | `PostData` | — |
| `PostGet::FromId()` | 投稿ID | `?PostData` | `null` |
| `CommentGet::From()` | `WP_Comment` | `CommentData` | — |
| `UserGet::From()` | `WP_User` | `?WpUserData` | `null` |
| `TermGet::From()` | `WP_Term` | `?TermData` | `null` |
| `MenuNodeGet::From()` | セットアップ済みのメニュー項目`WP_Post` | `MenuNodeData` | — |
| `AttachmentGet::ImgFromId()` | 添付ID | `?ImgData` | `null` |
| `AttachmentGet::FileFromId()` | 添付ID | `?FileData` | `null` |

`PostGet::FromId()`は`get_post()`で取得した投稿を変換し、0以下のID・存在しない投稿・`WP_Post`以外の結果では`null`を返します。コメントID・ユーザーID・タームIDからの検索は行いません。

```php
<?php
use WpTypeSafety\Wp\Get\AttachmentGet;
use WpTypeSafety\Wp\Get\CommentGet;
use WpTypeSafety\Wp\Get\MenuNodeGet;
use WpTypeSafety\Wp\Get\PostGet;
use WpTypeSafety\Wp\Get\TermGet;
use WpTypeSafety\Wp\Get\UserGet;

$postData = PostGet::From($post);
$postDataFromId = PostGet::FromId($postId); // ?PostData
$commentData = CommentGet::From($comment);
$menuNodeData = MenuNodeGet::From($menuItem);
$userData = UserGet::From($user);
$termData = TermGet::From($term);
$image = AttachmentGet::ImgFromId($attachmentId, 'medium_large');
$file = AttachmentGet::FileFromId($attachmentId);
```

`PostGet::Props()`は値専用の`PostProps`を返します。`CommentGet`・`UserGet`・`TermGet`・`MenuNodeGet`・`PostTypeGet`・`TaxonomyGet`も対応するPropsを返します。Propsは案件固有Dataを作る前に、型付きプロパティを変更できる構築用オブジェクトです。Dataへ渡した時点の値がreadonlyプロパティへコピーされます。`MenuNodeGet`は`wp_get_nav_menu_items()`等でセットアップ済みのメニュー項目`WP_Post`を対象とし、必要なら構築済みの子ノードを第2引数で受け取ります。

PostDataはprotectedなcreateRelatedPost()を上書きすると、親・子・前後投稿に案件固有の派生型を使用できます。著者はGetAuthor()を直接上書きします。PostDataのコンストラクターとPostGet::From()/FromId()からrelatedPostFactory・authorFactoryを廃止しています。TermDataはcreateRelatedTerm()、CommentDataはcreateRelatedComment()を上書きします。CommentDataのGetUser()/GetPost()も直接上書きします。構築用の関連FactoryとResolverは廃止しました。

`PostData`・`TermData`・`CommentData`・`MenuNodeData`は関連Data型をジェネリクスとして保持します（PostPropsは値だけで型パラメーターを持ちません）。そのため、案件固有Dataの`@extends`へ関連型を指定すると、子・親・前後投稿・投稿者などを取得した後もPHPStanで派生型を維持できます。

```php
<?php
/** @extends PostData<ArticleData, ProjectUserData> */
final class ArticleData extends PostData
{
	protected function createRelatedPost(\WP_Post $post): ?self
	{
		return self::FromWpPost($post);
	}

	public function GetAuthor(): ?ProjectUserData
	{
		$user = $this->AuthorId > 0 ? get_userdata($this->AuthorId) : null;
		return $user instanceof \WP_User ? ProjectUserData::FromWpUser($user) : null;
	}

	public static function FromWpPost(\WP_Post $post): self
	{
		return new self(PostGet::Props($post));
	}
}

$child = $article->GetChildren()[0]; // ArticleData
$author = $article->GetAuthor();     // ProjectUserData|null
```

生成フックを上書きしなければ関連投稿は基底PostDataです。@extendsには実際の関連型を指定してください。構築時にFactoryを渡す必要はありません。

```php
<?php
$props = PostGet::Props($post);
$props->Title = '表示用：' . $props->Title;

$postData = new PostData($props);
```

`PostData`は投稿の`Status`・`PostTypeName`・`Slug`を保持します。`IsPublished()`は投稿状態が`publish`かを返し、`IsPubliclyViewable`はWordPressの`is_post_publicly_viewable()`による投稿状態と投稿タイプを合わせた結果です。`IsPasswordProtected`はパスワード文字列が設定されているかを表しますが、パスワード文字列自体や、現在の閲覧者が認証済みかどうかは保持しません。

SVG添付画像は、ローカルファイルの単位なし・`px`の`width`と`height`を寸法として優先します。両方を取得できない場合は、`viewBox`から比率を取得します。SVGにはラスター画像のサイズバリエーションがないため、指定したサイズ名にかかわらず元のURLと寸法または比率を持つ`ImgData`を返します。どちらも判定できないSVGは`null`です。

案件固有の派生データクラスは、案件側の生成メソッドで組み立てます。上の取得口は共通Dataを返します。

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

案件固有の派生Dataを一覧へ保持する場合は、各Queryへ生成処理を指定できます。`PostQueryData`等の要素型も、指定した派生Dataとして静的解析できます。

```php
<?php
use WpTypeSafety\Wp\Get\PostQuery;

$result = PostQuery::Get(
	[
		'post_type' => 'cases',
		'posts_per_page' => 12,
	],
	postFactory: static fn (WP_Post $post): CaseData =>
		CaseData::FromWpPost($post),
);

foreach ($result->Posts as $caseData) {
	// $caseDataはCaseDataとして推論されます。
}
```

各Queryの生成処理と結果は次のとおりです。Factoryは`Get()`と`From()`のどちらでも指定できます。

| Query | Factory引数 | 結果Data | 要素プロパティ | ページ情報 |
|---|---|---|---|---|
| `PostQuery` | `postFactory` | `PostQueryData` | `Posts` | あり |
| `UserQuery` | `userFactory` | `UserQueryData` | `Users` | あり |
| `CommentQuery` | `commentFactory` | `CommentQueryData` | `Comments` | あり |
| `TermQuery` | `termFactory` | `TermQueryData` | `Terms` | なし |

ユーザーとコメントのFactoryには、第2引数として指定済みの`avatarSize`も渡します。生成処理はクエリ結果の各オブジェクトに対して対応するDataを必ず返し、`null`は返せません。Queryの絞り込みは生成処理ではなく、WordPressのクエリ引数で行ってください。Factory未指定時はWpTypeSafetyの共通Dataへ変換します。

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
$result = PostQuery::From($wpQuery);
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

$postData = PostGet::From($post);
$rawContent = $postData->RawContent;
echo $postData->GetRenderedContent();
$terms = $postData->GetTerms('category');
```

GetRenderedContent(): stringは、内部Adapterでget_the_content()・the_contentフィルター・]]>の置換を行い、保持したRawContentから描画用HTMLを返します。出力・キャッシュは行いません。現在のIdに対応する投稿が取得できない場合は空文字です。直接構築したDataでも標準処理を実行します。

現在のWordPress投稿を複製し、本文・タイトル・抜粋・著者ID・親ID・状態・投稿タイプ・スラッグ・保持した日時をDataの値へ置き換えます。パスワードなどDataが保持しない描画用情報は現在の投稿を使います。完全なWordPress状態のスナップショットではありません。投稿用グローバル変数は一時的に切り替え、正常終了・例外時とも復元します。WordPressのクエリ初期化後に利用してください。setup_postdata()失敗はLogicException、不正なフィルター戻り値はUnexpectedValueExceptionです。

これは任意の投稿に対してメインクエリ全体を再現するAPIではありません。`is_singular()`等のクエリ条件・閲覧権限は変更せず、`the_post`や描画フィルターが起こす任意の副作用も巻き戻しません。パスワード保護・ページ分割等はWordPressの本文取得処理に従います。通常のテンプレートと同じく、本文描画時に登録されるブロックのCSS等の配信タイミングにも注意してください。生成HTMLの一括エスケープはせず、出力するかの判断はテンプレートで行います。

`GetTerms(string $taxonomy, ?Closure $termFactory = null): array`は、`TermGet::FromPost()`と同じ内部処理で`list<TermData>`を返します。呼び出すたびに取得し、無効なId・空タクソノミー・タームなし・取得エラー時は空配列です。案件固有の`Closure(WP_Term): TTerm`を渡すと、その呼び出しの戻り値は`list<TTerm>`になります。クラスの型パラメーターは増やしていません。

取得処理は内部Adapterへ委譲します。動作を変更する場合はメソッドをオーバーライドします。戻り値がlist<TermData>でなければUnexpectedValueExceptionです。HTML出力用のRenderContent()は設けていません。

## WordPressのターム

`TermGet`は投稿のタームを`TermData`のリストで返します。`Roots()`・`Deepest()`・`Shallowest()`はターム階層を調べます。取得失敗や該当なしは空配列です。

```php
<?php
use WpTypeSafety\Wp\Get\TermGet;

$terms = TermGet::FromPost($postId, 'category');
$roots = TermGet::Roots($terms);
$children = $roots !== [] ? $roots[0]->GetChildren() : [];
```

`GetChildren()`は呼び出し時にWordPressから直下の子を取得します。

`TermGet::FromPost()`は`WP_Post | PostData | int`を受け取ります。`ArticlePostData`などの派生DTOも渡せます。オブジェクトから投稿IDを取り出して取得し、DTOの内容を更新したり作り直したりはしません。

案件固有の派生`TermData`を使用する場合は、`termFactory`を指定します。`Deepest()`と`Shallowest()`は入力されたDataと同じインスタンスを選別するため派生型を維持します。`Roots()`はWordPressから最上位祖先を新たに取得するため、派生型を維持する場合は同じFactoryを渡します。

```php
<?php
$termFactory = AreaData::FromWpTerm(...);
$areas = TermGet::FromPost(
	$post,
	'area',
	termFactory: $termFactory,
);
$roots = TermGet::Roots(
	$areas,
	termFactory: $termFactory,
);
$deepest = TermGet::Deepest($areas);
```

Factoryが呼ばれた場合は`TermData`の派生型を必ず返す必要があり、`null`による要素除外はできません。Factory未指定時は共通`TermData`を返します。

`Deepest()`・`Shallowest()`は各タームの祖先数を深さとして比較します。同じ深さのタームが複数ある場合はすべて入力順で返し、空入力は空配列です。`Roots()`は各タームの最上位祖先を取得し、同じタクソノミーとIDの重複を除きます。

## WordPressメニュー

```php
<?php
use WpTypeSafety\Wp\Get\MenuNodeGet;

$menu = MenuNodeGet::TreeFromLocation('primary');
```

案件固有の`MenuNodeData`をツリー全体で使用する場合は、第2引数へNode Factoryを渡します。Factoryは現在のメニュー項目と、すでに構築済みの子ノードを受け取ります。未指定時は純粋な`MenuNodeData`を返します。独自Factoryを指定したツリーは、Factoryごとの型が混ざらないよう内部キャッシュの対象外です。

```php
<?php
$menu = MenuNodeGet::TreeFromLocation(
	'primary',
	static fn (\WP_Post $item, array $children): ProjectMenuNodeData =>
		new ProjectMenuNodeData(
			MenuNodeGet::Props($item, $children),
		),
);
```

`nodeFactory`を指定した場合、戻り値はFactoryが返す派生型のリストとして推論されます。Factoryはすべての階層へ再帰的に適用され、`FindBy*()`と`PathTo*()`も渡されたツリーの派生型を維持します。

`FindById()`はメニュー項目自身のID、`FindByObject()`はリンク先の種別・オブジェクト名・オブジェクトIDを全て比較し、最初に一致したノードを深さ優先で返します。

```php
<?php
use WpTypeSafety\Wp\Data\MenuItemType;

$menuItem = MenuNodeGet::FindById($menu, 20);
$postNode = MenuNodeGet::FindByObject(
	nodes: $menu,
	type: MenuItemType::PostType,
	object: 'post',
	objectId: $post->ID,
);
```

`FindByPageId()`は、`TreeFromLocation()`で得たツリーを子階層まで検索し、指定した固定ページIDに対応する最初のノードを返します。派生型のツリーを渡した場合はその派生型として推論され、該当なし・0以下のIDは`null`です。

```php
<?php
$currentNode = MenuNodeGet::FindByPageId($menu, $post->ID);
```

`FindByUrl()`は、URLが完全一致する最初のノードを深さ優先で返します。カスタムリンクや外部URLも検索できます。空文字・該当なしは`null`です。末尾スラッシュ、クエリ、フラグメント等は自動で正規化しません。

```php
<?php
$customNode = MenuNodeGet::FindByUrl($menu, '/company/access/');
```

`PathToUrl()`は、URLが一致するノードまで探索する間に通ったノードを、ルートから対象自身までの順で返します。対象がルートなら1件、空文字・該当なしは空配列です。

```php
<?php
$path = MenuNodeGet::PathToUrl($menu, '/company/access/');
```

`PathToId()`・`PathToObject()`も同様に、メニュー項目IDまたはリンク先オブジェクトが一致するノードまでのパスを返します。IDやオブジェクトで対象を特定できないカスタムリンクには、URL用の取得口を使用します。

## WordPressのコメント

`CommentData`は匿名コメントと登録ユーザーのコメントを同じ型で表します。`AuthorName`・`WebsiteUrl`（コメント投稿者のWebサイト）はどちらでも参照でき、`AuthorUserId`が0より大きい場合だけ`HasUser()`がtrueになります。`GetUser()`は対応する`WpUserData`を返し、匿名コメント・ユーザー取得失敗では`null`です。投稿者メールアドレスは公開プロパティに保持しません。

```php
<?php
$commentData = CommentGet::From($comment);

echo esc_html($commentData->AuthorName);
$user = $commentData->GetUser();
$avatar = $commentData->GetAvatar(64);
```

`GetPost()`・`GetParent()`・`GetChildren()`は関連する投稿・親コメント・直下の子コメントを呼び出し時に取得します。`GetChildren()`の第1引数はWordPressのコメントクエリへ渡す状態（既定値`approve`）、第2引数は並び順（既定値`ASC`）です。

案件固有DataはCommentGet::Props($comment, $avatarSize)から構築します。親・子コメントはprotected createRelatedComment(WP_Comment): ?CommentData、ユーザーと投稿はGetUser()/GetPost()を直接上書きします。relatedCommentFactory・userFactory・postFactoryを構築時に渡す方式は廃止しました。

GetAvatar()は現在のコメントを複製し、保持済み著者情報を反映して取得します。ゲストのメールアドレス等は現在のコメントを使い、削除済みならnullです。サイズ省略時はPropsのAvatarSizeまたは96、最小1です。詳細は[Dataと派生クラス](data.md)を参照してください。

## 投稿タイプ・タクソノミーの登録定義

`PostTypeGet::Current()`は、元のメインクエリの投稿・固定ページ・CPT詳細、または投稿タイプアーカイブから`PostTypeData`を取得します。通常の投稿一覧（ブログトップ）・検索・カテゴリー・ターム・著者ページは対象外で`null`です。メインクエリ未設定・登録解除済みでも`null`を返し、ループ中の投稿やクエリ条件から推測しません。`PostData::GetPostType()`が保持する投稿の種類を取得するのに対し、こちらは表示中のページを基準にします。

`PostTypeData`は投稿タイプの定義、`TaxonomyData`はタクソノミーの定義を表します。投稿1件を表す`PostData`、ターム1件を表す`TermData`とは別の型です。

```php
<?php
use WpTypeSafety\Wp\Get\PostTypeGet;
use WpTypeSafety\Wp\Get\TaxonomyGet;

// 投稿タイプ・タクソノミーの登録完了後に呼び出す。
$postTypeData = PostTypeGet::FromName('cases');
$currentPostType = PostTypeGet::Current();
$taxonomyData = TaxonomyGet::FromName('area');

$archiveUrl = $postTypeData?->ArchiveUrl;
$taxonomyNames = $postTypeData?->GetTaxonomyNames() ?? [];
$isCasesTaxonomy = $taxonomyData?->HasObjectType('cases') ?? false;
```

| 取得口 | `From()`の入力・戻り値 | `Props()`の戻り値 | `FromName()`の戻り値 |
|---|---|---|---|
| `PostTypeGet` | `WP_Post_Type` → `PostTypeData` | `PostTypeProps` | `PostTypeData|null` |
| `TaxonomyGet` | `WP_Taxonomy` → `TaxonomyData` | `TaxonomyProps` | `TaxonomyData|null` |

`FromName()`は登録名を検索し、未登録・空文字では`null`を返します。`From()`と`Props()`は取得済みのWordPressオブジェクトを受け取ります。`Props()`で調整してから派生Dataへ渡すこともできます。これらの取得口は登録・更新を行いません。

共通プロパティは`Name`・`Label`・`SingularLabel`・`Description`・`IsPublic`・`IsPubliclyQueryable`・`IsHierarchical`です。文字列以外の表示名・説明は空文字、欠損または空の単数形ラベルは`Label`へフォールバックします。公開設定は登録上のbool値であり、現在の閲覧者の権限や記事単位の閲覧可否を保証しません。

`PostTypeData`は追加で`HasArchive: bool`・`ArchiveUrl: string`を保持します。`HasArchive`は登録設定`has_archive`をboolへ変換した値です。URLは生成時に`get_post_type_archive_link()`から取得し、HTTP(S)絶対URL以外・取得失敗は空文字です。標準投稿`post`は`HasArchive`がfalseでも投稿一覧URLを持つ場合があるため、URLの有無は`ArchiveUrl !== ''`で判断してください。

`GetTaxonomyNames(): list<string>`は保持したNameで内部Adapterへ委譲し、get_object_taxonomies()から現在の関連名を取得します。文字列以外・空文字を除外して再採番し、空のName・不正な取得結果は[]です。変更する場合はGetTaxonomyNames()をオーバーライドします。

`TaxonomyData`は`ObjectTypes: list<string>`を生成時点のスナップショットとして保持します。取得口は不正な要素・空文字を除外して連番化します。`HasObjectType(string $postType): bool`は登録名の完全一致で判定し、WordPress APIを呼びません。タクソノミー全体の`Url`や投稿・ターム取得メソッドは提供せず、タームURLは`TermData`、一覧取得は`*Query`で扱います。

DataはPropsの値をreadonlyプロパティへコピーします。Propsの後続の変更は既存Dataへ反映されません。直接構築した値の正規化は呼び出し側の責任です。`PostData::$PostTypeName`と`TermData::$TaxonomyName`は文字列です。登録定義はそれぞれ`GetPostType()`・`GetTaxonomy()`で必要になった時点で取得できます。
