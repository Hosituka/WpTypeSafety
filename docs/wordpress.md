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

`WpDataFactory`はWordPressの値から共通データクラスを生成します。`WP_Post`・`WP_Comment`・`WP_User`・`WP_Term`はオブジェクトを渡します。投稿ID・コメントID・ユーザーID・タームIDからの検索は行いません。添付画像とファイルはIDを渡します。ユーザー・ターム・添付ファイルを変換できない場合は`null`です。

```php
<?php
use WpTypeSafety\Wp\Get\WpDataFactory;

$postData = WpDataFactory::PostFromWpPost($post);
$commentData = WpDataFactory::CommentFromWpComment($comment);
$userData = WpDataFactory::UserFromWpUser($user);
$termData = WpDataFactory::TermFromWpTerm($term);
$image = WpDataFactory::ImgFromAttachmentId($attachmentId, 'medium_large');
$file = WpDataFactory::FileFromAttachmentId($attachmentId);
```

SVG添付画像は、ローカルファイルの単位なし・`px`の`width`と`height`を寸法として優先します。両方を取得できない場合は、`viewBox`から比率を取得します。SVGにはラスター画像のサイズバリエーションがないため、指定したサイズ名にかかわらず元のURLと寸法または比率を持つ`ImgData`を返します。どちらも判定できないSVGは`null`です。

案件固有の派生データクラスは、案件側の生成メソッドで組み立てます。このFactoryは共通データクラスを返します。

## WordPressのコメント

`CommentData`は匿名コメントと登録ユーザーのコメントを同じ型で表します。`AuthorName`・`AuthorUrl`はどちらでも参照でき、`AuthorUserId`が0より大きい場合だけ`HasUser()`がtrueになります。`GetUser()`は対応する`WpUserData`を返し、匿名コメント・ユーザー取得失敗では`null`です。投稿者メールアドレスは公開プロパティに保持しません。

```php
<?php
$commentData = WpDataFactory::CommentFromWpComment($comment);

echo esc_html($commentData->AuthorName);
$user = $commentData->GetUser();
$avatar = $commentData->GetAvatar(64);
```

`GetPost()`・`GetParent()`・`GetChildren()`は関連する投稿・親コメント・直下の子コメントを呼び出し時に取得します。`GetChildren()`の第1引数はWordPressのコメントクエリへ渡す状態（既定値`approve`）、第2引数は並び順（既定値`ASC`）です。

`Status`はWordPressの`comment_approved`を正規化し、`1`は`approved`、`0`は`unapproved`になります。`spam`・`trash`など、それ以外の値は維持します。`Type`は旧データの空文字を`comment`へ正規化します。`CreatedAt`は`comment_date`をサイトのタイムゾーンで解釈した`?DateTimeImmutable`です。欠損・不正な日時は`null`になります。
