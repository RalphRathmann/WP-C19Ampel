<?php
/*
* Plugin Name: Covid19-Ampel
* Plugin URI: https://rredv.net/WPcorona-ampel/
* Description: German Corona-Ampel, Incidence as Value, Traffic-light and Chart
* Version: 1.1.20
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

global $c19a_db_version;
$c19a_db_version = '1.0';


require(plugin_dir_path( __FILE__ ) . 'C19AAdmin.php');


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
		timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,		
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'c19a_db_version', $c19a_db_version );	
	
}


function C19A_render_Corona_Ampel($showmode = 'all'){


    setlocale(LC_TIME, "de_DE");
	
	$html='';
	
	//Pinneberg https://npgeo-corona-npgeo-de.hub.arcgis.com/datasets/917fc37a709542548cc3be077a786c17_0/data?geometry=-30.805%2C46.211%2C52.823%2C55.839&selectedAttribute=cases7_lk

	$rki_objid = get_option( 'CA19LK_ID',9);	
	
	if(isset($_GET["landkreis"]) && is_numeric($_GET["landkreis"])){
		$rki_objid = $_GET["landkreis"];
	}
	
	$grenzwert1 = get_option( 'CA19LK_grenzwert1',100 );
	$grenzwert2 = get_option( 'CA19LK_grenzwert2',165 );


	$aRKIdaten = array();

	$aRKIdaten[0] = C19A_fetch_RKI_Data($rki_objid, 0);	

	$aVerlauf = array();
	$aVerlauf = C19A_get_incidencesof_past($rki_objid,5);
	$tendenz_img = C19A_render_tendenz($aVerlauf,$aRKIdaten[0]['cases7_per_100k'],C19A_STYLE_TENDENZ_IMG_ONLY);
	

	$html.= "<div id='C19Abox'>";

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


		$html.= "<br>Verlauf letzte 5 Tage:<br>";
		$svg_width = 310;
		$svg_height = 200;
		$svg_bar_steps = $svg_width / 5;
	
		$highest_incidence = max(array_column($aVerlauf,"incidence"));
		if($highest_incidence > $svg_height) {$svg_height = round($highest_incidence + 20);}
		if($grenzwert2 > $svg_height){$svg_height = round($highest_incidence + 20);}
		$svg_text_y = $svg_height - 5;
		
		$svg_grenzwert35 = $svg_height - 35;	//scaled: intval($svg_height - ($svg_height * 35 / 100));
		$svg_grenzwert50 = $svg_height - 50;
		$svg_grenzwert1 = $svg_height - $grenzwert1;
		$svg_grenzwert2 = $svg_height - $grenzwert2;
		
		$seconds_a_day = 60 * 60 * 24;
		$html.= "<svg width='$svg_width' height='$svg_height' style='border: 1px solid gray; background-color: white;'>";
		
		$html.= "<rect x='0' y='$svg_grenzwert35' width='$svg_width' height='1' style='fill:green;stroke:black;opacity:0.3' />";
		$html.= "<text fill='#444' font-size='8' x='2' y='$svg_grenzwert35'>35</text>";	
		$html.= "<rect x='0' y='$svg_grenzwert50' width='$svg_width' height='1' style='fill:green;stroke:black;opacity:0.3' />";
		$html.= "<text fill='#444' font-size='8' x='2' y='$svg_grenzwert50'>50</text>";	
		$html.= "<rect x='0' y='$svg_grenzwert1' width='$svg_width' height='1' style='fill:red;stroke:black;opacity:0.3' />";
		$html.= "<text fill='#444' font-size='8' x='2' y='$svg_grenzwert1'>$grenzwert1</text>";			
		$html.= "<rect x='0' y='$svg_grenzwert2' width='$svg_width' height='1' style='fill:red;stroke:black;opacity:0.3' />";		
		$html.= "<text fill='#444' font-size='8' x='2' y='$svg_grenzwert2'>$grenzwert2</text>";					
		
		
		for($iic = 4;$iic >=0 ;$iic--){
			$svg_text_pos = $svg_width - 35 - ($iic * $svg_bar_steps);
			$html.= "<text fill='#000' font-size='10' x='$svg_text_pos' y='$svg_text_y'>" . date("d.m", time() - $seconds_a_day * $iic) . " </text>";	
			if(isset($aVerlauf[$iic]["incidence"])){
				$this_y_pos = $svg_height - intval($aVerlauf[$iic]["incidence"]);
				
				($this_y_pos > $svg_height - 15) ? $this_y_text_pos = $this_y_pos -14 : $this_y_text_pos = $this_y_pos;
				$html.= "<text fill='#000' font-size='10' x='$svg_text_pos' y='" . $this_y_text_pos . "'>" . round($aVerlauf[$iic]["incidence"]) . " </text>";	
				$html.= "<rect x='$svg_text_pos' y='$this_y_pos' rx='1' ry='1' width='25' height='$svg_height' style='fill:red;stroke:black;stroke-width:1;opacity:0.4' />";
			}
		}
		
		$html.= "</svg>";		
	
	
    $html.= "</div>";
	
	$html.= "</div>";    


return $html;
		
}

function C19A_render_tendenz($aVerlauf,$todays_incid,$style=C19A_STYLE_TENDENZ_IMG_ONLY){
	
	if(!is_array($aVerlauf)){return false;}
	
	$html = "";
	$c19image_pfad = plugin_dir_url( __FILE__ ) . "assets";
	
	$iCounter = 0;

	if($style > C19A_STYLE_TENDENZ_IMG_ONLY){$html.= "<div id='incidenz_tendenz' style='width:200px; height: 200px; background-color: silver;'>";}

	foreach($aVerlauf AS $past_incid){
		$iCounter++;
		if($iCounter == 2){
			$yesterdays_incid = $past_incid["incidence"];
			$iTendenz = round($yesterdays_incid - $todays_incid); 
			if($iTendenz > 0){
				$inzidenztext = "<img src='$c19image_pfad/Pfeil_runter.png' style='width:64px;' alt='sinkende Inzidenz'>";
			} elseif($iTendenz < 0){
				$inzidenztext = "<img src='$c19image_pfad/Pfeil_hoch.png' style='width:64px;' alt='steigende Inzidenz'>";
			} else {
				$inzidenztext = "<img src='$c19image_pfad/Pfeil_rechts.png' style='width:64px;' alt='gleichbleibende Inzidenz'>";
			}
		}

		if($style > C19A_STYLE_TENDENZ_IMG_ONLY){
			$html.= "<br>" . substr($past_incid["last_update"],0,5) . " " . $past_incid["incidence"];
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

    $html = "<div id='ampel-inzidenzwert' style=' width: 8rem; text-align: center; font-size: 2.5rem; font-weight: 700;'>";

	$html.= '<div id="ampel_heute" style="width: 5rem; height: 15rem; margin: 1rem 1.5rem; background-color: #555; border: 2px solid black; border-radius: 0.5rem; padding: 0.4rem; ">';

    if ($this_incidence > $grenzwert2) {  $opacity = "1"; } else {  $opacity = $gedimmt; } 
    $html.= "<div id='ampel_rot' class='C19Ampel_glas' style='width: 4rem; height: 4rem; border-radius: 2rem; background-color: red; opacity: $opacity; border: 1px solid black; margin-bottom: 0.5rem;'></div>";

    if ($this_incidence > $grenzwert1 && $this_incidence < $grenzwert2) {  $opacity = "1"; } else {  $opacity = $gedimmt; } 
    $html.= "<div id='ampel_gelb' class='C19Ampel_glas' style='width: 4rem; height: 4rem; border-radius: 2rem; background-color: yellow; opacity: $opacity; border: 1px solid black; margin-bottom: 0.5rem;'></div>";

    if ($this_incidence < $grenzwert1) {  $opacity = "1"; } else {  $opacity = $gedimmt; } 
    $html.= "<div id='ampel_gruen' class='C19Ampel_glas' style='width: 4rem; height: 4rem; border-radius: 2rem; background-color: lime; opacity: $opacity; border: 1px solid black; margin-bottom: 0.5rem;'></div>";    
    $html.= "</div>";
    $html.= "" . number_format($this_incidence,2,',','') . "<br><span style='display: inline-block; line-height: 1.2; font-size: 0.8rem; font-weight: 300;'>Fälle pro 100.000 Einwohner in 7 Tagen.</span>
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

// this part is from cT Corona-Ampel:
// https://www.heise.de/select/ct/2021/9/2107016303143684652
	$arcgis_uri = 'https://services7.arcgis.com/mOBPykOjAyBO2ZKk/arcgis/rest/services/RKI_Landkreisdaten/FeatureServer/0/query?where=OBJECTID=';
	$arcgis_fields = array(
		'OBJECTID', 'GEN', 'BEZ', 'BL',
		'cases', 'deaths',
		'cases_per_population',
		'cases7_per_100k', 'cases7_lk', 'death7_lk',
		'cases7_bl_per_100k', 'cases7_bl', 'death7_bl',
		'last_update'
	);

	$fieldstr = implode(",", $arcgis_fields);

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $arcgis_uri . $rki_objid . '&outFields=' . $fieldstr . '&returnGeometry=false&outSR=&f=json');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$RKI_result = curl_exec($curl);
	if (curl_errno($curl)) { return false;}   //"Arcgis Server no connection"
	curl_close($curl);

	$json = json_decode($RKI_result, true);

	if (!isset($json['features'][0]['attributes'])) { return false; }   // no data

	$RKI_data = $json['features'][0]['attributes'];
	$last_update = DateTime::createFromFormat("d.m.Y, H:i", str_replace(" Uhr", "", $RKI_data['last_update']));
	$RKI_data['timestamp'] = $last_update->format("U");

	if(idate("H") > 2){
		C19A_setHistory($RKI_data,$iToday);	//maybe unreliable until 2 ?
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
		'timestamp' => $rki_zeile[0]->timestamp
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
				  'last_update' => $RKI_data["last_update"]
				 );
	$format = array('%s','%d','%s','%s','%s','%d','%d','%f','%f','%d','%d','%f','%d','%d','%s');
	$wpdb->insert($table,$data,$format);
	
	$result = $wpdb->insert_id;

	return $result;
}


//-----------------------------------------------------------------------------------------------

add_shortcode( 'C19Ampel', 'C19A_C19Ampel' );

function C19A_shortcodes_init(){

	function C19A_C19Ampel($atts = [], $content = null, $tag = ''){

		$C19A_app_shortcodepar = shortcode_atts(array("show" => "all"),$atts,$tag);

        $C19A_sc_action = $C19A_app_shortcodepar["show"];

        $debug_html = $C19A_sc_action;
 
		return C19A_render_Corona_Ampel($C19A_sc_action);
	}
 
}

add_action('init', 'C19A_shortcodes_init');

register_activation_hook( __FILE__, 'C19A_install_db' );


function C19A_register_plugin_styles() {
    wp_register_style( 'C19Ampel_style', plugin_dir_url( __FILE__ ) . 'css/c19style.css' );
    wp_enqueue_style( 'C19Ampel_style' );
}
add_action( 'wp_enqueue_scripts', 'C19A_register_plugin_styles' );

?>
