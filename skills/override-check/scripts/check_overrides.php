<?php

/**
 * Scan application/ for Concrete CMS customizations.
 *
 * Usage (from the project root, or pass --root):
 *   php check_overrides.php [--root DIR] [--core DIR] [--target-core DIR]
 *       [--max-diff-lines N] [--json]
 *
 * Exit 1 if application/ is missing or arguments are invalid.
 * Exit 0 even when zero customizations are found.
 */

$opts = parseArgs($argv);
$root = $opts['root'] ?: findProjectRoot(getcwd());
if ($root === null || !is_dir($root . '/application')) {
    fwrite(STDERR, "Error: no Concrete CMS project root found (expected application/).\n");
    exit(1);
}

$appDir = $root . '/application';
$coreDir = $opts['core'] ?: detectLiveCore($root);
if ($coreDir === null || !is_dir($coreDir)) {
    fwrite(STDERR, "Error: live core directory not found. Pass --core /path/to/concrete.\n");
    exit(1);
}

$targetCore = $opts['target-core'];
if ($targetCore !== null && !is_dir($targetCore)) {
    fwrite(STDERR, "Error: --target-core is not a directory: {$targetCore}\n");
    exit(1);
}

$skipDirNames = [
    'vendor' => true,
    'node_modules' => true,
    'phpexcel' => true,
    'tcpdf' => true,
    'fpdi' => true,
    '.git' => true,
    'generated_overrides' => true,
];

$skipFileBasenames = [
    'phpexcel.php' => true,
    'tcpdf.php' => true,
];

$skipExt = [
    'png' => true, 'jpg' => true, 'jpeg' => true, 'gif' => true, 'webp' => true,
    'svg' => true, 'ico' => true, 'pdf' => true, 'zip' => true, 'gz' => true,
    'mo' => true, 'po' => true, 'ttf' => true, 'woff' => true, 'woff2' => true,
    'eot' => true, 'mp4' => true, 'mp3' => true, 'map' => true,
];

$skipRelPrefixes = [
    'files/',
    'config/doctrine/',
    'config/generated_overrides/',
    'languages/',
];

$records = [];
$skipped = ['vendored' => 0, 'gitignored' => 0, 'binary' => 0, 'placeholder' => 0];
$gitIgnored = gitIgnoredRelPaths($root, $appDir);

$dirIter = new RecursiveDirectoryIterator($appDir, FilesystemIterator::SKIP_DOTS);
$filter = new RecursiveCallbackFilterIterator($dirIter, function ($current) use ($appDir, $skipDirNames, $skipRelPrefixes, &$skipped) {
    $path = $current->getPathname();
    $rel = substr($path, strlen($appDir) + 1);
    $rel = str_replace('\\', '/', $rel);
    if ($current->isDir()) {
        if (isset($skipDirNames[strtolower($current->getFilename())])) {
            $skipped['vendored']++;
            return false;
        }
        foreach ($skipRelPrefixes as $prefix) {
            $dirRel = $rel . '/';
            if ($dirRel === $prefix || strpos($dirRel, $prefix) === 0) {
                $skipped['vendored']++;
                return false;
            }
        }
        return true;
    }
    return true;
});

$iterator = new RecursiveIteratorIterator($filter);

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $path = $file->getPathname();
    $rel = substr($path, strlen($appDir) + 1);
    $rel = str_replace('\\', '/', $rel);

    if (shouldSkipPath($rel, $skipDirNames, $skipRelPrefixes)) {
        $skipped['vendored']++;
        continue;
    }

    $base = basename($rel);
    if ($base === 'index.html' || $base === '.gitkeep' || $base === '.DS_Store') {
        $skipped['placeholder']++;
        continue;
    }
    if (isset($skipFileBasenames[strtolower($base)])) {
        $skipped['vendored']++;
        continue;
    }

    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
    if (isset($skipExt[$ext])) {
        $skipped['binary']++;
        continue;
    }

    if (isset($gitIgnored[$rel]) || isset($gitIgnored['application/' . $rel])) {
        $skipped['gitignored']++;
        continue;
    }

    $coreRel = $rel;
    $liveCorePath = $coreDir . '/' . $coreRel;
    $isOverride = is_file($liveCorePath);
    $identical = $isOverride ? filesAreIdentical($liveCorePath, $path) : null;

    $targetChanged = null;
    if ($isOverride && $targetCore !== null) {
        $targetPath = rtrim($targetCore, '/') . '/' . $coreRel;
        if (!is_file($targetPath)) {
            $targetChanged = 'removed in target core';
        } elseif (!filesAreIdentical($liveCorePath, $targetPath)) {
            $targetChanged = 'changed in target core';
        } else {
            $targetChanged = 'unchanged';
        }
    }

    $text = isTextFile($path) ? file_get_contents($path) : '';
    if ($text === false) {
        $text = '';
    }

    $record = [
        'path' => 'application/' . $rel,
        'relative' => $rel,
        'category' => categorize($rel, $isOverride),
        'kind' => $isOverride ? 'override' : 'custom',
        'core' => $isOverride ? coreLabel($coreDir, $root) . '/' . $coreRel : null,
        'identical' => $identical,
        'git' => gitLog($root, $path),
        'related' => extractRelated($text),
        'target_core' => $targetChanged,
        'bytes' => $file->getSize(),
    ];

    if ($isOverride && $identical === false) {
        $record['diff'] = truncate(unifiedDiff($liveCorePath, $path), $opts['max-diff-lines']);
    } elseif (!$isOverride && $text !== '') {
        $record['preview'] = truncate($text, 12);
    }

    $records[] = $record;
}

