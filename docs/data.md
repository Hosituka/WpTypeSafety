# Dataと派生クラス

## データクラスと取得契約

このライブラリの`Data`は、純粋な値だけを保持するDTOに限定しません。利用側のコードで、ひとまとまりとして扱う基本単位を表します。正規化済みの値を保持するデータクラスだけでなく、注入済みのResolverを通じて関連データを取得するクラスや、その単位を操作するための`Get`を構成要素として保持するクラスも含みます。

`Get`は、外部から得た値の取得・型確認・正規化を担当し、用途に対応する`Data`またはPHPの固定型を返します。`Data`自身がWordPress・ACFのグローバル関数を直接呼ぶことは原則として避けますが、概念を一単位として扱うために、注入済みのResolverや`Get`を保持することは許容します。

たとえば`FlexibleNode`は、フレキシブルコンテンツの1レイアウトを表す基本単位です。レイアウト名を`Layout`、その行のフィールドを型付きで取得する入口を`Get`として保持します。

```php
<?php
final readonly class FlexibleNode
{
	public function __construct(
		public string $Layout,
		public AcfGet $Get,
	) {}
}
```

この構成では、`FlexibleNode`を単なる転送用DTOとはみなしません。`Data`が扱う単位を表し、`Get`がその単位から安全に値を取得する、という役割分担です。

`WpUserData->Description` はWP標準の「プロフィール情報」（自己紹介文）です。Factoryが生成時に `WP_User->description` から取得します。未設定・文字列以外は空文字です。直接構築時は`UserMembers`の`Description:`で指定でき、省略時は空文字です。HTML出力やエスケープは利用側で行い、Dataは値をそのまま保持します。

`WpUserData->AuthorUrl` は投稿者一覧のURLです。Factoryが生成時に `get_author_posts_url()` で取得し、HTTP(S)の有効なURLならDataに保持します。不正なURL・未設定・文字列以外は空文字です。Data内ではWP APIを呼びません。直接構築する場合は`UserMembers`の`AuthorUrl:`で指定でき、省略時は空文字です。コンストラクターはURL形式を検証しないため、直接渡す値は呼び出し元で確認してください。HTML出力時は利用側で `esc_url()` を適用してください。

`Core/Data/`には`ImgData`・`LinkData`・`ChoiceData`・`FileData`・`TimeData`を置きます。`TimeData`は日付・タイムゾーンを含まない時刻を`Hour`・`Minute`・`Second`として保持し、`Format()`で文字列化できます。WordPress固有の`PostData`・`CommentData`・`TermData`・`WpUserData`・`MenuNodeData`・`MenuItemType`は`Wp/Data/`へ置き、名前空間は`WpTypeSafety\Wp\Data`です。いずれも値をコンストラクターで受け取ります。`PostData`・`CommentData`・`TermData`・`WpUserData`・`MenuNodeData`は値に加え、案件側が渡す取得関数を呼ぶメソッドを持ちます。分類は扱う概念によるもので、これらのデータクラス自身がWordPressの関数を直接呼ぶわけではありません。

URLの形式、IDや寸法の正負、配列要素の型など、PHPの型宣言では表せない条件はコンストラクターで検証しません。呼び出し元が確認してください。

`ImgData`は幅と高さ、またはHTMLの寸法属性に使える縦横比が判明している画像を表し、`Width`・`Height`には整数を必ず渡します。ラスター画像ではピクセル寸法、SVG添付画像では単位なし・`px`の`width`と`height`を優先し、両方を取得できない場合は`viewBox`から得た整数の比率を保持します。`viewBox`由来の値は実ピクセル寸法ではなく、ブラウザーが表示領域の比率を事前に確保するための組です。ACF画像フィールドをURL返却形式にした場合は、画像URLを`?string`で扱う`AcfGet->ImgUrl()`を使います。URLだけの値を`Img()`へ渡すと`null`になります。

`FileData`は添付ファイルのIDとファイルサイズが判明しているファイルを表し、`Id`・`FileSize`には整数を必ず渡します。ACFファイルフィールドがURL返却形式の場合は`AcfGet->FileUrl()`を使います。URLだけの値やID・サイズが欠けた値を`File()`へ渡すと`null`になります。

