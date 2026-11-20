# リリースノート

## 未リリース

### 公開APIの統一

- MetaGetのPost・User・Term・Commentは、末尾の`dataClass`で派生Dataを指定できます。
- クラス指定はメタ値の読み取り前に検証します。
- 投稿タイプとタクソノミーの登録定義は`PostTypeGet::ByName()`・`TaxonomyGet::ByName()`で取得します。

この変更に対するタグ・バージョン番号はまだ付けていません。

### Rootsの取得契約

- `TermGet::Roots()`の第2引数`dataClass`は必須です。標準型が必要なら`TermData::class`を明示します。
- 親なしを含む全ルートを現在のWordPress階層から取得し、指定クラスで生成し直します。戻り値はlist<T>であり、入力Dataの型との和ではありません。
- 入力インスタンスと編集済みPropsは引き継ぎません。取得・変換失敗は要素除外とし、入力へフォールバックしません。重複除去は維持します。
- `Deepest()`・`Shallowest()`は入力インスタンスを選別します。
