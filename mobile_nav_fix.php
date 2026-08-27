<?php
$content = file_get_contents("includes/header.php");

$navs = [
    ["index.php", "Home"],
    ["events.php", "All Events"],
    ["gallery.php", "Media Gallery"],
    ["about.php", "About Us"],
    ["contact.php", "Contact"],
    ["faqs.php", "FAQs"]
];

$mobile_nav_replacement = "    <div id=\"mobileMenu\" class=\"hidden md:hidden bg-dark-900/95 border-b border-white/10 px-4 pt-2 pb-6 space-y-2 backdrop-blur-xl\">\n";
foreach ($navs as $nav) {
    $mobile_nav_replacement .= "        <a href=\"<?= BASE_URL ?>/" . $nav[0] . "\" class=\"<?= get_nav_class(\"" . $nav[0] . "\", \$current_page_name, \"block\") ?>\">" . $nav[1] . "</a>\n";
}
$mobile_nav_replacement .= "    </div>";

// Replace Mobile Nav block
$startNav = strpos($content, "<div id=\"mobileMenu\"");
$endNav = strpos($content, "</div>", $startNav) + 6;
$content = substr_replace($content, $mobile_nav_replacement, $startNav, $endNav - $startNav);

file_put_contents("includes/header.php", $content);
?>
