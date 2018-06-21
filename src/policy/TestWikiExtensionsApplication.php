<?php

final class WMFExtensionsApplication extends PhabricatorApplication {

  public function getName() {
    return pht('Test Wiki Customizations');
  }

  public function getRoutes() {
    return array(
      '/wmf/' => array(
        'escalate-task/(?P<id>\d+)/' => 'WMFEscalateTaskController',
      ),
    );
  }

  public function getEventListeners() {
    return array(
      new WMFEscalateTaskEventListener(),
    );
  }

}

