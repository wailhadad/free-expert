<div class="modal fade" id="customerOfferModal" tabindex="-1" aria-labelledby="customerOfferModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="customerOfferModalLabel">
          <i class="fas fa-gift text-success me-2"></i>Create Customer Offer
        </h5>
        <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="customer-offer-form">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group mb-3">
                <label for="offer-title" class="form-label fw-bold">Offer Title *</label>
                <input type="text" class="form-control" id="offer-title" name="title" required placeholder="Enter offer title">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group mb-3">
                <label for="offer-price" class="form-label fw-bold">Price *</label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="number" class="form-control" id="offer-price" name="price" step="1" min="0" required placeholder="0.00">
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group mb-3">
                <label for="offer-delivery-time" class="form-label fw-bold">Delivery Time (days) *</label>
                <input type="number" class="form-control" id="offer-delivery-time" name="delivery_time" min="1" required placeholder="Enter delivery time in days">
              </div>
            </div>
          </div>
          
          <div class="form-group mb-3">
            <label for="offer-description" class="form-label fw-bold">Description *</label>
            <textarea class="form-control" id="offer-description" name="description" rows="4" required placeholder="Describe your offer..."></textarea>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-group mb-3">
                <label for="offer-form" class="form-label fw-bold">Attach Form (Optional)</label>
                <select class="form-control" id="offer-form" name="form_id">
                  <option value="">No form attached</option>
                </select>
                <small class="form-text text-muted">Select a form if you need additional information from the customer</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group mb-3">
                <label for="offer-expires" class="form-label fw-bold">Expires At (Optional)</label>
                <input type="datetime-local" class="form-control" id="offer-expires" name="expires_at">
                <small class="form-text text-muted">Leave empty for no expiration</small>
              </div>
            </div>
          </div>
          
          <!-- Form Preview Section -->
          <div id="form-preview-section" class="d-none">
            <hr>
            <h6 class="fw-bold mb-3">
              <i class="fas fa-eye text-info me-2"></i>Form Preview
            </h6>
            <div id="form-preview-content" class="border rounded p-3 bg-light">
              <!-- Form fields will be displayed here -->
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="create-offer-btn">
          <i class="fas fa-gift me-2"></i>Create Offer
        </button>
      </div>
    </div>
  </div>
</div> 