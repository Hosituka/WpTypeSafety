# 内部構造と検証

`Acf/Get/`には`WpTypeSafety\Acf\Get\AcfGet`・`AcfDataFactory`、`Wp/Get/`には`WpGet`・`WpGetTerm`・`WpGetMenuNode`と対象別Factoryを置いています。WordPressの対象別Factoryは`PostFactory`・`CommentFactory`・`UserFactory`・`TermFactory`・`MenuNodeFactory`・`AttachmentFactory`です。名前空間には`Public`を含めず、`Data`・`Get`は含めます。ACF固有のデータクラスは`Acf/Data/`に配置し、`AcfBlockData`の名前空間は`WpTypeSafety\Acf\Data`です。

各対象別Factoryは通常利用向けの公開入口で、`Wp/Internal/DtoFactory.php`または対象別Adapterへ処理を委譲します。`PostFactory`・`CommentFactory`・`UserFactory`・`TermFactory`・`MenuNodeFactory`は、完成したDataを返す`From()`と、案件固有Dataの構築に使う`Members()`を提供します。

起動処理は`autoload.php`へ集約しています。`autoload.php`が`WpTypeSafety\`のPSR-4設定を登録し、Core・Wp・Acfのクラスは参照された時点で個別に読み込まれます。領域別の中継入口や、取得処理の初期登録はありません。

```text
autoload.php
  ├─ Autoload/Internal/ComposerJsonReader.php
  └─ Autoload/Psr4Autoload.php
       └─ composer.jsonのWpTypeSafety\\を登録
```

公開Get系は対応するAdapterへ直接委譲し、staticなClosure登録や登録順への依存を持ちません。`AcfGet`は`AcfAdapter`をインスタンスに1個保持し、投稿・ユーザー・ターム・ACF参照先ID・取得済み配列を共通の取得元として扱います。ユーザーとタームは同じAdapterでACFの参照先IDへ正規化します。`Nested()`・`Repeater()`・`Flexible()`・`PostFields()`・`UserFields()`・`TermFields()`が作る子取得口は、呼び出し元の派生型と同じAdapterを引き継ぎます。Wpのstatic取得口は、対応するInternal Adapterのstaticメソッドへ直接委譲します。

`Wp/Internal/DtoFactory.php`はWordPressの値をWp固有またはCore共通のDTOへ変換し、必要なResolverをDTOへ渡します。`Acf/Internal/AcfDtoFactory.php`はACFの戻り値をDTOへ変換します。ターム階層は`TermAdapter`、メニューツリーは`MenuAdapter`、ACF値の正規化は`AcfAdapter`が担当します。依存方向は`Acf → Wp → Core`および`Acf → Core`です。CoreはWp・Acfを参照しません。

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
