<?php

/**
 * Authentication adapter for MediaWiki OAuth2.
 */
final class PhutilMediaWikiAuthAdapter
    extends PhutilOAuthAuthAdapter {

  private $userinfo;
  private $domain = '';
  private $mediaWikiBaseURI = '';
  private $callback_uri = '';

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

  public function getAccountID() {
    $this->getHandshakeData();
    return idx($this->loadOAuthAccountData(), 'userid');
  }

  public function getAccountName() {
    return idx($this->loadOAuthAccountData(), 'username');
  }

  public function getAccountURI() {
    $name = $this->getAccountName();
    if (strlen($name)) {
      return $this->getWikiPageURI('User:'.$name);
    }
    return null;
  }

  public function getAccountImageURI() {
    $info = $this->loadOAuthAccountData();
    return idx($info, 'profile_image_url');
  }

  public function getAccountRealName() {
    $info = $this->loadOAuthAccountData();
    return idx($info, 'name');
  }

  public function getAdapterType() {
    return 'mediawiki';
  }

  public function getAdapterDomain() {
    return $this->domain;
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

  /* mediawiki oauth needs the callback uri to be "oob"
   (out of band callback) */
  public function getCallbackURI() {
    return $this->callback_uri;
  }

  public function setCallbackURI($uri) {
    $this->callback_uri = $uri;
  }

  public function shouldAddCSRFTokenToCallbackURI() {
    return false;
  }

  protected function getAuthenticateBaseURI() {
    return $this->mediaWikiBaseURI('rest.php/oauth2/authorize');
  }

  public function setAdapterDomain($domain) {
    $this->domain = $domain;
    return $this;
  }

  public function setMediaWikiBaseURI($uri) {
    $this->mediaWikiBaseURI = $uri;
    return $this;
  }

  protected function getTokenBaseURI() {
    return $this->mediaWikiBaseURI('rest.php/oauth2/access_token');
  }

  protected function loadOAuthAccountData() {
    if ($this->userinfo === null) {
      $uri = id(new PhutilURI($this->mediaWikiBaseURI('rest.php/oauth2/resource/profile')))
        ->replaceQueryParam('access_token', $this->getAccessToken());
      list($body) = id(new HTTPSFuture($uri))->resolvex();
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
    return $this->userinfo;
  }

  protected function willProcessTokenRequestResponse($body) {
    if (substr_count($body, 'Error:') > 0) {
      throw new Exception(
        pht('OAuth provider returned an error response.'));
    }
  }
}
