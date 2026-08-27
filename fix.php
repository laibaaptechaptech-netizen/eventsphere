<?php
$content = file_get_contents("event_detail.php");
$orgCard = file_get_contents("org_card.txt");

$regStart = strpos($content, "<!-- Registration & Ticket Pass Box -->");
$regEnd = strpos($content, "<!-- End of Event Detail Wrapper -->");

if ($regStart === false) {
    echo "Could not find reg start";
    exit;
}

$regBlock = substr($content, $regStart, $regEnd - $regStart);
// Actually, it ends before the closing </div> of the grid, let us find `    </div>\n</div>\n\n<!-- End of Event Detail Wrapper -->`
// Just cut out $regBlock:
$content = str_replace($regBlock, $orgCard . "\n        </div>\n", $content);

// Remove sticky top-24 from regBlock
$regBlockFixed = str_replace("sticky top-24", "", $regBlock);

$reviewsPos = strpos($content, "<!-- Multi-Dimensional Peer Reviews & Feedback Section -->");
$content = substr_replace($content, $regBlockFixed . "\n\n            ", $reviewsPos, 0);

file_put_contents("event_detail.php", $content);
echo "Fixed layout successfully.";
?>
