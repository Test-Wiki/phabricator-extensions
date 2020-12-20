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
    $token_header = sprintf('token %s', $this->getAccessToken());
    $future->addHeader('Authorization', $token_header);
    list($body) = $future->resolvex();
    try {
      $data = $this->decodeJWT($body);
      return $data['result'];
    } catch (Exception $ex) {
      throw new Exception(
        pht(
          'Expected valid JSON response from MediaWiki request'),
        $ex);
     }
  }

private function decodeJWT($jwt) {
    list($headb64, $bodyb64, $sigb64) = explode('.', $jwt);

    $header = json_decode($this->urlsafeB64Decode($headb64));
    $body = json_decode($this->urlsafeB64Decode($bodyb64));
    $sig = $this->urlsafeB64Decode($sigb64);

    $expect_sig = hash_hmac(
        'sha256',
        "$headb64.$bodyb64",
        $this->getConsumerSecret()->openEnvelope(),
        true);

    // MediaWiki will only use sha256 hmac (HS256) for now.
    // This checks that an attacker doesn't return invalid JWT signature type.
    if ($header->alg !== 'HS256' ||
        !$this->compareHash($sig, $expect_sig)) {
      throw new Exception('Invalid JWT signature.');
    }

    return $body;
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
