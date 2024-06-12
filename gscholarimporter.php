<?php
/**
 * @package Test Plugin
 * @version 0.0.1
 */
/*
Plugin Name: Test PLugin
Plugin URI: https://github.com/tr0llx4ls/testplugin
Description: This is a plugin done by tr0llx4ls to learn how to do them
Version: 0.0.1
Author URI: https://carlescalpe.es
*/

//requires
require_once plugin_dir_path(__FILE__).'clases/codigocorto.class.php';


//Activar el plugin
function Activar(){
    global $wpdb;

    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}encuestas(
         `EncuestaId` INT NOT NULL AUTO_INCREMENT,
         `Nombre` VARCHAR(45) NULL,
         `ShortCode` VARCHAR(45) NULL,
         PRIMARY KEY (`EncuestaId`))";

    $wpdb->query($sql);

    $sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}encuestas_detalle(
         `DetalleId` INT NOT NULL AUTO_INCREMENT,
         `EncuestaId` INT NULL,
         `Pregunta` VARCHAR(150) NULL,
         `Tipo` VARCHAR(45) NULL,
        PRIMARY KEY (`DetalleId`))";

    $wpdb->query($sql2);

    $sql3 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}encuestas_respuesta(
        `RespuestaId` INT NOT NULL AUTO_INCREMENT,
        `DetalleId` INT NULL,
        `Codigo` VARCHAR(45) NULL,
        `Respuesta` VARCHAR(45) NULL,
        PRIMARY KEY (`RespuestaId`))";

    $wpdb->query($sql3);



}
//Desactivar el plugin
function Desactivar(){
}

//Hooks de activación y desactivación
register_activation_hook(__FILE__, 'Activar');
register_deactivation_hook( __FILE__,'Desactivar');

//Crear menu en el admin
add_action('admin_menu','CreaMenu');

//Función que crea el menu
function CreaMenu(){
    add_menu_page(
        'Plugin de test', //Titol del la pagina
        'Test plugin', //Titol del menu
        'manage_options', //Capability -> A quin usuaris?
        plugin_dir_path(__FILE__).'admin/lista_encuestas.php', //slug 
        //'test_menu', //slug  -> Se gasta si es una función
        null, //Funció que mostra el contingut
        //'MostrarContenido', //Funció que mostra el contingut
        plugin_dir_url(__FILE__).'admin/img/icon.png', //Url e la imatge
        '1'//Posició
    ); 

    add_submenu_page(
        plugin_dir_path(__FILE__).'admin/lista_encuestas.php', //slug del padre
        'Ajustes', //Titol de la pagina
        'Ajustes', //Titol del menu
        'manage_options', //Capability -> A quin usuaris?
        'test_menu_ajustes', //slug
        'Submenu' //Funció
    );
}

//Crear Menu
function MostrarContenido(){
    echo "<h1>El plugin molon</h1>";

    echo "<p>Esto es un plugin de prueba</p>";
}

//Crear submenu
function Submenu(){
    echo "<h1>Esto es el submenu</h1>";
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
