# WP-C19Ampel #
* Contributor: Ralph Rathmann
* Requires at least: 5.1.0
* Tested up to: 5.7.2
* Stable tag: 1.1.21
* License:  GPLv2 or later
* License URI:  https://www.gnu.org/licenses/gpl-2.0.html


## Description ##

Wordpress-Plugin to show Cov19-incidences from RKI for german districts (Landkreise) as traffic-light, as value and history chart


### Corona Ampel Settings: ###

Find Your German district (Landkreis) in the following pdf: https://github.com/RalphRathmann/WP-C19Ampel/blob/main/assets/RKI_Corona_Landkreise.pdf (Data from RKI-Datahub )

Set the OBJECTID in the Plugin-Page (WP-Backend / C19Ampel).

Set threshold 1 and 2 in the settings page

Set the value for number days in the past to show in chart

Check or uncheck, if you want to show the integrated visit counter

Check or uncheck, if you want to remove the history data on plugin remove


### Embed with a shortcode: ###

[C19Ampel] - standard-settings

[C19Ampel show='16'] - Show Ampel with Objectid 16 (Hamburg)

Call per GET-Parameter: append ?landkreis=NN to your Page-URL, to show this certain district (Landkreis)

E.g.: https://rredv.net/corona-ampel/?landkreis=16 shows Hamburgs incidence


## Changelog: ##

V1.1.21:

- use of wordpress integrated functions to call external rest-api
- option to remove data on plugin remove
- number of days to show in the chart
- elements in css ids and classes in c19style.css
- unique prefix for every function

- minor changes to design and appearance
