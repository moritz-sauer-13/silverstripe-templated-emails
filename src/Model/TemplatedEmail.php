<?php

namespace TemplatedMails\Model;

use SilverStripe\Control\Email\Email;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\TextareaField;

/**
 * TemplatedEmail
 *
 * A helper class to send emails using the unified email template.
 *
 * This class automatically detects and uses the correct theme for template resolution,
 * making it project-independent and suitable for packaging as a module.
 *
 * The class will scan all available themes in the project and try to find the template
 * in each of them. This makes the class more flexible and project-independent, as it will
 * automatically find the template in any theme without requiring hardcoded theme names.
 */
class TemplatedEmail extends Email
{
    /**
     * Path to the email template
     *
     * @var string
     */
    private static string $HTMLTemplate = 'EmailTemplate/EmailTemplate';

    /**
     * Configuration: Skip empty values (can be overridden via YAML config)
     * @var bool
     */
    private static bool $skip_empty = true;

    /**
     * Configuration: Exact keys to exclude from output
     * @var array
     */
    private static array $excludes = [
        'SecurityID',
        'url',
        'g-recaptcha-response'
    ];

    /**
     * Configuration: Key prefixes to exclude from output
     * @var array
     */
    private static array $exclude_prefixes = [
        'action_'
    ];

    /**
     * Configuration: Separator for array values
     * @var string
     */
    private static string $array_separator = ', ';

    /**
     * Configuration: Use placeholder as label if Title is empty
     * @var bool
     */
    private static bool $label_from_placeholder = true;

    /**
     * Configuration: Remove trailing required-asterisk (*) from labels/placeholders
     * @var bool
     */
    private static bool $strip_required_asterisk = true;


    /**
     * Logo to use in the email header
     *
     * @var Image|null
     */
    protected ?Image $logo = null;

    /**
     * Greeting text
     *
     * @var string|null
     */
    protected ?string $greeting = null;

    /**
     * Title of the email
     *
     * @var string|null
     */
    protected ?string $title = null;

    /**
     * Email content
     *
     * @var string|null
     */
    protected ?string $emailContent = null;

    /**
     * Call to action button text
     *
     * @var string|null
     */
    protected ?string $callToAction = null;

    /**
     * Call to action button link
     *
     * @var string|null
     */
    protected ?string $callToActionLink = null;

    /**
     * Signature text
     *
     * @var string|null
     */
    protected ?string $signature = null;

    /**
     * Footer content
     *
     * @var string|null
     */
    protected ?string $footerContent = null;

    /**
     * Normalized form entries for template rendering
     *
     * @var ArrayList|null
     */
    protected ?ArrayList $formEntries = null;

    /**
     * Constructor
     *
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param string $body
     */
    public function __construct($from = '', $to = '', $subject = '', $body = '')
    {
        // Ensure body is a string to avoid type error in parent constructor
        $bodyString = $body ?? '';

        parent::__construct($from, $to, $subject, $bodyString);

        // Store the body content
        $this->emailContent = $bodyString;

        // Set default template
        $this->setHTMLTemplate(self::config()->get('HTMLTemplate'));
    }

    /**
     * Set the logo to use in the email header
     *
     * @param Image $logo
     */
    public function setLogo(Image $logo): void
    {
        $this->logo = $logo;
    }

    /**
     * Set the greeting text
     *
     * @param string $greeting
     */
    public function setGreeting($greeting): void
    {
        $this->greeting = $greeting;
    }

    /**
     * Set the title of the email
     *
     * @param string $title
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }

    /**
     * Set the call to action button
     *
     * @param string $text
     * @param string $link
     */
    public function setCallToAction($text, $link): void
    {
        $this->callToAction = $text;
        $this->callToActionLink = $link;
    }

    /**
     * Set the signature text
     *
     * @param string $signature
     */
    public function setSignature($signature): void
    {
        $this->signature = $signature;
    }

