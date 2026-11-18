# Dataと派生クラス

## データクラスと取得契約

WordPress Dataは、値だけのPropsから構築します。値の調整はProps、動作変更はGetter・生成フックのオーバーライドで行います。旧*Members・PostProperty・Members()/Property()は廃止しました。

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

URLプロパティは`string`で保持し、未設定・取得できないURLは空文字で表します。Data自体の取得失敗による`null`とは区別します。URL形式の許容範囲は用途別で、メニューの相対パスやアンカーまでHTTP(S)に限定するものではありません。

| Data | URLプロパティ | 内容 | 取得できない場合 |
|---|---|---|---|
| `WpUserData` | `ArchiveUrl` | 投稿者一覧 | 空文字 |
| `WpUserData` | `WebsiteUrl` | ユーザーが登録したWebサイト | 空文字 |
| `CommentData` | `WebsiteUrl` | コメント投稿者のWebサイト | 空文字 |
| `PostTypeData` | `ArchiveUrl` | 投稿タイプのアーカイブ | 空文字 |

`WpUserData->Description` はWP標準の「プロフィール情報」（自己紹介文）です。Factoryが生成時に `WP_User->description` から取得します。未設定・文字列以外は空文字です。直接構築時は`UserProps`の`Description:`で指定でき、省略時は空文字です。HTML出力やエスケープは利用側で行い、Dataは値をそのまま保持します。

`WpUserData->ArchiveUrl` は投稿者一覧のURLです。Factoryが生成時に `get_author_posts_url()` で取得し、HTTP(S)の有効なURLならDataに保持します。不正なURL・未設定・文字列以外は空文字です。Data内ではWP APIを呼びません。直接構築する場合は`UserProps`の`ArchiveUrl:`で指定でき、省略時は空文字です。コンストラクターはURL形式を検証しないため、直接渡す値は呼び出し元で確認してください。HTML出力時は利用側で `esc_url()` を適用してください。

`WpUserData->WebsiteUrl` はユーザーに登録されたWebサイト（`WP_User->user_url`）です。FactoryではHTTP(S) URLを検証し、不正・未設定・文字列以外は空文字にします。著者一覧の`ArchiveUrl`とは独立した値です。直接構築時は`UserProps`の`WebsiteUrl:`で指定できます。

公開Dataは、扱う概念によって次の領域に分かれます。

| 領域 | Data | 主な内容 | 関連取得 |
|---|---|---|---|
| `Core/Data` | `ImgData` | URL・幅・高さ・代替テキスト | なし |
| `Core/Data` | `LinkData` | URL・タイトル・ターゲット | なし |
| `Core/Data` | `ChoiceData`・`FileData` | 選択肢、添付ファイル | なし |
| `Core/Data` | `DateData`・`TimeData` | 日付または時刻の構成値 | `Format()` |
| `Wp/Data` | `PostData` | 投稿情報 | 投稿者・抜粋・画像・関連投稿など |
| `Wp/Data` | `CommentData` | コメント情報 | ユーザー・画像・投稿・親子コメント |
| `Wp/Data` | `TermData` | ターム情報 | 親・子ターム |
| `Wp/Data` | `PostTypeData` | 投稿タイプの登録定義・アーカイブURL | タクソノミー登録名 |
| `Wp/Data` | `TaxonomyData` | タクソノミーの登録定義・対象投稿タイプ | なし |
| `Wp/Data` | `WpUserData` | ユーザー情報 | アバター画像 |
| `Wp/Data` | `MenuNodeData` | メニュー項目 | サムネイル画像 |
| `Wp/Data` | 各`*QueryData` | Query結果とページ情報 | なし |
| `Wp/Data` | `MenuItemType` | メニュー項目の種類 | なし |
| `Acf/Data` | `FlexibleNode` | レイアウト名と行の`AcfGet` | 行内のACF取得 |
| `Acf/Data` | `AcfBlockData` | ACFブロックの描画情報 | ラッパー属性 |

`DateData`は時刻とタイムゾーンを含まない日付を`Year`・`Month`・`Day`、`TimeData`は日付とタイムゾーンを含まない時刻を`Hour`・`Minute`・`Second`として保持します。WordPress固有のDataの名前空間は`WpTypeSafety\Wp\Data`です。いずれも値をコンストラクターで受け取り、Data自身がWordPressの関数を直接呼ぶことはありません。

URLの形式、IDや寸法の正負、配列要素の型など、PHPの型宣言では表せない条件はコンストラクターで検証しません。呼び出し元が確認してください。

画像とファイルのDataを取得する際の必要条件は次のとおりです。Dataを直接構築する場合は、表の値を呼び出し元で用意してください。

| Data | 必要な情報 | 情報が足りない場合 | URL返却形式のACFフィールド |
|---|---|---|---|
| `ImgData` | 画像URLと整数の`Width`・`Height` | `Img()`は`null` | `ImgUrl()`で`?string`を取得 |
| `FileData` | 添付ファイルのIDと整数の`FileSize` | `File()`は`null` | `FileUrl()`で`?string`を取得 |

