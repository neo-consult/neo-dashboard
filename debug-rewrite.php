<?php
/**
 * Debug Script - überprüft WordPress Rewrite Rules
 */
$url = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($url, '/neo-dashboard/') === 0) {
    echo "<!-- DEBUG: Neo Dashboard Route Debug -->\n";
    echo "<!-- REQUEST_URI: " . htmlspecialchars($url) . " -->\n";
    
    global $wp_rewrite;
    if ($wp_rewrite) {
        echo "<!-- REWRITE RULES (neo-dashboard): -->\n";
        $rules = $wp_rewrite->rules;
        foreach ($rules as $pattern => $replacement) {
            if (strpos($pattern, 'neo') !== false) {
                echo "<!-- Pattern: " . htmlspecialchars($pattern) . " => " . htmlspecialchars($replacement) . " -->\n";
            }
        }
    }
}
