<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Core\Check\TreeGet;
use WpTypeSafety\Core\Check\ValueMode;
use WpTypeSafety\Core\Check\ValueRules;
use WpTypeSafety\Internal\TreeTypeReader\ResolvedValue;
use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Internal\MetaAdapter;
use WpTypeSafety\Wp\Internal\ReferenceAdapter;
use WpTypeSafety\Wp\Internal\WpValueRules;

/** WordPressメタと、その中に保存されたPHP木構造を同じ取得口で読み取ります。 */
final class MetaGet extends TreeGet
{
	private ?string $metaType = null;

	private int $objectId = 0;

	private ?MetaAdapter $adapter = null;

	/**
	 * WPオブジェクト・対応Data・投稿IDからメタ取得口を作ります。
	 * その他の値は、取得済みPHP木構造として扱います。
	 */
	public function __construct(
		mixed $source,
		ValueMode|ValueRules|null $modeOrRules = null,
	) {
		parent::__construct(null, WpValueRules::From($modeOrRules));

		if ($source instanceof \WP_Post || $source instanceof PostData || is_int($source)) {
			$this->useMetaSource(
				'post',
				$source instanceof \WP_Post
					? (int) $source->ID
					: ($source instanceof PostData ? $source->PostId : $source),
			);
			return;
		}

		if ($source instanceof \WP_Term || $source instanceof TermData) {
			$this->useMetaSource(
				'term',
				$source instanceof \WP_Term ? (int) $source->term_id : $source->Id,
			);
			return;
		}

		if ($source instanceof \WP_User || $source instanceof WpUserData) {
			$this->useMetaSource(
				'user',
				$source instanceof \WP_User ? (int) $source->ID : $source->Id,
			);
			return;
		}

		if ($source instanceof \WP_Comment || $source instanceof CommentData) {
			$this->useMetaSource(
				'comment',
				$source instanceof \WP_Comment ? (int) $source->comment_ID : $source->Id,
			);
			return;
		}

		$this->useTreeSource($source);
	}

	public static function FromPost(
		\WP_Post|PostData|int $post,
		ValueMode|ValueRules|null $modeOrRules = null,
	): self {
		$postId = $post instanceof \WP_Post
			? (int) $post->ID
			: ($post instanceof PostData ? $post->PostId : $post);
		return self::fromMeta('post', $postId, $modeOrRules);
	}

	public static function FromTerm(
		\WP_Term|TermData|int $term,
		ValueMode|ValueRules|null $modeOrRules = null,
	): self {
		$termId = $term instanceof \WP_Term
			? (int) $term->term_id
			: ($term instanceof TermData ? $term->Id : $term);
		return self::fromMeta('term', $termId, $modeOrRules);
	}

	public static function FromUser(
		\WP_User|WpUserData|int $user,
		ValueMode|ValueRules|null $modeOrRules = null,
	): self {
		$userId = $user instanceof \WP_User
			? (int) $user->ID
			: ($user instanceof WpUserData ? $user->Id : $user);
		return self::fromMeta('user', $userId, $modeOrRules);
	}

	public static function FromComment(
		\WP_Comment|CommentData|int $comment,
		ValueMode|ValueRules|null $modeOrRules = null,
	): self {
		$commentId = $comment instanceof \WP_Comment
			? (int) $comment->comment_ID
			: ($comment instanceof CommentData ? $comment->Id : $comment);
		return self::fromMeta('comment', $commentId, $modeOrRules);
	}

	/** 取得済みの配列・オブジェクトをWordPress向けの規則で読み取ります。 */
	public static function FromTree(
		mixed $source,
		ValueMode|ValueRules|null $modeOrRules = null,
	): self {
		$instance = new self([], $modeOrRules);
		$instance->useTreeSource($source);
		return $instance;
	}

	public function ExistsPath(int|string $segment, int|string ...$segments): bool
	{
		$reader = $this;
		foreach ([$segment, ...$segments] as $pathSegment) {
			$resolved = $reader->resolveValue($pathSegment);
			if (! $resolved->Exists)
			{return false;}

			$reader = $reader->createChild($resolved->Value);
		}

		return true;
	}

