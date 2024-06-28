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
//Variables globales
global $serpapi_key;
$serpapi_key = get_option('serpapi_key');


//Funcion que muestra la cabecera del contenido de la pagina
function gscholarimporter_header(){
    //aca va el contenido de la pagina
    echo '<h1>GScholarImporter</h1>';
    echo '<p>GScholarImporter is a plugin that allows you to import your Google Scholar publications to your WordPress site.</p>';
    echo '<p>It uses the <a href="https://serpapi.com/">SerpApi</a> API to get the publications from Google Scholar.</p>';
}

//Funcion que muestre la el pie del contenido de la pagina
function gscholarimporter_footer(){
    echo '<p>Desarrollado por <a href="https://reanimandowebs.com/">Reanmimando Webs</a></p>';
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Este código se ejecutará cuando el usuario haga clic en un botón con el ID 'load-content'
        $('#load-content').click(function() {
            $.ajax({
                url: ajaxurl, // 'ajaxurl' es una variable definida por WordPress que contiene la URL para manejar solicitudes AJAX
                type: 'POST',
                data: {
                    action: 'load_dynamic_content', // Este valor corresponde al hook de acción registrado en PHP
                    missatge: 'fent proves' // Este valor se enviará al servidor como parte de la solicitud AJAX
                },
                success: function(response) {
                    // Añade el contenido recibido a un elemento de tu página
                    $('#dynamic-content').append(response);
                    //quiero que lo añada a lo que ya hay

                }
            });
        });
    });
    </script>
    <?php
}
   
//Funcion que muestra la pagina principal  del plugin
function gscholarimporter_main_page(){
    ?>
    <div class="wrap">
        <?php
        gscholarimporter_header();
        echo '<button id="load-content">Cargar contenido dinámico</button>';
        echo '<div id="dynamic-content"></div>';
        $author_id = gscholarimporter_publicaciones_form();
        if($author_id != ''){
            $result = consulta_autor($author_id);
            
            echo '<pre>';
            print_r($result);
            echo '</pre>';

            //gscholarimporter_publicaciones_table($result->publicaciones);
        }
        gscholarimporter_footer();?>
    </div>
    <?php
}

function consulta_autor($author_id, $start=0 , $num=100, $sort="pubdate"){
    require plugin_dir_path( __FILE__ ).'google-search-results-php/google-search-results.php';
    require plugin_dir_path( __FILE__ ).'google-search-results-php/restclient.php';
    global $serpapi_key;
    $query = [
     "engine" => "google_scholar_author",
     "author_id" => $author_id,
     "start" => $start,
     "num" => $num,
     "sort" => $sort,
    ];
    
    try {
        $search = new GoogleSearch($serpapi_key);
        $result = $search->get_json($query);
        return $result;
    } catch (Exception $e) {
        // Manejar la excepción
        echo '<div class="notice notice-error is-dismissible">
        <p>GScholarImporter Error: ',  $e->getMessage(), '</p>
        </div>';
        return null; // O manejar de otra manera
    }
}

function consulta_article($id){
    require plugin_dir_path( __FILE__ ).'google-search-results-php/google-search-results.php';
    require plugin_dir_path( __FILE__ ).'google-search-results-php/restclient.php';
    global $serpapi_key;
    $query = [
    "engine" => "google_scholar_author",
    "view_op" => "view_citation",
    "citation_id" => $id,
    ];
    
    $search = new GoogleSearch($serpapi_key);
    $result = $search->get_json($query);
    return $result;
}
//Funcion con action a este mismo fichero que crea un formulario para introducir el id de la citation_id de un articulo
function gscholarimporter_article_form(){
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
        $articulo = consulta_article($citation_id);
        echo '<pre>';   
        print_r($articulo);
        echo '</pre>';
    }
}

//Funcion que muestre unta tabla con las publicaciones y de ellas se muestre el titulo, con enlace, autores, año y tipo
function gscholarimporter_publicaciones_table($publicaciones){
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr class="manage-column column-">
                <th class="manage-column column-number" style="width:3%;">#</th>
                <th class="manage-column column-title column-primary sortable desc">Title</th>
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
                echo '<td> tipo x </td>';
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

//Funcion con action a este mismo fichero que crea un formulario para introducir el id de un autor
function gscholarimporter_publicaciones_form(){
    global $author_id;
    ?>
    <form method="post" action="">
        <label for="author_id">Author ID:</label>
        <select name="author_id">
            <option value="qawKnNkAAAAJ">JG Victores</option>
            <option value="Ng8WUR4AAAAJ">C Balaguer</option>
            <option value="1nlf7XQAAAAJ">MA Salichs</option>
            <option value="1nlf7XQAAAJ">MAlito</option>
        <input type="submit" value="Submit" />
    </form>
    <?php
     // Verifica si el formulario ha sido enviado
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['author_id'])) {
        // Recoge el valor de 'citation_id' del formulario enviado
        $author_id = $_POST['author_id'];
        return $author_id;}
    else{
        return '';
    }
}


 gscholarimporter_main_page();