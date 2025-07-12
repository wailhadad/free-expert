// Pusher.logToConsole = true;
var pusher = new Pusher(pusherKey, {
  cluster: pusherCluster
});

var channel = pusher.subscribe('notification-channel');

channel.bind('notification.received', function (data) {
  playNotificationSound();
  // Reload the notification dropdown if it exists
  if ($('#notification-dropdown').length) {
    $('#notification-dropdown').load(location.href + ' #notification-dropdown > *');
  }
  // Reload the notification list if it exists
  if ($('#notification-list').length) {
    $('#notification-list').load(location.href + ' #notification-list > *');
  }
});

function playNotificationSound() {
  try {
    var audio = new Audio('/assets/notification.mp3');
    audio.volume = 1.0;
    audio.play().catch(function(e){});
    
    // Stop the sound after 3 seconds
    setTimeout(function() {
      audio.pause();
      audio.currentTime = 0;
    }, 4000);
  } catch (e) {
    console.warn('Notification sound could not be played:', e);
  }
} 