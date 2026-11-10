<?php
declare(strict_types=1);

namespace WpTypeSafety\Wp\Get;

use WpTypeSafety\Core\Check\ArrayGet;
use WpTypeSafety\Core\Check\ObjectGet;
use WpTypeSafety\Core\Check\TreeGet;
use WpTypeSafety\Core\Check\ValueMode;
use WpTypeSafety\Core\Check\ValueReader;
use WpTypeSafety\Core\Check\ValueRules;
use WpTypeSafety\Core\Internal\ResolvedValue;
use WpTypeSafety\Core\Internal\ValueResolver;
use WpTypeSafety\Wp\Data\CommentData;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\WpUserData;
use WpTypeSafety\Wp\Internal\MetaAdapter;
use WpTypeSafety\Wp\Internal\ReferenceAdapter;
use WpTypeSafety\Wp\Internal\WpValueRules;

/** WordPressメタと、その中に保存されたPHP木構造を同じ取得口で読み取ります。 */
final class MetaGet
{
	private ?string $metaType = null;

	private int $objectId = 0;

	private ?MetaAdapter $adapter = null;

	private mixed $treeSource = null;

	private ?TreeGet $treeGet = null;

	private ValueRules $rules;

	private ValueReader $valueReader;

	/**
	 * WPオブジェクト・対応Data・投稿IDからメタ取得口を作ります。
	 * その他の値は、取得済みPHP木構造として扱います。
	 */
	public function __construct(
		mixed $source,
		ValueMode|ValueRules|null $modeOrRules = null,
	) {
		$this->rules = WpValueRules::From($modeOrRules);
		$this->valueReader = new ValueReader($this->rules);

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

	public function WithMode(ValueMode $mode): self
	{
		return $this->metaType !== null
			? self::fromMeta($this->metaType, $this->objectId, $mode)
			: self::FromTree($this->treeSource, $mode);
	}

	public function WithRules(ValueRules $rules): self
	{
		return $this->metaType !== null
			? self::fromMeta($this->metaType, $this->objectId, $rules)
			: self::FromTree($this->treeSource, $rules);
	}

	public function Exists(int|string $segment): bool
	{
		return $this->resolve($segment)->Exists;
	}

	public function ExistsPath(int|string $segment, int|string ...$segments): bool
	{
		$resolved = $this->resolve($segment);
		if (! $resolved->Exists || $segments === [])
		{return $resolved->Exists;}

		$firstSegment = array_shift($segments);
		return ValueResolver::ResolvePath(
			$resolved->Value,
			$firstSegment,
			...$segments,
		)->Exists;
	}

	/** 保存済みの値を変換せず返します。欠損時はnullです。 */
	public function Native(int|string $segment): mixed
	{
		$resolved = $this->resolve($segment);
		return $resolved->Exists ? $resolved->Value : null;
	}

	/**
	 * メタ取得元では同じキーの複数行、木構造では指定ノードの配列を返します。
	 *
	 * @return list<mixed>
	 */
	public function NativeList(int|string $segment): array
	{
		$resolved = $this->resolveList($segment);
		return $resolved->Exists && is_array($resolved->Value)
			? array_values($resolved->Value)
			: [];
	}

	/** @param (callable(string): bool)|null $predicate */
	public function String(int|string $segment, ?callable $predicate = null): string
	{
		$resolved = $this->resolve($segment);
		return $resolved->Exists
			? ($this->valueReader->String($resolved->Value, $predicate) ?? '')
			: '';
	}

	/** @param (callable(int): bool)|null $predicate */
	public function Int(int|string $segment, ?callable $predicate = null): ?int
	{
		$resolved = $this->resolve($segment);
		return $resolved->Exists
			? $this->valueReader->Int($resolved->Value, $predicate)
			: null;
	}

	/** @param (callable(float): bool)|null $predicate */
	public function Float(int|string $segment, ?callable $predicate = null): ?float
	{
		$resolved = $this->resolve($segment);
		return $resolved->Exists
			? $this->valueReader->Float($resolved->Value, $predicate)
			: null;
	}

	/** @param (callable(bool): bool)|null $predicate */
	public function Bool(int|string $segment, ?callable $predicate = null): ?bool
	{
		$resolved = $this->resolve($segment);
		return $resolved->Exists
			? $this->valueReader->Bool($resolved->Value, $predicate)
			: null;
	}

	/**
	 * @param (callable(array<array-key, mixed>): bool)|null $predicate
	 * @return array<array-key, mixed>
	 */
	public function Array(int|string $segment, ?callable $predicate = null): array
	{
		$resolved = $this->resolve($segment);
		return $resolved->Exists
			? ($this->valueReader->Array($resolved->Value, $predicate) ?? [])
			: [];
	}

	/**
	 * @param (callable(string): bool)|null $predicate
	 * @return list<string>
	 */
	public function StringList(int|string $segment, ?callable $predicate = null): array
	{
		$resolved = $this->resolveList($segment);
		return $resolved->Exists
			? ($this->valueReader->StringList($resolved->Value, $predicate) ?? [])
			: [];
	}

	/**
	 * @param (callable(int): bool)|null $predicate
	 * @return list<int>
	 */
	public function IntList(int|string $segment, ?callable $predicate = null): array
	{
		$resolved = $this->resolveList($segment);
		return $resolved->Exists
			? ($this->valueReader->IntList($resolved->Value, $predicate) ?? [])
			: [];
	}

	/**
	 * @param (callable(float): bool)|null $predicate
	 * @return list<float>
	 */
	public function FloatList(int|string $segment, ?callable $predicate = null): array
	{
		$resolved = $this->resolveList($segment);
		return $resolved->Exists
			? ($this->valueReader->FloatList($resolved->Value, $predicate) ?? [])
			: [];
	}

	/**
	 * @param (callable(bool): bool)|null $predicate
	 * @return list<bool>
	 */
	public function BoolList(int|string $segment, ?callable $predicate = null): array
	{
		$resolved = $this->resolveList($segment);
		return $resolved->Exists
			? ($this->valueReader->BoolList($resolved->Value, $predicate) ?? [])
			: [];
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param (callable(T): bool)|null $predicate
	 * @return T|null
	 */
	public function Instance(int|string $segment, string $className, ?callable $predicate = null): ?object
	{
		$resolved = $this->resolve($segment);
		return $resolved->Exists
			? $this->valueReader->Instance($resolved->Value, $className, $predicate)
			: null;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param (callable(T): bool)|null $predicate
	 * @return list<T>
	 */
	public function InstanceList(int|string $segment, string $className, ?callable $predicate = null): array
	{
		$resolved = $this->resolveList($segment);
		return $resolved->Exists
			? ($this->valueReader->InstanceList($resolved->Value, $className, $predicate) ?? [])
			: [];
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @param (callable(T): bool)|null $predicate
	 * @return T|null
	 */
	public function BackedEnum(int|string $segment, string $enumClass, ?callable $predicate = null): ?\BackedEnum
	{
		$resolved = $this->resolve($segment);
		return $resolved->Exists
			? $this->valueReader->BackedEnum($resolved->Value, $enumClass, $predicate)
			: null;
	}

	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $enumClass
	 * @param (callable(T): bool)|null $predicate
	 * @return list<T>
	 */
	public function BackedEnumList(int|string $segment, string $enumClass, ?callable $predicate = null): array
	{
		$resolved = $this->resolveList($segment);
		return $resolved->Exists
			? ($this->valueReader->BackedEnumList($resolved->Value, $enumClass, $predicate) ?? [])
			: [];
	}

	public function AsArrayGet(): ?ArrayGet
	{
		return $this->treeGet?->AsArrayGet();
	}

	public function AsObjectGet(): ?ObjectGet
	{
		return $this->treeGet?->AsObjectGet();
	}

	public function Nested(int|string $segment): self
	{
		$resolved = $this->resolve($segment);
		return self::FromTree(
			$resolved->Exists ? $resolved->Value : null,
			$this->rules,
		);
	}

	public function NestedPath(int|string $segment, int|string ...$segments): self
	{
		$nestedGet = $this->Nested($segment);
		foreach ($segments as $nestedSegment) {
			$nestedGet = $nestedGet->Nested($nestedSegment);
		}
		return $nestedGet;
	}

	/** @return list<self> */
	public function NestedList(int|string $segment): array
	{
		$items = [];
		foreach ($this->Array($segment) as $value) {
			if (! is_array($value) && ! is_object($value)) {
				continue;
			}

			$items[] = self::FromTree($value, $this->rules);
		}
		return $items;
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
		return self::FromPost($this->Int($segment) ?? 0, $this->rules);
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
		return self::FromUser($this->Int($segment) ?? 0, $this->rules);
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
		return self::FromTerm($this->Int($segment) ?? 0, $this->rules);
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
		return self::FromComment($this->Int($segment) ?? 0, $this->rules);
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
		$this->treeSource = null;
		$this->treeGet = null;
	}

	private function useTreeSource(mixed $source): void
	{
		$this->metaType = null;
		$this->objectId = 0;
		$this->adapter = null;
		$this->treeSource = $source;
		$this->treeGet = new TreeGet($source, $this->rules);
	}

	private function resolve(int|string $segment): ResolvedValue
	{
		if ($this->adapter !== null) {
			return is_string($segment)
				? $this->adapter->Resolve($segment)
				: new ResolvedValue(Exists: false, Value: null);
		}

		return ValueResolver::Resolve($this->treeSource, $segment);
	}

	private function resolveList(int|string $segment): ResolvedValue
	{
		if ($this->adapter !== null) {
			return is_string($segment)
				? $this->adapter->ResolveList($segment)
				: new ResolvedValue(Exists: false, Value: null);
		}

		return ValueResolver::Resolve($this->treeSource, $segment);
	}
}
