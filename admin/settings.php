<?php
//settings.php
// Check if user has the required capability

if ( !current_user_can( 'manage_options' ) ) {
    return;
  }

// Render the page content
?>
<?php
function gscholarimporter_settings_page(){
  ?>
  <div>
      <?php screen_icon(); ?>
      <h2>GScholarImporter Settings</h2>
      <form method="post" action="options.php">
          <?php settings_fields( 'Main options' ); ?>
          <h3>Main Settings</h3>
          <p>
              <label for="serpapi_key">API Key:</label>
              <input type="text" id="serpapi_key" name="serpapi_key" value="<?php echo get_option('serpapi_key'); ?>" />
          </p>
          <p>
              <label for="test_option">Author ID:</label>
              <input type="text" id="author_id" name="author_id" value="<?php echo get_option('author_id'); ?>" />
          </p>
          <p>
              <input type="submit" class="button-primary" value="Save Changes" />
          </p>
      </form>
  </div>
  <?php
}

gscholarimporter_settings_page();
