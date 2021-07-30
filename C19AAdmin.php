<?php
/*
* Admin-Backend:
*/

function C19A_install_db(){

	// thanks to https://codex.wordpress.org/Creating_Tables_with_Plugins

	global $wpdb;
	global $c19a_db_version;

	$table_name = $wpdb->prefix . 'c19a_corona_history';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		date varchar(8) NOT NULL,
		objectid mediumint(5) NOT NULL,
		gen varchar(80),
		bez varchar(80),
		bl varchar(80),
		cases mediumint(9),
		deaths mediumint(9),
		cases_per_population float,
		cases7_per_100k float,
		cases7_lk mediumint(9),
		death7_lk mediumint(9),
		cases7_bl_per_100k float,
		cases7_bl mediumint(9),
		death7_bl mediumint(9),
		last_update varchar(80),
		visits mediumint(9),
		timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,		
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	update_option( 'c19a_db_version', $c19a_db_version );	
	
}

function C19A_ca_uninstall(){

	global $wpdb;
	if ( get_option( 'C19ALK_data_on_delete' ) !== true ) {
		return;
	}

	// thanks to https://developer.wordpress.org/reference/functions/delete_option/
	$settingOptions = array( 'C19ALK_data_on_delete', 'c19a_db_version', 'C19ALK_ID', 'C19ALK_grenzwert1', 'C19ALK_grenzwert2' ); // etc
	// Clear up our settings
	foreach ( $settingOptions as $settingName ) {
		delete_option( $settingName );
	}
	$wpdb->query( 'OPTIMIZE TABLE `' . $wpdb->options . '`' );

	$table_name = $wpdb->prefix . 'c19a_corona_history';
	$sql = 'drop table `' . $table_name . '` if exists ';
	$wpdb->query( $sql );
	
}

function C19A_ca_deactivate() {
    $timestamp = wp_next_scheduled( 'c19a_ampel_cron_hook' );
    wp_unschedule_event( $timestamp, 'c19a_ampel_cron_hook' );
}	