	/** 保存済みの値を変換せず返します。欠損時はnullです。 */
	public function Native(int|string $segment): mixed
	{
		$resolved = $this->resolveValue($segment);
		return $resolved->Exists ? $resolved->Value : null;
	}

	/**
	 * メタ取得元では同じキーの複数行、木構造では指定ノードの配列を返します。
	 *
	 * @return list<mixed>
	 */
	public function NativeList(int|string $segment): array
	{
		$resolved = $this->resolveListValue($segment);
		return $resolved->Exists && is_array($resolved->Value)
			? array_values($resolved->Value)
			: [];
	}

	public function Post(int|string $segment): ?PostData
	{
		return ReferenceAdapter::Post($this->Int($segment) ?? 0);
	}

	public function WpPost(int|string $segment): ?\WP_Post
	{
		return ReferenceAdapter::WpPost($this->Int($segment));
	}

	public function PostMeta(int|string $segment): self
	{
		return self::FromPost($this->Int($segment) ?? 0, $this->publicRulesSnapshot());
	}

	public function User(int|string $segment, int $avatarSize = 96): ?WpUserData
	{
		return ReferenceAdapter::User($this->Int($segment) ?? 0, $avatarSize);
	}

	public function WpUser(int|string $segment): ?\WP_User
	{
		return ReferenceAdapter::WpUser($this->Int($segment));
	}

	public function UserMeta(int|string $segment): self
	{
		return self::FromUser($this->Int($segment) ?? 0, $this->publicRulesSnapshot());
	}

	public function Term(int|string $segment, ?string $taxonomy = null): ?TermData
	{
		return ReferenceAdapter::Term($this->Int($segment) ?? 0, $taxonomy);
	}

	public function WpTerm(int|string $segment, ?string $taxonomy = null): ?\WP_Term
	{
		return ReferenceAdapter::WpTerm($this->Int($segment), $taxonomy);
	}

	public function TermMeta(int|string $segment): self
	{
		return self::FromTerm($this->Int($segment) ?? 0, $this->publicRulesSnapshot());
	}

	public function Comment(int|string $segment, int $avatarSize = 96): ?CommentData
	{
		return ReferenceAdapter::Comment($this->Int($segment) ?? 0, $avatarSize);
	}

	public function WpComment(int|string $segment): ?\WP_Comment
	{
		return ReferenceAdapter::WpComment($this->Int($segment));
	}

	public function CommentMeta(int|string $segment): self
	{
		return self::FromComment($this->Int($segment) ?? 0, $this->publicRulesSnapshot());
	}

	private static function fromMeta(
		string $metaType,
		int $objectId,
		ValueMode|ValueRules|null $modeOrRules,
	): self {
		$instance = new self([], $modeOrRules);
		$instance->useMetaSource($metaType, $objectId);
		return $instance;
	}

	private function useMetaSource(string $metaType, int $objectId): void
	{
		$this->metaType = $metaType;
		$this->objectId = $objectId;
		$this->adapter = new MetaAdapter($metaType, $objectId);
		$this->replaceSource(null);
	}

	private function useTreeSource(mixed $source): void
	{
		$this->metaType = null;
		$this->objectId = 0;
		$this->adapter = null;
		$this->replaceSource($source);
	}

	protected function resolveValue(int|string $segment): ResolvedValue
	{
		if ($this->adapter !== null) {
			return is_string($segment)
				? $this->adapter->Resolve($segment)
				: new ResolvedValue(Exists: false, Value: null);
		}

		return parent::resolveValue($segment);
	}

	protected function resolveListValue(int|string $segment): ResolvedValue
	{
		if ($this->adapter !== null) {
			return is_string($segment)
				? $this->adapter->ResolveList($segment)
				: new ResolvedValue(Exists: false, Value: null);
		}

		return parent::resolveListValue($segment);
	}

	protected function createChild(mixed $source): static
	{
		$child = parent::createChild($source);
		$child->metaType = null;
		$child->objectId = 0;
		$child->adapter = null;
		return $child;
	}
}
