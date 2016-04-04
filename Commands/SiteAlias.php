<?php

namespace Terminus\Commands;

use Terminus\Commands\TerminusCommand;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\Collections\Sites;
use Terminus\Session;

/**
 * Generates a drush alias for a given site.
 *
 * @command site
 */
class SiteAlias extends TerminusCommand {

  /**
   * Object constructor
   *
   * @param array $options Elements as follow:
   * @return SiteAlias
   */
  public function __construct(array $options = []) {
    $options['require_login'] = true;
    parent::__construct($options);
    //$this->helpers->auth->ensureLogin();
  }

  /**
   * Retrieves and writes Pantheon aliases for the evironments of a single site to a file.
   *
   * --site=<site>
   * : Name of site for which aliases should be created
   *
   * [--location=<location>]
   * : Location for the new file to be saved
   *
   * [--print]
   * : Print out the aliases after generation
   *
   * @subcommand aliases
   */
  public function siteAlias($args, $assoc_args) {
    $location = $this->input()->optional(
      [
        'key'     => 'location',
        'choices' => $assoc_args,
        'default' => getenv('HOME') . '/.drush/onealias.aliases.drushrc.php',
      ]
    );
    if (is_dir($location)) {
      $message  = 'Please provide a full path with filename,';
      $message .= ' e.g. {location}/pantheon.aliases.drushrc.php';
      $this->failure($message, compact('location'));
    }

    $file_exists = file_exists($location);

    // Create the directory if it doesn't yet exist
    $dirname = dirname($location);
    if (!is_dir($dirname)) {
      mkdir($dirname, 0700, true);
    }

    $site_name = $this->input()->siteName(array('args' => $assoc_args));

    $content = $this->getAlias($site_name);
    $handle  = fopen($location, 'w+');
    fwrite($handle, $content);
    fclose($handle);
    chmod($location, 0700);

    $message = 'Pantheon aliases file created';
    if ($file_exists) {
      $message = 'Pantheon aliases file updated';
    }
    $this->log()->info($message);

    if (isset($assoc_args['print'])) {
      $aliases = str_replace(array('<?php', '?>'), '', $content);
      $this->output()->outputDump($aliases);
    }
  }

  /**
   * Constructs a Drush alias for an environment. Used to supply
   *   organizational Drush aliases not provided by the API.
   *
   * @param Environment $environment Environment to create an alias for
   * @return string
   * @throws TerminusException
   */
  private function constructAlias($environment) {
    $info      = $environment->connectionInfo();
    $site_name = $environment->site->get('name');
    $site_id   = $environment->site->get('id');
    $env_id    = $environment->get('id');
    $hostnames = method_exists($environment, 'getHostnames') ? array_keys((array)$environment->getHostnames()) : $environment->hostnames->ids();

    if (empty($hostnames)) {
      throw new TerminusException(
        'No hostname entry for {site}.{env}',
        ['site' => $site_name, 'env' => $env_id,],
        1
      );
    }

    $uri = array_shift($hostnames);
    $db_url = $info['mysql_url'];
    $remote_host = $info['sftp_host'];
    $remote_user = $info['sftp_username'];
    $output = "array(
    'uri'              => '$uri',
    'db-url'           => '$db_url',
    'db-allows-remote' => true,
    'remote-host'      => '$remote_host',
    'remote-user'      => '$remote_user',
    'ssh-options'      => '-p 2222 -o \"AddressFamily inet\"',
    'path-aliases'     => array(
      '%files'        => 'code/sites/default/files',
      '%drush-script' => 'drush',
    ),
  );";
    return $output;
  }

  /**
   * Requests API data and returns aliases
   *
   * @return string
   */
  private function getAlias($site_name) {
/*
    $user         = Session::getUser();
    $alias_string = $user->getAliases();
    eval(str_replace('<?php', '', $alias_string));
    $formatted_aliases = substr($alias_string, 0, -1);
*/
    $sites_object = new Sites();
    $site        = $sites_object->get($site_name);

      $environments = $site->environments->all();
      foreach ($environments as $environment) {
        $key = $site->get('name') . '.'. $environment->get('id');
        if (isset($aliases[$key])) {
          break;
        }
        try {
          $formatted_alias = "  \$aliases['$key'] = ";
          $formatted_alias .= $this->constructAlias($environment);
        } catch (TerminusException $e) {
          continue;
        }
      }

    return $formatted_alias;
  }

}
