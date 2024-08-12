<?php
//gscholarimporter_preimporter.php
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

//Funciones

//Funcion que consulta las publicaciones de un autor en google scholar
function consulta_autor($author_id, $start=0 , $num=100, $sort="pubdate"){
    require_once plugin_dir_path( __FILE__ ).'google-search-results-php/google-search-results.php';
    require_once plugin_dir_path( __FILE__ ).'google-search-results-php/restclient.php';
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

//Funcion a la que se le pase un objeto de una  publicacion y si citation id no esta en la tabla inserte en la tabla gsi_publicaciones los sigueinte campos
//title, link, citation_id, authors y year si existen en la publicacion
function insertar_publicacion($publicacion){
    global $wpdb;
    $table_name = $wpdb->prefix.'gsi_publicaciones';
    $result = $wpdb->get_results("SELECT * FROM $table_name WHERE citation_id = '$publicacion->citation_id'");
    if(count($result) == 0){
        $wpdb->insert(
            $table_name,
            array(
                'title' => $publicacion->title,
                'status' => 'preimported', //por defecto preimportado
                'link' => $publicacion->link,
                'citation_id' => $publicacion->citation_id,
                'authors' => $publicacion->authors,
                'year' => $publicacion->year
            )
        );
        return true;
    }
    else{
        return false;   
    }
}

//Funcion que reciba un author_id, compruebe que este no esta vació
//Start = 0
//-Invoque a la funcion consulta_autor con el author_id con start  0 y num 100
//Bucle mientras el tamaño de la lista de articulos sea 100
//-Si en el objeto search_metadata, el campo status no es sucess, que muestre este campo y salga
//-Si el objeto author no existe que muestre un mensaje de error y salga
//-Buclle Si array articles existe y no esta vació  y tiene exactamernte 100 articulos que por cada uno de llos ejecute la funcion insertar_publicacion copn el articulo y ponga start = start + 100
// Que muestre por pantalla cada iteracion del bucle con el numero de articulos importados y el name del auhtor que esta en el objeto author
//Que muestre un mensaje de exito si se han importado todos los articulos
function importar_publicaciones($author_id){
    $start = 0;
    $num = 100;
    $total = 0;
    $result = consulta_autor($author_id, $start, $num);
    if($author_id != ''){
        if($result->search_metadata->status != 'Success'){
            echo '<div class="notice notice-error is-dismissible">
            <p>GScholarImporter Error: ',  $result->search_metadata->status, '</p>
            </div>';
            return;
        }
        if($result->author == null){
            echo '<div class="notice notice-error is-dismissible">
            <p>GScholarImporter Error: Author not found</p>
            </div>';
            return;
        }
        while(count($result->articles) == 100){
            $imported = 0;
            if($result->articles != null && count($result->articles) == 100){
                foreach($result->articles as $article){
                    if (insertar_publicacion($article)== true){
                        $imported = $imported + 1;
                    }
                }
                $start = $start + 100;
                $total = $total + $imported;
                echo '<div class="notice notice-success is-dismissible">
                <p>GScholarImporter: '. $imported . ' articles imported</p>
                </div>';
                $result = consulta_autor($author_id, $start, $num);                
            }
        }
        //importamos el resto
        $imported = 0;
        if($result->articles != null){
            foreach($result->articles as $article){
                if (insertar_publicacion($article)== true){
                    $imported = $imported + 1;
                }
            }
            $total = $total + $imported;
            echo '<div class="notice notice-success is-dismissible">
            <p>GScholarImporter: '. $imported . ' articles imported</p>
            </div>';
        }

        echo '<div class="notice notice-success is-dismissible">
        <p>GScholarImporter: '. $total . ' articles imported</p> 
        </div>';
    }
}

//Funcion que muestre una tabla con el contenido de la tabla gsi_publicaciones, esta debe tener paginacion (por defecto 50 campos), ordenacion y busqueda debe usar las clases de wordpress
function gscholarimporter_publicaciones_table(){
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
    class GSI_Publicaciones_Table extends WP_List_Table {
        function get_columns(){
            $columns = array(
                'cb'  => '<input type="checkbox" />', //Render a checkbox instead of text
                'title' => 'Title',
                //'link' => 'Link',
                'citation_id' => 'Citation ID',
                'authors' => 'Authors',
                'year' => 'Year'
            );
            return $columns;
        }
        function column_default($item, $column_name){
            return $item->$column_name;
        }
        function column_title($item){
            $actions = array(
                'edit' => sprintf('<a href="?page=%s&action=%s&citation_id=%s">Edit</a>',$_REQUEST['page'],'edit',$item->citation_id),
                'delete' => sprintf('<a href="?page=%s&action=%s&citation_id=%s">Delete</a>',$_REQUEST['page'],'delete',$item->citation_id),
            );
            return sprintf('%1$s %2$s', $item->title, $this->row_actions($actions) );
        }
        function get_sortable_columns() {
            $sortable_columns = array(
                'title' => array('title',true),
                'link' => array('link',true),
                'citation_id' => array('citation_id',true),
                'authors' => array('authors',true),
                'year' => array('year',true)
            );
            return $sortable_columns;
        }
        function prepare_items() {
            global $wpdb;
            $table_name = $wpdb->prefix.'gsi_publicaciones';
            $per_page = 50;
            $columns = $this->get_columns();
            $sortable = $this->get_sortable_columns();
            $this->_column_headers = array($columns, array(), $sortable);
            $this->process_bulk_action();
            $data = $wpdb->get_results("SELECT * FROM $table_name");
            $current_page = $this->get_pagenum();
            $total_items = count($data);
            $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
            $this->items = $data;
            $this->set_pagination_args( array(
                'total_items' => $total_items,
                'per_page'    => $per_page
            ) );
            
        }
    } 
    $table = new GSI_Publicaciones_Table();
    $table->prepare_items();
    echo '<div class="wrap"><h2>Publicaciones</h2>';
    $table->display();
    echo '</div>';
}

echo '<h1>GScholarImporter Preimporter</h1>';
echo '<h2>Import Publications</h2>';

$author_id = gscholarimporter_publicaciones_form();
if($author_id != ''){
     importar_publicaciones($author_id);
}
gscholarimporter_publicaciones_table();
?> 