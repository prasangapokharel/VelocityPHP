<div class="container">
    <div class="mb-xl flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-sm">Users</h1>
            <p class="text-neutral-600">Manage all registered users</p>
        </div>
        <button class="btn btn-primary" id="btn-create-user">+ New User</button>
    </div>

    <!-- Alert area -->
    <div id="users-alert" class="hidden mb-lg p-md rounded" role="alert"></div>

    <!-- Users table card -->
    <div class="card">
        <div class="card-content" style="overflow-x:auto">
            <table class="w-full text-sm" id="users-table">
                <thead>
                    <tr class="border-b border-neutral-200">
                        <th class="text-left py-sm px-md font-semibold text-neutral-700">ID</th>
                        <th class="text-left py-sm px-md font-semibold text-neutral-700">Name</th>
                        <th class="text-left py-sm px-md font-semibold text-neutral-700">Email</th>
                        <th class="text-left py-sm px-md font-semibold text-neutral-700">Role</th>
                        <th class="text-left py-sm px-md font-semibold text-neutral-700">Status</th>
                        <th class="text-left py-sm px-md font-semibold text-neutral-700">Actions</th>
                    </tr>
                </thead>
                <tbody id="users-tbody">
                    <tr>
                        <td colspan="6" class="py-xl text-center text-neutral-500">Loading users...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== Create / Edit Modal ===== -->
<div id="user-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,.45)">
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-md mx-4 p-xl">
        <h2 class="text-xl font-bold mb-lg" id="modal-title">Create User</h2>
        <form id="user-form" novalidate>
            <input type="hidden" id="user-id" value="">

            <div class="mb-md">
                <label class="block text-sm font-medium mb-xs text-neutral-700" for="user-name">Name</label>
                <input type="text" id="user-name" name="name" class="input w-full" placeholder="Full name" autocomplete="off">
                <p class="text-xs text-red-600 mt-xs hidden" id="err-name"></p>
            </div>

            <div class="mb-md">
                <label class="block text-sm font-medium mb-xs text-neutral-700" for="user-email">Email</label>
                <input type="email" id="user-email" name="email" class="input w-full" placeholder="user@example.com" autocomplete="off">
                <p class="text-xs text-red-600 mt-xs hidden" id="err-email"></p>
            </div>

            <div class="mb-lg" id="role-field">
                <label class="block text-sm font-medium mb-xs text-neutral-700" for="user-role">Role</label>
                <select id="user-role" name="role" class="input w-full">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                    <option value="moderator">Moderator</option>
                </select>
                <p class="text-xs text-red-600 mt-xs hidden" id="err-role"></p>
            </div>

            <div class="flex gap-sm justify-end">
                <button type="button" class="btn btn-outline" id="btn-modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary" id="btn-modal-save">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== Confirm Delete Modal ===== -->