```php
<?php
use WpTypeSafety\Core\Data\ImgData;

$image = new ImgData(
	Url: $url,
	Width: $width,
	Height: $height,
	Alt: $alt,
);
```

`PostData`の`GetAuthor()`、`GetExcerpt()`、`HasExcerpt()`、`GetThumbnail()`、`GetChildren()`、`GetPreviousPost()`、`GetNextPost()`、`CommentData`の`GetUser()`、`GetAvatar()`、`GetPost()`、`GetParent()`、`GetChildren()`、`TermData`の`GetChildren()`、`WpUserData`の`GetAvatar()`、`MenuNodeData`の`GetThumbnail()`は、対応するMembersへ渡した`Closure`へ処理を委ねます。関数を持つMembersプロパティには`AuthorResolver`・`ThumbnailResolver`・`ChildrenResolver`のように`Resolver`を付け、利用側のGet系メソッドと区別します。関数を省略すると文字列は空文字、リストは空配列、真偽値はfalse、それ以外はnullを返します。結果はキャッシュせず、呼び出すたびに関数を実行します。`GetChildren()`はリストと要素のData型を実行時にも確認し、契約違反なら`UnexpectedValueException`を投げます。他のGet系はPHPの戻り値型で確認します。

```php
<?php
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostMembers;
use WpTypeSafety\Core\Data\ImgData;

$data = new PostData(
	new PostMembers(
		PostId: $post->ID,
		Url: (string) get_permalink($post),
		Title: get_the_title($post),
		Content: $post->post_content,
		Status: $post->post_status,
		PostType: $post->post_type,
		Slug: $post->post_name,
		IsPasswordProtected: $post->post_password !== '',
		IsPubliclyViewable: is_post_publicly_viewable($post),
		ThumbnailResolver: static function (string|array $size) use ($post): ?ImgData {
			$source = wp_get_attachment_image_src(get_post_thumbnail_id($post), $size);
			return is_array($source)
				? new ImgData(Url: $source[0], Width: $source[1], Height: $source[2])
				: null;
		},
	),
);

$image = $data->GetThumbnail('medium_large');
```

`PostData`は`Status`・`PostType`・`Slug`・`IsPasswordProtected`・`IsPubliclyViewable`も保持します。`IsPublished()`は`Status === 'publish'`だけを判定し、投稿タイプの公開可否までは判定しません。`IsPubliclyViewable`はWordPressの`is_post_publicly_viewable()`による、投稿状態と投稿タイプを合わせた判定です。パスワード文字列自体は保持しません。直接生成時に省略した場合、文字列は空文字、真偽値はfalseになり、状態が不明であることを表します。

取得関数はWordPress以外のデータ源を使っても構いません。`GetChildren()`の引数と戻り値型など、公開メソッドの契約は共通です。`MenuItemType`はWordPressの値から自動変換せず、該当するenum caseを呼び出し元が選びます。

`MenuNodeData->Children`と`Classes`は取得済みの値です。コンストラクターでは要素型を検証しません。`readonly`は配列プロパティの再代入を防ぎますが、配列に入れたオブジェクト自体の変更を防ぐものではありません。

`PostData`・`MenuNodeData`の`GetLink()`は、保持している`Url`・`Title`から`LinkData`を作ります。`Target`は空文字です。WordPressへの問い合わせは行いません。

`PostData::GetTemplateSlug(): string`は、`PostMembers::$TemplateSlugResolver`へ委譲して、管理画面で選択されたテンプレートの相対パス（例：`custom-service-subpage.php`）を返します。拡張子・ハイフン・サブディレクトリは変換しません。テンプレート階層で実際に適用されるPHPファイルを判定するAPIではありません。Resolver省略時は空文字です。InternalのFactoryは`get_page_template_slug($postId)`を呼ぶResolverを注入し、デフォルト・取得失敗・文字列以外の値を空文字へ正規化します。取得は呼び出すたびに行います。

```php
<?php
$template = $postData->GetTemplateSlug();
// 'custom-service-subpage.php'、またはデフォルトなら ''
```

