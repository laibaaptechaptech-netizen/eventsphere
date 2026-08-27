<?php
$content = file_get_contents("event_detail.php");
$orgCard = file_get_contents("org_card.txt");

// Find the Registration block
$regStartStr = "<!-- Registration & Ticket Pass Box -->";
$regEndStr = "<!-- Calendar Sync Integration";

$regStart = strpos($content, $regStartStr);
$regEnd = strpos($content, $regEndStr);

if ($regStart === false || $regEnd === false) {
    echo "Could not find registration boundaries.";
    exit;
}

// Extract the block (including the starting comment, up to but not including the end comment)
// Wait, the end comment is inside the same right column div. The right column div holds both.
$regBlockFull = substr($content, $regStart, $regEnd - $regStart);
$regBlockClean = trim($regBlockFull);

// We want to replace the Registration Block in the right column with the Organizer Card
$content = str_replace($regBlockFull, $orgCard . "\n\n                ", $content);

// We want to insert the Registration Block (without sticky top-24) into the left column
// before <!-- Multi-Dimensional Peer Reviews & Feedback Section -->
$reviewsStart = strpos($content, "<!-- Multi-Dimensional Peer Reviews & Feedback Section -->");
$regBlockForLeft = str_replace("sticky top-24", "", $regBlockClean);

if ($reviewsStart !== false) {
    $content = substr_replace($content, $regBlockForLeft . "\n\n            ", $reviewsStart, 0);
}

file_put_contents("event_detail.php", $content);
echo "Fixed successfully.";
?>
