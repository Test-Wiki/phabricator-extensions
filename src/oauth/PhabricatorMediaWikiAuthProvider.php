<?php

final class PhabricatorMediaWikiAuthProvider
  extends PhabricatorOAuth2AuthProvider {

  const PROPERTY_MEDIAWIKI_NAME = 'oauth2:mediawiki:name';
  const PROPERTY_MEDIAWIKI_URI = 'oauth2:mediawiki:uri';

 public function readFormValuesFromProvider() {
    $config = $this->getProviderConfig();
    $uri = $config->getProperty(self::PROPERTY_MEDIAWIKI_URI);

    return parent::readFormValuesFromProvider() + array(
      self::PROPERTY_MEDIAWIKI_NAME => $this->getProviderDomain(),
      self::PROPERTY_MEDIAWIKI_URI  => $uri,
    );
  }

  public function readFormValuesFromRequest(AphrontRequest $request) {
    $is_setup = $this->isCreate();
    if ($is_setup) {
      $parent_values = array();
      $name = $request->getStr(self::PROPERTY_MEDIAWIKI_NAME);
    } else {
      $parent_values = parent::readFormValuesFromRequest($request);
      $name = $this->getProviderDomain();
    }

    return $parent_values + array(
      self::PROPERTY_MEDIAWIKI_NAME => $name,
      self::PROPERTY_MEDIAWIKI_URI =>
        $request->getStr(self::PROPERTY_MEDIAWIKI_URI),
    );
  }

  public function getProviderName() {
    return pht('MediaWiki');
  }

  public function getWikiURI() {
    $config = $this->getProviderConfig();
    $uri = $config->getProperty(self::PROPERTY_MEDIAWIKI_URI);
    $uri = new PhutilURI($uri);
    $normalized = $uri->getProtocol().'://'.$uri->getDomain();
    if ($uri->getPort() != 80 && $uri->getPort() != 443) {
      $normalized .= ':'.$uri->getPort();
    }
    if (strlen(($uri->getPath())) > 0 && $uri->getPath() !== '/') {
      $normalized .= $uri->getPath();
    }
    if (substr($normalized, -1) == '/') {
      $normalized = substr($normalized, 0, -1);
    }
    return $normalized;
  }
  
  public function getConfigurationHelp() {
    if ($this->isCreate()) {
      return pht(
        "**Step 1 of 2**: Provide the name and URI for your MediaWiki install.\n\n".
        "In the next step, you will create an auth consumer in MediaWiki to be used by Phabricator oauth.");
    }

    return parent::getConfigurationHelp();
  }

  protected function getProviderConfigurationHelp() {
    $config = $this->getProviderConfig();
    $base_uri = rtrim(
      $config->getProperty(self::PROPERTY_MEDIAWIKI_URI), '/');
    $login_uri = PhabricatorEnv::getURI($this->getLoginURI());

    return pht(
        "**Step 2 of 2**: Create a MediaWiki auth consumer for this Phabricator instance.".
        "\n\n".
        "NOTE: Propose a consumer with the form at this url: %s".
        "\n\n".
        "Provide the following settings on the consumer registration:\n\n".
        "  - **Callback URL:** Set this to: `%s`\n".
        "  - **Grants:** `Basic Rights` is all that is needed for authentication.\n".
        "\n\n".
        "After you register the consumer, a **Consumer Key** and ".
        "**Consumer Secret** will be provided to you by MediaWiki. ".
        "To complete configuration of phabricator, copy the provided keys into ".
        "the corresponding fields above.".
        "\n\n".
        "NOTE: Before Phabricator can successfully authenticate to your MediaWiki,".
        " a wiki admin must approve the oauth consumer registration using the form".
        " which can be found at the following url: %s",
        $wiki_uri.'/index.php?title=Special:OAuthConsumerRegistration/propose',
        $login_uri,
        $wiki_uri.'/index.php?title=Special:OAuthManageConsumers/proposed');
  }

  protected function newOAuthAdapter() {
    $config = $this->getProviderConfig();

    return id(new PhutilMediaWikiAuthAdapter())
      ->setAdapterDomain($config->getProviderDomain())
      ->setMediaWikiBaseURI(
        $config->getProperty(self::PROPERTY_MEDIAWIKI_URI));
  }

  protected function getLoginIcon() {
    return 'MediaWiki';
  }

  private function isCreate() {
    return !$this->getProviderConfig()->getID();
  }

  public function processEditForm(
    AphrontRequest $request,
    array $values) {

    $is_setup = $this->isCreate();

    if (!$is_setup) {
      list($errors, $issues, $values) =
        parent::processEditForm($request, $values);
    } else {
      $errors = array();
      $issues = array();
    }

    $key_name = self::PROPERTY_MEDIAWIKI_NAME;
    $key_uri = self::PROPERTY_MEDIAWIKI_URI;

    if (!strlen($values[$key_name])) {
      $errors[] = pht('MediaWiki instance name is required.');
      $issues[$key_name] = pht('Required');
    } else if (!preg_match('/^[a-z0-9.]+\z/', $values[$key_name])) {
      $errors[] = pht(
        'MediaWiki instance name must contain only lowercase letters, '.
        'digits, and periods.');
      $issues[$key_name] = pht('Invalid');
    }

    if (!strlen($values[$key_uri])) {
      $errors[] = pht('MediaWiki base URI is required.');
      $issues[$key_uri] = pht('Required');
    } else {
      $uri = new PhutilURI($values[$key_uri]);
      if (!$uri->getProtocol()) {
        $errors[] = pht(
          'MediaWiki base URI should include protocol (like "%s").',
          'https://');
        $issues[$key_uri] = pht('Invalid');
      }
    }

    if (!$errors && $is_setup) {
      $config = $this->getProviderConfig();

      $config->setProviderDomain($values[$key_name]);
    }

    return array($errors, $issues, $values);
  }

  public function extendEditForm(
    AphrontRequest $request,
    AphrontFormView $form,
    array $values,
    array $issues) {

    $is_setup = $this->isCreate();

    $e_required = $request->isFormPost() ? null : true;

    $v_name = $values[self::PROPERTY_MEDIAWIKI_NAME];
    if ($is_setup) {
      $e_name = idx($issues, self::PROPERTY_MEDIAWIKI_NAME, $e_required);
    } else {
      $e_name = null;
    }

    $v_uri = $values[self::PROPERTY_MEDIAWIKI_URI];
    $e_uri = idx($issues, self::PROPERTY_MEDIAWIKI_URI, $e_required);

    if ($is_setup) {
      $form
       ->appendChild(
          id(new AphrontFormTextControl())
            ->setLabel(pht('MediaWiki Instance Name'))
            ->setValue($v_name)
            ->setName(self::PROPERTY_MEDIAWIKI_NAME)
            ->setError($e_name)
            ->setCaption(pht(
            'Use lowercase letters, digits, and periods. For example: %s',)));
    } else {
      $form
        ->appendChild(
          id(new AphrontFormStaticControl())
            ->setLabel(pht('MediaWiki Instance Name'))
            ->setValue($v_name));
    }

    $form
      ->appendChild(
        id(new AphrontFormTextControl())
          ->setLabel(pht('MediaWiki Base URI'))
          ->setValue($v_uri)
          ->setName(self::PROPERTY_MEDIAWIKI_URI)
          ->setCaption(
            pht('URL to your MediaWiki install upto but not including index.php',))
          ->setError($e_uri));

    if (!$is_setup) {
      parent::extendEditForm($request, $form, $values, $issues);
    }
  }

  public function hasSetupStep() {
    return true;
  }

  public static function getMediaWikiProvider() {
    $providers = self::getAllEnabledProviders();

    foreach ($providers as $provider) {
      if ($provider instanceof PhabricatorMediaWikiAuthProvider) {
        return $provider;
      }
    }

    return null;
  }

}
