# WP-C19Ampel
Wordpress-Plugin to show incidences as traffic-light and more

Until it is available in the WordPress Plugin repository, Copy to Your WordPress-Plugin Directory as subfolder "C19Ampel"

Corona Ampel Settings:

Besuchen bitte die folgende Seite und wähle dort den anzuzeigenden Landkreis aus.
RKI Dashboard Landkreise

Die OBJECTID aus der linken Spalte dann hier im untenstehenden Formular eintragen.

Einbettung:
Das Plugin wird als Shortcode eingebunden:

[C19Ampel] - Mit Standardwerten einbinden

[C19Ampel show='16'] - Ampel mit Objectid 16 einbinden (Hamburg)

Aufruf per GET-Parameter: wird die Ampel-Seite mit ?landkreis=NN aufgerufen, kann ein beliebiger Landkreis angezeigt werden.
Beispiel: https://meine-beispielseite.de/corona-ampel/?landkreis=16


