#!/bin/bash

set -euo pipefail

script_directory="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
subject_url="${1:-}"

exec php "${script_directory}/AlfredAdapter.php" subject-details "${subject_url}"
