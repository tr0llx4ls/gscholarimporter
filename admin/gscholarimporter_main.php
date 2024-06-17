<?php
//gscholarimporter_main.php
// Check if user has the required capability
if ( !current_user_can( 'manage_options' ) ) {
    return;
}
//Si no esta creada la opcion de la api key o es igual a '' redirigir a la pagina de settings
if(get_option('serpapi_key') == '' || get_option('serpapi_key') == null){
    //wp_redirect( admin_url( 'admin.php?page=gscholarimporter_settings' ) );
    //no esta funcionando el redireccionamiento
    header('Location: '.admin_url( 'admin.php?page=gscholarimporter_settings' ));
    //mensaje de erro con enlace a la pagina de settings con clases de wordpress
    echo '<div class="notice notice-error is-dismissible">
        <p>GScholarImporter: You need to set the API key. <a href="'.admin_url( 'admin.php?page=gscholarimporter_settings' ).'">Settings</a></p>
    </div>';
    exit;
}
//funcio'n que recoge como parametro el id de un autor y devuelve un array con las publicaciones

function gscholarimporter_main_page(){
    //aca va el contenido de la pagina
    echo '<h1>GScholarImporter</h1>';
    echo '<p>GScholarImporter is a plugin that allows you to import your Google Scholar publications to your WordPress site.</p>';
    echo '<p>It uses the <a href="https://serpapi.com/">SerpApi</a> API to get the publications from Google Scholar.</p>';
}
gscholarimporter_main_page();

$serpapi_key = get_option('serpapi_key');
$author_id = get_option('author_id');

function consulta_autor($id, $key, $start=0){
    require plugin_dir_path( __FILE__ ).'google-search-results-php/google-search-results.php';
    require plugin_dir_path( __FILE__ ).'google-search-results-php/restclient.php';
    
    $query = [
     "engine" => "google_scholar_author",
     "author_id" => $id,
     "start" => $start,
     "num" => 100,
    ];
    
    $search = new GoogleSearch($key);
    $result = $search->get_json($query);
    return $result;
}

$publicaciones = consulta_autor($author_id, $serpapi_key);

echo '<pre>';   
print_r($publicaciones);
echo '</pre>';