`ImgData`の`Width`・`Height`は、ラスター画像ではピクセル寸法です。SVG添付画像では単位なし・`px`の`width`と`height`を優先し、両方を取得できない場合は`viewBox`から得た整数の比率を保持します。`viewBox`由来の値は実ピクセル寸法ではなく、ブラウザーが表示領域の比率を事前に確保するための組です。URLだけの値を`Img()`へ渡すと`null`になります。`File()`もURLだけの値では`null`になります。

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

## Propsと共通値のreadonly

| Data | 構築用Props | 公開取得口 |
|---|---|---|
| PostData | PostProps | PostGet::Props(WP_Post) |
| TermData | TermProps | TermGet::Props(WP_Term): ?TermProps |
| CommentData | CommentProps | CommentGet::Props(WP_Comment, avatarSize: 96) |
| WpUserData | UserProps | UserGet::Props(WP_User, avatarSize: 96): ?UserProps |
| MenuNodeData | MenuNodeProps<TChild> | MenuNodeGet::Props(WP_Post, children: []) |
| PostTypeData | PostTypeProps | PostTypeGet::Props(WP_Post_Type) |
| TaxonomyData | TaxonomyProps | TaxonomyGet::Props(WP_Taxonomy) |

Propsは変更可能な値の集合です。Resolver・Factoryは持たず、Dataは構築時の値をreadonlyプロパティへコピーします。元のPropsの後続変更は反映しません。Propsを再利用したい場合は変更前にcloneしてください。コンストラクターではWordPress・ACFへ問い合わせません。

継承可能な7種類のWordPress Dataは、クラス全体ではなく共通プロパティへ個別にreadonlyを指定します。サブクラスの追加状態まで不変とは保証しません。案件側は追加プロパティをreadonlyにするか、編集状態・キャッシュ等として変更可能にするかを選べます。配列プロパティ自体の変更はできませんが、保持するオブジェクトの内部状態まで固定する深い不変性は保証しません。

PHPでは非readonlyクラスをreadonlyクラスで継承できません。案件側も通常のclassで継承し、固定したい追加プロパティへreadonlyを付けてください。親のId・Title等のreadonlyは解除できません。編集用の追加状態を保存する処理は案件側の責務であり、保存後に共通値を更新したい場合はDataを再取得します。この変更自体はORM・保存APIを提供するものではありません。Coreのfinal値型、finalなQuery結果・ACF Dataは引き続きクラス全体をreadonlyにします。

TermGet::Props()/From()は従来どおりURLの取得失敗でもnullを返します。UserGet::Props()/From()は存在しないユーザーならnullです。直接構築するPropsの値の妥当性は呼び出し側の責任です。

```php
use WpTypeSafety\Wp\Get\PostGet;
use WpTypeSafety\Wp\Data\PostData;

$props = PostGet::Props($post);
$props->Title = '表示用タイトル';
$props->RawContent = '変更した本文';
$props->RawExcerpt = '変更した手入力抜粋';
$data = new PostData($props);
```

## 案件用Dataの生成と継承

```php
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\PostProps;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Get\PostGet;
use WpTypeSafety\Acf\Get\AcfGet;

/** @extends PostData<ArticlePostData, WpUserData> */
final class ArticlePostData extends PostData
{
	public function __construct(PostProps $props, public readonly string $Subtitle)
	{
		parent::__construct($props);
	}

	protected function createRelatedPost(\WP_Post $post): ?self
	{
		return self::FromWpPost($post);
	}

	public static function FromWpPost(\WP_Post $post): self
	{
		$postGet = new AcfGet($post);
		return new self(
			props: PostGet::Props($post),
			Subtitle: $postGet->Text('subtitle'),
		);
	}
}
```

複数のGetterで共通する関連Data生成は、次のprotectedフックを上書きします。

| Data | 生成フック | 適用先 |
|---|---|---|
| PostData | createRelatedPost(WP_Post): ?PostData | GetParent・GetChildren・GetPreviousPost・GetNextPost |
| TermData | createRelatedTerm(WP_Term): ?TermData | GetParent・GetChildren |
| CommentData | createRelatedComment(WP_Comment): ?CommentData | GetParent・GetChildren |

標準フックは基底Dataを生成します。派生型を返す場合は生成フックを上書きしてください。nullは単一取得ではnull、リストでは要素の除外を意味します。順序を維持して再採番します。戻り値型違反はPHPのTypeError、フック内の例外はそのまま伝播します。前後投稿の処理では一時的なグローバルpostを例外時も復元します。

一つのGetterだけの変更には、別のFactoryやフックを設けません。PostData::GetAuthor()、CommentData::GetUser()/GetPost()/GetAvatar()、TermData::GetTaxonomy()等を直接オーバーライドします。標準処理は内部static Adapterへ委譲します。Internalは公開拡張点ではありません。

## 保持値と呼び出し時点の情報