    /**
     * Set the footer content
     *
     * @param string $content
     */
    public function setFooterContent($content): void
    {
        $this->footerContent = $content;
    }

    /**
     * Override setBody to update emailContent property
     *
     * @param \Symfony\Component\Mime\Part\AbstractPart|string|null $body
     * @return static
     */
    public function setBody($body = null): static
    {
        // If body is a string, update emailContent
        if (is_string($body)) {
            $this->emailContent = $body;
        }

        // Call parent setBody
        return parent::setBody($body);
    }

    /**
     * Set the template to use for this email
     *
     * @param string $template
     */
    public function setTemplate($template): void
    {
        $this->setHTMLTemplate($template);
    }

    /**
     * Add custom data to the template
     *
     * @param string|array $nameOrData Name of the data item or array of data items
     * @param mixed $value Value of the data item (if $nameOrData is a string)
     * @return static
     */
    public function addCustomData($nameOrData, $value = null): static
    {
        return $this->addData($nameOrData, $value);
    }

    /**
     * Provide raw form data and an optional Form to render entries in the template.
     * No labels or options can be passed here; configuration and the form determine labels
     * and behavior (excludes, skipping empty values, etc.).
     *
     * Extension hooks:
     *  - updateFormDataInput(&$data, ?Form $form)
     *  - updateFormEntry(&$entry, string $key, array $data, ?Form $form)
     *  - updateFormEntries(&$entries, array $data, ?Form $form)
     *
     * @param array $data
     * @param Form|null $form
     * @return static
     */
    public function setFormData(array $data, ?Form $form = null): static
    {
        // Allow extensions to massage input data first
        $mods = $this->extend('updateFormDataInput', $data, $form);
        if (is_array($mods)) {
            foreach ($mods as $m) {
                if (is_array($m)) {
                    $data = $m; // take last non-null array as authoritative
                }
            }
        }

        $skipEmpty = (bool)static::config()->get('skip_empty');
        $excludes = (array)static::config()->get('excludes') ?: [];
        $excludePrefixes = (array)static::config()->get('exclude_prefixes') ?: [];
        $arraySep = (string)static::config()->get('array_separator') ?: ', ';
        $usePlaceholder = (bool)static::config()->get('label_from_placeholder');
        $stripAsterisk = (bool)static::config()->get('strip_required_asterisk');

        // Build label map and order from form if provided
        $labels = [];
        $order = [];
        $fieldMap = [];
        if ($form) {
            foreach ($form->Fields() as $field) {
                if (!method_exists($field, 'getName')) {
                    continue;
                }
                $name = $field->getName();
                if (!$name) {
                    continue;
                }
                $order[] = $name;
                $fieldMap[$name] = $field;

                $label = method_exists($field, 'Title') ? $field->Title() : null;
                if ((!$label || trim((string)$label) === '') && $usePlaceholder && method_exists($field, 'getAttribute')) {
                    $ph = (string)$field->getAttribute('placeholder');
                    if ($ph !== '') {
                        $label = $ph;
                    }
                }
                if ($stripAsterisk && is_string($label)) {
                    // Remove a trailing * and surrounding whitespace
                    $label = preg_replace('/\s*\*\s*$/', '', $label);
                }
                if ($label) {
                    $labels[$name] = (string)$label;
                }
            }
        }

        // Build the final ordered key list: form order first, then remaining data keys
        $keys = [];
        foreach ($order as $k) {
            if (array_key_exists($k, $data)) {
                $keys[] = $k;
            }
        }
        foreach ($data as $k => $_) {
            if (!in_array($k, $keys, true)) {
                $keys[] = $k;
            }
        }

        // Helper: exclusion check
        $isExcluded = function (string $key) use ($excludes, $excludePrefixes): bool {
            if (in_array($key, $excludes, true)) {
                return true;
            }
            foreach ($excludePrefixes as $prefix) {
                if ($prefix !== '' && str_starts_with($key, $prefix)) {
                    return true;
                }
            }
            return false;
        };

        // Helper: humanize key
        $humanize = function (string $key): string {
            // Convert camelCase to spaced, then replace _ and -
            $spaced = preg_replace('/(?<!^)([A-Z])/', ' $1', $key);
            $spaced = str_replace(['_', '-'], ' ', $spaced);
            $spaced = trim(preg_replace('/\s+/', ' ', $spaced));
            return ucfirst($spaced);
        };

        $entries = [];
        foreach ($keys as $key) {
            if ($isExcluded($key)) {
                continue;
            }
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $value = $data[$key];

            // Determine emptiness
            $isEmpty = false;
            if (is_array($value)) {
                $isEmpty = count($value) === 0;
            } elseif (is_bool($value)) {
                $isEmpty = false; // booleans are meaningful
            } else {
                $str = (string)$value;
                $isEmpty = trim($str) === '';
            }
            if ($skipEmpty && $isEmpty) {
                continue;
            }

            // Normalize value to string for presentation
            if (is_array($value)) {
                $value = implode($arraySep, array_map(fn($v) => (string)$v, $value));
            } elseif (is_bool($value)) {
                $value = $value ? 'Ja' : 'Nein';
            } elseif (is_object($value) && method_exists($value, '__toString')) {
                $value = (string)$value;
            } else {
                $value = (string)$value;
            }

            $label = $labels[$key] ?? $humanize($key);

            $isFreeText = false;
            if (isset($fieldMap) && isset($fieldMap[$key])) {
                $fld = $fieldMap[$key];
                if ($fld instanceof TextareaField) {
                    $isFreeText = true;
                }
            }
            if ($isFreeText) {
                // Convert various newlines to <br> for HTML output
                $value = str_replace("\\r\\n", '<br>', $value);
                $value = DBField::create_field('HTMLText', $value);
            }

            $entry = [
                'Key' => $key,
                'Label' => $label,
                'Value' => $value,
                'IsFreeText' => $isFreeText,
            ];

            // Per-entry hook
            $modsEntry = $this->extend('updateFormEntry', $entry, $key, $data, $form);
            if (is_array($modsEntry)) {
                foreach ($modsEntry as $me) {
                    if (is_array($me)) {
                        // merge but keep keys Key/Label/Value stable
                        $entry = array_merge($entry, $me);
                    }
                }
            }

            $entries[] = $entry;
        }

        // Post-process hook for the list as a whole
        $modsEntries = $this->extend('updateFormEntries', $entries, $data, $form);
        if (is_array($modsEntries)) {
            foreach ($modsEntries as $ml) {
                if (is_array($ml)) {
                    $entries = $ml; // allow full replacement
                }
            }
        }

        // Convert to ArrayList of ArrayData for templating
        $list = ArrayList::create();
        foreach ($entries as $e) {
            $list->push(ArrayData::create($e));
        }

        $this->formEntries = $list;
        return $this;
    }


    /**
     * Send the email
     *
     * @return void
     */
    public function send(): void
    {
        // Get site config for default values
        $siteConfig = SiteConfig::current_site_config();

        // Prepare template data
        $templateData = [
            'Logo' => $this->logo,
            'Greeting' => $this->greeting,
            'Title' => $this->title,
            'EmailContent' => $this->emailContent,
            'CallToAction' => $this->callToAction,
            'CallToActionLink' => $this->callToActionLink,
            'Signature' => $this->signature,
            'FooterContent' => $this->footerContent,
            'SiteConfig' => $siteConfig,
            'FormEntries' => $this->formEntries,
        ];

        // Let extensions tweak the final data passed to the template
        $modsData = $this->extend('updateEmailData', $templateData);
        if (is_array($modsData)) {
            foreach ($modsData as $md) {
                if (is_array($md)) {
                    $templateData = array_merge($templateData, $md);
                }
            }
        }

        // Set up template data
        $this->setData($templateData);

        // Call parent send method
        parent::send();
    }
}
