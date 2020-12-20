<?php

/**
 * Authentication adapter for MediaWiki OAuth2.
 */
final class PhutilMediaWikiAuthAdapter
    extends PhutilOAuthAuthAdapter {
    
  private $mediaWikiBaseURI = '';
  private $adapterDomain;
    
  public function setMediaWikiBaseURI($uri) {
    $this->mediaWikiBaseURI = $uri;
    return $this;
  }
    
  public function getMediaWikiBaseURI() {
    return $this->mediaWikiBaseURI;
  }

  public function getWikiPageURI($title, $query_params = null) {
    $uri = $this->mediaWikiBaseURI;
    if (substr($uri, -1) != '/') {
      $uri .= '/';
    }
    if (!is_array($query_params)) {
      $query_params = array();
    }
    $query_params['title'] = $title;
    return rawurldecode( $uri.'index.php?'.
        http_build_query(
          $query_params,
          '',
          '&'));
  }

  public function getAdapterDomain() {
    return $this->adapterDomain;
  }
    
  public function setAdapterDomain($domain) {
    $this->adapterDomain = $domain;
    return $this;
  }
    
  public function getAdapterType() {
    return 'mediawiki';
  }

  public function getAccountID() {
   // $this->getHandshakeData();
    return idx($this->loadOAuthAccountData(), 'userid');
  }
    
  public function getAccountEmail() {
    return idx($this->loadOAuthAccountData(), 'email');
  }

  public function getAccountURI() {
    $name = $this->getAccountName();
    if (strlen($name)) {
      return $this->getWikiPageURI('User:'.$name);
    }
    return null;
  }
    
  public function getAccountName() {
    return idx($this->loadOAuthAccountData(), 'username');
  }

  public function getAccountRealName() {
    $info = $this->loadOAuthAccountData();
    return idx($info, 'realname');
  }
    
  protected function getAuthenticateBaseURI() {
    return $this->getMediaWikiURI('rest.php/oauth2/authorize');
  }
    
  protected function getTokenBaseURI() {
    return $this->getMediaWikiURI('rest.php/oauth2/access_token');
  }
    
  public function getScope() {
     return '';
   }
    
  public function getExtraAuthenticateParameters() {
    return array(
      'response_type' => 'code',
    );
  }

  public function getExtraTokenParameters() {
    return array(
      'grant_type' => 'authorization_code',
    );
  }

  protected function loadOAuthAccountData() {
    $uri = id(new PhutilURI($this->getMediaWikiURI('rest.php/oauth2/resource/profile')));
    $future = new HTTPSFuture($uri);
    $token_header = sprintf('Bearer %s', $this->getAccessToken());
    $future->addHeader('Authorization', $token_header);
    list($body) = $future->resolvex();
    try {
      $data = phutil_json_decode($body);
      return $data['result'];
    } catch (PhutilJSONParserException $ex) {
      throw new Exception(
        pht(
          'Expected valid JSON response from MediaWiki request'),
        $ex);
     }
  }

  protected function willProcessTokenRequestResponse($body) {
    if (substr_count($body, 'Error:') > 0) {
      throw new Exception(
        pht('OAuth provider returned an error response.'));
    }
  }
    
   private function getMediaWikiURI($path) {
    return rtrim($this->mediaWikiBaseURI, '/').'/'.ltrim($path, '/');
  }
}
