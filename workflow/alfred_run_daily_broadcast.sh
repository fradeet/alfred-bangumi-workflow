#!/bin/bash

set -euo pipefail

script_directory="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
cache_directory="${alfred_workflow_cache:-${TMPDIR:-/tmp}/com.fradeet.bangumitv}"

exec php "${script_directory}/AlfredAdapter.php" daily-broadcast "${cache_directory}"
