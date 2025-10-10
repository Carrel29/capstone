<div class="modal fade" id="drinkModal" tabindex="-1" aria-labelledby="drinkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="drinkModalLabel">Customize Your Drink</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="drinkCustomizationForm">
                    <input type="hidden" id="drinkId" name="drinkId">
                    <input type="hidden" id="drinkName" name="drinkName">
                    <input type="hidden" id="basePrice" name="basePrice">
                    
                    <!-- Size Selection -->
                    <div class="mb-3">
                        <label class="form-label">Size</label>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="size" id="hotSize" value="hot">
                            <label class="btn btn-outline-primary" for="hotSize">Hot</label>
                            
                            <input type="radio" class="btn-check" name="size" id="mediumSize" value="medium" checked>
                            <label class="btn btn-outline-primary" for="mediumSize">Medium</label>
                            
                            <input type="radio" class="btn-check" name="size" id="largeSize" value="large">
                            <label class="btn btn-outline-primary" for="largeSize">Large</label>
                        </div>
                    </div>
                    
                    <!-- Sugar Level -->
                    <div class="mb-3">
                        <label class="form-label">Sugar Level</label>
                        <select class="form-select" name="sugarLevel" id="sugarLevel">
                            <option value="100">100% Sugar</option>
                            <option value="75">75% Sugar</option>
                            <option value="50">50% Sugar</option>
                            <option value="25">25% Sugar</option>
                            <option value="0">No Sugar</option>
                        </select>
                    </div>
                    
                    <!-- Add-ons -->
                    <div class="mb-3">
                        <label class="form-label">Add-ons</label>
                        <div id="addonsContainer">
                            <!-- Add-ons will be populated dynamically -->
                        </div>
                    </div>

                    <!-- Total Price -->
                    <div class="mb-3">
                        <h5>Total Price: ₱<span id="totalPrice">0.00</span></h5>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="addToCartBtn">Add to Cart</button>
            </div>
        </div>
    </div>
</div>