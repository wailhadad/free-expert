// Pusher.logToConsole = true;
var pusher = new Pusher(pusherKey, {
  cluster: pusherCluster
});

var channel = pusher.subscribe('notification-channel');

channel.bind('notification.received', function (data) {
  // Reload the notification dropdown if it exists
  if ($('#notification-dropdown').length) {
    $('#notification-dropdown').load(location.href + ' #notification-dropdown > *');
  }
  // Reload the notification list if it exists
  if ($('#notification-list').length) {
    $('#notification-list').load(location.href + ' #notification-list > *');
  }
}); 