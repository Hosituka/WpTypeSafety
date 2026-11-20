# WordPress Dataの生成API

PostDataの生成方式をTermData・WpUserData・CommentData・PostTypeData・TaxonomyData・MenuData・MenuNodeDataにも適用しました。

## 共通契約

- final public static From → protected static getProps → final protected __construct → protected initialize →返却。
- Propsの変更はgetProps、追加プロパティの初期化はinitialize。親initializeを先に呼び、readonly値は宣言クラス内で設定します。
- WordPress Dataは外部から`new`せず、各Dataの`From()`または対象別Get・Queryから生成します。
- Core値型・Query結果Data・AcfBlockDataは対象外です。
- 関連Dataは従来どおりcreateRelatedPost/Term/CommentやGetterの上書きで選びます。既定では基底Dataを返します。

## 取得時のクラス指定

```php
$term = TermGet::ById(1, dataClass: ProjectTerm::class);
$user = UserGet::ById(1, avatarSize: 96, dataClass: ProjectUser::class);
$comments = CommentQuery::Get([], dataClass: ProjectComment::class);
$terms = $acfGet->Terms('areas', dataClass: ProjectTerm::class);
```

Term/User/Menu/MenuNodeのFromはnullableで、getPropsのnullは構築・初期化前に生成を中止します。Query・ACF複数参照ではnullの要素を除外します。Post/Comment/PostType/TaxonomyのFromは非nullableです。失敗・欠損と不正なクラス指定は区別し、後者は取得前にInvalidArgumentExceptionです。生成例外は伝播します。

## Menu：案2

```php
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

$menu = MenuGet::ByLocation('header', dataClass: ProjectMenu::class);
$nodes = $menu?->Nodes ?? [];
$current = $menu?->FindCurrent();
```

MenuData::From(WP_Term)は項目取得・ツリー構築を伴います。MenuNodeData::From(WP_Post, children: [])はセットアップ済みの項目と構築済みの子を受けます。子から親の順に生成し、ノードのgetPropsがnullなら子孫ごと除外します。子をルートへ昇格しません。

取得は`MenuGet`、変換は`Data::From`、取得済みツリーの探索は`MenuData`が担当します。`FindCurrent()`は取得時の`IsCurrent`を検索するだけで、現在位置を再計算しません。空メニューはData、未発見・項目取得失敗は`null`です。

詳細は[data.md](data.md)・[wordpress.md](wordpress.md)を参照してください。
