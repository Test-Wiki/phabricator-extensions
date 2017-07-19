<?php

// Custom greeter on login page. See T963, T862, T116142

final class CustomLoginHandler
  extends PhabricatorAuthLoginHandler {

  public function getAuthLoginHeaderContent() {
    return phutil_safe_html(
      '<div style="font-weight:bold;font-size:1.8em;text-align:center">Log in or register to Test Wiki Phabricator</div><p style="font-size:1.1em;text-align:center;line-height:1.5;padding:10px">Click the MediaWiki button below to connect your Test Wiki account.</p>');
  }

}
