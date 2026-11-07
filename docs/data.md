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

`WpUserData->Description` はWP標準の「プロフィール情報」（自己紹介文）です。Factoryが生成時に `WP_User->description` から取得します。未設定・文字列以外は空文字です。直接構築時は `Description:` で指定でき、省略時は空文字です。HTML出力やエスケープは利用側で行い、DTOは値をそのまま保持します。

`WpUserData->AuthorUrl` は投稿者一覧のURLです。Factoryが生成時に `get_author_posts_url()` で取得し、HTTP(S)の有効なURLならDTOに保持します。不正なURL・未設定・文字列以外は空文字です。DTO内ではWP APIを呼びません。直接構築する場合は名前付き引数 `AuthorUrl:` で指定でき、省略時は空文字です。コンストラクターはURL形式を検証しないため、直接渡す値は呼び出し元で確認してください。既存の位置引数の順序は維持しています。HTML出力時は利用側で `esc_url()` を適用してください。

`Core/Data/`には`ImgData`・`LinkData`・`ChoiceData`・`FileData`・`TimeData`を置きます。`TimeData`は日付・タイムゾーンを含まない時刻を`Hour`・`Minute`・`Second`として保持し、`Format()`で文字列化できます。WordPress固有の`PostData`・`TermData`・`WpUserData`・`MenuNodeData`・`MenuItemType`は`Wp/Data/`へ置き、名前空間は`WpTypeSafety\Wp\Data`です。いずれも値をコンストラクターで受け取ります。`PostData`・`TermData`・`WpUserData`・`MenuNodeData`は値に加え、案件側が渡す取得関数を呼ぶメソッドを持ちます。分類は扱う概念によるもので、これらのデータクラス自身がWordPressの関数を直接呼ぶわけではありません。

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

`PostData`の`GetAuthor()`、`GetExcerpt()`、`HasExcerpt()`、`GetThumbnail()`、`GetChildren()`、`GetPreviousPost()`、`GetNextPost()`、`TermData`の`GetChildren()`、`WpUserData`の`GetAvatar()`、`MenuNodeData`の`GetThumbnail()`は、コンストラクターに渡した`Closure`へ処理を委ねます。関数を渡す名前付き引数には`authorResolver`・`thumbnailResolver`・`childrenResolver`のように`Resolver`を付け、利用側のGet系メソッドと区別します。関数を省略すると文字列は空文字、リストは空配列、真偽値はfalse、それ以外はnullを返します。結果はキャッシュせず、呼び出すたびに関数を実行します。`GetChildren()`はリストと要素のDTO型を実行時にも確認し、契約違反なら`UnexpectedValueException`を投げます。他のGet系はPHPの戻り値型で確認します。`GetAuthor()`と`authorResolver`は引数を取りません。

```php
<?php
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Core\Data\ImgData;

$data = new PostData(
	PostId: $post->ID,
	Url: (string) get_permalink($post),
	Title: get_the_title($post),
	Content: $post->post_content,
	thumbnailResolver: static function (string|array $size) use ($post): ?ImgData {
		$source = wp_get_attachment_image_src(get_post_thumbnail_id($post), $size);
		return is_array($source)
			? new ImgData(Url: $source[0], Width: $source[1], Height: $source[2])
			: null;
	},
);

$image = $data->GetThumbnail('medium_large');
```

取得関数はWordPress以外のデータ源を使っても構いません。`GetChildren()`の引数と戻り値型など、公開メソッドの契約は共通です。`MenuItemType`はWordPressの値から自動変換せず、該当するenum caseを呼び出し元が選びます。

`MenuNodeData->Children`と`Classes`は取得済みの値です。コンストラクターでは要素型を検証しません。`readonly`は配列プロパティの再代入を防ぎますが、配列に入れたオブジェクト自体の変更を防ぐものではありません。

`PostData`・`MenuNodeData`の`GetLink()`は、保持している`Url`・`Title`から`LinkData`を作ります。`Target`は空文字です。WordPressへの問い合わせは行いません。

`PostData::GetTemplateSlug(): string`は、`templateSlugResolver`へ委譲して、管理画面で選択されたテンプレートの相対パス（例：`custom-service-subpage.php`）を返します。拡張子・ハイフン・サブディレクトリは変換しません。テンプレート階層で実際に適用されるPHPファイルを判定するAPIではありません。Resolver省略時は空文字です。InternalのFactoryは`get_page_template_slug($postId)`を呼ぶResolverを注入し、デフォルト・取得失敗・文字列以外の値を空文字へ正規化します。取得は呼び出すたびに行います。

```php
<?php
$template = $postData->GetTemplateSlug();
// 'custom-service-subpage.php'、またはデフォルトなら ''
```

## 案件用サブクラスのコンストラクター

親クラスが持つ値・Resolverは、子クラスの通常の引数として受け取り、`parent::__construct()`へ明示的に渡します。親へ渡すだけの引数には`public`・`protected`・`private`を付けません。案件固有の値だけを子クラスのプロパティとして追加します。引き継ぎ元DTOをコンストラクターの必須引数にする必要はありません。

この形なら、一度の`new ArticlePostData(...)`で共通値・追加の値・Resolverを指定できます。値の取得は`FromWpPost()`等の生成メソッドへ分け、コンストラクターではWordPressやACFへ問い合わせません。以下のクラス名・フィールド名は説明用の例です。

