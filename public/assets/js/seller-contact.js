"use strict";
$('body').on('submit', '#sellerContactForm', function (e) {
  e.preventDefault();
  if (demo_mode == 'active') {
    toastr['warning']("This is demo version. you can't change anything.");
    return;
  }

  let sellerContactForm = document.getElementById('sellerContactForm');
  $('.request-loader').addClass('show');
  var url = $(this).attr('action');
  var method = $(this).attr('method');

  let fd = new FormData(sellerContactForm);
  $.ajax({
    url: url,
    method: method,
    data: fd,
    contentType: false,
    processData: false,
    success: function (data) {
      $('.request-loader').removeClass('show');
      $('.em').each(function () {
        $(this).html('');
      });

      if (data == 'success') {
        location.reload();
      }
    },
    error: function (error) {
      grecaptcha.reset();
      $('.em').each(function () {
        $(this).html('');
      });

      for (let x in error.responseJSON.errors) {
        document.getElementById('err_' + x).innerHTML = error.responseJSON.errors[x][0];
      }

      $('.request-loader').removeClass('show');
    }
  })
});

window.openDirectChatModal = function(chatId, partnerName, partnerAvatar) {
  // Set partner name
  document.getElementById('direct-chat-partner-name').textContent = partnerName || 'Chat';
  // Set partner avatar
  var avatarElem = document.getElementById('direct-chat-partner-avatar');
  if (avatarElem) {
    avatarElem.innerHTML = partnerAvatar ? '<img src="' + partnerAvatar + '" style="width:100%;height:100%;object-fit:cover;">' : '';
  }
  // Clear previous messages (optional, or you can load chat history here)
  var messagesElem = document.getElementById('direct-chat-messages');
  if (messagesElem) messagesElem.innerHTML = '';
  // Open the modal (Bootstrap 5)
  var modal = new bootstrap.Modal(document.getElementById('directChatModal'));
  modal.show();
};
