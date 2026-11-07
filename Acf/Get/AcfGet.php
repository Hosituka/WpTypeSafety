<?php
declare(strict_types=1);

namespace WpTypeSafety\Acf\Get;

use WpTypeSafety\Acf\Data\FlexibleNode;
use WpTypeSafety\Core\Data\ChoiceData;
use WpTypeSafety\Core\Data\FileData;
use WpTypeSafety\Core\Data\ImgData;
use WpTypeSafety\Core\Data\LinkData;
use WpTypeSafety\Core\Data\TimeData;
use WpTypeSafety\Wp\Data\PostData;
use WpTypeSafety\Wp\Data\TermData;
use WpTypeSafety\Wp\Data\WpUserData;

/** 投稿・ACF参照先ID・取得済み配列からフィールドを取得する。 */
class AcfGet
{
	/** @var \WP_Post|PostData|int|string|array<mixed>|null */
	private readonly \WP_Post|PostData|int|string|array|null $source;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): mixed */
	private static ?\Closure $raw = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): array<mixed> */
	private static ?\Closure $array = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): AcfGet */
	private static ?\Closure $nested = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): string */
	private static ?\Closure $text = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?PostData */
	private static ?\Closure $post = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): list<PostData> */
	private static ?\Closure $posts = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?TermData */
	private static ?\Closure $term = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): list<TermData> */
	private static ?\Closure $terms = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null, int): ?WpUserData */
	private static ?\Closure $user = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null, int): list<WpUserData> */
	private static ?\Closure $users = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?LinkData */
	private static ?\Closure $link = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?float */
	private static ?\Closure $number = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?int */
	private static ?\Closure $int = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null, string|array): ?ImgData */
	private static ?\Closure $img = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?string */
	private static ?\Closure $imgUrl = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?FileData */
	private static ?\Closure $file = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?string */
	private static ?\Closure $fileUrl = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null, string|array): list<ImgData> */
	private static ?\Closure $gallery = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): list<AcfGet> */
	private static ?\Closure $repeater = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): list<FlexibleNode> */
	private static ?\Closure $flexible = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): list<ChoiceData> */
	private static ?\Closure $checkbox = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?ChoiceData */
	private static ?\Closure $radio = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?ChoiceData */
	private static ?\Closure $buttonGroup = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?bool */
	private static ?\Closure $bool = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?\DateTimeImmutable */
	private static ?\Closure $date = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?\DateTimeImmutable */
	private static ?\Closure $dateTime = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?TimeData */
	private static ?\Closure $time = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?string */
	private static ?\Closure $radioValue = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?string */
	private static ?\Closure $radioLabel = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?string */
	private static ?\Closure $buttonGroupValue = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?string */
	private static ?\Closure $buttonGroupLabel = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): list<string> */
	private static ?\Closure $checkboxValues = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): list<string> */
	private static ?\Closure $checkboxLabels = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?ChoiceData */
	private static ?\Closure $select = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?string */
	private static ?\Closure $selectValue = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?string */
	private static ?\Closure $selectLabel = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): list<string> */
	private static ?\Closure $selectValues = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): list<string> */
	private static ?\Closure $selectLabels = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): list<ChoiceData> */
	private static ?\Closure $selectChoices = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): ?string */
	private static ?\Closure $url = null;

	/** @var null|\Closure(string, \WP_Post|PostData|int|string|array|null): list<string> */
	private static ?\Closure $galleryUrl = null;

	/** @param \WP_Post|PostData|int|string|array<mixed>|null $source */
	public function __construct(\WP_Post|PostData|int|string|array|null $source = null)
	{
		$this->source = $source;
	}

	/**
	 * @internal
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): mixed $raw
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): array<mixed> $array
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): string $text
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?PostData $post
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): list<PostData> $posts
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?TermData $term
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): list<TermData> $terms
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null, int): ?WpUserData $user
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null, int): list<WpUserData> $users
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?LinkData $link
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?float $number
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?int $int
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null, string|array): ?ImgData $img
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?string $imgUrl
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?FileData $file
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?string $fileUrl
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null, string|array): list<ImgData> $gallery
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): list<AcfGet> $repeater
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): list<FlexibleNode> $flexible
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): list<ChoiceData> $checkbox
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?ChoiceData $radio
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?ChoiceData $buttonGroup
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?bool $bool
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?\DateTimeImmutable $date
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?\DateTimeImmutable $dateTime
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?TimeData $time
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?string $radioValue
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?string $radioLabel
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?string $buttonGroupValue
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?string $buttonGroupLabel
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): list<string> $checkboxValues
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): list<string> $checkboxLabels
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?ChoiceData $select
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?string $selectValue
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?string $selectLabel
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): list<string> $selectValues
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): list<string> $selectLabels
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): list<ChoiceData> $selectChoices
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): ?string $url
	 * @param \Closure(string, \WP_Post|PostData|int|string|array|null): list<string> $galleryUrl
	 * @param null|\Closure(string, \WP_Post|PostData|int|string|array|null): AcfGet $nested
	 */
	public static function Register(
		\Closure $raw,
		\Closure $array,
		\Closure $text,
		\Closure $post,
		\Closure $posts,
		\Closure $term,
		\Closure $terms,
		\Closure $user,
		\Closure $users,
		\Closure $link,
		\Closure $number,
		\Closure $int,
		\Closure $img,
		\Closure $imgUrl,
		\Closure $file,
		\Closure $fileUrl,
		\Closure $gallery,
		\Closure $repeater,
		\Closure $flexible,
		\Closure $checkbox,
		\Closure $radio,
		\Closure $buttonGroup,
		\Closure $bool,
		\Closure $date,
		\Closure $dateTime,
		\Closure $time,
		\Closure $radioValue,
		\Closure $radioLabel,
		\Closure $buttonGroupValue,
		\Closure $buttonGroupLabel,
		\Closure $checkboxValues,
		\Closure $checkboxLabels,
		\Closure $select,
		\Closure $selectValue,
		\Closure $selectLabel,
		\Closure $selectValues,
		\Closure $selectLabels,
		\Closure $selectChoices,
		\Closure $url,
		\Closure $galleryUrl,
		?\Closure $nested = null,
	): void {
		if (self::$array !== null) {
			throw new \LogicException('AcfGet implementation is already registered.');
		}
		self::$raw = $raw;
		self::$array = $array;
		self::$text = $text;
		self::$post = $post;
		self::$posts = $posts;
		self::$term = $term;
		self::$terms = $terms;
		self::$user = $user;
		self::$users = $users;
		self::$link = $link;
		self::$number = $number;
		self::$int = $int;
		self::$img = $img;
		self::$imgUrl = $imgUrl;
		self::$file = $file;
		self::$fileUrl = $fileUrl;
		self::$gallery = $gallery;
		self::$repeater = $repeater;
		self::$flexible = $flexible;
		self::$checkbox = $checkbox;
		self::$radio = $radio;
		self::$buttonGroup = $buttonGroup;
		self::$bool = $bool;
		self::$date = $date;
		self::$dateTime = $dateTime;
		self::$time = $time;
		self::$radioValue = $radioValue;
		self::$radioLabel = $radioLabel;
		self::$buttonGroupValue = $buttonGroupValue;
		self::$buttonGroupLabel = $buttonGroupLabel;
		self::$checkboxValues = $checkboxValues;
		self::$checkboxLabels = $checkboxLabels;
		self::$select = $select;
		self::$selectValue = $selectValue;
		self::$selectLabel = $selectLabel;
		self::$selectValues = $selectValues;
		self::$selectLabels = $selectLabels;
		self::$selectChoices = $selectChoices;
		self::$url = $url;
		self::$galleryUrl = $galleryUrl;
		self::$nested = $nested;
	}

	/** 派生クラスが案件固有・拡張フィールドの変換に使用する生値を返す。 */
	protected function raw(string $fieldName): mixed
	{
		$resolver = self::$raw ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** @return array<mixed> */
	public function Array(string $fieldName): array
	{
		$resolver = self::$array ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** グループ内のフィールドを取得する。未設定・配列以外は空の取得元になる。 */
	public function Nested(string $fieldName): self
	{
		if (self::$nested === null) {
			return new self($this->Array($fieldName));
		}

		return (self::$nested)($fieldName, $this->source);
	}

	public function Text(string $fieldName): string
	{
		$resolver = self::$text ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function Post(string $fieldName): ?PostData
	{
		$resolver = self::$post ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** @return list<PostData> */
	public function Posts(string $fieldName): array
	{
		$resolver = self::$posts ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function Term(string $fieldName): ?TermData
	{
		$resolver = self::$term ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** @return list<TermData> */
	public function Terms(string $fieldName): array
	{
		$resolver = self::$terms ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** 単一選択のタームを参照先として、そのACFフィールドを取得する。 */
	public function TermFields(string $fieldName): AcfGetTerm
	{
		return new AcfGetTerm($this->Term($fieldName));
	}

	public function User(string $fieldName, int $avatarSize = 96): ?WpUserData
	{
		$resolver = self::$user ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source, $avatarSize);
	}

	/** @return list<WpUserData> */
	public function Users(string $fieldName, int $avatarSize = 96): array
	{
		$resolver = self::$users ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source, $avatarSize);
	}

	/** 単一選択のユーザーを参照先として、そのACFフィールドを取得する。 */
	public function UserFields(string $fieldName): AcfGetUser
	{
		return new AcfGetUser($this->User($fieldName));
	}

	/** HTTP(S)の絶対URLを返す。未入力・不正値はnull。エスケープは描画側で行う。 */
	public function Url(string $fieldName): ?string
	{
		$resolver = self::$url ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function Link(string $fieldName): ?LinkData
	{
		$resolver = self::$link ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function Number(string $fieldName): ?float
	{
		$resolver = self::$number ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function Int(string $fieldName): ?int
	{
		$resolver = self::$int ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function Img(string $fieldName, string|array $size = 'full'): ?ImgData
	{
		$resolver = self::$img ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source, $size);
	}

	public function ImgUrl(string $fieldName): ?string
	{
		$resolver = self::$imgUrl ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function File(string $fieldName): ?FileData
	{
		$resolver = self::$file ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function FileUrl(string $fieldName): ?string
	{
		$resolver = self::$fileUrl ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** @return list<ImgData> */
	public function Gallery(string $fieldName, string|array $size = 'full'): array
	{
		$resolver = self::$gallery ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source, $size);
	}

	/** @return list<string> */
	public function GalleryUrl(string $fieldName): array
	{
		$resolver = self::$galleryUrl ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** @return list<AcfGet> */
	public function Repeater(string $fieldName): array
	{
		$resolver = self::$repeater ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** @return list<FlexibleNode> */
	public function Flexible(string $fieldName): array
	{
		$resolver = self::$flexible ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** @return list<ChoiceData> */
	public function Checkbox(string $fieldName): array
	{
		$resolver = self::$checkbox ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function Radio(string $fieldName): ?ChoiceData
	{
		$resolver = self::$radio ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function RadioValue(string $fieldName): ?string
	{
		$resolver = self::$radioValue ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function RadioLabel(string $fieldName): ?string
	{
		$resolver = self::$radioLabel ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function ButtonGroup(string $fieldName): ?ChoiceData
	{
		$resolver = self::$buttonGroup ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function ButtonGroupValue(string $fieldName): ?string
	{
		$resolver = self::$buttonGroupValue ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function ButtonGroupLabel(string $fieldName): ?string
	{
		$resolver = self::$buttonGroupLabel ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** @return list<string> */
	public function CheckboxValues(string $fieldName): array
	{
		$resolver = self::$checkboxValues ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** @return list<string> */
	public function CheckboxLabels(string $fieldName): array
	{
		$resolver = self::$checkboxLabels ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function Select(string $fieldName): ?ChoiceData
	{
		$resolver = self::$select ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function SelectValue(string $fieldName): ?string
	{
		$resolver = self::$selectValue ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function SelectLabel(string $fieldName): ?string
	{
		$resolver = self::$selectLabel ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** @return list<string> */
	public function SelectValues(string $fieldName): array
	{
		$resolver = self::$selectValues ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** @return list<string> */
	public function SelectLabels(string $fieldName): array
	{
		$resolver = self::$selectLabels ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** @return list<ChoiceData> */
	public function SelectChoices(string $fieldName): array
	{
		$resolver = self::$selectChoices ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	public function Bool(string $fieldName): ?bool
	{
		$resolver = self::$bool ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** Date Pickerをサイトタイムゾーンの00:00:00として返す。 */
	public function Date(string $fieldName): ?\DateTimeImmutable
	{
		$resolver = self::$date ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** Date Time Pickerをサイトタイムゾーンの日時として返す。 */
	public function DateTime(string $fieldName): ?\DateTimeImmutable
	{
		$resolver = self::$dateTime ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

	/** Time Pickerを日付・タイムゾーンを含まない時刻として返す。 */
	public function Time(string $fieldName): ?TimeData
	{
		$resolver = self::$time ?? throw new \LogicException('AcfGet implementation is not registered.');
		return $resolver($fieldName, $this->source);
	}

}
