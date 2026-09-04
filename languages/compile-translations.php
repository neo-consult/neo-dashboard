<?php
/**
 * Kompiliert alle .po-Dateien zu .mo-Dateien
 * 
 * Verwendung: php compile-translations.php
 */

$plugin_dir = __DIR__;
$po_files = glob($plugin_dir . '/*.po');

if (empty($po_files)) {
    echo "Keine .po-Dateien gefunden in: $plugin_dir\n";
    exit(1);
}

foreach ($po_files as $po_file) {
    $mo_file = str_replace('.po', '.mo', $po_file);
    echo "Kompiliere: " . basename($po_file) . " -> " . basename($mo_file) . "\n";
    
    if (compile_po_to_mo($po_file, $mo_file)) {
        echo "  ✓ Erfolgreich: " . basename($mo_file) . "\n";
    } else {
        echo "  ✗ Fehler beim Kompilieren von " . basename($po_file) . "\n";
    }
}

echo "\nFertig!\n";

/**
 * Kompiliert eine .po-Datei zu einer .mo-Datei
 */
function compile_po_to_mo(string $po_file, string $mo_file): bool
{
    if (!file_exists($po_file)) {
        return false;
    }
    
    $content = file_get_contents($po_file);
    if ($content === false) {
        return false;
    }
    
    $entries = parse_po_file($content);
    return write_mo_file($mo_file, $entries);
}

function parse_po_file(string $content): array
{
    $entries = [];
    $lines = explode("\n", $content);
    $current_msgid = null;
    $current_msgstr = null;
    $in_msgid = false;
    $in_msgstr = false;
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        if (empty($line) || $line[0] === '#') {
            if ($current_msgid !== null && $current_msgstr !== null) {
                $entries[$current_msgid] = $current_msgstr;
                $current_msgid = null;
                $current_msgstr = null;
            }
            continue;
        }
        
        if (preg_match('/^msgid\s+"(.*)"$/', $line, $matches)) {
            $current_msgid = stripcslashes($matches[1]);
            $in_msgid = true;
            $in_msgstr = false;
            continue;
        }
        
        if ($in_msgid && preg_match('/^"(.*)"$/', $line, $matches)) {
            $current_msgid .= stripcslashes($matches[1]);
            continue;
        }
        
        if (preg_match('/^msgstr\s+"(.*)"$/', $line, $matches)) {
            $current_msgstr = stripcslashes($matches[1]);
            $in_msgid = false;
            $in_msgstr = true;
            continue;
        }
        
        if ($in_msgstr && preg_match('/^"(.*)"$/', $line, $matches)) {
            $current_msgstr .= stripcslashes($matches[1]);
            continue;
        }
        
        $in_msgid = false;
        $in_msgstr = false;
    }
    
    if ($current_msgid !== null && $current_msgstr !== null) {
        $entries[$current_msgid] = $current_msgstr;
    }
    
    return $entries;
}

function write_mo_file(string $mo_file, array $entries): bool
{
    unset($entries['']);
    ksort($entries);
    
    $msgids = array_keys($entries);
    $msgstrs = array_values($entries);
    
    $num_strings = count($entries);
    $offset_table_size = $num_strings * 2 * 4;
    $offset_msgid = 28;
    $offset_msgstr = $offset_msgid + $offset_table_size;
    
    $msgid_offsets = [];
    $msgstr_offsets = [];
    $current_offset = $offset_msgstr + $offset_table_size;
    
    foreach ($msgids as $msgid) {
        $msgid_offsets[] = $current_offset;
        $current_offset += strlen($msgid) + 1;
    }
    
    foreach ($msgstrs as $msgstr) {
        $msgstr_offsets[] = $current_offset;
        $current_offset += strlen($msgstr) + 1;
    }
    
    $fp = fopen($mo_file, 'wb');
    if (!$fp) {
        return false;
    }
    
    fwrite($fp, pack('V', 0x950412de));
    fwrite($fp, pack('V', 0));
    fwrite($fp, pack('V', $num_strings));
    fwrite($fp, pack('V', $offset_msgid));
    fwrite($fp, pack('V', $offset_msgstr));
    fwrite($fp, pack('V', 0));
    fwrite($fp, pack('V', 0));
    
    foreach ($msgid_offsets as $offset) {
        fwrite($fp, pack('V', strlen($msgids[array_search($offset, $msgid_offsets)])));
        fwrite($fp, pack('V', $offset));
    }
    
    foreach ($msgstr_offsets as $i => $offset) {
        fwrite($fp, pack('V', strlen($msgstrs[$i])));
        fwrite($fp, pack('V', $offset));
    }
    
    foreach ($msgids as $msgid) {
        fwrite($fp, $msgid . "\0");
    }
    
    foreach ($msgstrs as $msgstr) {
        fwrite($fp, $msgstr . "\0");
    }
    
    fclose($fp);
    return true;
}

