# Alfred Bangumi Workflow

An Alfred workflow that lists today's Bangumi anime schedule, browses every anime in the current calendar, and displays details and related entries for a selected subject. The project separates Alfred-specific adapters from reusable core logic:

```text
Alfred -> operation-specific PHP adapter -> core class
```

## Requirements

- macOS with [Alfred](https://www.alfredapp.com/)
- PHP 8.3 or later
- [Composer](https://getcomposer.org/)

Install the workflow dependencies after cloning the repository:

```bash
composer install --working-dir=workflow
```

## Run the workflow operations

Run the daily broadcast Adapter directly:

```bash
workflow/src/AlfredAdapter/DailyBroadcast.php
```

List all anime in the current Bangumi calendar:

```bash
workflow/src/AlfredAdapter/SeasonalAnime.php
```

It reads the cover cache directory from `alfred_workflow_cache` and the selected Bangumi mirror from `BGM_SITE_DOMAIN`. When unavailable, they default to the system temporary directory and `https://bgm.tv/` respectively.

Fetch details for a subject URL:

```bash
workflow/src/AlfredAdapter/SubjectDetails.php https://bgm.tv/subject/424883
```

List entries related to a subject URL:

```bash
workflow/src/AlfredAdapter/SubjectRelations.php https://bgm.tv/subject/424883
```

Successful Bangumi GET responses are cached locally for eight hours by Saloon, using Symfony's filesystem cache backend. The cache is stored in `saloon-responses` below `alfred_workflow_cache`, or below the system temporary directory when Alfred does not provide one. Set `alfred_debug=1` to bypass the response cache while debugging. Cover images and Alfred's own Script Filter result cache remain separate.

Each Adapter writes the JSON expected by its Alfred object to standard output: Script Filter JSON for daily broadcasts, seasonal anime, and subject relations, and Text View JSON for subject details. PHP diagnostics go to standard error so they do not corrupt Alfred's input. Failures produce valid JSON for the corresponding Alfred object and return a non-zero exit status.

The bundled `workflow/info.plist` configures the Grid and Text View objects to use these executable PHP files directly.

## Project structure

- Put each executable Alfred business Adapter in `workflow/src/AlfredAdapter/`, with one PascalCase PHP file per operation.
- Read Alfred inputs and environment variables in the relevant Adapter. There is no shared CLI task dispatcher.
- Keep Alfred JSON response value objects in `workflow/src/AlfredAdapter/Types/` and the `Alfred\Workflow\AlfredAdapter\Types` namespace.
- Keep Alfred-independent business logic in `workflow/src/` under the `Alfred\Workflow` namespace. Core classes accept explicit inputs and return plain PHP values.
- Load all classes through `workflow/vendor/autoload.php`; do not require individual core source files.

After adding or renaming a class, refresh Composer's autoloader:

```bash
composer dump-autoload --working-dir=workflow
```

## Package the workflow

Package the contents of `workflow/` as an Alfred workflow from the repository root:

```bash
./workflow-packager
```

The command reads the workflow name from `workflow/info.plist` and creates `<workflow-name>.alfredworkflow` in the repository root. To choose another destination, pass the complete output path; its parent directory must already exist:

```bash
./workflow-packager build/bangumi.alfredworkflow
```

If Composer development dependencies are installed, the packager removes them from the packaged copy. It also clears workflow variables listed in `variablesdontexport` without changing the source files.

## Development checks

```bash
find workflow -name '*.php' -not -path 'workflow/vendor/*' -exec php -l {} \;
workflow/src/AlfredAdapter/DailyBroadcast.php
workflow/src/AlfredAdapter/SeasonalAnime.php
workflow/src/AlfredAdapter/SubjectDetails.php https://bgm.tv/subject/424883
workflow/src/AlfredAdapter/SubjectRelations.php https://bgm.tv/subject/424883
(cd workflow && vendor/bin/phpstan analyse src --no-progress)
(cd workflow && vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no --sequential)
plutil -lint workflow/info.plist
```

## License

This project is licensed under the [GNU General Public License v3.0](LICENSE).
