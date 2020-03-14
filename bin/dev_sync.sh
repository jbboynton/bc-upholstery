#!/bin/bash

root="$(git rev-parse --show-toplevel)"

# shellcheck disable=SC1090,SC1091
source "$root/.env"

rsync -a "$BC_UPHOLSTERY_JSON_DIR" "$BC_UPHOLSTERY_TRACKED_DIR"
rsync -a --delete "$BC_UPHOLSTERY_TRACKED_DIR" "$BC_UPHOLSTERY_MU_PLUGIN_DIR"
