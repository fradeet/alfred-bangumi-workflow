# Alfred Workflow PHP Template

A small PHP starter project for building Alfred Script Filter workflows. It keeps
the workflow's core logic separate from Alfred-specific JSON and output handling.

## Requirements

- macOS with [Alfred](https://www.alfredapp.com/)
- PHP 8.4.1 or later
- [Composer](https://getcomposer.org/)

## Installation

### Create a repository from the GitHub template

1. Open the
   [template repository](https://github.com/fradeet/alfred-workflow-php-template).
2. Select **Use this template**, then **Create a new repository**.
3. Clone your new repository and enter its directory:

   ```bash
   git clone https://github.com/YOUR-USERNAME/YOUR-REPOSITORY.git
   cd YOUR-REPOSITORY
   ```

4. Install the workflow dependencies:

   ```bash
   composer install --working-dir=workflow
   ```

### Create a project with Composer

Once the package is available on Packagist, create a project with:

```bash
composer create-project fradeet/alfred-workflow-php-template my-alfred-workflow
cd my-alfred-workflow
composer install --working-dir=workflow
```

## Run the example

Use the included Alfred runner script:

```bash
./workflow/alfred_run_hello.sh
```

You can also invoke the PHP adapter directly:

```bash
php workflow/AlfredAdapter.php hello
```

The command writes valid Alfred Script Filter JSON to standard output:

```json
{"items":[{"title":"Hello Alfred"}]}
```

PHP warnings and other displayed diagnostics are written to standard error so
they do not corrupt Alfred's JSON input.

CLI usage and task errors are also returned as Alfred Script Filter JSON, with
a non-zero exit status. For example, an unknown task outputs:

```json
{"items":[{"title":"Unknown task: unknown"}]}
```

## Connect it to Alfred

Create a workflow in Alfred and add a **Script Filter** object. Choose
`/bin/bash` as the language and use the included runner script:

```bash
"$PWD/workflow/alfred_run_hello.sh"
```

Alternatively, run the PHP adapter directly:

```bash
php "$PWD/workflow/AlfredAdapter.php" hello
```

Connect the Script Filter to the actions needed by your workflow.

The adapter uses positional CLI arguments. The first argument selects the task,
and all remaining arguments are forwarded to that task:

```bash
php workflow/AlfredAdapter.php hello Ada Lovelace
./workflow/alfred_run_hello.sh Ada Lovelace
```

Both commands output `{"items":[{"title":"Hello Ada Lovelace"}]}`.

## Package the workflow

Package the contents of `workflow/` as an Alfred workflow from the repository
root:

```bash
./workflow-packager
```

The command reads the workflow name from `workflow/info.plist` and creates
`<workflow-name>.alfredworkflow` in the repository root. Generated workflow
packages are ignored by Git. To choose another destination, pass the complete
output path:

```bash
./workflow-packager build/hello.alfredworkflow
```

The destination directory must already exist. If Composer development
dependencies are installed in `workflow/vendor`, the packager first runs
`composer install --no-dev` in `workflow/` so they are not included in the
package. The remaining packaging happens from a temporary copy, where
`.DS_Store` files are removed and variables listed in `variablesdontexport` are
cleared without modifying their source files.

## Customize the workflow

- Add one `workflow/alfred_run_<operation>.sh` script for each operation exposed
  to Alfred. Read Alfred user data and environment variables in these scripts,
  then forward the values through `workflow/AlfredAdapter.php`.
- Use `workflow/AlfredAdapter.php` as the single gateway for all runner scripts.
  Keep Alfred-specific PHP functions, core calls, result adaptation, JSON
  encoding, and standard output handling there.
- Put Alfred-independent business logic in `workflow/src/` under the
  `Alfred\Workflow` namespace. Core classes should accept explicit inputs and
  return plain PHP values.
- After adding or renaming a core class, refresh Composer's autoloader:

  ```bash
  composer dump-autoload --working-dir=workflow
  ```

## Development checks

Run the example and the project checks before committing changes:

```bash
workflow/alfred_run_hello.sh
cd workflow
vendor/bin/phpstan analyse src AlfredAdapter.php --no-progress
vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no
```

## License

This project is available under the MIT License.
