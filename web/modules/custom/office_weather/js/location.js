(function (Drupal, drupalSettings) {
  Drupal.behaviors.officeWeather = {
    attach: function (context, settings) {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
          let lat = position.coords.latitude;
          let lon = position.coords.longitude;

          // Redirect to the weather route with coordinates
          window.location.href = `/weather/forecast/${lat}/${lon}`;
        }, function () {
          alert('Location access denied. Please enable it.');
        });
      } else {
        alert('Geolocation is not supported by your browser.');
      }
    }
  };
})(Drupal, drupalSettings);