<div id="delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,.45)">
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-sm mx-4 p-xl text-center">
        <h2 class="text-xl font-bold mb-md">Delete User</h2>
        <p class="text-neutral-600 mb-lg">Are you sure you want to delete <strong id="delete-user-name"></strong>? This cannot be undone.</p>
        <div class="flex gap-sm justify-center">
            <button type="button" class="btn btn-outline" id="btn-delete-cancel">Cancel</button>
            <button type="button" class="btn btn-danger" id="btn-delete-confirm">Delete</button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var csrfToken = window.CSRF_TOKEN || '';
    var deleteTargetId = null;

    // ── Helpers ──────────────────────────────────────────────────────────────

    function showAlert(msg, type) {
        var el = document.getElementById('users-alert');
        el.textContent = msg;
        el.className = 'mb-lg p-md rounded ' + (type === 'success'
            ? 'bg-green-100 text-green-800'
            : 'bg-red-100 text-red-800');
        el.classList.remove('hidden');
        setTimeout(function () { el.classList.add('hidden'); }, 4000);
    }

    function clearErrors() {
        ['err-name', 'err-email', 'err-role'].forEach(function (id) {
            var el = document.getElementById(id);
            el.textContent = '';
            el.classList.add('hidden');
        });
    }

    function showErrors(errors) {
        var map = { name: 'err-name', email: 'err-email', role: 'err-role' };
        Object.keys(errors).forEach(function (field) {
            var el = document.getElementById(map[field]);
            if (el) {
                el.textContent = (errors[field] || []).join(', ');
                el.classList.remove('hidden');
            }
        });
    }

    function ajax(method, url, data, done) {
        var xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.onload = function () {
            var res = {};
            try { res = JSON.parse(xhr.responseText); } catch (e) {}
            done(null, res, xhr.status);
        };
        xhr.onerror = function () { done(new Error('Network error')); };
        xhr.send(data ? JSON.stringify(data) : null);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    function renderTable(users) {
        var tbody = document.getElementById('users-tbody');
        if (!users || users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="py-xl text-center text-neutral-500">No users found.</td></tr>';
            return;
        }
        tbody.innerHTML = users.map(function (u) {
            return '<tr class="border-b border-neutral-100 hover:bg-neutral-50 dark:hover:bg-gray-800">'
                + '<td class="py-sm px-md text-neutral-500">' + escHtml(String(u.id)) + '</td>'
                + '<td class="py-sm px-md font-medium">' + escHtml(u.name || '') + '</td>'
                + '<td class="py-sm px-md text-neutral-600">' + escHtml(u.email || '') + '</td>'
                + '<td class="py-sm px-md"><span class="badge">' + escHtml(u.role || '') + '</span></td>'
                + '<td class="py-sm px-md"><span class="badge ' + (u.status === 'active' ? 'badge-success' : 'badge-neutral') + '">'
                +   escHtml(u.status || '') + '</span></td>'
                + '<td class="py-sm px-md flex gap-xs">'
                +   '<button class="btn btn-outline btn-sm btn-edit" data-id="' + u.id + '" data-name="' + escAttr(u.name) + '" data-email="' + escAttr(u.email) + '" data-role="' + escAttr(u.role) + '">Edit</button>'
                +   '<button class="btn btn-danger btn-sm btn-delete" data-id="' + u.id + '" data-name="' + escAttr(u.name) + '">Delete</button>'
                + '</td></tr>';
        }).join('');
        attachRowHandlers();
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function escAttr(s) { return escHtml(s || ''); }

    function loadUsers() {
        ajax('GET', '/api/users', null, function (err, res) {
            if (err || !res.success) {
                document.getElementById('users-tbody').innerHTML =
                    '<tr><td colspan="6" class="py-xl text-center text-red-500">'
                    + escHtml((res && res.message) || 'Failed to load users') + '</td></tr>';
                return;
            }
            renderTable(res.data || []);
        });
    }

    // ── Row action handlers ──────────────────────────────────────────────────

    function attachRowHandlers() {
        document.querySelectorAll('.btn-edit').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openEditModal(btn.dataset.id, btn.dataset.name, btn.dataset.email, btn.dataset.role);
            });
        });
        document.querySelectorAll('.btn-delete').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openDeleteModal(btn.dataset.id, btn.dataset.name);
            });
        });
    }

    // ── Create modal ─────────────────────────────────────────────────────────

    function openCreateModal() {
        clearErrors();
        document.getElementById('modal-title').textContent = 'Create User';
        document.getElementById('user-id').value = '';
        document.getElementById('user-name').value = '';
        document.getElementById('user-email').value = '';
        document.getElementById('user-role').value = 'user';
        document.getElementById('role-field').style.display = '';
        document.getElementById('user-modal').classList.remove('hidden');
        document.getElementById('user-name').focus();
    }

    function openEditModal(id, name, email, role) {
        clearErrors();
        document.getElementById('modal-title').textContent = 'Edit User';
        document.getElementById('user-id').value = id;
        document.getElementById('user-name').value = name;
        document.getElementById('user-email').value = email;
        document.getElementById('user-role').value = role || 'user';
        document.getElementById('role-field').style.display = 'none'; // role not editable after create
        document.getElementById('user-modal').classList.remove('hidden');
        document.getElementById('user-name').focus();
    }

    function closeUserModal() {
        document.getElementById('user-modal').classList.add('hidden');
    }

    // ── Delete modal ─────────────────────────────────────────────────────────

    function openDeleteModal(id, name) {
        deleteTargetId = id;
        document.getElementById('delete-user-name').textContent = name;
        document.getElementById('delete-modal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        deleteTargetId = null;
        document.getElementById('delete-modal').classList.add('hidden');
    }

    // ── Form submit ──────────────────────────────────────────────────────────

    document.getElementById('user-form').addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();

        var id     = document.getElementById('user-id').value;
        var isEdit = !!id;
        var payload = {
            name:  document.getElementById('user-name').value.trim(),
            email: document.getElementById('user-email').value.trim(),
            role:  document.getElementById('user-role').value
        };

        var url    = isEdit ? '/api/users/' + id : '/api/users';
        var method = isEdit ? 'PUT' : 'POST';

        document.getElementById('btn-modal-save').disabled = true;

        ajax(method, url, payload, function (err, res) {
            document.getElementById('btn-modal-save').disabled = false;
            if (err) { showAlert('Network error. Please try again.', 'error'); return; }
            if (!res.success) {
                if (res.errors) showErrors(res.errors);
                else showAlert(res.message || 'Operation failed.', 'error');
                return;
            }
            closeUserModal();
            showAlert(res.message || 'Done.', 'success');
            loadUsers();
        });
    });

    // ── Delete confirm ────────────────────────────────────────────────────────

    document.getElementById('btn-delete-confirm').addEventListener('click', function () {
        if (!deleteTargetId) return;
        var id = deleteTargetId;
        closeDeleteModal();
        ajax('DELETE', '/api/users/' + id, null, function (err, res) {
            if (err || !res.success) {
                showAlert((res && res.message) || 'Delete failed.', 'error');
                return;
            }
            showAlert(res.message || 'User deleted.', 'success');
            loadUsers();
        });
    });

    // ── Wire up buttons ───────────────────────────────────────────────────────

    document.getElementById('btn-create-user').addEventListener('click', openCreateModal);
    document.getElementById('btn-modal-cancel').addEventListener('click', closeUserModal);
    document.getElementById('btn-delete-cancel').addEventListener('click', closeDeleteModal);

    // Close modals on backdrop click
    document.getElementById('user-modal').addEventListener('click', function (e) {
        if (e.target === this) closeUserModal();
    });
    document.getElementById('delete-modal').addEventListener('click', function (e) {
        if (e.target === this) closeDeleteModal();
    });

    // ── Bootstrap ─────────────────────────────────────────────────────────────
    loadUsers();
}());
</script>
