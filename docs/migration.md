# 旧APIからの移行

## 旧取得口からの移行

- 値形式の`Radio('field')?->Value`は`RadioValue('field')`へ変更します。
- ラベル形式の`Radio('field')?->Label`は`RadioLabel('field')`へ変更します。
- 値形式・ラベル形式の`Checkbox()`は、それぞれ`CheckboxValues()`・`CheckboxLabels()`へ変更します。
- 両方形式の`Radio()`・`Checkbox()`は引き続きDTOを返します。
- 旧`$returnFormat`引数は廃止しました。対応する取得メソッドを使用してください。

## Data・Get分割への移行

旧名前空間の互換エイリアスは提供しません。利用側の`use`と、直接ファイルを読み込んでいる場合のパスを更新してください。

| 変更前 | 変更後 |
|---|---|
| `WpTypeSafety\Core\Data\PostData`などWordPress固有の5型 | `WpTypeSafety\Wp\Data\PostData`など |
| `WpTypeSafety\Wp\WpGet`など公開取得API | `WpTypeSafety\Wp\Get\WpGet`など |
| `WpTypeSafety\Acf\AcfGet`など公開取得API | `WpTypeSafety\Acf\Get\AcfGet`など |
| `ArrayGet->PostData('post')` | `ArrayGet->Instance('post', PostData::class)` |
| `ArrayGet->PostDataArray('posts')` | `ArrayGet->InstanceArray('posts', PostData::class)` |

`TermData`・`WpUserData`・`MenuNodeData`のArrayGet専用メソッドも同様に置き換えます。既存メソッド本体の契約やDTOのプロパティ名は、今回の移行では変更しません。

ほかの取得口の変更点は[ACFの取得API](acf.md)の各節を参照してください。
