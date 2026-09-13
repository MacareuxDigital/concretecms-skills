<?php

/**
 * This script identifies and extracts metadata from Concrete CMS packages.
 */

$packagesDir = 'packages';

if (!is_dir($packagesDir)) {
    echo "Error: packages directory not found.\n";
    exit(1);
}

$packages = [];
$dirs = array_filter(glob($packagesDir . '/*'), 'is_dir');

foreach ($dirs as $dir) {
    $handle = basename($dir);
    $controllerPath = $dir . '/controller.php';
    
    $package = [
        'handle' => $handle,
        'path' => $dir,
        'name' => '',
        'description' => '',
        'version' => '',
        'requiredVersion' => '',
        'hasComposer' => file_exists($dir . '/composer.json'),
        'gitLog' => shell_exec("git log -1 --pretty=format:'%s (%h, %ad)' --date=short -- " . escapeshellarg($dir)),
    ];

    if (file_exists($controllerPath)) {
        $content = file_get_contents($controllerPath);
        
        if (preg_match('/protected\s+\$pkgName\s*=\s*[\'"](.*?)[\'"];/s', $content, $matches)) {
            $package['name'] = $matches[1];
        }
        if (preg_match('/protected\s+\$pkgDescription\s*=\s*[\'"](.*?)[\'"];/s', $content, $matches)) {
            $package['description'] = $matches[1];
        }
        if (preg_match('/protected\s+\$pkgVersion\s*=\s*[\'"](.*?)[\'"];/s', $content, $matches)) {
            $package['version'] = $matches[1];
        }
        if (preg_match('/protected\s+\$appVersionRequired\s*=\s*[\'"](.*?)[\'"];/s', $content, $matches)) {
            $package['requiredVersion'] = $matches[1];
        }
    }

    $packages[] = $package;
}

// Output in Markdown format (raw)
echo "# Raw Package Data\n\n";

if (empty($packages)) {
    echo "No packages found in the `packages` directory.\n";
    exit;
}

foreach ($packages as $p) {
    echo "## Package: {$p['handle']}\n";
    echo "- **Name**: " . ($p['name'] ?: "N/A") . "\n";
    echo "- **Description**: " . ($p['description'] ?: "N/A") . "\n";
    echo "- **Version**: " . ($p['version'] ?: "N/A") . "\n";
    echo "- **Required Concrete Version**: " . ($p['requiredVersion'] ?: "N/A") . "\n";
    echo "- **Composer**: " . ($p['hasComposer'] ? "Yes" : "No") . "\n";
    echo "- **Git**: " . ($p['gitLog'] ?: "No git info") . "\n";
    
    echo "\n### Controller Metadata\n";
    echo "```php\n";
    if (file_exists($p['path'] . '/controller.php')) {
        // Just show the first 100 lines of controller for context
        $lines = file($p['path'] . '/controller.php');
        echo implode('', array_slice($lines, 0, 100));
        if (count($lines) > 100) echo "\n...";
    } else {
        echo "// controller.php not found.\n";
    }
    echo "\n```\n\n";
    echo "---\n\n";
}
