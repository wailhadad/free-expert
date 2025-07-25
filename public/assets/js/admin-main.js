"use strict";
$(function ($) {
  /*****************************************************
  ==========Bootstrap Notify start==========
  ******************************************************/
  function bootnotify(message, title, type) {
    var content = {};

    content.message = message;
    content.title = title;
    content.icon = 'fa fa-bell';

    $.notify(content, {
      type: type,
      placement: {
        from: 'top',
        align: 'right'
      },
      showProgressbar: true,
      time: 1000,
      allow_dismiss: true,
      delay: 4000
    });
  }
  /*****************************************************
  ==========Bootstrap Notify end==========
  ******************************************************/
  /*****************************************************
 ==========Demo code ==========
 ******************************************************/
  if (demo_mode == 'active') {
    $.ajaxSetup({
      beforeSend: function (jqXHR, settings, event) {
        if (settings.type == 'POST' && settings.url.indexOf('user/qr-code/generate') == -1) {
          if ($(".request-loader").length > 0) {
            $(".request-loader").removeClass('show');
          }
          if ($(".modal").length > 0) {
            $(".modal").modal('hide');
          }
          if ($("button[disabled='disabled']").length > 0) {
            $("button[disabled='disabled']").removeAttr('disabled');
          }
          bootnotify('This is demo version. You cannot change anything here!', 'Demo Version', 'warning')
          jqXHR.abort(event);
        }
      },
      complete: function () {
        // hide progress spinner
      }
    });
  }
  /*****************************************************
  ==========Demo code end==========
  ******************************************************/

  if (account_status == 1 || secret_login == 1) {
    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    });
  } else {
    $.ajaxSetup({
      beforeSend: function (jqXHR, settings) {
        if (settings.type == 'POST' && status == 0) {
          if ($(".request-loader").length > 0) {
            $(".request-loader").removeClass('show');
          }
          if ($(".modal").length > 0) {
            $(".modal").modal('hide');
          }
          if ($("button[disabled='disabled']").length > 0) {
            $("button[disabled='disabled']").removeAttr('disabled');
          }
          bootnotify('Your account needs Admin approval!', 'Alert', 'warning')
          jqXHR.abort(event);
        }
      },
      complete: function () {
      }
    });
  }

  // sidebar search start
  $(".sidebar-search").on('input', function () {
    let term = $(this).val().toLowerCase();

    if (term.length > 0) {
      $(".sidebar ul li.nav-item").each(function (i) {
        let menuName = $(this).find("p").text().toLowerCase();
        let $mainMenu = $(this);

        // if any main menu is matched
        if (menuName.indexOf(term) > -1) {
          $mainMenu.removeClass('d-none');
          $mainMenu.addClass('d-block');
        } else {
          let matched = 0;
          let count = 0;
          // search sub-items of the current main menu (which is not matched)
          $mainMenu.find('span.sub-item').each(function (i) {
            // if any sub-item is matched of the current main menu, set the flag
            if ($(this).text().toLowerCase().indexOf(term) > -1) {
              count++;
              matched = 1;
            }
          });

          // if any sub-item is matched  of the current main menu (which is not matched)
          if (matched == 1) {
            $mainMenu.removeClass('d-none');
            $mainMenu.addClass('d-block');
          } else {
            $mainMenu.removeClass('d-block');
            $mainMenu.addClass('d-none');
          }
        }
      });
    } else {
      $(".sidebar ul li.nav-item").addClass('d-block');
    }
  });
  // sidebar search end


  // disabling default behave of form submits start
  $("#ajaxEditForm").attr('onsubmit', 'return false');
  $("#ajaxForm").attr('onsubmit', 'return false');
  // disabling default behave of form submits end


  // datepicker & timepicker start
  $('.datepicker').datepicker({
    autoclose: true
  });

  $('.timepicker').timepicker();
  // datepicker & timepicker end


  // fontawesome icon picker start
  $('.icp-dd').iconpicker();
  // fontawesome icon picker end


  // select2 start
  $('.select2').select2();
  // select2 end


  // summernote initialization start
  $(".summernote").each(function (i) {
    tinymce.init({
      selector: '.summernote',
      plugins: 'autolink charmap emoticons image link lists media searchreplace table visualblocks wordcount',
      toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
      tinycomments_mode: 'embedded',
      tinycomments_author: 'Author name',
      promotion: false,
      mergetags_list: [
        { value: 'First.Name', title: 'First Name' },
        { value: 'Email', title: 'Email' },
      ]
    });
  });

  // summernote initialization start
  $(".summernote2").each(function (i) {
    tinymce.init({
      selector: '.summernote2',
      plugins: 'autolink charmap emoticons image link lists media searchreplace table visualblocks wordcount',
      toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
      tinycomments_mode: 'embedded',
      tinycomments_author: 'Author name',
      promotion: false,
      mergetags_list: [
        { value: 'First.Name', title: 'First Name' },
        { value: 'Email', title: 'Email' },
      ]
    });
  });



  $(document).on('click', ".note-video-btn", function () {
    let i = $(this).index();

    if ($(".summernote").eq(i).parents(".modal").length > 0) {
      setTimeout(() => {
        $("body").addClass('modal-open');
      }, 500);
    }
  });
  // summernote initialization end


  // Form Submit with AJAX Request Start
  $("#submitBtn").on('click', function (e) {
    $(e.target).attr('disabled', true);
    $(".request-loader").addClass("show");

    if ($(".iconpicker-component").length > 0) {
      $("#inputIcon").val($(".iconpicker-component").find('i').attr('class'));
    }

    let ajaxForm = document.getElementById('ajaxForm');
    let fd = new FormData(ajaxForm);
    let url = $("#ajaxForm").attr('action');
    let method = $("#ajaxForm").attr('method');

    if ($("#ajaxForm .summernote").length > 0) {
      $("#ajaxForm .summernote").each(function (i) {

        let index = i;
        let $toInput = $('.summernote').eq(index);

        let tmcId = $toInput.attr('id');
        let content = tinyMCE.get(tmcId).getContent();

        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
      });
    }

    $.ajax({
      url: url,
      method: method,
      data: fd,
      contentType: false,
      processData: false,
      success: function (data) {
        $(e.target).attr('disabled', false);
        $('.request-loader').removeClass('show');

        $('.em').each(function () {
          $(this).html('');
        });

        if (data.status == 'success') {
          location.reload();
        }
      },
      error: function (error) {
        $('.em').each(function () {
          $(this).html('');
        });

        // Check for package limit errors and redirect with flash message
        if (error.responseJSON.error && 
            (error.responseJSON.error.includes('package') || 
             error.responseJSON.error.includes('maximum number') ||
             error.responseJSON.error.includes('does not allow'))) {
          
          // Redirect to forms index with flash message
          window.location.href = '/seller/service-management/forms?language=en&error=' + encodeURIComponent(error.responseJSON.error);
          return;
        }

        // Check for specific error message from backend
        if (error.responseJSON.error) {
          // Show general error message
          alert(error.responseJSON.error);
        }
        // Check for field-specific errors
        else if (error.responseJSON.errors) {
          for (let x in error.responseJSON.errors) {
            document.getElementById('err_' + x).innerHTML = error.responseJSON.errors[x][0];
          }
        }

        $('.request-loader').removeClass('show');
        $(e.target).attr('disabled', false);
      }
    });
  });
  // Form Submit with AJAX Request End


  // Form Prepopulate After Clicking Edit Button Start
  $(".editBtn").on('click', function () {
    $('.em').each(function () {
      $(this).html('');
    });

    let datas = $(this).data();
    delete datas['toggle'];

    for (let x in datas) {
      if ($("#in_" + x).hasClass('summernote')) {
        tinyMCE.activeEditor.setContent(datas[x])
      } else if ($("#in_" + x).hasClass('summernote2')) {
        tinyMCE.activeEditor.setContent(datas[x]);
      } else if ($("#in_" + x).data('role') == 'tagsinput') {
        if (datas[x].length > 0) {
          let arr = datas[x].split(" ");

          for (let i = 0; i < arr.length; i++) {
            $("#in_" + x).tagsinput('add', arr[i]);
          }
        } else {
          $("#in_" + x).tagsinput('removeAll');
        }
      } else if ($("input[name='" + x + "']").attr('type') == 'radio') {
        $("input[name='" + x + "']").each(function (i) {
          if ($(this).val() == datas[x]) {
            $(this).prop('checked', true);
          }
        });
      } else {
        $("#in_" + x).val(datas[x]);

        if ($('.in_image').length > 0) {
          $('.in_image').attr('src', datas['image']);
        }

        if ($('#in_icon').length > 0) {
          $('#in_icon').attr('class', datas['icon']);
        }
      }
    }


    if ('edit' in datas && datas.edit === 'editAdvertisement') {
      if (datas.ad_type === 'banner') {
        if (!$('#edit-slot-input').hasClass('d-none')) {
          $('#edit-slot-input').addClass('d-none');
        }

        $('#edit-image-input').removeClass('d-none');
        $('#edit-url-input').removeClass('d-none');
      } else {
        if (!$('#edit-image-input').hasClass('d-none') && !$('#edit-url-input').hasClass('d-none')) {
          $('#edit-image-input').addClass('d-none');
          $('#edit-url-input').addClass('d-none');
        }

        $('#edit-slot-input').removeClass('d-none');
      }
    }


    // focus & blur colorpicker inputs
    setTimeout(() => {
      $(".jscolor").each(function () {
        $(this).focus();
        $(this).blur();
      });
    }, 300);
  });
  // Form Prepopulate After Clicking Edit Button End


  // Form Update with AJAX Request Start
  $("#updateBtn").on('click', function (e) {
    $(".request-loader").addClass("show");

    if ($(".edit-iconpicker-component").length > 0) {
      $("#editInputIcon").val($(".edit-iconpicker-component").find('i').attr('class'));
    }

    let ajaxEditForm = document.getElementById('ajaxEditForm');
    let fd = new FormData(ajaxEditForm);
    let url = $("#ajaxEditForm").attr('action');
    let method = $("#ajaxEditForm").attr('method');

    if ($("#ajaxEditForm .summernote").length > 0) {
      $("#ajaxEditForm .summernote").each(function (i) {
        let index = i;
        let $toInput = $('.summernote').eq(index);
        let tmcId = $toInput.attr('id');
        let content = tinyMCE.get(tmcId).getContent();
        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
      })
    }

    $.ajax({
      url: url,
      method: method,
      data: fd,
      contentType: false,
      processData: false,
      success: function (data) {
        $('.request-loader').removeClass('show');
        $(e.target).attr('disabled', false);

        $('.em').each(function () {
          $(this).html('');
        });

        if (data.status == 'success') {
          location.reload();
        }
      },
      error: function (error) {
        $('.em').each(function () {
          $(this).html('');
        });

        if (error.responseJSON && error.responseJSON.errors) {
          for (let x in error.responseJSON.errors) {
            const errorElement = document.getElementById('editErr_' + x);
            if (errorElement) {
              errorElement.innerHTML = error.responseJSON.errors[x][0];
            }
          }
        }

        $('.request-loader').removeClass('show');
        $(e.target).attr('disabled', false);
      }
    });
  });
  // Form Update with AJAX Request End

  // Form Update with AJAX Request Start
  $("#updateBtn2").on('click', function (e) {
    $(".request-loader").addClass("show");

    if ($(".edit-iconpicker-component").length > 0) {
      $("#editInputIcon").val($(".edit-iconpicker-component").find('i').attr('class'));
    }

    let ajaxEditForm2 = document.getElementById('ajaxEditForm2');
    let fd = new FormData(ajaxEditForm2);
    let url = $("#ajaxEditForm2").attr('action');
    let method = $("#ajaxEditForm2").attr('method');

    if ($("#ajaxEditForm2 .summernote2").length > 0) {
      $("#ajaxEditForm2 .summernote2").each(function (i) {
        let index = i;
        let $toInput = $('.summernote2').eq(index);
        let tmcId = $toInput.attr('id');
        let content = tinyMCE.get(tmcId).getContent();
        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
      })
    }

    $.ajax({
      url: url,
      method: method,
      data: fd,
      contentType: false,
      processData: false,
      success: function (data) {
        $('.request-loader').removeClass('show');
        $(e.target).attr('disabled', false);

        $('.em').each(function () {
          $(this).html('');
        });

        if (data.status == 'success') {
          location.reload();
        }
      },
      error: function (error) {
        $('.em').each(function () {
          $(this).html('');
        });

        for (let x in error.responseJSON.errors) {
          document.getElementById('editErr_' + x).innerHTML = error.responseJSON.errors[x][0];
        }

        $('.request-loader').removeClass('show');
        $(e.target).attr('disabled', false);
      }
    });
  });
  // Form Update with AJAX Request End





  // Delete Using AJAX Request Start
  $('.deleteBtn').on('click', function (e) {
    e.preventDefault();
    $(".request-loader").addClass("show");

    swal({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      type: 'warning',
      buttons: {
        confirm: {
          text: 'Yes, delete it',
          className: 'btn btn-success'
        },
        cancel: {
          visible: true,
          className: 'btn btn-danger'
        }
      }
    }).then((Delete) => {
      if (Delete) {
        $(this).parent(".deleteForm").submit();
      } else {
        swal.close();
        $(".request-loader").removeClass("show");
      }
    });
  });
  // Delete Using AJAX Request End


  // completed btn onchange event
  $('.completeBtn').on('change', function (e) {
    e.preventDefault();
    $(".request-loader").addClass("show");

    swal({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      type: 'warning',
      buttons: {
        confirm: {
          text: 'Yes, Mark it as complete',
          className: 'btn btn-success'
        },
        cancel: {
          visible: true,
          className: 'btn btn-danger'
        }
      }
    }).then((Delete) => {
      if (Delete) {
        $(this).parent(".completeForm").submit();
      } else {
        swal.close();
        $(".request-loader").removeClass("show");
      }
    });
  });
  // completed btn onchange event end


  // Bulk Delete Using AJAX Request Start
  $(".bulk-check").on('change', function () {
    let val = $(this).data('val');
    let checked = $(this).prop('checked');

    // if selected checkbox is 'all' then check all the checkboxes
    if (val == 'all') {
      if (checked) {
        $(".bulk-check").each(function () {
          $(this).prop('checked', true);
        });
      } else {
        $(".bulk-check").each(function () {
          $(this).prop('checked', false);
        });
      }
    }

    // if any checkbox is checked then flag = 1, otherwise flag = 0
    let flag = 0;

    $(".bulk-check").each(function () {
      let status = $(this).prop('checked');

      if (status) {
        flag = 1;
      }
    });

    // if any checkbox is checked then show the delete button
    if (flag == 1) {
      $(".bulk-delete").addClass('d-inline-block');
      $(".bulk-delete").removeClass('d-none');
    } else {
      // if no checkbox is checked then hide the delete button
      $(".bulk-delete").removeClass('d-inline-block');
      $(".bulk-delete").addClass('d-none');
    }
  });

  $('.bulk-delete').on('click', function () {
    swal({
      title: 'Are you sure?',
      text: "You won't be able to revert this",
      type: 'warning',
      buttons: {
        confirm: {
          text: 'Yes, delete it',
          className: 'btn btn-success'
        },
        cancel: {
          visible: true,
          className: 'btn btn-danger'
        }
      }
    }).then((Delete) => {
      if (Delete) {
        $(".request-loader").addClass('show');
        let href = $(this).data('href');
        let ids = [];

        // take ids of checked one's
        $(".bulk-check:checked").each(function () {
          if ($(this).data('val') != 'all') {
            ids.push($(this).data('val'));
          }
        });

        let fd = new FormData();
        for (let i = 0; i < ids.length; i++) {
          fd.append('ids[]', ids[i]);
        }
        // Add CSRF token
        fd.append('_token', $('meta[name="csrf-token"]').attr('content'));

        $.ajax({
          url: href,
          method: 'POST',
          data: fd,
          contentType: false,
          processData: false,
          beforeSend: function() {
            console.log('AJAX request starting...');
            console.log('URL:', href);
            console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));
          },
          success: function (data) {
            console.log('AJAX success:', data);
            $(".request-loader").removeClass('show');

            if (data.status == "success") {
              location.reload();
            } else if (data.status == "partial") {
              // Just reload the page to show the session flash message
              location.reload();
            }
          },
          error: function (xhr, status, error) {
            console.log('AJAX error:', xhr, status, error);
            console.log('Response Text:', xhr.responseText);
            console.log('Status Code:', xhr.status);
            $(".request-loader").removeClass('show');
            
            // Handle error response
            let errorMessage = 'An error occurred while deleting orders.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }
            
            swal({
              title: 'Error',
              text: errorMessage,
              icon: 'error', // use icon instead of type
              buttons: {
                confirm: {
                  text: 'OK',
                  className: 'btn btn-danger'
                }
              }
            });
          }
        });
      } else {
        swal.close();
      }
    });
  });
  // Bulk Delete Using AJAX Request End


  // DataTable Start
  if (typeof $.fn.DataTable !== 'undefined' && $('#basic-datatables').length > 0) {
    $('#basic-datatables').DataTable({
      ordering: false,
      responsive: true
    });
  }
  // DataTable End


  // Uploaded Image Preview Start
  $('.img-input').on('change', function (event) {
    let file = event.target.files[0];
    let reader = new FileReader();

    reader.onload = function (e) {
      $('.uploaded-img').attr('src', e.target.result);
    };

    reader.readAsDataURL(file);
  });
  // Uploaded Image Preview End


  // Uploaded Background Image Preview Start
  $('.background-img-input').on('change', function (event) {
    let file = event.target.files[0];
    let reader = new FileReader();

    reader.onload = function (e) {
      $('.uploaded-background-img').attr('src', e.target.result);
    };

    reader.readAsDataURL(file);
  });
  // Uploaded Background Image Preview End


  // Change Input Direction Start
  $('select[name="language_id"]').change(function() {
    var langId = $(this).val();
    var isAdmin = window.location.pathname.startsWith('/admin');
    var $form = $(this).closest('form');
    var $loader = $form.find('.rtl-loader');
    $loader.show();

    if (isAdmin) {
      // Only admins have this endpoint
      $.get('/language-management/' + langId + '/check-rtl', function(data) {
        $loader.hide();
        if (data == 1) {
          $form.find('input, textarea').addClass('rtl text-right');
        } else {
          $form.find('input, textarea').removeClass('rtl text-right');
        }
      }).fail(function() {
        $loader.hide();
        $form.find('input, textarea').removeClass('rtl text-right');
      });
    } else {
      // Seller: skip AJAX, just set direction based on language code (optional: add logic for Arabic)
      $loader.hide();
      if ($(this).find('option:selected').text().trim().match(/عرب|arabic|ar/i)) {
        $form.find('input, textarea').addClass('rtl text-right');
      } else {
        $form.find('input, textarea').removeClass('rtl text-right');
      }
    }
  });
  // Change Input Direction End
});

