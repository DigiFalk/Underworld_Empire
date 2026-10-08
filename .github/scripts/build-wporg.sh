#!/usr/bin/env bash
# Builds the wordpress.org edition of Mafia PBBG Engine into <output dir>/mafia-pbbg-engine.
#
# The wordpress.org edition is lite/ without the GitHub updater (WordPress.org delivers the
# updates) and without the Bridge (plugins from WordPress.org may not install other plugins).
# Its folder and main file follow the WordPress.org slug "mafia-pbbg-engine"; the GitHub
# edition keeps "underworld-empire" so existing sites keep updating.
# Run from the root of the repository.
set -euo pipefail

out="${1:?usage: build-wporg.sh <output dir>}"
dir="$out/mafia-pbbg-engine"
mkdir -p "$dir"
rsync -a \
  --exclude 'includes/Updater.php' \
  --exclude 'includes/Bridge.php' \
  --exclude 'underworld-empire.php' \
  lite/ "$dir/"
sed '/^ \* Update URI:/d' lite/underworld-empire.php > "$dir/mafia-pbbg-engine.php"
