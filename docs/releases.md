# リリースノート

## v0.10.0（2026-11-21）

v0.9.0からの更新です。公開APIの破壊的変更を含みます。

### WordPress Dataの生成とメニューAPI

- 継承可能なWordPress Dataは、finalな`From()`から`getProps()`・構築・`initialize()`の順で生成します。追加値やPropsの調整はprotectedフックで実装します。
- Get・Query・ACF参照での派生Data指定を、クロージャー方式から`dataClass`のクラス名指定へ統一しました。旧形式の引数や直接newは、新しい生成契約へ移行してください。
- メニュー全体の取得を`MenuGet`へ、取得済みツリーの探索を`MenuData`へ分離しました。`MenuNodeGet`は廃止しました。項目の変換は`MenuNodeData::From()`を使用します。
- 具体的な生成・継承方法は[WordPress Data生成API](wp-data-generation.md)、取得・探索は[WordPress API](wordpress.md)を参照してください。

### 公開APIの統一

- MetaGetのPost・User・Term・Commentは、末尾の`dataClass`で派生Dataを指定できます。
- クラス指定はメタ値の読み取り前に検証します。
- 投稿タイプとタクソノミーの登録定義は`PostTypeGet::ByName()`・`TaxonomyGet::ByName()`で取得します。

旧`PostTypeGet::FromName()`・`TaxonomyGet::FromName()`は廃止しました。互換エイリアスは提供しません。

### Rootsの取得契約

- `TermGet::Roots()`の第2引数`dataClass`は必須です。標準型が必要なら`TermData::class`を明示します。
- 親なしを含む全ルートを現在のWordPress階層から取得し、指定クラスで生成し直します。戻り値はlist<T>であり、入力Dataの型との和ではありません。
- 入力インスタンスと編集済みPropsは引き継ぎません。取得・変換失敗は要素除外とし、入力へフォールバックしません。重複除去は維持します。
- `Deepest()`・`Shallowest()`は入力インスタンスを選別します。
