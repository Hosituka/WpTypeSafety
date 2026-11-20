# リリースノート

## 未リリース

### 公開APIの統一

- MetaGetのPost・User・Term・Commentへ末尾のdataClass引数を追加。既存の位置引数は維持し、派生Dataを指定できるようにしました。
- クラス指定はメタ値の読み取り前に検証します。ID解決、ネイティブ取得、メタReader取得の契約は維持します。
- **破壊的変更**：PostTypeGet::FromName・TaxonomyGet::FromNameをByNameへ改名しました。旧名は削除し、非推奨エイリアスも設けません。
- 移行時はFromName(...)をByName(...)へ置き換えてください。引数順・dataClass・戻り値・例外規則は維持します。Internal Adapterの名前は変更しません。

この変更に対するタグ・バージョン番号はまだ付けていません。

### Rootsの取得契約

- **破壊的変更**：TermGet::Rootsの第2引数dataClassを必須にしました。標準型が必要ならTermData::classを明示してください。
- 親なしを含む全ルートを現在のWordPress階層から取得し、指定クラスで生成し直します。戻り値はlist<T>であり、入力Dataの型との和ではありません。
- 入力インスタンスと編集済みPropsは引き継ぎません。取得・変換失敗は要素除外とし、入力へフォールバックしません。重複除去は維持します。
- Deepest/Shallowestは従来どおり入力インスタンスを選別します。