usort($records, function ($a, $b) {
    return strcmp($a['path'], $b['path']);
});

$counts = [];
foreach ($records as $r) {
    $key = $r['category'];
    if (!isset($counts[$key])) {
        $counts[$key] = 0;
    }
    $counts[$key]++;
}

$meta = [
    'root' => $root,
    'core' => $coreDir,
    'target_core' => $targetCore,
    'current_version' => readCoreVersion($coreDir),
    'counts' => $counts,
    'listed' => count($records),
    'skipped' => $skipped,
];

if ($opts['json']) {
    echo json_encode(['meta' => $meta, 'files' => $records], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

echo "# Raw override data\n\n";
echo "## Scan meta\n\n";
echo '- Root: `' . $meta['root'] . "`\n";
echo '- Live core: `' . $meta['core'] . "`\n";
echo '- Current version: ' . ($meta['current_version'] ?: 'unknown') . "\n";
if ($targetCore) {
    echo '- Target core: `' . $targetCore . "`\n";
}
echo '- Listed files: ' . $meta['listed'] . "\n";
echo '- Skipped vendored trees or stubs: ' . $skipped['vendored'] . "\n";
echo '- Skipped gitignored: ' . $skipped['gitignored'] . "\n";
echo '- Skipped binary: ' . $skipped['binary'] . "\n";
echo "\n### Counts by category\n\n";
if (!$counts) {
    echo "None.\n";
} else {
    ksort($counts);
    foreach ($counts as $cat => $n) {
        echo "- {$cat}: {$n}\n";
    }
}

$byCat = [];
foreach ($records as $r) {
    $byCat[$r['category']][] = $r;
}
ksort($byCat);

foreach ($byCat as $cat => $items) {
    echo "\n## {$cat}\n\n";
    foreach ($items as $c) {
        echo "### `{$c['path']}`\n\n";
        echo '- Kind: ' . $c['kind'] . "\n";
        if ($c['core']) {
            echo '- Core: `' . $c['core'] . "`\n";
            echo '- Identical to live core: ' . ($c['identical'] ? 'yes' : 'no') . "\n";
        }
        echo '- Git: ' . ($c['git'] ?: 'none') . "\n";
        if ($c['related']) {
            echo '- Related: ' . implode(', ', $c['related']) . "\n";
        }
        if ($c['target_core']) {
            echo '- Target core: ' . $c['target_core'] . "\n";
        }
        if (!empty($c['diff'])) {
            echo "\n```diff\n{$c['diff']}\n```\n";
        } elseif (!empty($c['preview'])) {
            echo "\n```\n{$c['preview']}\n```\n";
        }
        echo "\n---\n\n";
    }
}

function parseArgs(array $argv)
{
    $opts = [
        'root' => null,
        'core' => null,
        'target-core' => null,
        'max-diff-lines' => 60,
        'json' => false,
    ];
    $args = array_slice($argv, 1);
    for ($i = 0; $i < count($args); $i++) {
        $a = $args[$i];
        if ($a === '--json') {
            $opts['json'] = true;
            continue;
        }
        if (strpos($a, '--') !== 0) {
            fwrite(STDERR, "Error: unexpected argument {$a}\n");
            exit(1);
        }
        $name = substr($a, 2);
        if (!array_key_exists($name, $opts) || $name === 'json') {
            fwrite(STDERR, "Error: unknown option {$a}\n");
            exit(1);
        }
        if (!isset($args[$i + 1])) {
            fwrite(STDERR, "Error: {$a} requires a value\n");
            exit(1);
        }
        $opts[$name] = $args[++$i];
        if ($name === 'max-diff-lines') {
            $opts[$name] = (int) $opts[$name];
        }
    }
    return $opts;
}

function findProjectRoot($cwd)
{
    $dir = realpath($cwd);
    while ($dir && $dir !== '/') {
        if (is_dir($dir . '/application')) {
            return $dir;
        }
        $parent = dirname($dir);
        if ($parent === $dir) {
            break;
        }
        $dir = $parent;
    }
    return null;
}

function detectLiveCore($root)
{
    $update = $root . '/application/config/update.php';
    if (is_file($update)) {
        $cfg = @include $update;
        if (is_array($cfg)) {
            $rel = null;
            if (!empty($cfg['core']['concrete'])) {
                $rel = $cfg['core']['concrete'];
            } elseif (!empty($cfg['updates']['core']['path'])) {
                $rel = $cfg['updates']['core']['path'];
            }
            if (is_string($rel) && $rel !== '') {
                $candidate = $rel[0] === '/' ? $rel : $root . '/' . ltrim($rel, '/');
                if (is_dir($candidate)) {
                    return $candidate;
                }
                $underUpdates = $root . '/updates/' . basename($rel);
                if (is_dir($underUpdates)) {
                    return $underUpdates;
                }
            }
        }
    }
    if (is_dir($root . '/concrete')) {
        return $root . '/concrete';
    }
    return null;
}

function coreLabel($coreDir, $root)
{
    $root = rtrim($root, '/');
    if (strpos($coreDir, $root . '/') === 0) {
        return substr($coreDir, strlen($root) + 1);
    }
    return $coreDir;
}

function shouldSkipPath($rel, array $skipDirNames, array $skipRelPrefixes)
{
    foreach ($skipRelPrefixes as $prefix) {
        if (strpos($rel, $prefix) === 0) {
            return true;
        }
    }
    foreach (explode('/', $rel) as $part) {
        if (isset($skipDirNames[strtolower($part)])) {
            return true;
        }
    }
    return false;
}

function gitIgnoredRelPaths($root, $appDir)
{
    if (!is_dir($root . '/.git')) {
        return [];
    }
    $cmd = 'git -C ' . escapeshellarg($root)
        . ' ls-files -o -i --exclude-standard -- ' . escapeshellarg($appDir);
    $out = shell_exec($cmd);
    if (!is_string($out) || $out === '') {
        return [];
    }
    $map = [];
    foreach (preg_split('/\r\n|\r|\n/', trim($out)) as $line) {
        if ($line === '') {
            continue;
        }
        $map[$line] = true;
        if (strpos($line, 'application/') === 0) {
            $map[substr($line, strlen('application/'))] = true;
        }
    }
    return $map;
}

function gitLog($root, $path)
{
    $cmd = 'git -C ' . escapeshellarg($root)
        . " log -1 --pretty=format:'%s (%h, %ad)' --date=short -- "
        . escapeshellarg($path) . ' 2>/dev/null';
    $out = shell_exec($cmd);
    return is_string($out) ? trim($out) : '';
}

function filesAreIdentical($a, $b)
{
    return md5_file($a) === md5_file($b);
}

function unifiedDiff($corePath, $appPath)
{
    $cmd = 'diff -u ' . escapeshellarg($corePath) . ' ' . escapeshellarg($appPath) . ' 2>/dev/null';
    $out = shell_exec($cmd);
    return is_string($out) ? rtrim($out) : '';
}

function isTextFile($path)
{
    $finfo = new finfo(FILEINFO_MIME_ENCODING);
    $enc = $finfo->file($path);
    return $enc !== 'binary';
}

function extractRelated($text)
{
    if ($text === '') {
        return [];
    }
    $found = [];
    if (preg_match_all('#https?://[^\s)\]>\'"]+#i', $text, $m)) {
        foreach ($m[0] as $url) {
            $found[] = rtrim($url, '.,;');
        }
    }
    if (preg_match_all('/@todo\s+(.+)/i', $text, $m)) {
        foreach ($m[1] as $todo) {
            $found[] = '@todo ' . trim($todo);
        }
    }
    return array_values(array_unique($found));
}

function categorize($rel, $isOverride)
{
    if ($rel === 'bootstrap/app.php' || strpos($rel, 'bootstrap/') === 0) {
        return 'bootstrap';
    }
    if (strpos($rel, 'blocks/') === 0) {
        if (strpos($rel, '/templates/') !== false) {
            return 'custom_block_template';
        }
        if (basename($rel) === 'controller.php') {
            return $isOverride ? 'overridden_block_controller' : 'custom_block_controller';
        }
        return $isOverride ? 'overridden_block_file' : 'custom_block_file';
    }
    if (strpos($rel, 'themes/') === 0) {
        return 'theme';
    }
    if (strpos($rel, 'config/') === 0) {
        return 'config';
    }
    if (preg_match('#^(controllers|src|jobs|elements|mail|single_pages|attributes|authentication|views)/#', $rel)) {
        return $isOverride ? 'overridden_code' : 'custom_code';
    }
    return $isOverride ? 'other_override' : 'other_custom';
}

function readCoreVersion($coreDir)
{
    $file = $coreDir . '/config/concrete.php';
    if (!is_file($file)) {
        return '';
    }
    $text = file_get_contents($file);
    if ($text && preg_match("/'version'\\s*=>\\s*'([^']+)'/", $text, $m)) {
        return $m[1];
    }
    return '';
}

function truncate($text, $maxLines)
{
    $lines = preg_split('/\r\n|\r|\n/', $text);
    if (count($lines) <= $maxLines) {
        return $text;
    }
    $head = implode("\n", array_slice($lines, 0, $maxLines));
    $more = count($lines) - $maxLines;
    return $head . "\n... ({$more} more lines truncated)\n";
}
