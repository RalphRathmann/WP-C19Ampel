# WP-C19Ampel #
* Contributor: Ralph Rathmann
* Requires at least: 5.1.0
* Tested up to: 5.7.2
* Stable tag: 1.1.20
* License:  GPLv2 or later
* License URI:  https://www.gnu.org/licenses/gpl-2.0.html


## Description ##

Wordpress-Plugin to show Cov19-incidences from RKI for german districts (Landkreise) as traffic-light, as value and history chart


### Corona Ampel Settings: ###

Find Your German district (Landkreis) in the following pdf: https://github.com/RalphRathmann/WP-C19Ampel/blob/main/assets/RKI_Corona_Landkreise.pdf (Data from RKI-Datahub )

Set the OBJECTID in the Plugin-Page (WP-Backend / C19Ampel).

Embed with a shortcode:

[C19Ampel] - standard-settings

[C19Ampel show='16'] - Show Ampel with Objectid 16 (Hamburg)

Call per GET-Parameter: append ?landkreis=NN to your Page-URL, to show this certain district (Landkreis)

E.g.: https://rredv.net/corona-ampel/?landkreis=16 shows Hamburgs incidence

