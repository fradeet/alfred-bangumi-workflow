<?php

/**
 * Base value object for Alfred Script Filter JSON structures.
 *
 * Null properties are omitted from the JSON output; false, zero, and empty
 * values are retained because they can have meaning in Alfred's JSON format.
 */
class AlfredSFBase implements JsonSerializable
{
    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return array_filter(get_object_vars($this), fn ($v) => null !== $v);
    }
}

/**
 * Top-level response returned by an Alfred Script Filter.
 *
 * A response must contain an items array, which may be empty. Session
 * variables are passed back to later runs of the same Script Filter session.
 *
 * @author fradeet
 *
 * @see https://www.alfredapp.com/help/workflows/inputs/script-filter/json/ Alfred Script Filter JSON format
 *
 * @property list<AlfredSFItem>        $items         Result rows displayed by Alfred.
 * @property null|array<string, mixed> $variables     Session variables available to downstream objects
 *                                                    and subsequent runs.
 * @property null|string               $rerun         Automatic rerun interval. Alfred expects a JSON number from 0.1 to 5.0 seconds.
 * @property null|AlfredSFCache        $cache         Result-cache configuration, available in Alfred 5.5 and later.
 * @property null|bool                 $skipknowledge Preserve the supplied order instead of applying Alfred's learned result ordering.
 *
 * @version 1.0.1
 */
class AlfredSF extends AlfredSFBase
{
    public function __construct(
        public array $items,
        public ?array $variables = null,
        public ?string $rerun = null,
        public ?AlfredSFCache $cache = null,
        public ?bool $skipknowledge = null,
    ) {}
}

/**
 * A single result row in an Alfred Script Filter response.
 *
 * Only the title is required by Alfred. Supplying a stable UID lets Alfred
 * learn the result's ranking; omit it when the returned order must be kept.
 *
 * @property string                                                 $title        Primary text displayed in the result row; required and non-empty.
 * @property null|AlfredSFItemAction|array<array-key, mixed>|string $action       Universal Action content;
 *                                                                                overrides arg for that action.
 * @property null|list<string>|string                               $arg          Value passed to the connected workflow action when selected.
 * @property null|string                                            $autocomplete Text inserted into Alfred's search field when autocompleted.
 * @property null|AlfredSFItemIcon                                  $icon         Result icon; relative paths resolve from the workflow root.
 * @property null|string                                            $match        Text used instead of title when Alfred Filters Results is enabled.
 * @property null|array<string, array<string, mixed>>               $mods         Modifier-key overrides for cmd, alt, ctrl,
 *                                                                                shift, fn, or combinations.
 * @property null|string                                            $quicklookurl Quick Look URL or path; Alfred falls back to arg when omitted.
 * @property null|string                                            $subtitle     Secondary text displayed below the title.
 * @property null|AlfredSFItemType                                  $type         Controls whether Alfred treats the result as an item or file.
 * @property null|AlfredSFItemText                                  $text         Text used for Copy (Command-C) and Large Type (Command-L).
 * @property null|string                                            $uid          Stable identifier used by Alfred to learn result ordering.
 * @property null|array<string, mixed>                              $variables    Variables emitted when selected; item values override
 *                                                                                session values.
 * @property null|bool                                              $valid        Whether Return can action the item; defaults to true in Alfred.
 */
class AlfredSFItem extends AlfredSFBase
{
    public function __construct(
        public string $title,
        public AlfredSFItemAction|array|string|null $action = null,
        public array|string|null $arg = null,
        public ?string $autocomplete = null,
        public ?AlfredSFItemIcon $icon = null,
        public ?string $match = null,
        public ?array $mods = null,
        public ?string $quicklookurl = null,
        public ?string $subtitle = null,
        public ?AlfredSFItemType $type = null,
        public ?AlfredSFItemText $text = null,
        public ?string $uid = null,
        public ?array $variables = null,
        public ?bool $valid = null,
    ) {}
}

/**
 * Typed Universal Action content for a result.
 *
 * Use auto to let Alfred infer the content type, or text, url, and file to
 * explicitly identify it.
 *
 * @property null|list<string>|string $text Text content.
 * @property null|string              $url  URL content.
 * @property null|string              $file File-path content.
 * @property null|string              $auto Content whose type Alfred should infer.
 */
class AlfredSFItemAction extends AlfredSFBase
{
    public function __construct(
        public array|string|null $text = null,
        public ?string $url = null,
        public ?string $file = null,
        public ?string $auto = null,
    ) {}
}

/**
 * Configuration for Alfred's automatic Script Filter result cache.
 *
 * @property int       $seconds     Cache lifetime from 5 to 86400 seconds (24 hours).
 * @property null|bool $loosereload Show cached data first and refresh stale results in the background.
 */
class AlfredSFCache extends AlfredSFBase
{
    public function __construct(
        public int $seconds,
        public ?bool $loosereload = null,
    ) {}
}

/**
 * Text used when the user copies a result or displays it in Large Type.
 *
 * @property string $copy      Text copied with Command-C.
 * @property string $largetype Text displayed with Command-L.
 */
class AlfredSFItemText extends AlfredSFBase
{
    public function __construct(
        public string $copy,
        public string $largetype,
    ) {}
}

/** How Alfred should interpret and validate a result item. */
enum AlfredSFItemType: string
{
    /** A normal, non-file result. */
    case Default = 'default';

    /** A file result; Alfred verifies that the path exists before displaying it. */
    case File = 'file';

    /** A file result for which Alfred skips the path-existence check. */
    case FileSkipcheck = 'file:skipcheck';
}

/**
 * Icon displayed alongside a Script Filter result.
 *
 * @property string      $path Image path, file path, or UTI depending on type.
 * @property null|string $type Use fileicon for a path's icon or filetype for a UTI; omit for an image path.
 */
class AlfredSFItemIcon extends AlfredSFBase
{
    public function __construct(
        public string $path,
        public ?string $type = null,
    ) {}
}

define(
    'RETURN_ERROR_ALFRED',
    new AlfredSF(
        items: [
            new AlfredSFItem(
                title: 'Unable to Load Results',
                subtitle: 'Open the debugger and try again',
                valid: false,
            ),
        ],
    ),
);
