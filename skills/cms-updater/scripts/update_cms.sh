#!/usr/bin/env bash
# Stage or apply a Concrete CMS core tree. Never run apply without a backup.
set -euo pipefail

usage() {
  cat <<'EOF'
Usage:
  update_cms.sh detect [--root DIR]
  update_cms.sh stage <version-or-url> [--root DIR] [--dir DIR]
  update_cms.sh apply <staged-dir> [--root DIR] --yes

detect   Print live core path, version, CLI, composer vendor-dir, gitignore.
stage    Download a GitHub tag or tarball into a staging directory. Do not replace core.
apply    Backup live core, then replace it with staged/concrete. Requires --yes.
EOF
}

die() {
  echo "Error: $*" >&2
  exit 1
}

ROOT="$(pwd)"
STAGED=""
YES=0
CMD=""
TARGET=""

while [ $# -gt 0 ]; do
  case "$1" in
    detect|stage|apply)
      if [ -n "$CMD" ]; then
        die "unexpected extra command $1"
      fi
      CMD="$1"
      shift
      if [ "$CMD" = "stage" ] || [ "$CMD" = "apply" ]; then
        [ $# -gt 0 ] || die "$CMD requires an argument"
        TARGET="$1"
        shift
      fi
      ;;
    --root)
      ROOT="${2:-}"
      [ -n "$ROOT" ] || die "--root requires a directory"
      shift 2
      ;;
    --dir)
      STAGED="${2:-}"
      [ -n "$STAGED" ] || die "--dir requires a directory"
      shift 2
      ;;
    --yes)
      YES=1
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      die "unknown argument $1"
      ;;
  esac
done

[ -n "$CMD" ] || { usage; exit 1; }

ROOT="$(cd "$ROOT" && pwd)"
[ -d "$ROOT/application" ] || die "no application/ under $ROOT"

live_core() {
  local update="$ROOT/application/config/update.php"
  if [ -f "$update" ]; then
    local hinted=""
    hinted="$(php -r '$c=@include $argv[1]; if(is_array($c)&&isset($c["core"]["concrete"])&&is_string($c["core"]["concrete"])) echo $c["core"]["concrete"];' "$update" 2>/dev/null || true)"
    if [ -n "$hinted" ]; then
      if [ -d "$hinted" ]; then
        echo "$hinted"
        return
      fi
      if [ -d "$ROOT/$hinted" ]; then
        echo "$ROOT/$hinted"
        return
      fi
      if [ -d "$ROOT/updates/$(basename "$hinted")" ]; then
        echo "$ROOT/updates/$(basename "$hinted")"
        return
      fi
    fi
  fi
  [ -d "$ROOT/concrete" ] || die "no concrete/ directory and no application/config/update.php core path"
  echo "$ROOT/concrete"
}

core_version() {
  local file="$1/config/concrete.php"
  if [ ! -f "$file" ]; then
    echo "unknown"
    return
  fi
  sed -n "s/.*'version' => '\\([^']*\\)'.*/\\1/p" "$file" | awk 'NR==1{print; exit}'
}

find_cli() {
  local dir="$1"
  if [ -x "$dir/bin/concrete" ]; then
    echo "$dir/bin/concrete"
  elif [ -x "$dir/bin/concrete5" ]; then
    echo "$dir/bin/concrete5"
  elif [ -f "$dir/bin/concrete5" ]; then
    echo "$dir/bin/concrete5"
  else
    echo ""
  fi
}

composer_vendor_dir() {
  if [ -f "$ROOT/composer.json" ]; then
    php -r '
      $j = json_decode(file_get_contents($argv[1]), true);
      echo $j["config"]["vendor-dir"] ?? "vendor";
    ' "$ROOT/composer.json"
  else
    echo "vendor"
  fi
}

core_gitignored() {
  if [ -d "$ROOT/.git" ] && git -C "$ROOT" check-ignore -q concrete 2>/dev/null; then
    echo "yes"
  else
    echo "no"
  fi
}

resolve_url() {
  local spec="$1"
  case "$spec" in
    https://*|http://*)
      echo "$spec"
      ;;
    *)
      echo "https://github.com/concretecms/concretecms/archive/refs/tags/${spec}.tar.gz"
      ;;
  esac
}

find_concrete_in() {
  local dir="$1"
  if [ -d "$dir/concrete" ] && [ -f "$dir/concrete/config/concrete.php" ]; then
    echo "$dir/concrete"
    return
  fi
  local found=""
  while IFS= read -r line; do
    found="$line"
    break
  done < <(find "$dir" -type f -path '*/concrete/config/concrete.php')
  [ -n "$found" ] || return 1
  dirname "$(dirname "$found")"
}

cmd_detect() {
  local core
  core="$(live_core)"
  echo "root=$ROOT"
  echo "live_core=$core"
  echo "version=$(core_version "$core")"
  echo "cli=$(find_cli "$core")"
  echo "php=$(php -r 'echo PHP_VERSION;')"
  echo "composer_vendor_dir=$(composer_vendor_dir)"
  echo "concrete_gitignored=$(core_gitignored)"
  echo "update_php=$([ -f "$ROOT/application/config/update.php" ] && echo yes || echo no)"
}

cmd_stage() {
  local url spec dest archive
  spec="$TARGET"
  [ -n "$spec" ] || die "stage requires a version tag (9.4.1) or a tarball URL"
  url="$(resolve_url "$spec")"
  dest="${STAGED:-$ROOT/.cursor/cms-update-stage}"
  mkdir -p "$dest"
  rm -rf "$dest"/*
  archive="$dest/concretecms.tar.gz"
  echo "Downloading $url"
  curl -fL --retry 3 --retry-delay 2 "$url" -o "$archive"
  tar -tzf "$archive" >/dev/null || die "download is not a gzip tar archive"
  tar -xzf "$archive" -C "$dest"
  rm -f "$archive"
  local staged_core
  staged_core="$(find_concrete_in "$dest")" || die "archive has no concrete/config/concrete.php"
  echo "staged_core=$staged_core"
  echo "staged_version=$(core_version "$staged_core")"
  echo "Next: run override-check with --target-core $staged_core"
  echo "Then: $0 apply $staged_core --root $ROOT --yes"
}

cmd_apply() {
  [ "$YES" = 1 ] || die "apply refuses to run without --yes"
  local staged_core live backup_dir stamp
  staged_core="$TARGET"
  [ -d "$staged_core" ] || die "staged core is not a directory: $staged_core"
  [ -f "$staged_core/config/concrete.php" ] || die "not a core tree (missing config/concrete.php): $staged_core"
  live="$(live_core)"
  stamp="$(date +%Y%m%d%H%M%S)"
  backup_dir="$ROOT/.cursor/cms-update-backups"
  mkdir -p "$backup_dir"
  local backup="$backup_dir/concrete-${stamp}.tar.gz"
  echo "Backing up $live to $backup"
  tar -czf "$backup" -C "$(dirname "$live")" "$(basename "$live")"
  local tmp="$ROOT/.cursor/cms-update-new-core"
  rm -rf "$tmp"
  mkdir -p "$(dirname "$tmp")"
  cp -a "$staged_core" "$tmp"
  echo "Replacing $live"
  rm -rf "$live"
  mv "$tmp" "$live"
  echo "applied_core=$live"
  echo "applied_version=$(core_version "$live")"
  echo "backup=$backup"
  echo "If composer vendor-dir is under concrete/, run composer install next."
  echo "Then run the Concrete CLI c5:update command."
}

case "$CMD" in
  detect) cmd_detect ;;
  stage) cmd_stage ;;
  apply) cmd_apply ;;
esac
