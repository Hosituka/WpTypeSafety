# WordPressの取得API

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

## WordPressのターム

`WpGetTerm`は投稿のタームを`TermData`のリストで返します。`Roots()`・`Deepest()`・`Shallowest()`はターム階層を調べます。取得失敗や該当なしは空配列です。

```php
<?php
use WpTypeSafety\Wp\Get\WpGetTerm;

$terms = WpGetTerm::FromPost($postId, 'category');
$roots = WpGetTerm::Roots($terms);
$children = $roots !== [] ? $roots[0]->GetChildren() : [];
```

`GetChildren()`は呼び出し時にWordPressから直下の子を取得します。

`WpGetTerm::FromPost()`は`WP_Post | PostData | int`を受け取ります。`ArticlePostData`などの派生DTOも渡せます。オブジェクトから投稿IDを取り出して取得し、DTOの内容を更新したり作り直したりはしません。

案件固有の派生`TermData`を使用する場合は、`termFactory`を指定します。`Deepest()`と`Shallowest()`は入力されたDataと同じインスタンスを選別するため派生型を維持します。`Roots()`はWordPressから最上位祖先を新たに取得するため、派生型を維持する場合は同じFactoryを渡します。

```php
<?php
$termFactory = AreaData::FromWpTerm(...);
$areas = WpGetTerm::FromPost(
	$post,
	'area',
	termFactory: $termFactory,
);
$roots = WpGetTerm::Roots(
	$areas,
	termFactory: $termFactory,
);
$deepest = WpGetTerm::Deepest($areas);
```

Factoryが呼ばれた場合は`TermData`の派生型を必ず返す必要があり、`null`による要素除外はできません。Factory未指定時は共通`TermData`を返します。

## WordPressメニュー

```php
<?php
use WpTypeSafety\Wp\Get\WpGetMenuNode;

$menu = WpGetMenuNode::TreeFromLocation('primary');
```

案件固有の`MenuNodeData`をツリー全体で使用する場合は、第2引数へNode Factoryを渡します。Factoryは現在のメニュー項目と、すでに構築済みの子ノードを受け取ります。未指定時は純粋な`MenuNodeData`を返します。独自Factoryを指定したツリーは、Factoryごとの型が混ざらないよう内部キャッシュの対象外です。

```php
<?php
$menu = WpGetMenuNode::TreeFromLocation(
	'primary',
	static fn (\WP_Post $item, array $children): ProjectMenuNodeData =>
		new ProjectMenuNodeData(
			MenuNodeFactory::Members($item, $children),
		),
);
```

`nodeFactory`を指定した場合、戻り値はFactoryが返す派生型のリストとして推論されます。Factoryはすべての階層へ再帰的に適用され、`FindBy*()`と`PathTo*()`も渡されたツリーの派生型を維持します。

`FindById()`はメニュー項目自身のID、`FindByObject()`はリンク先の種別・オブジェクト名・オブジェクトIDを全て比較し、最初に一致したノードを深さ優先で返します。

```php
<?php
use WpTypeSafety\Wp\Data\MenuItemType;

$menuItem = WpGetMenuNode::FindById($menu, 20);
$postNode = WpGetMenuNode::FindByObject(
	nodes: $menu,
	type: MenuItemType::PostType,
	object: 'post',
	objectId: $post->ID,
);
```

`FindByPageId()`は、`TreeFromLocation()`で得たツリーを子階層まで検索し、指定した固定ページIDに対応する最初のノードを返します。派生型のツリーを渡した場合はその派生型として推論され、該当なし・0以下のIDは`null`です。

```php
<?php
$currentNode = WpGetMenuNode::FindByPageId($menu, $post->ID);
```

`FindByUrl()`は、URLが完全一致する最初のノードを深さ優先で返します。カスタムリンクや外部URLも検索できます。空文字・該当なしは`null`です。末尾スラッシュ、クエリ、フラグメント等は自動で正規化しません。

```php
<?php
$customNode = WpGetMenuNode::FindByUrl($menu, '/company/access/');
```

`PathToUrl()`は、URLが一致するノードまで探索する間に通ったノードを、ルートから対象自身までの順で返します。対象がルートなら1件、空文字・該当なしは空配列です。

```php
<?php
$path = WpGetMenuNode::PathToUrl($menu, '/company/access/');
```

