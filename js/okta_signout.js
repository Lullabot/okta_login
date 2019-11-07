(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.oktaSignoutBehavior = {
    attach: function (context, settings) {
      var settings = drupalSettings.okta_login;
      var oktaSignIn = new OktaSignIn({
        baseUrl: settings.baseUrl
      });
      oktaSignIn.session.close(function (res) {
        window.location.href = settings.logoutRedirectUrl;
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
