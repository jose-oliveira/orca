<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ConfigLoader;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides access to the test fixture.
 */
class Fixture {

  public const BASE_FIXTURE_GIT_BRANCH = 'base-fixture';

  public const WEB_ADDRESS = '127.0.0.1:8080';

  /**
   * The config loader.
   *
   * @var \Acquia\Orca\Utility\ConfigLoader
   */
  private $configLoader;

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The root path.
   *
   * @var string
   */
  private $rootPath;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Utility\ConfigLoader $configLoader
   *   The config loader.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param string $fixture_dir
   *   The absolute path of the fixture root directory.
   */
  public function __construct(ConfigLoader $configLoader, Filesystem $filesystem, string $fixture_dir) {
    $this->configLoader = $configLoader;
    $this->filesystem = $filesystem;
    $this->rootPath = $fixture_dir;
  }

  /**
   * Determines whether or not the fixture already exists.
   *
   * @return bool
   */
  public function exists(): bool {
    return $this->filesystem->exists($this->getPath());
  }

  /**
   * Gets the fixture root path with an optional sub-path appended.
   *
   * @param string|null $sub_path
   *   (Optional) A sub-path to append.
   *
   * @return string
   */
  public function getPath(?string $sub_path = ''): string {
    $path = $this->rootPath;

    // Append optional subpath.
    if ($sub_path) {
      $path .= "/{$sub_path}";
    }

    // Approximate realpath() without requiring the path parts to exist yet.
    // @see https://stackoverflow.com/a/14354948/895083
    $patterns = ['~/{2,}~', '~/(\./)+~', '~([^/\.]+/(?R)*\.{2,}/)~', '~\.\./~'];
    $replacements = ['/', '/', '', ''];
    $path = preg_replace($patterns, $replacements, $path);

    // Remove trailing slashes.
    $path = rtrim($path, '/');

    return $path;
  }

}
