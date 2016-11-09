<?php

namespace Drupal\colors\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides dynamic tabs based on active color schemes.
 */
class ColorsLocalTask extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (\Drupal::service('plugin.manager.colors')->getDefinitions() as $plugin_id => $plugin) {
      if (!empty($plugin['parent'])) {
        $this->derivatives[$plugin_id] = $base_plugin_definition;
        $this->derivatives[$plugin_id]['title'] = $plugin['title'];
        $this->derivatives[$plugin_id]['route_parameters'] = array('type' => $plugin_id);
        $this->derivatives[$plugin_id]['parent_id'] = $base_plugin_definition['base_route'];
        $this->derivatives[$plugin_id]['weight'] = $plugin['weight'];
      }

      // Default tab
      if ($plugin['default']) {
        dpm($plugin);
        dpm($base_plugin_definition);
        $this->derivatives[$plugin_id] = $base_plugin_definition;
//        $this->derivatives[$plugin_id]['parent_id'] = $base_plugin_definition['base_route'];


        $this->derivatives[$plugin_id]['parent_id'] = 'colors.ui_settings_entity.user';
        $this->derivatives[$plugin_id]['title'] = $plugin['title'];
        $this->derivatives[$plugin_id]['route_parameters'] = array('type' => $plugin_id);
      }
    }

    return $this->derivatives;
  }

}
