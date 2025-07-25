<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Slider') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <form id="ajaxForm" class="modal-form create" action="{{ route('admin.home_page.store_slider') }}" method="post" enctype="multipart/form-data">
          @csrf
          <div class="form-group">
            <label for="">{{ __('Image') . '*' }}</label>
            <br>
            <div class="thumb-preview">
              <img src="{{ asset('assets/img/noimage.jpg') }}" alt="..." class="uploaded-img">
            </div>

            <div class="mt-3">
              <div role="button" class="btn btn-primary btn-sm upload-btn">
                {{ __('Choose Image') }}
                <input type="file" class="img-input" name="image">
              </div>
            </div>
            <p id="err_image" class="mt-2 mb-0 text-danger em"></p>
          </div>

          <div class="row no-gutters">
            <div class="col-md-6">
              <div class="form-group">
                <label for="">{{ __('Language') . '*' }}</label>
                <select name="language_id" class="form-control">
                  <option selected disabled>{{ __('Select a Language') }}</option>
                  @foreach ($langs as $lang)
                    @if ($lang->code !== 'ar')
                    <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                    @endif
                  @endforeach
                </select>
                <p id="err_language_id" class="mt-2 mb-0 text-danger em"></p>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label for="">{{ __('Title') }}</label>
                <input type="text" class="form-control" name="title" placeholder="Enter Title">
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="">{{ __('Text') }}</label>
            <textarea class="form-control" name="text" rows="5" placeholder="Enter Text"></textarea>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
          {{ __('Close') }}
        </button>
        <button id="submitBtn" type="button" class="btn btn-primary btn-sm">
          {{ __('Save') }}
        </button>
      </div>
    </div>
  </div>
</div>
