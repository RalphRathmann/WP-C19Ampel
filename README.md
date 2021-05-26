# WP-C19Ampel

Wordpress-Plugin to show Cov19-incidences from RKI for german regions (Landkreise) as traffic-light and more

Until it is available in the WordPress Plugin repository, copy these files and subfolders to Your WordPress-Plugin Directory as subfolder "C19Ampel"


Corona Ampel Settings:

Find Your German district (Landkreis) in the following pdf:
https://github.com/RalphRathmann/WP-C19Ampel/blob/main/assets/RKI_Corona_Landkreise.pdf
(Data from [RKI-Datahub](https://npgeo-corona-npgeo-de.hub.arcgis.com/datasets/dd4580c810204019a7b8eb3e0b329dd6_0) ) 

Set the OBJECTID in the Plugin-Page (WP-Backend / C19Ampel).

Embed with a shortcode:

[C19Ampel] - standard-settings

[C19Ampel show='16'] - Show Ampel with Objectid 16 (Hamburg)

Call per GET-Parameter: append ?landkreis=NN to your Page-URL, to show this certain Landkreis

E.g.: https://rredv.net/corona-ampel/?landkreis=16 shows Hamburgs incidence


