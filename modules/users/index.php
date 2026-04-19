<section class="module-screen" data-module="users">
    <div class="module-toolbar">
        <div>
            <h3>Users</h3>
            <p>Manage system accounts and role permissions for admins and staff.</p>
        </div>
        <div class="toolbar-actions">
            <button class="btn btn-primary" type="button" data-open-modal="userModal">
                <i data-lucide="plus"></i>
                Add User
            </button>
        </div>
    </div>

    <section class="panel">
        <div class="panel-head">
            <h4>User Accounts</h4>
            <span>Password hashes are stored securely with bcrypt</span>
        </div>
        <div class="table-wrap">
            <table class="data-table" id="usersTable">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr><td colspan="6" class="empty-cell">Loading users...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="modal" id="userModal" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-head">
                <h4 id="userModalTitle">Add User</h4>
                <button class="icon-btn" type="button" data-close-modal aria-label="Close modal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form id="userForm" class="stack-form" data-validate>
                <input type="hidden" id="userId" name="id">

                <label for="userName">Name</label>
                <input id="userName" name="name" type="text" placeholder="Employee Name" required>

                <label for="userEmail">Email</label>
                <input id="userEmail" name="email" type="email" placeholder="employee@store.local" required>

                <label for="userRole">Role</label>
                <select id="userRole" name="role" required>
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>

                <label for="userPassword">Password</label>
                <input id="userPassword" name="password" type="password" minlength="8" placeholder="Required for new users">
                <small class="field-help">Leave blank when editing if you do not want to change the password.</small>

                <div class="modal-actions">
                    <button class="btn btn-ghost" type="button" data-close-modal>Cancel</button>
                    <button class="btn btn-primary" type="submit">
                        <i data-lucide="save"></i>
                        Save User
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
