<?php

namespace FCMS\Core;

/**
 * Module Interface
 *
 * Definiert die Schnittstelle für fCMS-Module
 */
interface ModuleInterface
{
    /**
     * Gibt den eindeutigen Modul-Namen zurück
     */
    public function getName(): string;

    /**
     * Gibt die Modul-Version zurück (Timestamp-Format: YYYYMMDDhhmm-dev/stable)
     */
    public function getVersion(): string;

    /**
     * Gibt den Anzeige-Titel zurück
     */
    public function getTitle(): string;

    /**
     * Gibt die Modul-Beschreibung zurück
     */
    public function getDescription(): string;

    /**
     * Gibt den Autor zurück
     */
    public function getAuthor(): string;

    /**
     * Gibt die Mindestversion von fCMS zurück
     */
    public function getRequiredFcmsVersion(): string;

    /**
     * Wird beim Aktivieren des Moduls aufgerufen
     */
    public function activate(): void;

    /**
     * Wird beim Deaktivieren des Moduls aufgerufen
     */
    public function deactivate(): void;

    /**
     * Wird beim Laden des Moduls aufgerufen
     * Hier können Services registriert und Routen hinzugefügt werden
     */
    public function boot(object $app, object $container): void;

    /**
     * Gibt die Modul-Konfiguration zurück
     */
    public function getConfig(): array;

    /**
     * Gibt an, ob das Modul installiert ist
     */
    public function isInstalled(): bool;

    /**
     * Installiert das Modul (erstellt Verzeichnisse, Dateien, etc.)
     */
    public function install(): bool;

    /**
     * Deinstalliert das Modul (entfernt Daten)
     */
    public function uninstall(): bool;
}