## 案件用サブクラスのコンストラクター

継承可能なWordPress Dataには、対応する構築用Membersがあります。`PostData`には`PostMembers`、`WpUserData`には`UserMembers`、`CommentData`には`CommentMembers`、`TermData`には`TermMembers`を渡します。Membersは生成前に型付きプロパティを変更でき、Dataは受け取った時点の値をreadonlyプロパティへコピーします。

この形なら、派生クラスは親の値やResolverを個別に再宣言・転送する必要がありません。値の取得は`FromWpPost()`等の生成メソッドへ分け、コンストラクターではWordPressやACFへ問い合わせません。以下のクラス名・フィールド名は説明用の例です。

```php
<?php
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostMembers;
use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Wp\Get\PostFactory;

final readonly class ArticlePostData extends PostData
{
	public function __construct(
		PostMembers $members,
		public string $Subtitle,
	) {
		parent::__construct($members);
	}

	public static function FromWpPost(\WP_Post $post): self
	{
		$members = PostFactory::Members($post);
		$postGet = new AcfGet($post->ID);

		return new self(
			members: $members,
			Subtitle: $postGet->Text('subtitle'),
		);
	}
}
```

`PostFactory::Members()`が返す`PostMembers`には、共通値とResolverがすべて含まれます。案件側で親の値を変更する場合は、Dataへ渡す前にMembersの型付きプロパティを変更します。元のMembersを再利用したい場合は、変更前に`clone`してください。

```php
<?php
$members = new PostMembers(
	PostId: 123,
	Url: '/sample/',
	Title: '任意のタイトル',
	Content: '任意の本文',
	ExcerptResolver: static fn (): string => '任意の抜粋',
);

$data = new ArticlePostData(
	members: $members,
	Subtitle: '記事の補足',
);
```

派生した投稿者Dataを返す場合は、Data生成前に`PostMembers::$AuthorResolver`を差し替えます。

```php
<?php
$members->AuthorResolver = static function () use ($post): ?AuthorProfileData {
	$user = get_userdata((int) $post->post_author);
	return $user instanceof \WP_User ? AuthorProfileData::FromWpUser($user) : null;
};
```

`WpUserData`の派生クラスには`UserMembers`を渡します。

```php
<?php
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Data\UserMembers;
use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Wp\Get\UserFactory;

final readonly class AuthorProfileData extends WpUserData
{
	public function __construct(
		UserMembers $members,
		public string $RoleLabel = '',
	) {
		parent::__construct($members);
	}

	public static function FromWpUser(\WP_User $user, int $avatarSize = 96): ?self
	{
		$members = UserFactory::Members($user, $avatarSize);
		$userGet = new AcfGet($user);

		return $members === null ? null : new self(
			members: $members,
			RoleLabel: $userGet->Text('role_label'),
		);
	}
}
```

`CommentData`と`TermData`を拡張する場合も、対応する`CommentMembers`と`TermMembers`を子で受け取り、親へそのまま渡します。さらに下のサブクラスを作る場合も、同じMembersを受け渡します。上の`final`クラスをさらに継承する場合は、`final`を外し、生成メソッドの戻り値・生成先も見直してください。

## タームの親取得

`TermData::GetParent(): ?TermData`は、直接の親を`parentResolver`から取得します。`ParentId`が0・Resolver省略・親の取得失敗の場合はnullです。`HasParent()`は親IDの有無だけを判定し、親が取得できるかどうかまでは確認しません。取得結果はキャッシュせず、呼び出すたびにResolverを実行します。

`TermFactory::From()`・`TermFactory::Members()`のInternal生成処理は、同じタクソノミーと親IDで`get_term()`を呼ぶクロージャーを注入します。親のURLを取得できずDataに変換できない場合もnullです。利用側から`TermMembers::$ParentResolver`へ直接注入することもできます。

```php
<?php
$parent = $birthplace?->GetParent();
if ($birthplace?->Taxonomy === 'area' && $parent?->Slug === 'kanagawa') {
	// 神奈川県直下の地域を選択している場合。
}
```
