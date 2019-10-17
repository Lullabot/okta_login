(function ($, Drupal) {
  Drupal.behaviors.oktaSignoutBehavior = {
    attach: function (context, settings) {
      var oktaSignIn = new OktaSignIn({
        baseUrl: "https://dev-703132.okta.com"
      });
      oktaSignIn.session.close(function (res) {
        window.location.href = 'https://drupaloktalogin.lndo.site/user/logout';
      });
    }
  };
})(jQuery, Drupal);
