<?php
$content = file_get_contents("includes/header.php");

$current_page_logic = "
\$current_user = current_user();
\$user_role = user_role();
\$db = getDB();

\$current_page_name = basename(\$_SERVER[\"PHP_SELF\"]);
function get_nav_class(\$page_name, \$current_page_name, \$extra = \"\") {
    \$base = \"px-3 py-2 rounded-lg transition \" . \$extra . \" \";
    if (\$page_name === \$current_page_name) {
        return \$base . \"text-white bg-white/10 border border-white/10 font-bold shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]\";
    }
    return \$base . \"hover:text-white hover:bg-white/5\";
}
";

$content = str_replace(
    "\$current_user = current_user();\n\$user_role = user_role();\n\$db = getDB();",
    $current_page_logic,
    $content
);

$navs = [
    ["index.php", "flex items-center gap-1.5", "<i class=\"fa-solid fa-house text-xs text-purple-400\"></i> Home"],
    ["events.php", "flex items-center gap-1.5", "<i class=\"fa-solid fa-calendar-days text-xs text-cyan-400\"></i> All Events"],
    ["gallery.php", "flex items-center gap-1.5", "<i class=\"fa-solid fa-images text-xs text-emerald-400\"></i> Media Gallery"],
    ["about.php", "", "About Us"],
    ["contact.php", "", "Contact"],
    ["faqs.php", "", "FAQs"]
];

$desktop_nav_replacement = "<nav class=\"hidden md:flex items-center gap-1 font-medium text-sm text-slate-300\">\n";
foreach ($navs as $nav) {
    $desktop_nav_replacement .= "                <a href=\"<?= BASE_URL ?>/" . $nav[0] . "\" class=\"<?= get_nav_class(\"" . $nav[0] . "\", \$current_page_name, \"" . $nav[1] . "\") ?>\">\n";
    $desktop_nav_replacement .= "                    " . $nav[2] . "\n";
    $desktop_nav_replacement .= "                </a>\n";
}
$desktop_nav_replacement .= "            </nav>";

// Replace Desktop Nav block
$startNav = strpos($content, "<nav class=\"hidden md:flex");
$endNav = strpos($content, "</nav>", $startNav) + 6;
$content = substr_replace($content, $desktop_nav_replacement, $startNav, $endNav - $startNav);

file_put_contents("includes/header.php", $content);
?>