```php
<?php
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Acf\Get\AcfGet;
use WpTypeSafety\Wp\Get\WpDataFactory;

final readonly class ArticlePostData extends PostData
{
	public function __construct(
		int $PostId,
		string $Url,
		string $Title,
		string $Content,
		public string $Subtitle,
		?\DateTimeImmutable $PublishedAt = null,
		?\DateTimeImmutable $ModifiedAt = null,
		?\Closure $authorResolver = null,
		?\Closure $excerptResolver = null,
		?\Closure $excerptPresenceResolver = null,
		?\Closure $thumbnailResolver = null,
		?\Closure $childrenResolver = null,
		?\Closure $previousPostResolver = null,
		?\Closure $nextPostResolver = null,
		?\Closure $templateSlugResolver = null,
	) {
		parent::__construct(
			PostId: $PostId,
			Url: $Url,
			Title: $Title,
			Content: $Content,
			PublishedAt: $PublishedAt,
			ModifiedAt: $ModifiedAt,
			authorResolver: $authorResolver,
			excerptResolver: $excerptResolver,
			excerptPresenceResolver: $excerptPresenceResolver,
			thumbnailResolver: $thumbnailResolver,
			childrenResolver: $childrenResolver,
			previousPostResolver: $previousPostResolver,
			nextPostResolver: $nextPostResolver,
			templateSlugResolver: $templateSlugResolver,
		);
	}

	public static function FromWpPost(\WP_Post $post): self
	{
		$common = WpDataFactory::PostFromWpPost($post);

		return new self(
			PostId: $common->PostId,
			Url: $common->Url,
			Title: $common->Title,
			Content: $common->Content,
			Subtitle: (new AcfGet($post->ID))->Text('subtitle'),
			PublishedAt: $common->PublishedAt,
			ModifiedAt: $common->ModifiedAt,
			authorResolver: $common->GetAuthor(...),
			excerptResolver: $common->GetExcerpt(...),
			excerptPresenceResolver: $common->HasExcerpt(...),
			thumbnailResolver: $common->GetThumbnail(...),
			childrenResolver: $common->GetChildren(...),
			previousPostResolver: $common->GetPreviousPost(...),
			nextPostResolver: $common->GetNextPost(...),
			templateSlugResolver: $common->GetTemplateSlug(...),
		);
	}
}
```

`$common->GetThumbnail(...)`等はメソッドをその場で実行する記述ではなく、呼び出し可能な`Closure`を取得するPHPの第一級callable構文です。画像サイズ等の引数も、子DTOのGet呼び出し時に渡されます。上のFactory利用は生成メソッド内だけの実装であり、子のコンストラクターは共通DTOを要求しません。Resolverを省略して直接生成すると、各Getは既定の空値を返します。

```php
<?php
$data = new ArticlePostData(
	PostId: 123,
	Url: '/sample/',
	Title: '任意のタイトル',
	Content: '任意の本文',
	Subtitle: '記事の補足',
	excerptResolver: static fn (): string => '任意の抜粋',
);
```

派生した投稿者DTOを返す場合は、生成メソッドで`authorResolver`へその生成処理を渡せます。

```php
<?php
// FromWpPost()内で、共通のauthorResolverの代わりに指定する。
$authorResolver = static function () use ($post): ?AuthorProfileData {
	$user = get_userdata((int) $post->post_author);
	return $user instanceof \WP_User ? AuthorProfileData::FromWpUser($user) : null;
};

// new self(..., authorResolver: $authorResolver)で渡す。
```

`WpUserData`の派生クラスも、ID・表示名・アバター設定を通常の引数として親へ渡します。

```php
<?php
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Acf\Get\AcfGetUser;
use WpTypeSafety\Wp\Get\WpDataFactory;

final readonly class AuthorProfileData extends WpUserData
{
	public function __construct(
		int $Id,
		string $DisplayName,
		public string $RoleLabel = '',
		int $avatarSize = 96,
		?\Closure $avatarResolver = null,
		string $AuthorUrl = '',
		string $Description = '',
	) {
		parent::__construct(
			Id: $Id,
			DisplayName: $DisplayName,
			avatarSize: $avatarSize,
			avatarResolver: $avatarResolver,
			AuthorUrl: $AuthorUrl,
			Description: $Description,
		);
	}

	public static function FromWpUser(\WP_User $user, int $avatarSize = 96): ?self
	{
		$common = WpDataFactory::UserFromWpUser($user, $avatarSize);
		return $common === null ? null : new self(
			Id: $common->Id,
			DisplayName: $common->DisplayName,
			RoleLabel: (new AcfGetUser($user))->Text('role_label'),
			avatarSize: $avatarSize,
			avatarResolver: $common->GetAvatar(...),
			AuthorUrl: $common->AuthorUrl,
			Description: $common->Description,
		);
	}
}
```

`TermData`を拡張する場合も、ID・名前・分類名などと`childrenResolver`・`parentResolver`を子で受け取り、親へ渡す形に揃えます。さらに下のサブクラスを作る場合も、中間クラスの引数を明示的に受け渡します。親の引数がPHPによって自動追加されるわけではありません。上の`final`クラスをさらに継承する場合は、`final`を外し、生成メソッドの戻り値・生成先も見直してください。

## タームの親取得

`TermData::GetParent(): ?TermData`は、直接の親を`parentResolver`から取得します。`ParentId`が0・Resolver省略・親の取得失敗の場合はnullです。`HasParent()`は親IDの有無だけを判定し、親が取得できるかどうかまでは確認しません。取得結果はキャッシュせず、呼び出すたびにResolverを実行します。

`WpDataFactory::TermFromWpTerm()`などのInternal生成処理は、同じタクソノミーと親IDで`get_term()`を呼ぶクロージャーを注入します。親のURLを取得できずDTOに変換できない場合もnullです。利用側から`parentResolver: static fn (): ?TermData => $parent`のように直接注入することもできます。

```php
<?php
$parent = $birthplace?->GetParent();
if ($birthplace?->Taxonomy === 'area' && $parent?->Slug === 'kanagawa') {
	// 神奈川県直下の地域を選択している場合。
}
```
