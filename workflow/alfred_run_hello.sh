#!/bin/bash

set -euo pipefail

script_directory="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"

exec php "${script_directory}/AlfredAdapter.php" hello "$@"
