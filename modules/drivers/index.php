<section class="module-screen" data-module="drivers">
    <div class="module-toolbar">
        <div>
            <h3>Drivers</h3>
            <p>Manage hired drivers, license details, and current assignment status.</p>
        </div>
        <div class="toolbar-actions">
            <button class="btn btn-primary" type="button" data-open-modal="driverModal">
                <i data-lucide="plus"></i>
                Add Driver
            </button>
        </div>
    </div>

    <section class="panel">
        <div class="panel-head">
            <h4>Driver Directory</h4>
            <span>Track active and inactive driver records</span>
        </div>
        <div class="table-wrap">
            <table class="data-table" id="driversTable">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>License No.</th>
                    <th>Vehicle</th>
                    <th>Hired Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr><td colspan="8" class="empty-cell">Loading drivers...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="modal" id="driverModal" aria-hidden="true">
        <div class="modal-card modal-wide">
            <div class="modal-head">
                <h4 id="driverModalTitle">Add Driver</h4>
                <button class="icon-btn" type="button" data-close-modal aria-label="Close modal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form id="driverForm" class="stack-form" data-validate>
                <input type="hidden" id="driverId" name="id">

                <div class="form-grid two-col">
                    <div>
                        <label for="driverName">Full Name</label>
                        <input id="driverName" name="full_name" type="text" placeholder="Juan Dela Cruz" required>
                    </div>
                    <div>
                        <label for="driverPhone">Phone Number</label>
                        <input id="driverPhone" name="phone" type="text" placeholder="09171234567" required>
                    </div>
                </div>

                <div class="form-grid two-col">
                    <div>
                        <label for="driverLicense">License Number</label>
                        <input id="driverLicense" name="license_no" type="text" placeholder="N04-12-123456" required>
                    </div>
                    <div>
                        <label for="driverVehicle">Vehicle Assigned</label>
                        <input id="driverVehicle" name="vehicle_assigned" type="text" placeholder="Truck #2 / Toyota Hilux">
                    </div>
                </div>

                <div class="form-grid two-col">
                    <div>
                        <label for="driverHiredDate">Hired Date</label>
                        <input id="driverHiredDate" name="hired_date" type="date" required>
                    </div>
                    <div>
                        <label for="driverStatus">Status</label>
                        <select id="driverStatus" name="status" required>
                            <option value="active">Active</option>
                            <option value="on_leave">On Leave</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <label for="driverNotes">Notes</label>
                <textarea id="driverNotes" name="notes" rows="3" placeholder="Additional details about assignment, availability, etc."></textarea>

                <div class="modal-actions">
                    <button class="btn btn-ghost" type="button" data-close-modal>Cancel</button>
                    <button class="btn btn-primary" type="submit">
                        <i data-lucide="save"></i>
                        Save Driver
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
