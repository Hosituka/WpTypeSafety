<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Get;

use WpTypeSafety\Acf\Internal\AcfAdapter;
use WpTypeSafety\Wp\Data\TermData;

/** タームを対象にしたACFフィールド取得。 */
final class AcfGetTerm extends AcfGet
{
	/** 対象なしは空の取得元として扱い、現在の投稿へフォールバックしない。 */
	public function __construct(\WP_Term|TermData|null $term, ?AcfAdapter $adapter = null)
	{
		$adapter ??= new AcfAdapter();
		if ($term === null) {
			parent::__construct([], $adapter);
			return;
		}

		parent::__construct($adapter->TermPostId($term), $adapter);
	}
}