`PathToId()`・`PathToObject()`も同様に、メニュー項目IDまたはリンク先オブジェクトが一致するノードまでのパスを返します。IDやオブジェクトで対象を特定できないカスタムリンクには、URL用の取得口を使用します。

## その他の公開取得口

| 取得口 | 戻り値 | 契約 |
|---|---|---|
| `WpGet::CurrentPageNumber()` | `int` | メインクエリの`paged`を取得し、未設定・0以下は1 |
| `WpGetMenuNode::FindById()` | `TNode|null` | メニュー項目IDに対応するノードをツリーから再帰検索 |
| `WpGetMenuNode::FindByObject()` | `TNode|null` | Type・Object・ObjectIdが一致するノードを再帰検索 |
| `WpGetMenuNode::FindByPageId()` | `TNode|null` | 固定ページIDに対応するノードをツリーから再帰検索 |
| `WpGetMenuNode::FindByUrl()` | `TNode|null` | URLが完全一致する最初のノードをツリーから再帰検索 |
| `WpGetMenuNode::PathToId()` | `list<TNode>` | メニュー項目IDが一致するノードまでのパスを取得 |
| `WpGetMenuNode::PathToObject()` | `list<TNode>` | Type・Object・ObjectIdが一致するノードまでのパスを取得 |
| `WpGetMenuNode::PathToUrl()` | `list<TNode>` | URLが完全一致するノードまでのパスをルートから順に取得 |
| `WpGetTerm::Deepest()` | `list<TermData>` | 最も深い階層のタームをすべて返す |
| `WpGetTerm::Shallowest()` | `list<TermData>` | 最も浅い階層のタームをすべて返す |

`Deepest()`・`Shallowest()`は各タームの祖先数を深さとして比較します。同じ深さのタームが複数ある場合はすべて入力順で返し、空入力は空配列です。`Roots()`は各タームの最上位祖先を取得し、同じタクソノミーとIDの重複を除きます。

## WordPressの値からデータクラスを生成する

対象別FactoryはWordPressの値から共通Dataを生成します。

| Factory | 入力 | 戻り値 | 変換できない場合 |
|---|---|---|---|
| `PostFactory::From()` | `WP_Post` | `PostData` | — |
| `PostFactory::FromId()` | 投稿ID | `?PostData` | `null` |
| `CommentFactory::From()` | `WP_Comment` | `CommentData` | — |
| `UserFactory::From()` | `WP_User` | `?WpUserData` | `null` |
| `TermFactory::From()` | `WP_Term` | `?TermData` | `null` |
| `MenuNodeFactory::From()` | セットアップ済みのメニュー項目`WP_Post` | `MenuNodeData` | — |
| `AttachmentFactory::ImgFromId()` | 添付ID | `?ImgData` | `null` |
| `AttachmentFactory::FileFromId()` | 添付ID | `?FileData` | `null` |

`PostFactory::FromId()`は`get_post()`で取得した投稿を変換し、0以下のID・存在しない投稿・`WP_Post`以外の結果では`null`を返します。コメントID・ユーザーID・タームIDからの検索は行いません。

```php
<?php
use WpTypeSafety\Wp\Get\AttachmentFactory;
use WpTypeSafety\Wp\Get\CommentFactory;
use WpTypeSafety\Wp\Get\MenuNodeFactory;
use WpTypeSafety\Wp\Get\PostFactory;
use WpTypeSafety\Wp\Get\TermFactory;
use WpTypeSafety\Wp\Get\UserFactory;

$postData = PostFactory::From($post);
$postDataFromId = PostFactory::FromId($postId); // ?PostData
$commentData = CommentFactory::From($comment);
$menuNodeData = MenuNodeFactory::From($menuItem);
$userData = UserFactory::From($user);
$termData = TermFactory::From($term);
$image = AttachmentFactory::ImgFromId($attachmentId, 'medium_large');
$file = AttachmentFactory::FileFromId($attachmentId);
```

`PostFactory`・`CommentFactory`・`UserFactory`・`TermFactory`・`MenuNodeFactory`の`Members()`は、対応する`PostMembers`・`CommentMembers`・`UserMembers`・`TermMembers`・`MenuNodeMembers`を返します。Membersは案件固有Dataを作る前に、型付きプロパティを変更できる構築用オブジェクトです。Dataへ渡した時点の値がreadonlyプロパティへコピーされます。`MenuNodeFactory`は`wp_get_nav_menu_items()`等でセットアップ済みのメニュー項目`WP_Post`を対象とし、必要なら構築済みの子ノードを第2引数で受け取ります。

