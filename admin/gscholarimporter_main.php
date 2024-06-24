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
//Variables
$serpapi_key = get_option('serpapi_key');
$author_id = get_option('author_id');

//Funcion que muestra la cabecera del contenido de la pagina
function gscholarimporter_main_header(){
    //aca va el contenido de la pagina
    echo '<h1>GScholarImporter</h1>';
    echo '<p>GScholarImporter is a plugin that allows you to import your Google Scholar publications to your WordPress site.</p>';
    echo '<p>It uses the <a href="https://serpapi.com/">SerpApi</a> API to get the publications from Google Scholar.</p>';
}

//Funcion que muestra la pagina principal  del plugin
function gscholarimporter_main_page($author_id, $serpapi_key){
    ?>
    <div class="wrap">
        <?php gscholarimporter_main_header(); ?>
        <?php $publicaciones = consulta_autor($author_id, $serpapi_key); ?>
        <?php gscholarimporter_publicaciones_table($publicaciones); ?>
    </div>
    <?php
}
 
function consulta_autor($id, $key, $start=0){
    require plugin_dir_path( __FILE__ ).'google-search-results-php/google-search-results.php';
    require plugin_dir_path( __FILE__ ).'google-search-results-php/restclient.php';
    
    $query = [
     "engine" => "google_scholar_author",
     "author_id" => $id,
     "start" => $start,
     "num" => 100,
     "sort" => "pubdate",
    ];
    
    $search = new GoogleSearch($key);
    $result = $search->get_json($query);
    $articles = $result->articles;
    //return $result;
    return $articles;

}

function consulta_article($id, $key){
    require plugin_dir_path( __FILE__ ).'google-search-results-php/google-search-results.php';
    require plugin_dir_path( __FILE__ ).'google-search-results-php/restclient.php';
    
    $query = [
    "engine" => "google_scholar_author",
    "view_op" => "view_citation",
    "citation_id" => $id,
    ];
    
    $search = new GoogleSearch($key);
    $result = $search->get_json($query);
    return $result;
}

//Funcion con action a este mismo fichero que crea un formulario para introducir el id de la citation_id de un articulo
function gscholarimporter_article_form($key){
    ?>
    <form method="post" action="">
        <label for="citation_id">Citation ID:</label>
        <input type="text" id="citation_id" name="citation_id" value="" />
        <input type="submit" value="Submit" />
    </form>
    <?php
     // Verifica si el formulario ha sido enviado
     if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['citation_id'])) {
        // Recoge el valor de 'citation_id' del formulario enviado
        $citation_id = $_POST['citation_id'];

        // Aquí puedes hacer algo con el $citation_id, como una consulta o validación
        $articulo = consulta_article($citation_id, $key);
        echo '<pre>';   
        print_r($articulo);
        echo '</pre>';
    }
}
// gscholarimporter_article_form($serpapi_key);
// //$publicaciones = consulta_autor($author_id, $serpapi_key);
// echo '<pre>';   
// print_r($publicaciones); 
// echo '</pre>';

//Funcion que muestre unta tabla con las publicaciones y de ellas se muestre el titulo, con enlace, autores, año 
//recibira un array de publicaciones
//se podra paginar, buscar y ordenar por titulo, autores y año y seleccionar cuantas publicaciones se quieren mostrar
function gscholarimporter_publicaciones_table($publicaciones){
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr class="manage-column column-">
                <th class="manage-column column-number" style="width:3%;">#</th>
                <th class="manage-column column-title column-primary sortable desc">Title</th>
                //publication type
                <th class="manage-column column-author">Type</th>
                <th class="manage-column column-author">Authors</th>
                <th class="manage-column column-date sorted desc">Year</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $num = 1;   
            foreach($publicaciones as $publicacion){
                echo '<tr>';
                echo '<td>'.$num.'</td>';
                echo '<td><a href="'.$publicacion->link.'">'.$publicacion->title.'</a></td>';
                //publication type

                echo '<td>'.$publicacion->authors.'</td>';
                echo '<td>'.$publicacion->year.'</td>';
                echo '</tr>';
                $num++;
            }
            ?>
        </tbody>
    </table>
    <?php
}
gscholarimporter_main_page( $author_id, $serpapi_key);