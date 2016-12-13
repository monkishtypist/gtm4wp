<?php
/**
* GitHub Plugin Updater class
* via https://code.tutsplus.com/tutorials/distributing-your-plugins-in-github-with-automatic-updates--wp-34817
*
*/
class NLKGitHubPluginUpdater {

  private $slug; // plugin slug
  private $pluginData; // plugin data
  private $username; // GitHub username
  private $repo; // GitHub repo name
  private $pluginFile; // __FILE__ of our plugin
  private $githubAPIResult; // holds data from GitHub
  private $githubAPIResults; // holds more data from GitHub

  function __construct( $pluginFile, $gitHubUsername, $gitHubProjectName ) {
      add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'setTransient' ) );
      add_filter( 'plugins_api', array( $this, 'setPluginInfo' ), 10, 3 );
      add_filter( 'upgrader_post_install', array( $this, 'postInstall' ), 10, 3 );

      $this->pluginFile = $pluginFile;
      $this->username = $gitHubUsername;
      $this->repo = $gitHubProjectName;
  }

  // Get information regarding our plugin from WordPress
  private function initPluginData() {
    $this->slug = plugin_basename( $this->pluginFile );
    $this->pluginData = get_plugin_data( $this->pluginFile );
  }

  // Get information regarding our plugin from GitHub
  private function getRepoReleaseInfo() {
    // Only do this once
    if ( ! empty( $this->githubAPIResult ) ) {
        return;
    }

    // Query the GitHub API
    $url = "https://api.github.com/repos/{$this->username}/{$this->repo}/releases";

    // Get the results
    $this->githubAPIResult = wp_remote_retrieve_body( wp_remote_get( $url ) );
    if ( ! empty( $this->githubAPIResult ) ) {
        $this->githubAPIResult = @json_decode( $this->githubAPIResult );
    }

    // Use only the latest release?
    if ( is_array( $this->githubAPIResult ) ) {
      $this->githubAPIResults = $this->githubAPIResult;
      $this->githubAPIResult = $this->githubAPIResult[0];
    } else {
      $this->githubAPIResults = array( $this->githubAPIResult );
    }
  }

  // Push in plugin version information to get the update notification
  public function setTransient( $transient ) {
    // If we have checked the plugin data before, don't re-check
    if ( empty( $transient->checked ) ) {
      return $transient;
    }
    // Get plugin & GitHub release information
    $this->initPluginData();
    $this->getRepoReleaseInfo();
    // Check the versions if we need to do an update
    $doUpdate = isset( $this->githubAPIResult->tag_name ) ? version_compare( $this->githubAPIResult->tag_name, $transient->checked[$this->slug] ) : 0;
    // Update the transient to include our updated plugin data
    if ( $doUpdate == 1 ) {
      $package = isset( $this->githubAPIResult->zipball_url ) ? $this->githubAPIResult->zipball_url : '';

      $obj = new stdClass();
      $obj->slug = $this->slug;
      $obj->new_version = $this->githubAPIResult->tag_name;
      $obj->url = $this->pluginData["PluginURI"];
      $obj->package = $package;
      $transient->response[$this->slug] = $obj;
    }

    return $transient;
  }

  // Push in plugin version information to display in the details lightbox
  public function setPluginInfo( $false, $action, $response ) {
    // Get plugin & GitHub release information
    $this->initPluginData();
    $this->getRepoReleaseInfo();
    // If nothing is found, do nothing
    if ( isset( $response->slug ) && ( $response->slug == $this->slug ) ) {
      // Add our plugin information
      $response->slug = $this->slug;
      // plugin name(s)
      $response->name  = $this->pluginData['Name'];
      $response->plugin_name  = $this->pluginData['Name'];
      // plugin URL
      if ( isset( $this->pluginData['PluginURI'] ) ) {
        $response->homepage = $this->pluginData['PluginURI'];
      }
      // plugin Author
      if ( isset( $this->pluginData['Author'] ) ) {
        $response->author = $this->pluginData['Author'];
      }
      // pull the updated version #
      if ( isset( $this->githubAPIResult->tag_name ) ) {
        $response->new_version = $this->githubAPIResult->tag_name;
        $response->version = $this->githubAPIResult->tag_name;
      }
      $chchchanges = '';
      if ( isset( $this->githubAPIResult->body ) ) {
        $chchchanges = $this->githubAPIResult->body;
        // Gets the required version of WP if available
        $matches = null;
        preg_match( "/requires:\s([\d\.]+)/i", $chchchanges, $matches );
        if ( ! empty( $matches ) ) {
            if ( is_array( $matches ) ) {
                if ( count( $matches ) > 1 ) {
                    $response->requires = $matches[1];
                }
            }
        }

        // Gets the tested version of WP if available
        $matches = null;
        preg_match( "/tested:\s([\d\.]+)/i", $chchchanges, $matches );
        if ( ! empty( $matches ) ) {
            if ( is_array( $matches ) ) {
                if ( count( $matches ) > 1 ) {
                    $response->tested = $matches[1];
                }
            }
        }
      }

      if ( isset( $this->githubAPIResult->published_at) ) {
        // take first half only?
        $response->last_updated = $this->githubAPIResult->published_at;
        if ( strpos( $response->last_updated, 'T' ) !== false ) {
          $response->last_updated = substr( $response->last_updated, 0, strpos( $response->last_updated, 'T' ) );
        }
      }
      // Create tabs for lightbox
      $response->sections = array(
          'description' => "Hello!",
          'changelog' => "

there are some changes here?

"
      );
      if ( isset( $this->pluginData['Description'] ) ) {
        $response->sections['description'] = $this->pluginData['Description'];
      }

      if ( isset( $this->githubAPIResult->body ) ) {

        // see if we can Parsedown it cleaner
        require_once( plugin_dir_path( __FILE__ ) . "Parsedown.php" );
        if ( class_exists( "Parsedown" ) ) {
          $response->sections['changelog'] = "";
          // actually loop through past changes too?
          foreach ( $this->githubAPIResults as $k => $a ) {
            if ( isset( $a->tag_name ) ) {
              $response->sections['changelog'] .= '<strong>'. $a->tag_name .'</strong>' ."\n\n";
            }
            if ( isset( $a->body ) ) {
              $response->sections['changelog'] .= Parsedown::instance()->parse( $a->body );
            }
            $response->sections['changelog'] .= "\n\n";
          }
        } else {
          // set initial changelog
          $response->sections['changelog'] = $this->githubAPIResult->body;
        }
      }

      $response->banners = array(
        'low' => plugin_dir_url( __FILE__ ) .'assets/banner-772x250.png'
      );

      // This is our release download zip file
      if ( isset( $this->githubAPIResult->zipball_url ) ) {
        $response->download_link = $this->githubAPIResult->zipball_url;
      }
      unset($response->is_ssl);
      unset($response->fields);
      unset($response->per_page);
      unset($response->locale);

      return $response;
    }
    return false;
  }

  // Perform additional actions to successfully install our plugin
  public function postInstall( $true, $hook_extra, $result ) {
    // Get plugin information
    $this->initPluginData();
    // Remember if our plugin was previously activated
    $wasActivated = is_plugin_active( $this->slug );
    // Since we are hosted in GitHub, our plugin folder would have a dirname of
    // reponame-tagname change it to our original one:
    global $wp_filesystem;
    $pluginFolder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname( $this->slug );
    $wp_filesystem->move( $result['destination'], $pluginFolder );
    $result['destination'] = $pluginFolder;
    // Re-activate plugin if needed
    if ( $wasActivated ) {
        $activate = activate_plugin( $this->slug );
    }

    return $result;
  }
}
