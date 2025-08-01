<?php
/**
 * Universal Section Redirect Handler
 * Redirects clean URLs to index.html with appropriate anchor fragments
 * 
 * Usage: Maps /contact, /about, /social to index.html#[section]
 */

// Get the section from the REQUEST_URI
$requestUri = $_SERVER['REQUEST_URI'];
$section = '';

// Extract section name from URL
if (preg_match('/\/(\w+)\/?$/', $requestUri, $matches)) {
    $section = $matches[1];
}

// Validate section exists in our SPA
$validSections = ['contact', 'about', 'social', 'menu', 'home'];
if (!in_array($section, $validSections)) {
    // Default to home if invalid section
    $section = 'home';
}

$targetUrl = '/index.html#' . $section;
$pageTitle = ucfirst($section) . ' - Plate St. Pete';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script>
        // Redirect to index.html with anchor immediately
        window.location.replace('<?= $targetUrl ?>');
    </script>
    <noscript>
        <meta http-equiv="refresh" content="0; url=<?= htmlspecialchars($targetUrl) ?>">
    </noscript>
</head>
<body>
    <p>Redirecting to <a href="<?= htmlspecialchars($targetUrl) ?>"><?= htmlspecialchars(ucfirst($section)) ?> section</a>...</p>
</body>
</html>
