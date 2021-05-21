# WP-C19Ampel

Wordpress-Plugin to show Cov19-incidences from RKI for german regions (Landkreise) as traffic-light and more

Until it is available in the WordPress Plugin repository, copy these files and subfolders to Your WordPress-Plugin Directory as subfolder "C19Ampel"


Corona Ampel Settings:

Besuche folgende Seite und wähle dort den anzuzeigenden Landkreis aus.
RKI Dashboard Landkreise

Die OBJECTID aus der linken Spalte dann im WP-Backend unter C19Ampel eintragen.

Einbettung:
Das Plugin wird als Shortcode eingebunden:

[C19Ampel] - Mit Standardwerten einbinden

[C19Ampel show='16'] - Ampel mit Objectid 16 einbinden (Hamburg)

Aufruf per GET-Parameter: wird die Ampel-Seite mit ?landkreis=NN aufgerufen, kann ein beliebiger Landkreis angezeigt werden.
Beispiel: https://rredv.net/corona-ampel/?landkreis=16


