(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.oktaAuthBehavior = {
    attach: function (context) {
      var settings = drupalSettings.okta_login;
      var oktaSignIn = new OktaSignIn({
        baseUrl: settings.baseUrl,
        clientId: settings.clientId,
        authParams: {
          issuer: settings.baseUrl + settings.issuer,
          responseType: ['token', 'id_token'],
          display: 'page'
        }
      });
      if (oktaSignIn.token.hasTokensInUrl()) {
        oktaSignIn.token.parseTokensFromUrl(
          function success(tokens) {
            // Save the tokens for later use, e.g. if the page gets refreshed:
            // Add the token to tokenManager to automatically renew the token when needed
            tokens.forEach(token => {
              if (token.idToken) {

                oktaSignIn.tokenManager.add('idToken', token);
              }
              if (token.accessToken) {
                oktaSignIn.tokenManager.add('accessToken', token);
              }
            });

            var idToken = oktaSignIn.tokenManager.get('idToken');

            // Remove the tokens from the window location hash
            window.location.hash='';

            authenticate();
          },
          function error(err) {
            // handle errors as needed
            console.error(err);
          }
        );
      } else {
        oktaSignIn.session.get(function (res) {
          // Session exists, close it.
          if (res.status === 'ACTIVE') {
            oktaSignIn.session.close();
          }

          // No session, show the login form
          oktaSignIn.renderEl(
            { el: '#' + settings.container_id },
            function success(res) {
              // Nothing to do in this case, the widget will automatically redirect
              // the user to Okta for authentication, then back to this page if successful
            },
            function error(err) {
              // handle errors as needed
              console.error(err);
            }
          );
        });
      }

      function authenticate() {
        var accessToken = oktaSignIn.tokenManager.get("accessToken");

        if (!accessToken) {
          return;
        }

        window.location.href = settings.authenticate_url + '?token=' + accessToken.accessToken;
      }

    }
  };
})(jQuery, Drupal, drupalSettings);
