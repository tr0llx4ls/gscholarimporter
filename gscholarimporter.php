<?php
/**
 * @package GScholarImporter
 * @version 0.0.1
 */
/*
Plugin Name: GScholarImporter
Plugin URI: https://github.com/tr0llx4ls/gscholarimporter
Description: This is a plugin imports data from Google Scholar
Version: 0.0.1
Author URI: https://carlescalpe.es
*/


echo "Hola mundo";

//Activar el plugin
function Activar(){
    global $wpdb;

    //Crea una tabla que se llame gsi_settings que tenga un campo que se llame id y otro que se llame key y otro que se llame valu
}
//Desactivar el plugin
function Desactivar(){
}

//Hooks de activación y desactivación
register_activation_hook(__FILE__, 'Activar');
register_deactivation_hook( __FILE__,'Desactivar');


//Crear menu en el admin
add_action('admin_menu','CreaMenu');

//inicializa los parametros de la tabla de configuracion
add_action('admin_init', 'gscholarimporter_settings_init');
 

//Función que crea el menu
function CreaMenu(){
    add_menu_page(
        'GScholarImporter', //Titol del la pagina
        'GSI', //Titol del menu
        'manage_options', //Capability -> A quin usuaris?
        plugin_dir_path(__FILE__).'admin/gscholarimporter_main.php', //slug 
        null, //Funció que mostra el contingut
        plugin_dir_url(__FILE__).'admin/img/icon.svg', //Url e la imatge
        '1'//Posició
    ); 

    add_submenu_page(
        plugin_dir_path(__FILE__).'admin/gscholarimporter_main.php', //slug del padre
        'Settings', //Titol de la pagina
        'Settings', //Titol del menu
        'manage_options', //Capability -> A quin usuaris?
        'gscholarimporter_settings', //Funcio
        'SubmenuSettings' //Funció
    );

}

//Función que crea el submenu   
function SubmenuSettings(){
    include_once plugin_dir_path(__FILE__).'admin/settings.php';
}
//Encolar 

//Encolar bootstrap
function EncolarBootstrapJS($hook){
    //compureba si el hook es el que queremos
    if($hook != 'testplugin/admin/lista_encuestas.php'){
        return ;
    }
    wp_enqueue_script('bootstrapjs',plugins_url('admin/bootstrap/js/bootstrap.min.js',__FILE__),array('jquery'));
}
add_action('admin_enqueue_scripts','EncolarBootstrapJS');

//Encolar css propi de bootstrap
function EncolarBootstrapCSS($hook){
    //compureba si el hook es el que queremos
    if($hook != 'testplugin/admin/lista_encuestas.php'){
        return ;
    }
    wp_enqueue_style('bootstrapcss',plugins_url('admin/bootstrap/css/bootstrap.min.css',__FILE__));
}
add_action('admin_enqueue_scripts','EncolarBootstrapCSS');


//Encolar js propi 
function EncolarJS($hook){
    //compureba si el hook es el que queremos
    if($hook != 'testplugin/admin/lista_encuestas.php'){
        return ;
    }
    wp_enqueue_script('JsExterno',plugins_url('admin/js/testplugin.js',__FILE__),array('jquery'));
    wp_localize_script('JsExterno','SolicitudesAjax',[
        'url' => admin_url('admin-ajax.php'),
        'seguridad' => wp_create_nonce('sec')
    ]);
}
add_action('admin_enqueue_scripts','EncolarJS');

//ajax

function EliminarEncuesta(){
    $nonce = $_POST['nonce'];
    if(!wp_verify_nonce($nonce,'sec')){
        die('No tienes permisos para hacer esto');
    }

    $id = $_POST['id'];
    global $wpdb;

    $wpdb->delete($wpdb->prefix.'encuestas',['EncuestaId' => $id]);
    $wpdb->delete($wpdb->prefix.'encuestas_detalle',['EncuestaId' => $id]);

    return true;
}
add_action('wp_ajax_peticioneliminar','EliminarEncuesta');


//shortcode

function ShortcodeEncuesta($atts){
    $_short = new CodigoCorto();
    //obtenemos el id por parametro
    $id = $atts['id'];
    //Programar aciones del botion
    if(isset($_POST['btnguardar'])){
        $listadepreguntas = $_short->ObtenerEncuestasDetalle($id);
        $codigo = uniqid();
        foreach ($listadepreguntas as $key => $value) {
            $detalleid = $value['DetalleId'];
            if(isset($_POST[$detalleid])) {
                $respuesta = $_POST[$detalleid];
                $datos = [
                    'DetalleId' => $detalleid,
                    'Codigo' => $codigo,
                    'Respuesta' => $respuesta
                ];
                $_short->GuardarRespuesta($datos);
            }
        }
        return "Gracias por responder la encuesta";
    }
    //imprimimos el formulario
    $html  = $_short->Armador($id);
    return $html;
}
add_shortcode('Enc','ShortcodeEncuesta');
