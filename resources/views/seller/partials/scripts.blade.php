<script>
  'use strict';
  let demo_mode = "{{ env('DEMO_MODE') }}";
</script>

{{-- core js files --}}
<script type="text/javascript" src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
<!-- Bootstrap 5 bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

{{-- jQuery ui --}}
<script type="text/javascript" src="{{ asset('assets/js/jquery-ui.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/jquery.ui.touch-punch.min.js') }}"></script>

{{-- jQuery timepicker --}}
<script type="text/javascript" src="{{ asset('assets/js/jquery.timepicker.min.js') }}"></script>

{{-- jQuery scrollbar --}}
<script type="text/javascript" src="{{ asset('assets/js/jquery.scrollbar.min.js') }}"></script>

{{-- bootstrap notify --}}
<script type="text/javascript" src="{{ asset('assets/js/bootstrap-notify.min.js') }}"></script>

{{-- sweet alert --}}
<script type="text/javascript" src="{{ asset('assets/js/sweet-alert.min.js') }}"></script>

{{-- bootstrap tags input --}}
<script type="text/javascript" src="{{ asset('assets/js/bootstrap-tagsinput.min.js') }}"></script>

{{-- bootstrap date-picker --}}
<script type="text/javascript" src="{{ asset('assets/js/bootstrap-datepicker.min.js') }}"></script>

{{-- tinymce js --}}
<script type="text/javascript" src="{{ asset('assets/js/tinymce/js/tinymce/tinymce.min.js') }}"></script>

{{-- js color --}}
<script type="text/javascript" src="{{ asset('assets/js/jscolor.min.js') }}"></script>

{{-- fontawesome icon picker js --}}
<script type="text/javascript" src="{{ asset('assets/js/fontawesome-iconpicker.min.js') }}"></script>

<!-- DataTables Bootstrap 5 integration -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" />
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

{{-- dropzone js --}}
<script type="text/javascript" src="{{ asset('assets/js/dropzone.min.js') }}"></script>

{{-- atlantis js --}}
<script type="text/javascript" src="{{ asset('assets/js/atlantis.js') }}"></script>

{{-- select2 js --}}
<script type="text/javascript" src="{{ asset('assets/js/select2.min.js') }}"></script>

{{-- setup csrf-token for ajax request --}}
<script>
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
</script>

{{-- fonts and icons script --}}
<script type="text/javascript" src="{{ asset('assets/js/webfont.min.js') }}"></script>

<script>
  WebFont.load({
    google: {
      "families": ["Lato:300,400,700,900"]
    },
    custom: {
      "families": ["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands",
        "simple-line-icons"
      ],
      urls: ['{{ asset('assets/css/fonts.min.css') }}']
    },
    active: function() {
      sessionStorage.fonts = true;
    }
  });
</script>

@if (session()->has('success'))
  <script>
    var content = {};

    content.message = '{{ session('success') }}';
    content.title = 'Success';
    content.icon = 'fas fa-check-circle';

    $.notify(content, {
      type: 'success',
      placement: {
        from: 'top',
        align: 'right'
      },
      showProgressbar: true,
      time: 1000,
      delay: 4000
    });
  </script>
@endif

@if (session()->has('warning'))
  <script>
    var content = {};

    content.message = '{{ session('warning') }}';
    content.title = 'Warning';
    content.icon = 'fas fa-exclamation-circle';

    $.notify(content, {
      type: 'warning',
      placement: {
        from: 'top',
        align: 'right'
      },
      showProgressbar: true,
      time: 1000,
      delay: 4000
    });
  </script>
@endif

@if (session()->has('error'))
  <script>
    var content = {};

    content.message = '{{ session('error') }}';
    content.title = 'Error';
    content.icon = 'fas fa-times-circle';

    $.notify(content, {
      type: 'danger',
      placement: {
        from: 'top',
        align: 'right'
      },
      showProgressbar: true,
      time: 1000,
      delay: 4000
    });
  </script>
@endif

<script>
  var account_status = {{ Auth::guard('seller')->user()->status }};
  var baseUrl = "{{ route('index') }}";
</script>
@if (session()->has('secret_login'))
  <script>
    var secret_login = {{ Session::get('secret_login') }};
  </script>
@else
  <script>
    var secret_login = 0;
  </script>
@endif

{{-- admin-main js --}}
<script>
// Prevent DataTable and RealTimeNotifications errors from breaking modal functionality
window.addEventListener('error', function(event) {
    if (event.error && event.error.message && (
        event.error.message.includes('DataTable') ||
        event.error.message.includes('Cannot read properties of undefined') ||
        event.error.message.includes('defaults') ||
        event.error.message.includes('RealTimeNotifications') ||
        event.error.message.includes('RealtimeNotifications') ||
        event.error.message.includes('has already been declared')
    )) {
        console.warn('Error prevented:', event.error.message);
        event.preventDefault();
        return false;
    }
});
</script>
<script type="text/javascript" src="{{ asset('assets/js/admin-main.js') }}"></script>