`PostFactory`・`CommentFactory`・`TermFactory`では、任意の`relatedPostFactory`・`relatedCommentFactory`・`relatedTermFactory`を指定すると自己参照する関連Dataにも案件固有の派生型を使用できます。未指定時は共通Dataを返すため、派生型を必要としない取得へ明示的に戻せます。

`PostData`・`TermData`・`CommentData`・`MenuNodeData`と対応するMembersは関連Data型をジェネリクスとして保持します。そのため、案件固有Dataの`@extends`へ関連型を指定すると、子・親・前後投稿・投稿者などを取得した後もPHPStanで派生型を維持できます。

```php
<?php
/** @extends PostData<ArticleData, ProjectUserData> */
final readonly class ArticleData extends PostData
{
	/** @param PostMembers<ArticleData, ProjectUserData> $members */
	public function __construct(PostMembers $members)
	{
		parent::__construct($members);
	}
}

$child = $article->GetChildren()[0]; // ArticleData
$author = $article->GetAuthor();     // ProjectUserData|null
```

関連Factoryを指定せず基底Dataを返す派生Dataも作る場合は、案件固有Data自体をテンプレート化し、コンストラクターへ渡すMembersの型引数と対応させます。

```php
<?php
$members = PostFactory::Members($post);
$members->Title = '表示用：' . $members->Title;

$postData = new PostData($members);
```

`PostData`は投稿の`Status`・`PostType`・`Slug`を保持します。`IsPublished()`は投稿状態が`publish`かを返し、`IsPubliclyViewable`はWordPressの`is_post_publicly_viewable()`による投稿状態と投稿タイプを合わせた結果です。`IsPasswordProtected`はパスワード文字列が設定されているかを表しますが、パスワード文字列自体や、現在の閲覧者が認証済みかどうかは保持しません。

SVG添付画像は、ローカルファイルの単位なし・`px`の`width`と`height`を寸法として優先します。両方を取得できない場合は、`viewBox`から比率を取得します。SVGにはラスター画像のサイズバリエーションがないため、指定したサイズ名にかかわらず元のURLと寸法または比率を持つ`ImgData`を返します。どちらも判定できないSVGは`null`です。

案件固有の派生データクラスは、案件側の生成メソッドで組み立てます。このFactoryは共通データクラスを返します。

## WordPressのコメント

`CommentData`は匿名コメントと登録ユーザーのコメントを同じ型で表します。`AuthorName`・`AuthorUrl`はどちらでも参照でき、`AuthorUserId`が0より大きい場合だけ`HasUser()`がtrueになります。`GetUser()`は対応する`WpUserData`を返し、匿名コメント・ユーザー取得失敗では`null`です。投稿者メールアドレスは公開プロパティに保持しません。

```php
<?php
$commentData = CommentFactory::From($comment);

echo esc_html($commentData->AuthorName);
$user = $commentData->GetUser();
$avatar = $commentData->GetAvatar(64);
```

`GetPost()`・`GetParent()`・`GetChildren()`は関連する投稿・親コメント・直下の子コメントを呼び出し時に取得します。`GetChildren()`の第1引数はWordPressのコメントクエリへ渡す状態（既定値`approve`）、第2引数は並び順（既定値`ASC`）です。

案件固有Dataを関連先にも使用する場合は、Factoryへ生成処理を指定します。`relatedCommentFactory`は親・子コメント、`userFactory`は`GetUser()`、`postFactory`は`GetPost()`へ適用されます。未指定時は共通Dataを返します。

```php
<?php
$commentMembers = CommentFactory::Members(
	$comment,
	relatedCommentFactory: static fn (\WP_Comment $related): ?ProjectCommentData =>
		ProjectCommentData::FromWpComment($related),
	userFactory: static fn (\WP_User $user): ?AuthorProfileData =>
		AuthorProfileData::FromWpUser($user),
	postFactory: static fn (\WP_Post $post): ?ArticlePostData =>
		ArticlePostData::FromWpPost($post),
);
```

`Status`はWordPressの`comment_approved`を正規化し、`1`は`approved`、`0`は`unapproved`になります。`spam`・`trash`など、それ以外の値は維持します。`Type`は旧データの空文字を`comment`へ正規化します。`CreatedAt`は`comment_date`をサイトのタイムゾーンで解釈した`?DateTimeImmutable`です。欠損・不正な日時は`null`になります。
