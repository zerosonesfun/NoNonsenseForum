<?php
// Ensure that the query parameter is passed
$query = isset($_GET['query']) ? $_GET['query'] : '';

// Pagination settings
$resultsPerPage = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1; // Current page (default to 1)

// Validate page number
if ($page < 1) {
    $page = 1;
}

if (empty($query)) {
    // Return an empty RSS feed if no query is provided
    header('Content-Type: application/rss+xml');
    echo '<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">
  <channel>
    <title>No search query provided</title>
    <link>' . $_SERVER['HTTP_HOST'] . '</link>
    <description>No search query provided</description>
  </channel>
</rss>';
    exit;
}

// Directory containing the .rss files
$directory = $_SERVER['DOCUMENT_ROOT'] . '/forum/'; // Ensure the path is correct

// Search for .rss files in the directory
$files = glob($directory . '*.rss');

// Check if any files are found
if (empty($files)) {
    // Return an empty RSS feed if no .rss files are found
    header('Content-Type: application/rss+xml');
    echo '<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">
  <channel>
    <title>No RSS files found</title>
    <link>' . $_SERVER['HTTP_HOST'] . '</link>
    <description>No RSS files found</description>
  </channel>
</rss>';
    exit;
}

// Prepare the RSS feed structure
header('Content-Type: application/rss+xml');
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><rss version="2.0"></rss>');
$channel = $xml->addChild('channel');
$channel->addChild('title', 'Search Results for "' . htmlspecialchars($query) . '"');
$channel->addChild('link', $_SERVER['HTTP_HOST']);
$channel->addChild('description', 'Search results for the query: "' . htmlspecialchars($query) . '"');

// Prepare an array to hold all results
$allResults = [];

// Search through the RSS files
foreach ($files as $file) {
    // Read the RSS file
    $rss = simplexml_load_file($file);
    if ($rss === false) {
        continue; // Skip if the file can't be loaded
    }

    // Search through the RSS items
    foreach ($rss->channel->item as $item) {
        if (stripos($item->title, $query) !== false || stripos($item->description, $query) !== false) {
            $allResults[] = [
                'title' => (string) $item->title,
                'link' => (string) $item->link,
                'description' => (string) $item->description
            ];
        }
    }
}

// Calculate pagination
$totalResults = count($allResults);
$totalPages = ceil($totalResults / $resultsPerPage);
$start = ($page - 1) * $resultsPerPage;
$paginatedResults = array_slice($allResults, $start, $resultsPerPage);

// Add paginated results to the RSS feed
foreach ($paginatedResults as $result) {
    $entry = $channel->addChild('item');
    $entry->addChild('title', $result['title']);
    $entry->addChild('link', $result['link']);
    $entry->addChild('description', $result['description']);
}

// Add pagination info to the feed
$channel->addChild('totalResults', $totalResults);
$channel->addChild('totalPages', $totalPages);
$channel->addChild('currentPage', $page);

// Output the RSS feed
echo $xml->asXML();
?>
