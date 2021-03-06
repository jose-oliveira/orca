<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\Package;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @covers \Acquia\Orca\Fixture\Package
 */
class PackageTest extends TestCase {

  public function setUp() {
    $this->fixture = $this->prophesize(Fixture::class);
  }

  /**
   * @dataProvider providerPackage
   */
  public function testPackage($data, $package_name, $project_name, $type, $repository_url, $version, $dev_version, $enable, $package_string, $dev_package_string, $install_path) {
    $package = $this->createPackage($data);

    $this->assertInstanceOf(Package::class, $package, 'Instantiated class.');
    $this->assertEquals($package_name, $package->getPackageName(), 'Set/got package name.');
    $this->assertEquals($project_name, $package->getProjectName(), 'Set/got project name.');
    $this->assertEquals($repository_url, $package->getRepositoryUrl(), 'Set/got repository URL.');
    $this->assertEquals($type, $package->getType(), 'Set/got type.');
    $this->assertEquals($version, $package->getVersionRecommended(), 'Set/got recommended version.');
    $this->assertEquals($dev_version, $package->getVersionDev(), 'Set/got dev version.');
    $this->assertEquals($enable, $package->shouldGetEnabled(), 'Determined whether or not should get enabled.');
    $this->assertEquals($package_string, $package->getPackageStringRecommended(), 'Got recommended dependency string.');
    $this->assertEquals($dev_package_string, $package->getPackageStringDev(), 'Got dev dependency string.');
    $this->assertEquals($install_path, $package->getInstallPathRelative(), 'Got relative install path.');
  }

  public function providerPackage() {
    return [
      'Full specification' => [
        [
          'name' => 'drupal/example_library',
          'type' => 'library',
          'install_path' => 'custom/path/to/example_library',
          'url' => '/var/www/example_library',
          'version' => '~1.0',
          'version_dev' => '1.x-dev',
        ],
        'drupal/example_library',
        'example_library',
        'library',
        '/var/www/example_library',
        '~1.0',
        '1.x-dev',
        FALSE,
        'drupal/example_library:~1.0',
        'drupal/example_library:1.x-dev',
        'custom/path/to/example_library',
      ],
      'Minimum specification/default values' => [
        [
          'name' => 'drupal/example_module',
          'version_dev' => '2.x-dev',
        ],
        'drupal/example_module',
        'example_module',
        'drupal-module',
        '../example_module',
        '*',
        '2.x-dev',
        TRUE,
        'drupal/example_module:*',
        'drupal/example_module:2.x-dev',
        'docroot/modules/contrib/example_module',
      ],
      'Module to not enable' => [
        [
          'name' => 'drupal/example_module',
          'version_dev' => '2.x-dev',
          'enable' => FALSE,
        ],
        'drupal/example_module',
        'example_module',
        'drupal-module',
        '../example_module',
        '*',
        '2.x-dev',
        FALSE,
        'drupal/example_module:*',
        'drupal/example_module:2.x-dev',
        'docroot/modules/contrib/example_module',
      ],
    ];
  }

  /**
   * @dataProvider providerConstructionError
   */
  public function testConstructionError($exception, $data) {
    $this->expectException($exception);

    $this->createPackage($data);
  }

  public function providerConstructionError() {
    return [
      'Missing "name" property' => [MissingOptionsException::class, ['version_dev' => '1.x']],
      'Missing "version_dev" property' => [MissingOptionsException::class, ['name' => 'drupal/example']],
      'Invalid "name" value: wrong type' => [InvalidOptionsException::class, ['name' => NULL, 'version_dev' => '1.x']],
      'Invalid "name" value: missing forward slash' => [InvalidOptionsException::class, ['name' => 'incomplete', 'version_dev' => '1.x']],
      'Invalid "enable" value: non-boolean' => [InvalidOptionsException::class, ['name' => 'drupal/example', 'version_dev' => '1.x', 'enable' => 'invalid']],
      'Unexpected property' => [UndefinedOptionsException::class, ['unexpected' => '', 'name' => 'drupal/example', 'version_dev' => '1.x'], 'Unexpected property: "unexpected"'],
    ];
  }

  /**
   * @dataProvider providerInstallPathCalculation
   */
  public function testInstallPathCalculation($type, $relative_install_path) {
    $absolute_install_path = "/var/www/{$relative_install_path}";
    $this->fixture
      ->getPath($relative_install_path)
      ->willReturn($absolute_install_path);
    $data = [
      'name' => 'drupal/example',
      'type' => $type,
      'version_dev' => '1.x-dev',
    ];

    $package = $this->createPackage($data);

    $this->assertEquals($relative_install_path, $package->getInstallPathRelative());
    $this->assertEquals($absolute_install_path, $package->getInstallPathAbsolute());
  }

  public function providerInstallPathCalculation() {
    return [
      ['bower-asset', 'docroot/libraries/example'],
      ['drupal-core', 'docroot/core'],
      ['drupal-drush', 'drush/Commands/example'],
      ['drupal-library', 'docroot/libraries/example'],
      ['drupal-module', 'docroot/modules/contrib/example'],
      ['drupal-profile', 'docroot/profiles/contrib/example'],
      ['drupal-theme', 'docroot/themes/contrib/example'],
      ['npm-asset', 'docroot/libraries/example'],
      ['something-nonstandard', 'vendor/drupal/example'],
    ];
  }

  protected function createPackage($data): Package {
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    return new Package($fixture, $data);
  }

}
