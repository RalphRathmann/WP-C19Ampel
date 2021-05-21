<?php
/*
* Plugin Name: Covid19-Ampel
* Plugin URI: https://rredv.net/WPcorona-ampel/
* Description: German Corona-Ampel als WordPress Plugin
* Author: Ralph Rathmann
* Author URI: https://rredv.net/
* Text Domain: C19Ampel
* Version: 0.16
* License:     GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

* Copyright (C) 2021 Ralph Rathmann (https://rredv.net)
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License along
* with this program; if not, write to the Free Software Foundation, Inc.,
* 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/




function C19A_admin_menu() {
		add_menu_page(
			__( 'C19 Ampel Einstellung', '' ),
			__( 'Corona Ampel', 'C19Ampel' ),
			'manage_options',
			'C19ASettings',
			'C19ASettings',
			'dashicons-schedule',
			65
		);
	}

	add_action( 'admin_menu', 'C19A_admin_menu' );


	function C19ASettings() {

    	echo "<h1>Corona Ampel Einstellungen:</h1>";

        echo "<div>
        <p>Besuchen bitte die folgende Seite und wähle dort den anzuzeigenden Landkreis aus.
        <br><a href='https://npgeo-corona-npgeo-de.hub.arcgis.com/datasets/917fc37a709542548cc3be077a786c17_0/data?geometry=-30.805%2C46.211%2C52.823%2C55.839&selectedAttribute=cases7_lk' target=_blank>RKI Dashboard Landkreise</a>
        <br><p>Die OBJECTID aus der linken Spalte dann hier im untenstehenden Formular eintragen.</p>
		<h3>Einbettung:</h3>
		<p>Das Plugin wird als Shortcode eingebunden:</p>
		<p>[C19Ampel] - Mit Standardwerten einbinden</p>
		<p>[C19Ampel show='16'] - Ampel mit Objectid 16 einbinden (Hamburg)</p>
		<p>Aufruf per GET-Parameter: wird die Ampel-Seite mit <code>?landkreis=NN</code> aufgerufen, kann ein beliebiger Landkreis angezeigt werden.<br>
		Beispiel: <code>https://meine-beispielseite.de/corona-ampel/?landkreis=16</code>
		</p>
        </div>";


        echo "<br><div class='' style='border: 1px solid green; border-radius: 1em; padding: 1em; margin: 1em;'>
        <form method='post' action='options.php'>";

        settings_fields( 'C19ASettings' );
        do_settings_sections( 'C19ASettings' );
        submit_button(); 

        echo "</form></div>";

        echo "<br><div><small>Das Plugin ist auf Basis des Informationsstandes vom 20.05.2021 erstellt worden.<br>DB-Version: " . get_option( 'c19a_db_version') . "</small></div>";
		
	    

	}

    add_action( 'admin_init', 'C19A_settings_init' );

    function C19A_settings_init() {
    
        add_settings_section(
            'C19A_Settings',
            __( 'Einstellungen:', 'C19Ampel' ),
            'C19A_setting_section_callback_function',
            'C19ASettings'
        );
    
            add_settings_field(
               'CA19LK_ID',
               __( 'Landkreis-ID:', 'C19Ampel' ),
               'CA19LK_ID_setting_markup',
               'C19ASettings',
               'C19A_Settings'
            );
    
            register_setting( 'C19ASettings', 'CA19LK_ID' );

            add_settings_field(
                'CA19LK_grenzwert1',
                __( 'Grenzwert 1:', 'C19Ampel' ),
                'CA19LK_grenzwert1_setting_markup',
                'C19ASettings',
                'C19A_Settings'
             );
     
             register_setting( 'C19ASettings', 'CA19LK_grenzwert1' );

             add_settings_field(
                'CA19LK_grenzwert2',
                __( 'Grenzwert 2:', 'C19Ampel' ),
                'CA19LK_grenzwert2_setting_markup',
                'C19ASettings',
                'C19A_Settings'
             );
     
             register_setting( 'C19ASettings', 'CA19LK_grenzwert2' );

             
    }
    
    
    function C19A_setting_section_callback_function() {
        //echo "";
    }
    
    
    function CA19LK_ID_setting_markup() {
        echo "<label for='CA19LK_ID'>Geben Sie Ihren Landkreis als ID ein:</label><br><br>
        <input type='number' id='CA19LK_ID' name='CA19LK_ID' value='" . get_option( 'CA19LK_ID' ) . "'><br><br>";
    }

    function CA19LK_grenzwert1_setting_markup() {
        echo "<label for='CA19LK_grenzwert1'>Geben Sie den Grenzwert für Schliessungen ein, also ab wann springt die Ampel auf Rot:</label><br><br>
        <input type='number' id='CA19LK_grenzwert1' name='CA19LK_grenzwert1' value='" . get_option( 'CA19LK_grenzwert1' ) . "'><br><br>";
    }

    function CA19LK_grenzwert2_setting_markup() {
        echo "<label for='CA19LK_grenzwert2'>Geben Sie den Grenzwert 2 für weitere Maßnahmen / Schliessungen ein:</label><br><br>
        <input type='number' id='CA19LK_grenzwert2' name='CA19LK_grenzwert2' value='" . get_option( 'CA19LK_grenzwert2' ) . "'><br><br>";
    }


?>
