<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\ecms\Form\EcmsSettingsBase;
use Drupal\ecms\Form\FilesSettings;
use Drupal\ecms\Form\FooterSettings;
use Drupal\ecms\Form\HeaderSettings;
use Drupal\ecms\Form\ThemeOptions;
use Drupal\system\Form\ThemeSettingsForm;

/**
 * Kernel tests for the eCMS theme settings form.
 *
 * Verifies that all theme settings sections produce the expected form structure
 * and that submitted values are correctly persisted to theme configuration.
 *
 * @group ecms
 * @group ecms_theme
 */
class ThemeSettingsFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'filter', 'file', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system', 'filter']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('file', ['file_usage']);

    \Drupal::service('theme_installer')->install(['ecms']);
    \Drupal::configFactory()->getEditable('system.theme')
      ->set('default', 'ecms')
      ->save();
  }

  /**
   * Builds and returns the theme settings form array for inspection.
   */
  private function getForm(): array {
    return \Drupal::formBuilder()->getForm(ThemeSettingsForm::class, 'ecms');
  }

  /**
   * Submits the theme settings form with the given values.
   *
   * Merges in a valid header_main_line (required) so callers only need to
   * specify the values they are actually testing.
   */
  private function submitSettings(array $values): void {
    $form_state = new FormState();
    $form_state->setValues($values + ['header_main_line' => 'Department of Testing']);
    \Drupal::formBuilder()->submitForm(ThemeSettingsForm::class, $form_state, 'ecms');
  }

  /**
   * Returns the saved ecms.settings config value for $key.
   */
  private function savedSetting(string $key): mixed {
    return \Drupal::config('ecms.settings')->get($key);
  }

  // ---------------------------------------------------------------------------
  // Container injection tests
  // ---------------------------------------------------------------------------

  /**
   * Verifies each settings class is resolvable from the container via
   * classResolver and is an instance of EcmsSettingsBase, confirming that
   * ContainerInjectionInterface is wired up correctly on the base class.
   *
   * @dataProvider settingsClassProvider
   */
  public function testContainerInjection(string $class): void {
    $instance = \Drupal::classResolver($class);
    $this->assertInstanceOf($class, $instance);
    $this->assertInstanceOf(EcmsSettingsBase::class, $instance);
  }

  /**
   * Data provider for testContainerInjection.
   */
  public static function settingsClassProvider(): array {
    return [
      'HeaderSettings' => [HeaderSettings::class],
      'FilesSettings' => [FilesSettings::class],
      'FooterSettings' => [FooterSettings::class],
      'ThemeOptions' => [ThemeOptions::class],
    ];
  }

  // ---------------------------------------------------------------------------
  // Form structure tests
  // ---------------------------------------------------------------------------

  /**
   * Verifies all four settings detail sections are present in the form.
   */
  public function testFormSectionsRender(): void {
    $form = $this->getForm();

    foreach (['ecms_theme_options', 'ecms_header', 'ecms_files', 'ecms_footer'] as $key) {
      $this->assertArrayHasKey($key, $form, "Section '$key' missing from form.");
      $this->assertEquals('details', $form[$key]['#type'], "Section '$key' should be a details element.");
    }
  }

  /**
   * Verifies the Theme Options section fields have the correct element types
   * and that no dark-mode palette variants are exposed.
   */
  public function testThemeOptionsFieldsRender(): void {
    $form = $this->getForm();
    $section = $form['ecms_theme_options'];

    $this->assertArrayHasKey('color_palette', $section);
    $this->assertEquals('select', $section['color_palette']['#type']);

    $this->assertArrayHasKey('illustration_option', $section);
    $this->assertEquals('select', $section['illustration_option']['#type']);

    // Dark-mode palette variants must be excluded from the options.
    foreach (array_keys($section['color_palette']['#options']) as $key) {
      $this->assertStringNotContainsString('--dark', (string) $key);
    }
  }

  /**
   * Verifies the Header section fields have the correct types and constraints.
   */
  public function testHeaderFieldsRender(): void {
    $form = $this->getForm();
    $section = $form['ecms_header'];

    $this->assertEquals('textfield', $section['header_top_line']['#type']);
    $this->assertEquals('textfield', $section['header_main_line']['#type']);
    $this->assertEquals('textfield', $section['header_bottom_line']['#type']);
    $this->assertEquals('checkbox', $section['logo_only']['#type']);

    // Only the main line should be required.
    $this->assertTrue($section['header_main_line']['#required']);
    $this->assertFalse($section['header_top_line']['#required'] ?? FALSE);
    $this->assertFalse($section['header_bottom_line']['#required'] ?? FALSE);
  }

  /**
   * Verifies the Files section field has the correct type.
   */
  public function testFilesFieldsRender(): void {
    $form = $this->getForm();

    $this->assertArrayHasKey('use_file_path', $form['ecms_files']);
    $this->assertEquals('checkbox', $form['ecms_files']['use_file_path']['#type']);
  }

  /**
   * Verifies the Footer section fields have the correct element types.
   */
  public function testFooterFieldsRender(): void {
    $form = $this->getForm();
    $section = $form['ecms_footer'];

    foreach (['footer_left', 'footer_center', 'footer_right', 'footer_state_info', 'footer_above'] as $key) {
      $this->assertArrayHasKey($key, $section, "Footer text field '$key' missing.");
      $this->assertEquals('text_format', $section[$key]['#type'], "'$key' should be a text_format element.");
    }

    $this->assertEquals('checkbox', $section['footer_divider']['#type']);
    $this->assertEquals('checkbox', $section['footer_wave']['#type']);
  }

  // ---------------------------------------------------------------------------
  // Save / persistence tests
  // ---------------------------------------------------------------------------

  /**
   * Verifies header text settings persist after form submission.
   */
  public function testHeaderSettingsSave(): void {
    $this->submitSettings([
      'header_top_line' => 'State of Rhode Island',
      'header_main_line' => 'Department of Testing',
      'header_bottom_line' => 'Quality Assurance Division',
      'logo_only' => 0,
    ]);

    $this->assertEquals('State of Rhode Island', $this->savedSetting('header_top_line'));
    $this->assertEquals('Department of Testing', $this->savedSetting('header_main_line'));
    $this->assertEquals('Quality Assurance Division', $this->savedSetting('header_bottom_line'));
    $this->assertEquals(0, $this->savedSetting('logo_only'));
  }

  /**
   * Verifies logo_only toggles correctly on save.
   */
  public function testLogoOnlySave(): void {
    $this->submitSettings(['logo_only' => 1]);
    $this->assertEquals(1, $this->savedSetting('logo_only'));

    $this->submitSettings(['logo_only' => 0]);
    $this->assertEquals(0, $this->savedSetting('logo_only'));
  }

  /**
   * Verifies the use_file_path checkbox toggles correctly on save.
   */
  public function testFilesSettingsSave(): void {
    $this->submitSettings(['use_file_path' => 1]);
    $this->assertEquals(1, $this->savedSetting('use_file_path'));

    $this->submitSettings(['use_file_path' => 0]);
    $this->assertEquals(0, $this->savedSetting('use_file_path'));
  }

  /**
   * Verifies all footer text column values persist after form submission.
   */
  public function testFooterColumnsSave(): void {
    $this->submitSettings([
      'footer_left' => ['value' => '<p>Left column</p>', 'format' => 'basic_html'],
      'footer_center' => ['value' => '<p>Center column</p>', 'format' => 'basic_html'],
      'footer_right' => ['value' => '<p>Right column</p>', 'format' => 'basic_html'],
      'footer_state_info' => ['value' => '<p>State info</p>', 'format' => 'basic_html'],
      'footer_above' => ['value' => '<p>Above columns</p>', 'format' => 'basic_html'],
    ]);

    $this->assertEquals('<p>Left column</p>', $this->savedSetting('footer_left')['value']);
    $this->assertEquals('basic_html', $this->savedSetting('footer_left')['format']);
    $this->assertEquals('<p>Center column</p>', $this->savedSetting('footer_center')['value']);
    $this->assertEquals('<p>Right column</p>', $this->savedSetting('footer_right')['value']);
    $this->assertEquals('<p>State info</p>', $this->savedSetting('footer_state_info')['value']);
    $this->assertEquals('<p>Above columns</p>', $this->savedSetting('footer_above')['value']);
  }

  /**
   * Verifies footer_divider and footer_wave checkboxes toggle correctly.
   */
  public function testFooterCheckboxesSave(): void {
    $this->submitSettings(['footer_divider' => 1, 'footer_wave' => 1]);
    $this->assertEquals(1, $this->savedSetting('footer_divider'));
    $this->assertEquals(1, $this->savedSetting('footer_wave'));

    $this->submitSettings(['footer_divider' => 0, 'footer_wave' => 0]);
    $this->assertEquals(0, $this->savedSetting('footer_divider'));
    $this->assertEquals(0, $this->savedSetting('footer_wave'));
  }

  // ---------------------------------------------------------------------------
  // Default value round-trip tests
  // These verify that ThemeSettingsProvider::getSetting() correctly populates
  // #default_value on a freshly built form after settings have been saved.
  // ---------------------------------------------------------------------------

  /**
   * Verifies header field default values reflect previously saved config.
   */
  public function testHeaderDefaultValuesRoundTrip(): void {
    $this->submitSettings([
      'header_top_line' => 'State of Rhode Island',
      'header_main_line' => 'Department of Testing',
      'header_bottom_line' => 'Quality Assurance Division',
      'logo_only' => 1,
    ]);

    $form = $this->getForm();
    $section = $form['ecms_header'];

    $this->assertEquals('State of Rhode Island', $section['header_top_line']['#default_value']);
    $this->assertEquals('Department of Testing', $section['header_main_line']['#default_value']);
    $this->assertEquals('Quality Assurance Division', $section['header_bottom_line']['#default_value']);
    $this->assertEquals(1, $section['logo_only']['#default_value']);
  }

  /**
   * Verifies the use_file_path default value reflects previously saved config.
   */
  public function testFilesDefaultValueRoundTrip(): void {
    $this->submitSettings(['use_file_path' => 1]);
    $this->assertEquals(1, $this->getForm()['ecms_files']['use_file_path']['#default_value']);

    $this->submitSettings(['use_file_path' => 0]);
    $this->assertEquals(0, $this->getForm()['ecms_files']['use_file_path']['#default_value']);
  }

  /**
   * Verifies footer text column default values reflect previously saved config.
   */
  public function testFooterDefaultValuesRoundTrip(): void {
    $this->submitSettings([
      'footer_left' => ['value' => '<p>Left</p>', 'format' => 'basic_html'],
      'footer_center' => ['value' => '<p>Center</p>', 'format' => 'basic_html'],
      'footer_right' => ['value' => '<p>Right</p>', 'format' => 'basic_html'],
      'footer_state_info' => ['value' => '<p>State</p>', 'format' => 'basic_html'],
      'footer_above' => ['value' => '<p>Above</p>', 'format' => 'basic_html'],
    ]);

    $section = $this->getForm()['ecms_footer'];

    $this->assertEquals('<p>Left</p>', $section['footer_left']['#default_value']);
    $this->assertEquals('<p>Center</p>', $section['footer_center']['#default_value']);
    $this->assertEquals('<p>Right</p>', $section['footer_right']['#default_value']);
    $this->assertEquals('<p>State</p>', $section['footer_state_info']['#default_value']);
    $this->assertEquals('<p>Above</p>', $section['footer_above']['#default_value']);
  }

  /**
   * Verifies footer checkbox default values reflect previously saved config.
   */
  public function testFooterCheckboxDefaultValuesRoundTrip(): void {
    $this->submitSettings(['footer_divider' => 1, 'footer_wave' => 1]);
    $section = $this->getForm()['ecms_footer'];
    $this->assertEquals(1, $section['footer_divider']['#default_value']);
    $this->assertEquals(1, $section['footer_wave']['#default_value']);
  }

}
