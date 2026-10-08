#!/usr/bin/env bash
# Builds the wordpress.org edition of Mafia PBBG Engine into <output dir>/mafia-pbbg-engine.
#
# The wordpress.org edition is lite/ without the GitHub updater (WordPress.org delivers the
# updates) and without the Bridge (plugins from WordPress.org may not install other plugins).
# Run from the root of the repository.
set -euo pipefail

out="${1:?usage: build-wporg.sh <output dir>}"
dir="$out/mafia-pbbg-engine"
mkdir -p "$dir"
rsync -a \
  --exclude 'includes/Updater.php' \
  --exclude 'includes/Bridge.php' \
  --exclude 'mafia-pbbg-engine.php' \
  lite/ "$dir/"
sed '/^ \* Update URI:/d' lite/mafia-pbbg-engine.php > "$dir/mafia-pbbg-engine.php"
