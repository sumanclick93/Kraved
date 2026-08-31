<?php
use App\Core\Helpers;
$f = $fulfillment ?? [];
$zones = $deliveryZones ?? [];
$isDelivery = ($f['type'] ?? 'collection') === 'delivery';
$selectedPrefix = strtoupper((string) ($f['postcode'] ?? ''));
$selectedZoneId = (int) (($f['zone']['id'] ?? 0));
$collectionAddress = $collectionAddress ?? (string) Helpers::setting('collection_address', 'Unit 2, Old Biscuit Factory, Dockray Street, Colne, BB8 9HT');
?>
<div class="modal fade" id="fulfillmentModal" tabindex="-1" aria-labelledby="fulfillmentTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content fulfillment-modal">
      <div class="modal-header border-0">
        <h2 class="modal-title h4" id="fulfillmentTitle">How would you like your desserts?</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="toggle-pills mb-3" role="group">
          <button type="button" class="pill <?= !$isDelivery ? 'active' : '' ?>" data-ff-type="collection">Collection</button>
          <button type="button" class="pill <?= $isDelivery ? 'active' : '' ?>" data-ff-type="delivery">Delivery</button>
        </div>
        <div id="ff-delivery-fields" class="<?= $isDelivery ? '' : 'd-none' ?>">
          <label class="form-label" for="ff-zone">Delivery area</label>
          <select id="ff-zone" class="form-select mb-2" required>
            <option value="">Choose a postal area</option>
            <?php foreach ($zones as $z):
              $zid = (int) $z['id'];
              $prefix = strtoupper((string) $z['postcode_prefix']);
              $selected = $selectedZoneId === $zid || $selectedPrefix === $prefix;
            ?>
              <option value="<?= $zid ?>"
                data-prefix="<?= Helpers::e($prefix) ?>"
                data-min="<?= Helpers::e((string) $z['min_order_amount']) ?>"
                <?= $selected ? 'selected' : '' ?>>
                <?= Helpers::e(Helpers::zoneChoiceLabel($z)) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <p class="small opacity-75 mb-2">Delivery charge is calculated at checkout.</p>
          <?php if (!$zones): ?>
            <div class="small text-danger mb-2">No delivery areas have been added yet. Please choose collection.</div>
          <?php endif; ?>
          <div id="ff-zone-msg" class="small mb-3"></div>
        </div>
        <div id="ff-collection-note" class="small opacity-75 mb-3 <?= !$isDelivery ? '' : 'd-none' ?>">
          Collect from <?= Helpers::e($collectionAddress) ?>
        </div>
        <label class="form-label">When</label>
        <select id="ff-slot" class="form-select mb-3">
          <option value="ASAP" <?= ($f['time_slot'] ?? '') === 'ASAP' ? 'selected' : '' ?>>ASAP (~35 mins)</option>
          <option value="Today 17:00–17:30">Today 17:00–17:30</option>
          <option value="Today 18:00–18:30">Today 18:00–18:30</option>
          <option value="Today 19:00–19:30">Today 19:00–19:30</option>
          <option value="Today 20:00–20:30">Today 20:00–20:30</option>
        </select>
        <button type="button" class="btn btn-accent w-100" id="ff-save">Confirm</button>
      </div>
    </div>
  </div>
</div>