function cloneInput(fromId, toId, event) {
  let $target = $(event.target);

  if ($target.is(':checked')) {
    $('#' + fromId + ' .form-control').each(function (i) {
      let index = i;
      let val = $(this).val();
      let $toInput = $('#' + toId + ' .form-control').eq(index);

      if ($(this).hasClass('summernote')) {
        let val = tinyMCE.activeEditor.getContent();
        let tmcId = $toInput.attr('id');
        tinyMCE.get(tmcId).setContent(val);
      } else if ($(this).data('role') == 'tagsinput') {
        if (val.length > 0) {
          let tags = val.split(',');
          tags.forEach(tag => {
            $toInput.tagsinput('add', tag);
          });
        } else {
          $toInput.tagsinput('removeAll');
        }
      } else {
        $toInput.val(val);
      }
    });
  } else {
    $('#' + toId + ' .form-control').each(function (i) {
      let $toInput = $('#' + toId + ' .form-control').eq(i);

      if ($(this).hasClass('summernote')) {
        let tmcId = $toInput.attr('id');
        tinyMCE.get(tmcId).setContent('');
      } else if ($(this).data('role') == 'tagsinput') {
        $toInput.tagsinput('removeAll');
      } else {
        $toInput.val('');
      }
    });
  }
}
function loaders() {
  $(function ($) {
    $(".request-loader").addClass("show");
  })
}

$(document).ready(function () {
  $("body").on('click', '#seller_admin_approval', function () {
    if ($('#seller_admin_approval').is(":checked")) {
      $('.admin_approval_notice').removeClass('d-none');
    } else {
      $('.admin_approval_notice').addClass('d-none');
    }
  });
})
$("[name='qr_builder_status']").on('change', function () {
  var val = $(this).val();
  if (val == 0) {
    $('#qr_code_save_limit').addClass('d-none');
  } else {
    $('#qr_code_save_limit').removeClass('d-none');
  }
});

// withdraw payment status
$('.withdrawStatusBtn').on('click', function (e) {
  e.preventDefault();
  $(".request-loader").addClass("show");

  swal({
    title: 'Are you sure?',
    text: "You won't be able to revert this!",
    type: 'warning',
    buttons: {
      confirm: {
        text: 'Yes',
        className: 'btn btn-success'
      },
      cancel: {
        visible: true,
        className: 'btn btn-danger'
      }
    }
  }).then((Delete) => {
    if (Delete) {
      var url = $(this).attr('href');
      window.location.href = url;
    } else {
      swal.close();
      $(".request-loader").removeClass("show");
    }
  });
});
// withdraw payment status end
