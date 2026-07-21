<?php
/**
 * Shared `.page-header` renderer — replaces the copy-pasted header markup
 * that used to be repeated at the top of every entry-point page. Purely
 * presentational (no session/config dependency), so this one file is used
 * directly by src/*.php pages and required by admin/lib.php as well.
 */
function render_page_header(string $title, array $navLinks, string $userLabel = ''): void {
    echo '<div class="page-header">' . "\n";
    echo '  <h1>' . htmlspecialchars($title, ENT_QUOTES) . '</h1>' . "\n";
    echo '  <span>' . "\n";
    if ($userLabel !== '') {
        echo '    <span style="color:rgba(255,255,255,.6);font-size:.8rem;">' . htmlspecialchars($userLabel, ENT_QUOTES) . '</span>' . "\n";
    }
    foreach ($navLinks as $link) {
        $href  = htmlspecialchars($link['href'], ENT_QUOTES);
        $label = htmlspecialchars($link['label'], ENT_QUOTES);
        $attrs = !empty($link['external']) ? ' target="_blank" rel="noopener"' : '';
        echo '    &nbsp;|&nbsp; <a href="' . $href . '"' . $attrs . '>' . $label . '</a>' . "\n";
    }
    echo '  </span>' . "\n";
    echo '</div>' . "\n";
}
