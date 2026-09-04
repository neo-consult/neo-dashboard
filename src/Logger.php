<?php
declare(strict_types=1);

namespace NeoDashboard\Core;

use Exception;

class Logger
{
    private bool $enabled;
    private bool $writeToFile;
    private bool $writeToErrorLog;

    public function __construct(?bool $enabled = null, ?bool $writeToFile = null, ?bool $writeToErrorLog = null)
    {
        $this->enabled = $enabled ?? (defined('WP_DEBUG') && WP_DEBUG);
        $this->writeToFile = $writeToFile ?? $this->enabled;
        $this->writeToErrorLog = $writeToErrorLog ?? false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Schreibt eine Logzeile mit Level, Nachricht und optionalen Daten.
     */
    public function log(string $message, array $data = [], string $level = 'INFO', ?string $category = null): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!$this->writeToFile && !$this->writeToErrorLog) {
            return;
        }

        try {
            $timestamp = current_time('Y-m-d H:i:s');
            $logPath   = $this->getLogPath();
            $logDir    = dirname($logPath);

            // Prüfe ob die uploads Direktorie existiert und erstelle sie bei Bedarf
            if (!file_exists($logDir)) {
                if (!wp_mkdir_p($logDir)) {
                    // Fallback: Wenn uploads nicht erstellt werden kann, keine Logs schreiben
                    return;
                }
            }

            // Prüfe ob die Direktorie beschreibbar ist
            if (!is_writable($logDir)) {
                return;
            }

            $category_prefix = $category ? "[{$category}] " : '';
            
            $line = sprintf(
                "[%s] [%s] %s%s | Data: %s\n",
                $timestamp,
                strtoupper($level),
                $category_prefix,
                $message,
                json_encode($data, JSON_UNESCAPED_UNICODE)
            );

            if ($this->writeToFile) {
                $result = @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
                if ($result === false) {
                    return;
                }
            }

            if ($this->writeToErrorLog) {
                error_log(rtrim($line));
            }
        } catch (Exception $e) {
            // Bei jedem Fehler einfach nichts tun (keine Logs ausgeben)
            return;
        }
    }

    /**
     * Convenience-Methode für Warnungen.
     */
    public function warn(string $message, array $data = []): void
    {
        $this->log($message, $data, 'WARNUNG');
    }

    /**
     * Compatibility alias for warning() used elsewhere as warning().
     */
    public function warning(string $message, array $data = []): void
    {
        $this->warn($message, $data);
    }

    /**
     * Convenience-Methode für Fehler.
     */
    public function error(string $message, array $data = []): void
    {
        $this->log($message, $data, 'ERROR');
    }

    /**
     * Convenience-Methode für Informationen.
     */
    public function info(string $message, array $data = []): void
    {
        $this->log($message, $data, 'INFO');
    }

    /**
     * Debug level logging. Mapped to INFO by default to keep logs available.
     */
    public function debug(string $message, array $data = []): void
    {
        $this->log($message, $data, 'DEBUG');
    }

    /**
     * Gibt den vollständigen Pfad zur Logdatei zurück.
     */
    public function getLogPath(): string
    {
        // Fallback für lokale Entwicklung - verwende das plugin Verzeichnis
        $uploads_dir = WP_CONTENT_DIR . '/uploads';
        
        // Wenn uploads Verzeichnis nicht existiert, verwende plugin Verzeichnis
        if (!file_exists($uploads_dir) || !is_writable($uploads_dir)) {
            return plugin_dir_path(__FILE__) . '../logs/neo-dashboard.log';
        }
        
        return $uploads_dir . '/neo-dashboard.log';
    }

    /**
     * Löscht (leert) die Logdatei, behält aber die Datei.
     *
     * @return bool true bei Erfolg, false bei Fehler.
     */
    public function clear(): bool
    {
        $path = $this->getLogPath();
        if (file_exists($path)) {
            // Datei leeren, behalte die Datei erhalten
            $result = @file_put_contents($path, '', LOCK_EX);
            return $result !== false;
        }
        return false;
    }
}
