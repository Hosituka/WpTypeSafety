# 内部構造と検証

`Acf/Get/`には`WpTypeSafety\Acf\Get\AcfGet`・`AcfGetUser`・`AcfGetTerm`・`AcfDataFactory`、`Wp/Get/`には`WpTypeSafety\Wp\Get\WpGet`・`WpGetTerm`・`WpGetMenuNode`・`WpDataFactory`を置いています。名前空間には`Public`を含めず、`Data`・`Get`は含めます。ACF固有のデータクラスは`Acf/Data/`に配置し、`AcfBlockData`の名前空間は`WpTypeSafety\Acf\Data`です。

`Wp/Get/WpDataFactory.php`は通常利用向けの公開入口で、`Wp/Internal/DtoFactory.php`と`TermAdapter`へ処理を委譲します。

起動処理は領域別の入口へ分かれています。`autoload.php`はPSR-4登録、`Core/entry.php`はAutoloader、`Wp/entry.php`はCore、`Acf/entry.php`はWpを準備します。最上位`entry.php`は`Acf/entry.php`を読む全機能用の入口です。上位入口が下位入口を`require_once`するため、呼び出し側で依存順を管理する必要はありません。

```text
entry.php
  └─ Acf/entry.php
       └─ Wp/entry.php
            └─ Core/entry.php
                 └─ autoload.php
```

公開Get系は対応するAdapterへ直接委譲し、staticなClosure登録や登録順への依存を持ちません。`AcfGet`は`AcfAdapter`をインスタンスに1個保持し、`Nested()`・`Repeater()`・`Flexible()`が作る子取得口にも同じAdapterを引き継ぎます。`AcfGetUser`・`AcfGetTerm`も同じAdapterでACFの参照先IDを組み立てます。Wpのstatic取得口は、対応するInternal Adapterのstaticメソッドへ直接委譲します。

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