function C19A_admin_menu() {
		add_menu_page(
			__( 'C19 Ampel Einstellung', '' ),
			__( 'Corona Ampel', 'C19Ampel' ),
			'manage_options',
			'C19ASettings',
			'C19A_C19ASettings',
			'dashicons-schedule',
			65
		);
	}

	add_action( 'admin_menu', 'C19A_admin_menu' );


	function C19A_C19ASettings() {

    	echo "<h1>Corona Ampel Einstellungen:</h1>";

        echo "<div>
        <p>Besuchen Sie bitte die folgende Seite und wählen dort den anzuzeigenden Landkreis aus.<br><a href='" .
        esc_url("https://npgeo-corona-npgeo-de.hub.arcgis.com/datasets/917fc37a709542548cc3be077a786c17_0/data?geometry=-30.805%2C46.211%2C52.823%2C55.839&selectedAttribute=cases7_lk") ."' target=_blank>RKI Dashboard Landkreise</a><br><a href='" . 
		esc_url("https://github.com/RalphRathmann/WP-C19Ampel/blob/main/assets/RKI_Corona_Landkreise.pdf") . "' target=_blank>RKI-OBJECTID Landkreise als PDF</a>
        <br><p>Die OBJECTID aus der linken Spalte dann hier im untenstehenden Formular als Landkreis-ID eintragen.</p>
		<h3>Einbettung:</h3>
		<p>Das Plugin wird als Shortcode eingebunden:</p>
		<p>[C19Ampel] - Mit Standardwerten einbinden</p>
		<p>[C19Ampel show='16'] - Ampel mit Objectid 16 einbinden (Hamburg)</p>
		<p>Aufruf per GET-Parameter: wird die Ampel-Seite mit <code>?landkreis=NN</code> aufgerufen, kann ein beliebiger Landkreis angezeigt werden.<br>
		Beispiel: <code>" . esc_url("https://meine-beispielseite.de/corona-ampel/?landkreis=16") . "</code>
		</p>
        </div>";


        echo "<br><div class='c19a-settings'>
        <form method='post' action='options.php'>";

        settings_fields( 'C19ASettings' );
        do_settings_sections( 'C19ASettings' );
        submit_button(); 

        echo "</form></div>";

        echo "<br><div><small>Das Plugin ist auf Basis des Informationsstandes vom 15.07.2021 erstellt worden.<br>DB-Version: " . esc_html(get_option( 'c19a_db_version')) . "</small></div>";
	    

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
               'C19ALK_ID',
               __( 'Landkreis-ID:', 'C19Ampel' ),
               'C19ALK_ID_setting_markup',
               'C19ASettings',
               'C19A_Settings'
            );
            register_setting( 'C19ASettings', 'C19ALK_ID' );

            add_settings_field(
                'C19ALK_grenzwert1',
                __( 'Grenzwert 1:', 'C19Ampel' ),
                'C19ALK_grenzwert1_setting_markup',
                'C19ASettings',
                'C19A_Settings'
             );
             register_setting( 'C19ASettings', 'C19ALK_grenzwert1' );

             add_settings_field(
                'C19ALK_grenzwert2',
                __( 'Grenzwert 2:', 'C19Ampel' ),
                'C19ALK_grenzwert2_setting_markup',
                'C19ASettings',
                'C19A_Settings'
             );
             register_setting( 'C19ASettings', 'C19ALK_grenzwert2' );
		

             add_settings_field(
                'C19A_days_back',
                __( 'Tage in Chart:', 'C19Ampel' ),
                'C19A_days_back_setting_markup',
                'C19ASettings',
                'C19A_Settings'
             );
             register_setting( 'C19ASettings', 'C19A_days_back' );		
		
             add_settings_field(
                'C19A_show_counter',
                __( 'Counter anzeigen:', 'C19Ampel' ),
                'C19A_show_counter_setting_markup',
                'C19ASettings',
                'C19A_Settings'
             );
             register_setting( 'C19ASettings', 'C19A_show_counter' );			

             add_settings_field(
                'C19ALK_data_on_delete',
                __( 'History entfernen, wenn Plugin gelöscht wird:', 'C19Ampel' ),
                'C19ALK_data_on_delete_setting_markup',
                'C19ASettings',
                'C19A_Settings'
             );
             register_setting( 'C19ASettings', 'C19ALK_data_on_delete' );             
    }
    
    
    function C19A_setting_section_callback_function() {
        C19A_install_db();
		
		if ( ! wp_next_scheduled( 'c19a_ampel_cron_hook' ) ) {
			wp_schedule_event( mktime(10, 10, 10, 01, 07, 2021), 'daily', 'c19a_ampel_cron_hook' );
		}		
		
    }
    
    
    function C19ALK_ID_setting_markup() {
        echo "<label for='C19ALK_ID'>Geben Sie Ihren Landkreis als ID ein:</label><br><br>
        <input type='number' id='C19ALK_ID' name='C19ALK_ID' value='" . intval(get_option( 'C19ALK_ID' )) . "'><br><br>";
    }

    function C19ALK_grenzwert1_setting_markup() {
        echo "<label for='C19ALK_grenzwert1'>Geben Sie den Grenzwert für Schliessungen ein, also ab wann springt die Ampel auf Rot:</label><br><br>
        <input type='number' id='C19ALK_grenzwert1' name='C19ALK_grenzwert1' value='" . intval(get_option( 'C19ALK_grenzwert1' )) . "'><br><br>";
    }

    function C19ALK_grenzwert2_setting_markup() {
        echo "<label for='C19ALK_grenzwert2'>Geben Sie den Grenzwert 2 für weitere Maßnahmen / Schliessungen ein:</label><br><br>
        <input type='number' id='C19ALK_grenzwert2' name='C19ALK_grenzwert2' value='" . intval(get_option( 'C19ALK_grenzwert2' )) . "'><br><br>";
    }

    function C19A_days_back_setting_markup() {
        echo "<label for='C19A_days_back'>Anzahl Tage in Chart:</label><br><br>
        <input type='number' id='C19A_days_back' name='C19A_days_back' value='" . intval(get_option( 'C19A_days_back' ,5 )) . "'><br><br>";
    }

    function C19A_show_counter_setting_markup() {
        echo "<label for='C19A_show_counter'>Besucherzähler anzeigen:</label><br><br>
        <input type='checkbox' id='C19A_show_counter' name='C19A_show_counter'";
		echo (get_option( 'C19A_show_counter' ) == true) ? " checked><br><br>" : "><br><br>";
    }

    function C19ALK_data_on_delete_setting_markup() {
        echo "<hr><label for='C19ALK_data_on_delete'>Falls dieses Plugin entfernt wird, die erzeugten Daten entfernen :</label><br><br>
        <input type='checkbox' id='C19ALK_data_on_delete' name='C19ALK_data_on_delete' ";
		echo (get_option( 'C19ALK_data_on_delete' ) == true) ? " checked><br><br>" : "><br><br>";
    }

?>
