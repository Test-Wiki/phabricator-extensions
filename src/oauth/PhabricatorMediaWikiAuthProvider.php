<?php

final class PhabricatorMediaWikiAuthProvider
  extends PhabricatorOAuth2AuthProvider {
  
  const PROPERTY_PHABRICATOR_NAME = 'oauth2:phabricator:name';
  const PROPERTY_PHABRICATOR_URI  = 'oauth2:phabricator:uri';

  public function getProviderName() {
    return pht('MediaWiki');
  }

  protected function getProviderConfigurationHelp() {
    $uri = PhabricatorEnv::getProductionURI('/');
    $callback_uri = PhabricatorEnv::getURI($this->getLoginURI());

        "**Step 1 of 2**: Provide the name and URI for your MediaWiki install.\n\n".
        "In the next step, you will create an auth consumer in MediaWiki to be used by Phabricator oauth.");
        "\n\n".
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
    return new PhutilMediaWikiAuthAdapter();
  }

  protected function getLoginIcon() {
    return 'MediaWiki';
  }

}
