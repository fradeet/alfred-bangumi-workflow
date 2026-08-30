<?php

/**
 * Base value object for Alfred Text View JSON structures.
 *
 * Null properties are omitted from the JSON output; false, zero, and empty
 * values are retained because they can have meaning in Alfred's JSON format.
 */
class AlfredTVBase implements JsonSerializable
{
    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $properties = [];

        foreach (get_object_vars($this) as $name => $value) {
            if (is_string($name) && null !== $value) {
                $properties[$name] = $value;
            }
        }

        return $properties;
    }
}

/**
 * Top-level response used to populate an Alfred Text View.
 *
 * The response contains the text shown in the view. Optional session variables
 * and rerun behaviour work in the same way as their Script Filter counterparts.
 *
 * @author fradeet
 *
 * @see https://www.alfredapp.com/help/workflows/user-interface/text/json/ Alfred Text View JSON format
 *
 * @property string                    $response     Text displayed in the Text View.
 * @property null|array<string, mixed> $variables    Session variables available to downstream objects
 *                                                   and subsequent runs.
 * @property null|float                $rerun        Automatic rerun interval from 0.1 to 5.0 seconds.
 * @property null|string               $footer       Text displayed in the window footer.
 * @property null|bool                 $actionoutput Close the Text View and send response to the next object.
 * @property null|AlfredTVBehaviour    $behaviour    How the view updates after each script rerun.
 *
 * @version 1.0.0
 */
class AlfredTV extends AlfredTVBase
{
    /** @param null|array<string, mixed> $variables */
    public function __construct(
        public string $response,
        public ?array $variables = null,
        public ?float $rerun = null,
        public ?string $footer = null,
        public ?bool $actionoutput = null,
        public ?AlfredTVBehaviour $behaviour = null,
    ) {}
}

/**
 * Controls how an Alfred Text View updates and handles its input field.
 *
 * @property null|AlfredTVBehaviourResponse   $response   How new response text updates the existing content.
 * @property null|AlfredTVBehaviourScroll     $scroll     Where the view scrolls after an update.
 * @property null|AlfredTVBehaviourInputField $inputfield What happens to the input field after actioning it.
 */
class AlfredTVBehaviour extends AlfredTVBase
{
    public function __construct(
        public ?AlfredTVBehaviourResponse $response = null,
        public ?AlfredTVBehaviourScroll $scroll = null,
        public ?AlfredTVBehaviourInputField $inputfield = null,
    ) {}
}

/** How new response text updates the existing Text View content. */
enum AlfredTVBehaviourResponse: string
{
    /** Replace all content with the new response. */
    case Replace = 'replace';

    /** Add the new response to the bottom of the view. */
    case Append = 'append';

    /** Add the new response to the top of the view. */
    case Prepend = 'prepend';

    /** Replace the content produced by the previous response. */
    case ReplaceLast = 'replacelast';
}

/** Where the Text View scrolls after receiving a response. */
enum AlfredTVBehaviourScroll: string
{
    /** Scroll to the start of the new response. */
    case Auto = 'auto';

    /** Scroll to the start of the Text View. */
    case Start = 'start';

    /** Scroll to the end of the Text View. */
    case End = 'end';
}

/** What happens to the Text View input field after actioning it. */
enum AlfredTVBehaviourInputField: string
{
    /** Erase the input field text. */
    case Clear = 'clear';

    /** Select the input field text. */
    case Select = 'select';
}