| Data・処理 | 基準 |
|---|---|
| PostData::GetLink | 保持したTitle・Url |
| PostData::GetExcerpt / HasExcerpt | 保持したRawExcerpt。本文から自動生成しない |
| PostData::GetRenderedContent | 保持したRawContent・主要な投稿値。詳細はwordpress.md |
| PostDataの親・著者 | 保持したParentId・AuthorId |
| PostDataの子・ターム・画像・前後投稿・テンプレート | 保持したId |
| TermDataの親 | 保持したParentId・TaxonomyName |
| TermDataの子 | 保持したId・TaxonomyName。階層タクソノミーのみ |
| CommentDataの親・子・ユーザー・投稿 | 保持したParentId・Id・AuthorUserId・PostId |
| WpUserData::GetAvatar | 保持したId・アバターサイズ |
| MenuNodeData::GetThumbnail | 保持したType・Object・ObjectId |
| PostData::GetPostType / TermData::GetTaxonomy | 保持した登録名 |
| PostTypeData::GetTaxonomyNames | 保持したNameで現在の関連登録名を取得 |
| TaxonomyData::HasObjectType | 構築時に保持したObjectTypes |

外部取得は呼び出しごとに実行し、Dataは結果をキャッシュしません。元のWordPressオブジェクトは保持しません。PropsのId等を変更した場合も変更後の保持値を使用します。

値の参照・GetLink・HasContent・HasExcerpt・HasParent・IsPublished・IsApproved・HasObjectTypeなどはWordPressなしで利用できます。標準のGetExcerptのタグ除去や外部取得・描画にはWordPressが必要です。直接newしたDataでも標準取得を実行し、Resolver省略による無効化はありません。オーバーライド内でparent::を呼べば、親処理のWordPress依存も残ります。

### PostData

RawExcerptは手入力抜粋の原文です。GetExcerptはwp_strip_all_tags()を適用し、HasExcerptはRawExcerpt !== ''を返します。DBから抜粋を再取得せず、has_excerptフィルターや本文からの自動生成は適用しません。

Status・PostTypeName・Slug・IsPasswordProtected・IsPubliclyViewableは構築時の値です。IsPublished()はStatus === 'publish'だけを判定します。パスワード文字列自体は保持しません。

GetTemplateSlug()は選択済みテンプレートの相対パスです。デフォルト・取得失敗・不正な値は空文字で、実際に適用されるPHPファイルの判定ではありません。GetParent()は添付ファイルの親も対象とし、GetChildren()は従来どおり階層型投稿タイプのみ対象です。

### TermData・登録定義

HasParent()は正の親IDの有無だけを判定します。親の存在確認はしません。GetTaxonomy()/GetPostType()は現在の登録定義を取得し、空の登録名・未登録ならnullです。取得できなくても保持済み登録名は変わりません。

PostTypeData::GetTaxonomyNames()はget_object_taxonomies(Name, 'names')の結果から、空文字・文字列以外を除外して再採番します。空のNameや不正な取得結果は空配列です。TaxonomyData::ObjectTypesは構築時の値で、自動更新しません。

### アバター

WpUserData・CommentDataともにGetAvatar(?int $size = null)でサイズを指定できます。省略時はPropsのAvatarSize、さらに未指定なら96です。最小値は1です。

CommentData::GetAvatar()は保持したIdで現在のWP_Commentを取得し、複製へ保持済みAuthorUserId・AuthorName・WebsiteUrlを反映してWordPressのアバターAPIへ渡します。ゲストのメールアドレス等は現在のコメントを使用します。新しい公開メールプロパティは追加しません。コメントの削除・型不一致ならnullです。これは完全なアバター情報のスナップショットではありません。

### メニュー

MenuNodeProps<TChild>・MenuNodeData<TChild>のChildrenは構築済みのlist<TChild>です。新たに取得・再生成するフックは設けません。TreeFromLocation()のnodeFactoryは木を構築する入口として維持し、既存の木キャッシュの扱いも変更しません。

GetThumbnail()は従来どおり固定ページを参照するメニュー項目（TypeがPostType、Objectがpage）だけを対象とします。リンク・判定メソッドは保持値から動作します。

## ジェネリクス・取得入口のFactory

PostData<TRelatedPost, TAuthor>、TermData<TRelatedTerm>、CommentData<TRelatedComment, TUser, TPost>、MenuNodeData<TChild>を維持します。MenuNodeProps以外のPropsは値だけなので関連型のパラメーターを持ちません。

型パラメーターの宣言だけでは実行時の生成型は変わりません。@extendsと生成フック・Getterの実装を一致させてください。既定実装は基底型を返すため、その2つを静的解析だけで完全には保証できません。PHPStanの回帰テストと実行時テストを併用します。

Current/Loop、Query、ACF、TreeFromLocationやGetTerms()等の取得入口・呼び出し単位のFactoryは維持します。Data構築に動作を注入するrelatedPostFactory・authorFactory・relatedTermFactory・relatedCommentFactory・CommentGetのuserFactory/postFactoryは廃止しました。

AcfBlockDataの描画用wrapperAttributesResolver、FlexibleNodeのGet、Core DataとQuery結果Dataの責務は変更していません。

静的型の回帰テストはtests/phpstan/posts.neonとdata-props.neonです。PHPStan導入済み環境でphpstan analyse -c tests/phpstan/data-props.neon等を実行してください。WordPress・DBの起動は不要です。
