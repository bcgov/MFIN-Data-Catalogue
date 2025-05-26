<?php

/**
 * @file
 * Contains \DrupalProject\composer\ScriptHandler.
 */

namespace DrupalWxT\WxT;

use Composer\Script\Event;
use Composer\Semver\Comparator;
use Drupal\Core\Site\Settings;
use Drupal\Core\Site\SettingsEditor;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ScriptHandler {

  /**
   * Retrieves the Drupal root directory.
   *
   * @param string $project_root
   *   Drupal root directory.
   */
  protected static function getDrupalRoot($project_root) {
    return $project_root . '/html';
  }

  /**
   * Creates the required directory structure.
   *
   * @param \Composer\Script\Event $event
   *   The script event.
   */
  public static function createRequiredFiles(Event $event) {
    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();

    $dirs = [
      'modules',
      'profiles',
      'themes',
    ];

    // Required for unit testing
    foreach ($dirs as $dir) {
      if (!$fs->exists($drupalRoot . '/'. $dir)) {
        $fs->mkdir($drupalRoot . '/'. $dir);
        $fs->touch($drupalRoot . '/'. $dir . '/.gitkeep');
      }
    }

    // Prepare the settings file for installation
    if (!$fs->exists($drupalRoot . '/sites/default/settings.php') && $fs->exists($drupalRoot . '/sites/default/default.settings.php')) {
      $fs->copy($drupalRoot . '/sites/default/default.settings.php', $drupalRoot . '/sites/default/settings.php');
      require_once $drupalRoot . '/core/includes/bootstrap.inc';
      require_once $drupalRoot . '/core/includes/install.inc';
      new Settings([]);
      $settings['settings']['config_sync_directory'] = (object) [
        'value' => Path::makeRelative($drupalFinder->getComposerRoot() . '/config/sync', $drupalRoot),
        'required' => TRUE,
      ];
      SettingsEditor::rewrite($drupalRoot . '/sites/default/settings.php', $settings);
      $fs->chmod($drupalRoot . '/sites/default/settings.php', 0666);
      $event->getIO()->write("Created a sites/default/settings.php file with chmod 0666");
    }

    // Create the files directory with chmod 0777
    if (!$fs->exists($drupalRoot . '/sites/default/files')) {
      $oldmask = umask(0);
      $fs->mkdir($drupalRoot . '/sites/default/files', 0777);
      umask($oldmask);
      $event->getIO()->write("Created a sites/default/files directory with chmod 0777");
    }
  }

  /**
   * Checks if the installed version of Composer is compatible.
   *
   * Composer 1.0.0 and higher consider a `composer install` without having a
   * lock file present as equal to `composer update`. We do not ship with a lock
   * file to avoid merge conflicts downstream, meaning that if a project is
   * installed with an older version of Composer the scaffolding of Drupal will
   * not be triggered. We check this here instead of in drupal-scaffold to be
   * able to give immediate feedback to the end user, rather than failing the
   * installation after going through the lengthy process of compiling and
   * downloading the Composer dependencies.
   *
   * @see https://github.com/composer/composer/pull/5035
   */
  public static function checkComposerVersion(Event $event) {
    $composer = $event->getComposer();
    $io = $event->getIO();

    $version = $composer::VERSION;

    // The dev-channel of composer uses the git revision as version number,
    // try to the branch alias instead.
    if (preg_match('/^[0-9a-f]{40}$/i', $version)) {
      $version = $composer::BRANCH_ALIAS_VERSION;
    }

    // If Composer is installed through git we have no easy way to determine if
    // it is new enough, just display a warning.
    if ($version === '@package_version@' || $version === '@package_branch_alias_version@') {
      $io->writeError('<warning>You are running a development version of Composer. If you experience problems, please update Composer to the latest stable version.</warning>');
    }
    elseif (Comparator::lessThan($version, '1.0.0')) {
      $io->writeError('<error>Drupal-project requires Composer version 1.0.0 or higher. Please update your Composer before continuing</error>.');
      exit(1);
    }
  }

  /**
   * Post create project script.
   *
   * @param \Composer\Script\Event $event
   *   The script event.
   */
  public static function postCreateProject(Event $event) {
    $composer = $event->getComposer();
    $composerFile = Factory::getComposerFile();
    $io = $event->getIO();
    $name = $composer->getPackage()->getName();

    $projDir = realpath(dirname($composerFile));
    $projectName = $io->ask('Enter composer project name (drupalwxt/site-wxt): ', 'drupalwxt/site-wxt');

    $finder = new Finder();
    foreach ($finder->files()->name('/composer\.(json|lock)/i')->in($projDir) as $file) {
      if (!empty($projectName)) {
        $file_contents = str_replace("$name", $projectName, $file->getContents());
        file_put_contents($file->getRealPath(), $file_contents);
        // reset the project name via reflection.
        $package = $composer->getPackage();
        $refl = new \ReflectionProperty(get_class($package), 'name');
        $refl->setAccessible(true);
        $refl->setValue($package, $projectName);
      }
    }

  }

}
