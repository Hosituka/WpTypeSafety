# WordPressの取得API

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

## WordPressメニュー

```php
<?php
use WpTypeSafety\Wp\Get\WpGetMenuNode;

$menu = WpGetMenuNode::TreeFromLocation('primary');
```

`FindByPageId()`は、`TreeFromLocation()`で得たツリーを子階層まで検索し、指定した固定ページIDに対応する最初の`MenuNodeData`を返します。該当なし・0以下のIDは`null`です。

```php
<?php
$currentNode = WpGetMenuNode::FindByPageId($menu, $post->ID);
```

## その他の公開取得口

| 取得口 | 戻り値 | 契約 |
|---|---|---|
| `WpGet::CurrentPageNumber()` | `int` | メインクエリの`paged`を取得し、未設定・0以下は1 |
| `WpGetMenuNode::FindByPageId()` | `?MenuNodeData` | 固定ページIDに対応するノードをツリーから再帰検索 |
| `WpGetTerm::Deepest()` | `list<TermData>` | 最も深い階層のタームをすべて返す |
| `WpGetTerm::Shallowest()` | `list<TermData>` | 最も浅い階層のタームをすべて返す |

`Deepest()`・`Shallowest()`は各タームの祖先数を深さとして比較します。同じ深さのタームが複数ある場合はすべて入力順で返し、空入力は空配列です。`Roots()`は各タームの最上位祖先を取得し、同じタクソノミーとIDの重複を除きます。

## WordPressの値からデータクラスを生成する

`WpDataFactory`はWordPressの値から共通データクラスを生成します。`WP_Post`・`WP_User`・`WP_Term`はオブジェクトを渡します。投稿ID・ユーザーID・タームIDからの検索は行いません。添付画像とファイルはIDを渡します。ユーザー・ターム・添付ファイルを変換できない場合は`null`です。

```php
<?php
use WpTypeSafety\Wp\Get\WpDataFactory;

$postData = WpDataFactory::PostFromWpPost($post);
$userData = WpDataFactory::UserFromWpUser($user);
$termData = WpDataFactory::TermFromWpTerm($term);
$image = WpDataFactory::ImgFromAttachmentId($attachmentId, 'medium_large');
$file = WpDataFactory::FileFromAttachmentId($attachmentId);
```

案件固有の派生データクラスは、案件側の生成メソッドで組み立てます。このFactoryは共通データクラスを返します。
