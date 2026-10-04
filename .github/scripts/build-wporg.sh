#!/usr/bin/env bash
# Builds the wordpress.org edition of Underworld Empire into <output dir>/underworld-empire.
#
# The wordpress.org edition is lite/ without the GitHub updater (WordPress.org delivers the
# updates) and without the Bridge (plugins from WordPress.org may not install other plugins).
# Run from the root of the repository.
set -euo pipefail

out="${1:?usage: build-wporg.sh <output dir>}"
mkdir -p "$out/underworld-empire"
rsync -a \
  --exclude 'includes/Updater.php' \
  --exclude 'includes/Bridge.php' \
  lite/ "$out/underworld-empire/"
sed -i '/^ \* Update URI:/d' "$out/underworld-empire/underworld-empire.php"
