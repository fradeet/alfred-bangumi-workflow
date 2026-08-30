# Project Organization Conventions

## Two-Layer Architecture

Keep the call direction strictly one-way:

```text
Alfred -> operation-specific Alfred adapter -> core class
```

### 1. Alfred Adapter Layer

- Put Alfred-facing executable PHP scripts in `workflow/src/AlfredAdapter/`.
- Provide one adapter for each business operation that Alfred can invoke. Use a PascalCase filename matching the operation, for example `workflow/src/AlfredAdapter/DailyBroadcast.php`.
- Make each adapter directly executable with a PHP shebang so Alfred can use it as an External Script input file.
- Read Alfred user input and Alfred environment variables in the operation's adapter. Do not use a shared CLI task dispatcher.
- Keep reusable Alfred response value objects in the `Alfred\Workflow\AlfredAdapter` namespace and follow PSR-4: each class name must match its filename.
- Call core classes from the adapter and convert their plain PHP return values into the format Alfred requires.
- Encode JSON with `JSON_THROW_ON_ERROR` and write only the Alfred response to standard output. Send diagnostics to standard error.
- Catch operation failures, return valid JSON for that adapter's Alfred object type, and exit with a non-zero status.
- Do not put reusable business rules in an adapter.

### 2. Core Layer

- Keep all logic that has no direct dependency on Alfred in `workflow/src/`, outside `workflow/src/AlfredAdapter/`.
- Place each core class in the `Alfred\Workflow` namespace and follow PSR-4: the class name must match the filename.
- Core classes must not read Alfred environment variables or depend on Alfred user data implicitly. Pass required values to them explicitly.
- Core classes must return plain PHP values. They must not contain Alfred response structures, JSON fields, or console output logic.

## Loading and Running

- Composer maps `Alfred\Workflow\` to `workflow/src/` in `workflow/composer.json`.
- Each executable adapter loads all classes through `workflow/vendor/autoload.php`; do not directly require individual source files.
- Invoke an adapter directly, for example `workflow/src/AlfredAdapter/DailyBroadcast.php` or `workflow/src/AlfredAdapter/SubjectDetails.php <subject-url>`.
- The daily broadcast adapter reads `alfred_workflow_cache` and `BGM_SITE_DOMAIN`, with safe defaults when either variable is unavailable.
- After adding or renaming a class, run `composer dump-autoload --working-dir=workflow` to update the autoloader.

## Alfred Output

- On successful execution, standard output must contain valid JSON for the corresponding Alfred object without logs or debugging text.
- Adapter errors must also produce valid JSON for the corresponding Alfred object on standard output and return a non-zero exit status.
- Script Filter and Grid results belong in the top-level `items` array and use the `title` field for their title.
- Text View results use the top-level `response` field.
- The daily broadcast operation outputs its schedule in the top-level `items` array.

## Verification

Run at least the following commands after making changes:

```bash
find workflow -name '*.php' -not -path 'workflow/vendor/*' -exec php -l {} \;
workflow/src/AlfredAdapter/DailyBroadcast.php
workflow/src/AlfredAdapter/SubjectDetails.php https://bgm.tv/subject/424883
(cd workflow && vendor/bin/phpstan analyse src --no-progress)
(cd workflow && vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no --sequential)
plutil -lint workflow/info.plist
```
