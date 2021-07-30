<?php
/*
* Plugin Name: Covid19-Ampel
* Plugin URI: https://rredv.net/WPcorona-ampel/
* Description: German Corona-Ampel, Incidence as Value, Traffic-light and Chart
* Version: 1.1.23
* Author: Ralph Rathmann
* Author URI: https://rredv.net/
* Requires at least: 5.1
* Text Domain: wprrpi-c19ampel
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

* Copyright (C) 2021 Ralph Rathmann
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


defined( 'ABSPATH' ) || exit;


define("C19A_STYLE_TENDENZ_IMG_ONLY", 1);
define("C19A_STYLE_TENDENZ_DIV", 2);
define("C19A_RKI_RELIABLE_AFTER", 5);	//until 5AM, RKI Data are not stable

global $c19a_db_version;
$c19a_db_version = '1.1';

if (is_admin() ){
	require_once(plugin_dir_path( __FILE__ ) . 'C19AAdmin.php');	
}



function C19A_render_Corona_Ampel($showmode = 'all'){

	$html='';
	
	//Pinneberg https://npgeo-corona-npgeo-de.hub.arcgis.com/datasets/917fc37a709542548cc3be077a786c17_0/data?geometry=-30.805%2C46.211%2C52.823%2C55.839&selectedAttribute=cases7_lk
	if(isset($_GET["landkreis"]) && is_numeric($_GET["landkreis"])){
		$rki_objid = intval($_GET["landkreis"]);
	} else {
		$rki_objid = intval(get_option( 'C19ALK_ID',9));			
	}
	
	$grenzwert1 = intval(get_option( 'C19ALK_grenzwert1',100 ));
	$grenzwert2 = intval(get_option( 'C19ALK_grenzwert2',165 ));
	$days_back = intval(get_option( 'C19A_days_back',5 ));
	$show_counter = false; if (get_option( 'C19A_show_counter',true ) == true){$show_counter = true;}

	

	$aRKIdaten = array();

	$aRKIdaten[0] = C19A_fetch_RKI_Data($rki_objid, 0);	
	$visits = C19A_setandgetCounter($rki_objid);

	$aVerlauf = array();
	$aVerlauf = C19A_get_incidencesof_past($rki_objid,$days_back);
	$tendenz_img = C19A_render_tendenz($aVerlauf,$aRKIdaten[0]['cases7_per_100k'],C19A_STYLE_TENDENZ_IMG_ONLY);
	

	$html.= "<div id='C19Abox'>";

	//var_dump($show_counter);	
	
    $html.= "<h3>Inzidenz-Ampel " . $aRKIdaten[0]['BEZ'] . " " . $aRKIdaten[0]['GEN'] . "</h3>";
	$html.= "<p>für den " . date("d.m.Y") . "</p>";    

    $html.= "<div class='C19Aframe'>";
    $html.= C19A_render_tages_Ampel($aRKIdaten[0]['cases7_per_100k'], $grenzwert1,$grenzwert2);
	$html.= "</div>";
	
	$html.= "<div id='C19Atendenz' class='C19Aframe'>";
		$html.= "<br><small>Tendenz:</small><br>$tendenz_img";	
	$html.= "</div>";

    $html.= "<div id='C19Adetailbox'>";    
	
//    $html.= "<h4>" . $aRKIdaten[0]['GEN'] . ":</h4>";    
    $html.= "<p> 7 Tages-Inzidenz: <strong>" . number_format($aRKIdaten[0]['cases7_per_100k'],2,',','') . 
    "</strong><br><small>Fälle: " . $aRKIdaten[0]['cases7_lk'] . " (" . $aRKIdaten[0]['death7_lk'] . ")</small>
    </p>";


    $html.= "<h4>Gesamtes Bundesland " . $aRKIdaten[0]['BL'] . ":</h4>
    <p>7 Tages-Inzidenz: <strong>" . number_format($aRKIdaten[0]['cases7_bl_per_100k'],2,',','') . 
    "</strong><br>
    <small>Fälle: " . $aRKIdaten[0]['cases'] . " (<small>" . $aRKIdaten[0]['deaths'] . ")</small>
    </p>";


		$html.= "<br>Verlauf letzte $days_back Tage:<br>";
		$svg_width = 280 + 10 * $days_back;
		$svg_height = 200;
		$svg_bar_steps = $svg_width / $days_back;
	
		$highest_incidence = max(array_column($aVerlauf,"incidence"));
		if($highest_incidence > $svg_height) {$svg_height = round($highest_incidence + 20);}
		if($grenzwert2 > $svg_height){$svg_height = round($highest_incidence + 20);}
		$svg_text_y = $svg_height - 2;
		
		$svg_grenzwert35 = $svg_height - 35;	//scaled: intval($svg_height - ($svg_height * 35 / 100));
		$svg_grenzwert50 = $svg_height - 50;
		$svg_grenzwert1 = $svg_height - $grenzwert1;
		$svg_grenzwert2 = $svg_height - $grenzwert2;
		
		$seconds_a_day = 60 * 60 * 24;
		$html.= "<svg width='$svg_width' height='$svg_height' id='c19a-svg-chart'>";
		
		$html.= "<rect x='0' y='$svg_grenzwert35' width='$svg_width' height='1' style='fill:green;stroke:black;opacity:0.1' />";
		$html.= "<text fill='#999' font-size='8' x='2' y='$svg_grenzwert35'>35</text>";	
		if ($svg_grenzwert1 !== $svg_grenzwert50){
			$html.= "<rect x='0' y='$svg_grenzwert50' width='$svg_width' height='1' style='fill:green;stroke:black;opacity:0.1' />";
			$html.= "<text fill='#999' font-size='8' x='2' y='$svg_grenzwert50'>50</text>";	
		}

		$html.= "<rect x='0' y='$svg_grenzwert1' width='$svg_width' height='1' style='fill:red;stroke:black;opacity:0.3' />";
		$html.= "<text fill='#444' font-size='8' x='2' y='$svg_grenzwert1'>$grenzwert1</text>";			
		$html.= "<rect x='0' y='$svg_grenzwert2' width='$svg_width' height='1' style='fill:red;stroke:black;opacity:0.3' />";		
		$html.= "<text fill='#444' font-size='8' x='2' y='$svg_grenzwert2'>$grenzwert2</text>";					
		
		
		for($iic = $days_back - 1;$iic >=0 ;$iic--){
			$svg_text_pos = $svg_width - 35 - ($iic * $svg_bar_steps);
			$html.= "<text fill='#000' font-size='10' x='$svg_text_pos' y='$svg_text_y'>" . date("d.m", time() - $seconds_a_day * $iic) . " </text>";	
			if(isset($aVerlauf[$iic]["incidence"])){
				$this_y_pos = $svg_height - intval($aVerlauf[$iic]["incidence"]);
				
				($this_y_pos > $svg_height - 100) ? $this_y_text_pos = $this_y_pos -6 : $this_y_text_pos = $this_y_pos + 10;
				$html.= "<text fill='#000' font-size='10' x='$svg_text_pos' y='" . $this_y_text_pos . "'>" . round($aVerlauf[$iic]["incidence"]) . " </text>";	
				$html.= "<rect x='$svg_text_pos' y='$this_y_pos' rx='1' ry='1' width='25' height='$svg_height' style='fill:red;stroke:black;stroke-width:1;opacity:0.4' />";
			}
		}
		
		$html.= "</svg>";		
	
	
    $html.= "</div>";
	$html.= "</div>";    

	if ($show_counter == true){
		$html.= "<div id='c19a-counter' class='c19a-small-info'>Aufrufe: " .  $visits . "</div>";			
	}
	if( date_i18n("G",current_time('timestamp')) < C19A_RKI_RELIABLE_AFTER){
		$html.= "<div class='c19a-small-info'>Die Daten stehen evtl. noch nicht vollständig zur Verfügung!</div>";
	}
	
return $html;
		
}

function C19A_render_tendenz($aVerlauf,$todays_incid,$style=C19A_STYLE_TENDENZ_IMG_ONLY){
	
	if(!is_array($aVerlauf)){return false;}
	
	$html = "";
	$c19image_pfad = plugin_dir_url( __FILE__ ) . "assets";
	
	$iCounter = 0;

	if($style > C19A_STYLE_TENDENZ_IMG_ONLY){$html.= "<div id='c19a-incidence-tendenz'>";}

	foreach($aVerlauf AS $past_incid){
		$iCounter++;
		if($iCounter == 2){
			$yesterdays_incid = floatval($past_incid["incidence"]);
			$iTendenz = round($yesterdays_incid - $todays_incid); 
			if($iTendenz > 0){
				$inzidenztext = "<img src='$c19image_pfad/Pfeil_runter.png' class='c19a-arrow' alt='sinkende Inzidenz'>";
			} elseif($iTendenz < 0){
				$inzidenztext = "<img src='$c19image_pfad/Pfeil_hoch.png' class='c19a-arrow' alt='steigende Inzidenz'>";
			} else {
				$inzidenztext = "<img src='$c19image_pfad/Pfeil_rechts.png' class='c19a-arrow' alt='gleichbleibende Inzidenz'>";
			}
		}

		if($style > C19A_STYLE_TENDENZ_IMG_ONLY){
			$html.= "<br>" . esc_html(substr($past_incid["last_update"],0,5) . " " . $past_incid["incidence"]);
			$html.= "<br>" . $inzidenztext;
		}

	}
	if($style > C19A_STYLE_TENDENZ_IMG_ONLY){
		$html.= "</div>";
	} else {
		$html = $inzidenztext;
	}

	return $html;
	
}



function C19A_render_tages_Ampel($this_incidence, $grenzwert1 = 100,$grenzwert2 = 165)
{
    $opacity = "0.1";
    $gedimmt = $opacity;

    $html = "<div id='c19a-ampel-inzidenzwert'>";

	$html.= '<div id="c19a-ampel-housing">';

    if ($this_incidence > $grenzwert2) {  $opacity = "1"; } else {  $opacity = $gedimmt; } 
    $html.= "<div id='c19a-ampel-rot' class='c19a-ampel-glas' style='opacity: $opacity;'></div>";

    if ($this_incidence > $grenzwert1 && $this_incidence < $grenzwert2) {  $opacity = "1"; } else {  $opacity = $gedimmt; } 
    $html.= "<div id='c19a-ampel-gelb' class='c19a-ampel-glas' style='opacity: $opacity;'></div>";

    if ($this_incidence < $grenzwert1) {  $opacity = "1"; } else {  $opacity = $gedimmt; } 
    $html.= "<div id='c19a-ampel-gruen' class='c19a-ampel-glas' style='opacity: $opacity;'></div>";    
    $html.= "</div>";
    $html.= esc_html(number_format($this_incidence,2,',','')) . "<br><span class='c19a-inline-info'>Fälle pro 100.000 Einwohner in 7 Tagen.</span>
    </div>";

    return $html;
}


function C19A_fetch_RKI_Data($rki_objid,$days_back = 0){
	
	$iToday = date("Ymd"); 
	
	$RKI_data = C19A_getHistory($rki_objid, $iToday);	// we set only todays Data, other Days are fetched from our DB
	if($RKI_data !== false){
		return $RKI_data;	// return data from history
	} elseif($days_back > 0) {
		return false;	// no data for that day in history
	}

	
	//fetch new data from rki for today:
	$arcgis_uri = 'https://services7.arcgis.com/mOBPykOjAyBO2ZKk/arcgis/rest/services/RKI_Landkreisdaten/FeatureServer/0/query?where=OBJECTID=';
	$arcgis_fields = array(
		'OBJECTID', 'GEN', 'BEZ', 'BL',	'cases', 'deaths', 'cases_per_population', 'cases7_per_100k',
		'cases7_lk', 'death7_lk', 'cases7_bl_per_100k', 'cases7_bl', 'death7_bl', 'last_update'
	);

	$fieldstr = implode(",", $arcgis_fields);

	
	$rki_response = wp_remote_get( $arcgis_uri . $rki_objid . '&outFields=' . $fieldstr . '&returnGeometry=false&outSR=&f=json' );
	$rki_http_code = wp_remote_retrieve_response_code( $rki_response );	
	if ($rki_http_code !== 200){
		echo "<h2>Arcgis-Server nicht erreichbar: " . $rki_http_code . "</h2>";
		return false;
	}
	
	$RKI_result = wp_remote_retrieve_body( $rki_response );
	
	$json = json_decode($RKI_result, true);

	if (!isset($json['features'][0]['attributes'])) { 
		// no data
		echo "<h4>Server erreicht, aber keine Daten erhalten.</h4>";		
		return false; 
	}   

	
	
	$RKI_data = $json['features'][0]['attributes'];
	$last_update = DateTime::createFromFormat("d.m.Y, H:i", str_replace(" Uhr", "", $RKI_data['last_update']));
	$RKI_data['timestamp'] = $last_update->format("U");

	if(date_i18n("G",current_time('timestamp')) > C19A_RKI_RELIABLE_AFTER){
		C19A_setHistory($RKI_data,$iToday);	//RKI-Data are maybe unreliable before
	} else {
		echo "<h4>RKI-Server erreicht und Daten erhalten, aber vor " . C19A_RKI_RELIABLE_AFTER . " Uhr sind die Daten noch unzuverlässig.</h4>";		
	}

	return $RKI_data;

}

function C19A_get_incidencesof_past($rki_objectid, $days_back = 5){

	global $wpdb;

	$table = $wpdb->prefix.'c19a_corona_history';
	$querystring = "SELECT cases7_per_100k, last_update FROM " . $table;
	$querystring.= " WHERE objectid = $rki_objectid ";
	$querystring.= " ORDER BY id DESC ";
	$querystring.= " LIMIT " . $days_back;
	$rki_zeile = $wpdb->get_results($querystring);
	if(!is_array($rki_zeile) || count($rki_zeile) < 1){
		//nothing found
		return false;
	}
	
	$aReturn = array();
	
	foreach($rki_zeile AS $this_incidence){
		$aReturn[] = array("last_update" => $this_incidence->last_update, "incidence" => $this_incidence->cases7_per_100k);
	}

	return $aReturn;
	
}

function C19A_getHistory($rki_objectid, $date){


	global $wpdb;

	$table = $wpdb->prefix.'c19a_corona_history';
	$querystring = "SELECT * FROM " . $table . " WHERE objectid = $rki_objectid AND date = '" . $date . "' LIMIT 1";
	$rki_zeile = $wpdb->get_results($querystring);
	if(!is_array($rki_zeile) || count($rki_zeile) < 1){
		//objectid on this day not found
		return false;
	}
	
    $RKI_data = array(
		'OBJECTID' => $rki_zeile[0]->objectid, 
		'GEN' => $rki_zeile[0]->gen, 
		'BEZ' => $rki_zeile[0]->bez, 
		'BL' => $rki_zeile[0]->bl,
		'cases' => $rki_zeile[0]->cases,
		'deaths' => $rki_zeile[0]->deaths,
		'cases_per_population' => $rki_zeile[0]->cases_per_population,
		'cases7_per_100k' => $rki_zeile[0]->cases7_per_100k,
		'cases7_lk' => $rki_zeile[0]->cases7_lk,
		'death7_lk' => $rki_zeile[0]->death7_lk,
		'cases7_bl_per_100k' => $rki_zeile[0]->cases7_bl_per_100k,
		'cases7_bl' => $rki_zeile[0]->cases7_bl,
		'death7_bl' => $rki_zeile[0]->death7_bl,
		'last_update' => $rki_zeile[0]->last_update,
		'timestamp' => $rki_zeile[0]->timestamp,
		'visits' => $rki_zeile[0]->visits
    );  

	return $RKI_data;
	
}

function C19A_setHistory($RKI_data,$iToday){
	
	global $wpdb;
	
	$table = $wpdb->prefix.'c19a_corona_history';
	$data = array('date' => $iToday,
				  'objectid' => $RKI_data["OBJECTID"],
				  'gen' => $RKI_data["GEN"],
				  'bez' => $RKI_data["BEZ"],
				  'bl' => $RKI_data["BL"],
				  'cases' => $RKI_data["cases"],
				  'deaths' => $RKI_data["deaths"],
				  'cases_per_population' => $RKI_data["cases_per_population"],
				  'cases7_per_100k' => $RKI_data["cases7_per_100k"],
				  'cases7_lk' => $RKI_data["cases7_lk"],
				  'death7_lk' => $RKI_data["death7_lk"],
				  'cases7_bl_per_100k' => $RKI_data["cases7_bl_per_100k"],
				  'cases7_bl' => $RKI_data["cases7_bl"],
				  'death7_bl' => $RKI_data["death7_bl"],
				  'last_update' => $RKI_data["last_update"],
				  'visits' => 1
				 );
	$format = array('%s','%d','%s','%s','%s','%d','%d','%f','%f','%d','%d','%f','%d','%d','%s','%d');
	$wpdb->insert($table,$data,$format);
	
	$result = $wpdb->insert_id;

	return $result;
}

function C19A_setandgetCounter($rki_objectid){
	
	global $wpdb;
	
	$table = $wpdb->prefix.'c19a_corona_history';
	
	$iToday = date("Ymd");	
	
	$rows_affected = $wpdb->query("UPDATE $table SET visits = visits + 1 WHERE objectid = $rki_objectid AND date = '" . $iToday . "' LIMIT 1");	
	
	$querystring = "SELECT visits FROM " . $table . " WHERE objectid = $rki_objectid AND date = '" . $iToday . "' LIMIT 1";
	$result = $wpdb->get_results($querystring);	
	
	if(!is_array($result) || count($result) < 1){
		return false;	//nothing found
	}
	
	$visits = 0;
	foreach($result AS $this_line){
		$visits = $this_line->visits;
		break;
	}
	
	return $visits;
}



//-----------------------------------------------------------------------------------------------

add_shortcode( 'C19Ampel', 'C19A_C19Ampel' );

function C19A_shortcodes_init(){

	function C19A_C19Ampel($atts = [], $content = null, $tag = ''){

		$C19A_app_shortcodepar = shortcode_atts(array("show" => "all"),$atts,$tag);

        $C19A_sc_action = $C19A_app_shortcodepar["show"];

		return C19A_render_Corona_Ampel($C19A_sc_action);
	}
 
}

add_action('init', 'C19A_shortcodes_init');

if (is_admin() ){
	register_activation_hook( __FILE__, 'C19A_install_db' );
	register_deactivation_hook( __FILE__, 'C19A_ca_deactivate' ); 
}


function C19A_register_plugin_styles() {
    wp_register_style( 'C19Ampel_style', plugin_dir_url( __FILE__ ) . 'css/c19style.css' );
    wp_enqueue_style( 'C19Ampel_style' );
}

add_action( 'wp_enqueue_scripts', 'C19A_register_plugin_styles' );
if (is_admin() ){
	add_action( 'admin_enqueue_scripts', 'C19A_register_plugin_styles' ); //there is not much in it, so we use the same for the backend
}	

add_action( 'c19a_ampel_cron_hook', 'C19A_daily_fetch_per_cron' );		

function C19A_daily_fetch_per_cron() {
	// try to make shure the call at least once a day
	// gets it from local database on subsequent calls to prevent from abuse of external ressources
	$rki_objid = intval(get_option( 'C19ALK_ID',9));	
	$aRKIdaten = array();
	$aRKIdaten[0] = C19A_fetch_RKI_Data($rki_objid, 0);	

}

?>
