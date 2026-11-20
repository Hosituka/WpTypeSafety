# 内部構造と検証

## ディレクトリと責務

| ディレクトリ | 公開・内部 | 責務 |
|---|---|---|
| `Core/Data` | 公開 | WordPress・ACFに依存しないData |
| `Core/Check` | 公開 | 型確認と汎用取得 |
| `Core/Internal` | 内部 | Wp・Acfが共用する処理 |
| `Internal/TreeTypeReader` | 内部 | Coreの汎用readerが利用する基盤 |
| `Wp/Data` | 公開 | WordPress固有のDataとQuery結果 |
| `Wp/Get` | 公開 | WordPressの対象別Get・Query・メタ取得API |
| `Wp/Internal` | 内部 | WordPress APIとの接続・正規化 |
| `Acf/Data` | 公開 | ACF固有のData |
| `Acf/Get` | 公開 | `AcfGet`と`AcfDataFactory` |
| `Acf/Internal` | 内部 | ACF APIとの接続・正規化 |

公開名前空間には`Public`を含めず、`Data`・`Get`を含めます。WordPressの対象別Getは`PostGet`・`CommentGet`・`UserGet`・`TermGet`・`MenuGet`・`AttachmentGet`、対象別Queryは`PostQuery`・`TermQuery`・`UserQuery`・`CommentQuery`です。

## GetとQuery

投稿タイプ・タクソノミーの登録定義には`PostTypeGet`・`TaxonomyGet`を使用します。両GetはDefinitionAdapterへ委譲し、対応するDataを返します。名前からの検索はByName()、取得済みWordPress定義オブジェクトからの変換は各DataのFrom()です。共通Propsの調整はDataのprotected static getProps()で行います。投稿タイプに紐づく分類名は保持したNameで内部Adapterから遅延取得し、他の定義情報は生成時のスナップショットとして保持します。

各対象別Getは検索・コンテキスト取得を担当します。8種類のWordPress Dataはfinal public static Fromで、protected static getProps → final protectedコンストラクター → protected initialize →返却を固定します。派生initializeは親を先に呼びます。Get・Query・ACF参照のdataClassはclass-string<T>で、未知・別系統・abstractクラスは取得前にInvalidArgumentExceptionです。GetのFrom/Propsは廃止しました。Propsは値だけを保持し、Dataの個別readonlyプロパティへコピーします。関連DataはGetter・protected生成フックで拡張し、関連Adapterの内部Closureは維持します。

メニュー全体の取得は`MenuGet`、セットアップ済み項目の変換は`MenuNodeData::From`、取得済みツリーの探索は`MenuData`が担当します。`FindCurrent()`も取得時の`IsCurrent`を探索するだけで、WPコンテキストを再取得しません。メニューの静的ツリーキャッシュは持ちません。

対象別Queryも`Wp/Get/`の公開入口ですが、クエリビルダーの責務は持ちません。`Get()`はWordPressネイティブの引数配列をクエリクラスへ渡し、`From()`は生成済みのネイティブクエリを受け取ります。実行とData変換は`Wp/Internal/*QueryAdapter.php`へ委譲し、結果は`Wp/Data/*QueryData.php`へまとめます。`TermQueryData`はWordPress側に共通のページ総数プロパティがないため、タームのリストだけを保持します。取得手段を表す`Query`はクラス名で区別し、`Wp/Get/Query/`のような取得層内の追加階層は設けません。

## Autoloader

起動処理は`autoload.php`へ集約しています。`autoload.php`が`WpTypeSafety\`のPSR-4設定を登録し、Core・Wp・Acfのクラスは参照された時点で個別に読み込まれます。領域別の中継入口や、取得処理の初期登録はありません。

```text
autoload.php
  ├─ Autoload/Internal/ComposerJsonReader.php
  └─ Autoload/Psr4Autoload.php
       └─ composer.jsonのWpTypeSafety\\を登録
```

## GetとAdapter

汎用Readerが別種類のReaderへ切り替える際は、`createArrayReader()`・`createObjectReader()`を経由します。汎用側が型判定・リスト走査を担当し、Coreの公開Facadeは生成先と公開戻り値型を指定します。切替先は`TArrayReader`・`TObjectReader`として汎用側からFacadeへ引き継ぎ、案件側の`@extends`と生成フックを揃えることで単数・リスト・`As*()`の静的型を維持します。公開Facadeの`publicRulesSnapshot()`はprotectedで、案件側の派生Readerも規則を引き継いだ生成に使用できます。同型の子取得口は`createChild()`による複製で維持します。

公開Get系は対応するAdapterへ直接委譲し、staticなClosure登録や登録順への依存を持ちません。`AcfGet`は`AcfAdapter`をインスタンスに1個保持し、投稿・ユーザー・ターム・ACF参照先ID・取得済み配列を共通の取得元として扱います。ユーザーとタームは同じAdapterでACFの参照先IDへ正規化します。`Nested()`・`Repeater()`・`Flexible()`・`PostFields()`・`UserFields()`・`TermFields()`と、その複数形メソッドが作る子取得口は、呼び出し元の派生型と同じAdapterを引き継ぎます。投稿等から作る入れ子の子取得口は、時間系とIcon Pickerのフィールド型判定に必要な未整形値・子フィールド定義も引き継ぎます。Wpのstatic取得口は、対応するInternal Adapterのstaticメソッドへ直接委譲します。

`AcfGet`の`protected createChild()`は現在の取得口を複製し、取得元だけを子配列へ差し替えます。派生クラスで複製時の追加初期化が必要な場合は、このメソッドをオーバーライドします。

## 依存方向

`Wp/Internal/DtoFactory.php`はWordPressの値をWp固有またはCore共通のPropsまたはDataへ変換します。Dataの標準取得はPostAdapter・TermAdapter・CommentAdapter・UserAdapter・MenuAdapter・DefinitionAdapterへ委譲します。`Acf/Internal/AcfDtoFactory.php`はACFの戻り値をDTOへ変換します。ターム階層は`TermAdapter`、メニューツリーは`MenuAdapter`、ACF値の正規化は`AcfAdapter`が担当します。

| 参照元 | 参照できる領域 |
|---|---|
| `Core` | `Core` |
| `Wp` | `Wp`・`Core` |
| `Acf` | `Acf`・`Wp`・`Core` |

領域間の主な依存方向は`Acf → Wp → Core`および`Acf → Core`です。CoreはWp・Acfを参照しません。

この依存方向は、`Acf`・`Wp`・`Core`という領域間の依存を表します。同じ領域内にある`Data`と`Get`の参照方向を一律に制限する規則ではありません。たとえば`Acf\Data\FlexibleNode`が`Acf\Get\AcfGet`を保持し、`AcfGet::Flexible()`が`FlexibleNode`を返す構成は、ACF領域内で基本単位と取得口を相互に構成するものです。`Wp`と`Acf`が相互依存しているわけではなく、CoreがWp・Acfを参照しないという境界も維持されます。

## 構造・動作の検証

現存する `tests/*.php` は、WordPress・ACFのAPIをスタブに置き換えて実行する独立したPHPテストです。実際のWordPress・ACF・DB上での結合動作を確認するものではありません。現在のテストファイルを全件実行してください。構文チェックは別途行います。

```bash
for test in tests/*.php; do php "$test" || exit 1; done
find . -type f -name '*.php' -exec php -l {} \;
```

PowerShellでは、次のように全件実行できます。

```powershell
Get-ChildItem tests/*.php | ForEach-Object {
	php $_.FullName
	if ($LASTEXITCODE -ne 0) { throw "Test failed: $($_.Name)" }
}
```
