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
 

//Activar el plugin
function Activar(){
    global $wpdb;

    //Crea una tabla que se llame gsi_publicaciones que tengca los campos id, title, link, citation_id, authors y year de los cuales el id sea primary key y citation_id sea unique
    $table_name = $wpdb->prefix.'gsi_publicaciones';
    $sql = "CREATE TABLE $table_name (
        `id` MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
        `status` VARCHAR(45) NOT NULL,
        `title` MEDIUMTEXT NOT NULL,
        `link` MEDIUMTEXT NOT NULL,
        `citation_id` VARCHAR(45) NOT NULL,
        `authors` MEDIUMTEXT NOT NULL,
        `year` VARCHAR(45) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE INDEX `citation_id_UNIQUE` (`citation_id` ASC));";
    $wpdb->query($sql);

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
        'GScholarImporter', //Titol del la pagina
        'GSI', //Titol del menu
        'manage_options', //Capability -> A quin usuaris?
        plugin_dir_path(__FILE__).'admin/gscholarimporter_main.php', //slug 
        null, //Funció que mostra el contingut
        plugin_dir_url(__FILE__).'admin/img/icon.svg', //Url e la imatge
        '1'//Posició
    ); 

    // Página de preimportacion a la bd
    add_submenu_page(
        plugin_dir_path(__FILE__).'admin/gscholarimporter_main.php', // Slug del padre
        'DB Pre Importer', // Título de la página
        'DB Pre Importer', // Título del menú
        'manage_options', // Capability
        'gscholarimporter_db_pre_importer', // Slug
        'PreImporter' // Función que muestra el contenido
    );

    add_submenu_page(
        plugin_dir_path(__FILE__).'admin/gscholarimporter_main.php', //slug del padre
        'Settings', //Titol de la pagina
        'Settings', //Titol del menu
        'manage_options', //Capability -> A quin usuaris?
        'gscholarimporter_settings', //Funcio
        'SubmenuSettings' //Funció
    );
    add_action( 'admin_init', 'gscholarimporter_settings' );
}

function gscholarimporter_settings(){
    //aci van el parametros
    register_setting(
        'Main options', //nom del grup
        'serpapi_key', //nom de la opció
        'gscholarimporter_callback' //funció de validació
    );
    register_setting(
        'Main options', //nom del grup
        'author_id', //nom de la opció
        'gscholarimporter_callback' //funció de validació
    );
}

function gscholarimporter_callback($input){
    return $input;
}

//Función que crea el submenu   
function SubmenuSettings(){
    include_once plugin_dir_path(__FILE__).'admin/settings.php';
}

//Función que crea el submenu   
function PreImporter(){
    include_once plugin_dir_path(__FILE__).'admin/preimporter.php';
}

function gscholarimporter_load_dynamic_content() {
    // Aquí generas el contenido que quieres cargar dinámicamente.
    // Por ejemplo, podrías hacer una consulta a la base de datos o realizar alguna otra operación.

    $missatge = isset($_POST['missatge']) ? $_POST['missatge'] : '';
    echo '<p> ' . $missatge . '</p>';
    // No olvides detener la ejecución después de enviar la respuesta
    wp_die();
}
add_action('wp_ajax_load_dynamic_content', 'gscholarimporter_load_dynamic_content');

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
