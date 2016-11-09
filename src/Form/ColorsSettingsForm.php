<?php

namespace Drupal\colors\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Asset\CssOptimizer;
use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Environment;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\colors\Plugin\ColorsSchemePluginCollection;

/**
 * Configure color settings.
 */
class ColorsSettingsForm extends ConfigFormBase {

  /**
   * An array of configuration names that should be editable.
   *
   * @var array
   */
  protected $editableConfig = [];

  /**
   * Stores the Colors Scheme plugins.
   *
   * @var \Drupal\colors\Plugin\ColorsSchemePluginCollection
   */
  protected $pluginCollection;


  /**
   * Constructs a ColorsSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   */
  public function __construct(ConfigFactoryInterface $config_factory, PluginManagerInterface $manager) {
    parent::__construct($config_factory);

    $this->pluginCollection = new ColorsSchemePluginCollection($manager, $this);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.colors')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'colors_ui_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return $this->editableConfig;
  }

  /**
   *
   * @return \Drupal\colors\Plugin\ColorsSchemePluginCollection;|\Drupal\colors\Plugin\ColorsSchemeinterface[]
   */
  public function getPlugins() {
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $route = \Drupal::routeMatch();
    $entity = $this->getEntityFromRoute($route);
    // Color picker.
    $form = colors_load_colorpicker();
    $form['#attached'] = array(
      'library' => array(
        'colors/colors',
      ),
    );

    $global_config = \Drupal::configFactory()->getEditable('colors.settings');

    $config = colors_get_info($entity);

    $palette = colors_get_palette($global_config);

    dpm($palette);


    $names = $global_config->get('fields');
    $form['palette']['#tree'] = TRUE;
//    foreach ($palette as $name => $value) {
//      if (isset($names[$name])) {
//        $form['palette'][$name] = array(
//          '#type' => 'textfield',
//          '#title' => $names[$name],
//          '#value_callback' => 'color_palette_color_value',
//          '#default_value' => $value,
//          '#size' => 8,
//          '#attributes' => array('class' => array('colorpicker-input')),
//        );
//      }
//    }

    $this->getExtraFields($form, $entity);


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @todo:

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo:

    parent::submitForm($form, $form_state);
  }

  protected function getExtraFields(&$form, $entity) {
    $plugins = \Drupal::service('plugin.manager.colors')->getDefinitions();
    $plugin = $plugins[$entity];

    if (!$plugin) {
      // Global settings tab.
      $form['process_order'] = array(
        '#tree' => TRUE,
        'info' => array(
          '#type' => 'item',
          '#title' => t('Process order'),
        ),
        'enabled' => array(
          '#type' => 'checkbox',
          '#title' => t('Change the CSS processing order.'),
          '#default_value' => \Drupal::configFactory()->getEditable('colors.settings')->get('override'),
          '#description' => t('Color order is cascading, CSS from modules at the bottom will override the top.'),
        ),
      );

      $form['modules'] = array(
        '#tree' => TRUE,
      );
      $delta = 0;
//      foreach (module_invoke_all('colors_info') as $module => $info) {
//        if (!variable_get("colors_$module" . '_enabled', FALSE)) {
//          continue;
//        }
//        $weight = variable_get('colors_weight_' . $module, $delta);
//        $form['modules'][$module]['#name'] = $info['title'];
//        $form['modules'][$module]['#weight'] = $weight;
//        $form['modules'][$module]['weight'] = array(
//          '#type' => 'textfield',
//          '#title' => t('Weight for @title', array('@title' => $info['title'])),
//          '#title_display' => 'invisible',
//          '#size' => 4,
//          '#default_value' => $weight,
//          '#attributes' => array('class' => array('colors-weight')),
//        );
//        $delta++;
//      }
//      uasort($form['modules'], 'element_sort');

      $form['order_settings'] = array(
        '#type' => 'container',
        '#states' => array(
          'visible' => array(
            'input[name="process_order[enabled]"]' => array('checked' => TRUE),
          ),
        ),
      );

    }
    else {

      $plugin_instance = \Drupal::service('plugin.manager.colors')->createInstance($plugin['id']);

      if ($plugin_instance) {
        dpm($plugin_instance);
        dpm($plugin_instance->testFunc());
      }





      # colors.node.article
      # colors.user.1234
      # colors.user_role.administrator
      # colors.vocabulary.tags

      $multiple = $plugin['multiple'];
      $callback = $plugin['callback'];

      // vocabs
      $options = [];
      if ($multiple && is_callable($multiple)) {
        $parents = $multiple();
        foreach ($parents as $parent) {
          $options[$parent->id()] = $callback($parent);
        }
      }
      elseif (is_callable($callback)) {
          $options = $callback();
      }

      dpm($options);


      $default_palette = \Drupal::configFactory()->getEditable('colors.settings')->get('palette');
      if($config_factory = \Drupal::configFactory()->listAll("colors.$entity.")) {
        foreach ($config_factory as $config) {

        }
      }
    }
  }

  // @todo: service
  public function getEntityFromRoute($route) {
    $part = '';
    $route_name = $route->getRouteName();
    if ($route_name === 'colors.ui_settings_entity.users') {
      $part .= 'user_current';
    }
    elseif ($route_name !== 'colors.ui_settings') {
      $parts = explode('/', $route->getRouteObject()->getPath());
      $part .= end($parts);
    }
    return $part;
  }

}
