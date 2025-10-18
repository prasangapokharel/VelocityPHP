<div class="container">
    <div class="page-header">
        <h1>Users</h1>
        <p>Manage all users in the system</p>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span>User List</span>
                <button class="btn btn-primary" onclick="openUserModal()">Add New User</button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                    <tr>
                        <td>1</td>
                        <td>John Doe</td>
                        <td>john@example.com</td>
                        <td><span class="badge badge-primary">Admin</span></td>
                        <td>
                            <a href="/users/1" class="btn-link">View</a>
                            <button class="btn-link" onclick="editUser(1)">Edit</button>
                            <button class="btn-link text-danger" onclick="deleteUser(1)">Delete</button>
                        </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Jane Smith</td>
                        <td>jane@example.com</td>
                        <td><span class="badge badge-secondary">User</span></td>
                        <td>
                            <a href="/users/2" class="btn-link">View</a>
                            <button class="btn-link" onclick="editUser(2)">Edit</button>
                            <button class="btn-link text-danger" onclick="deleteUser(2)">Delete</button>
                        </td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Bob Johnson</td>
                        <td>bob@example.com</td>
                        <td><span class="badge badge-secondary">User</span></td>
                        <td>
                            <a href="/users/3" class="btn-link">View</a>
                            <button class="btn-link" onclick="editUser(3)">Edit</button>
                            <button class="btn-link text-danger" onclick="deleteUser(3)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Modal (Example of dynamic content) -->
<div id="user-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New User</h3>
            <button class="modal-close" onclick="closeUserModal()">&times;</button>
        </div>
        
        <form id="user-form" data-ajax>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save User</button>
            </div>
        </form>
    </div>
</div>

<style>
.table-responsive {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table thead {
    background: #f8fafc;
}

.table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid var(--border-color);
}

.table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.table tbody tr:hover {
    background: #f8fafc;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 500;
}

.badge-primary {
    background: #dbeafe;
    color: #1e40af;
}

.badge-secondary {
    background: #f1f5f9;
    color: #475569;
}

.btn-link {
    background: none;
    border: none;
    color: var(--primary-color);
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    text-decoration: none;
}

.btn-link:hover {
    text-decoration: underline;
}

.text-danger {
    color: var(--error-color);
}

/* Modal Styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-content {
    background: white;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 2rem;
    cursor: pointer;
    color: var(--secondary-color);
    padding: 0;
    width: 32px;
    height: 32px;
}

.modal-content form {
    padding: 1.5rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}
</style>

<script>
function openUserModal() {
    $('#user-modal').fadeIn(200);
}

function closeUserModal() {
    $('#user-modal').fadeOut(200);
    $('#user-form')[0].reset();
}

function editUser(id) {
    NativeApp.showSuccess('Edit user ' + id + ' - This would open an edit modal');
}

function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user?')) {
        console.log('Attempting to delete user:', id);
        
        // Example AJAX delete
        NativeApp.api.delete('/api/users/' + id)
            .done(function(response) {
                console.log('Delete response:', response);
                
                if (response.success) {
                    NativeApp.showSuccess(response.message || 'User deleted successfully');
                    // Refresh the page to update user list
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    NativeApp.showError(response.message || 'Failed to delete user');
                }
            })
            .fail(function(jqxhr, textStatus, error) {
                console.error('Delete failed:', {
                    status: jqxhr.status,
                    statusText: jqxhr.statusText,
                    responseText: jqxhr.responseText,
                    error: error
                });
                
                let errorMsg = 'Failed to delete user';
                
                // Try to parse error response
                if (jqxhr.responseJSON) {
                    errorMsg = jqxhr.responseJSON.message || jqxhr.responseJSON.error || errorMsg;
                } else if (jqxhr.status === 404) {
                    errorMsg = 'Delete endpoint not found. Route: /api/users/' + id;
                } else if (jqxhr.status === 500) {
                    errorMsg = 'Server error occurred';
                } else if (jqxhr.status === 0) {
                    errorMsg = 'Network error - Cannot connect to server';
                }
                
                NativeApp.showError(errorMsg);
                
                // Show detailed error in console
                console.group('❌ Delete User Error Details');
                console.log('User ID:', id);
                console.log('URL:', '/api/users/' + id);
                console.log('Status:', jqxhr.status);
                console.log('Status Text:', jqxhr.statusText);
                console.log('Response:', jqxhr.responseText);
                console.log('Error:', error);
                console.groupEnd();
            });
    }
}

// Handle form submission
$(document).on('submit', '#user-form', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    
    console.log('Submitting user form:', formData);
    
    NativeApp.api.post('/api/users', formData)
        .done(function(response) {
            console.log('Create user response:', response);
            
            if (response.success) {
                NativeApp.showSuccess(response.message || 'User created successfully');
                closeUserModal();
                // Refresh user list
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                NativeApp.showError(response.message || 'Failed to create user');
            }
        })
        .fail(function(jqxhr, textStatus, error) {
            console.error('Create user failed:', {
                status: jqxhr.status,
                response: jqxhr.responseText,
                error: error
            });
            
            let errorMsg = 'Failed to create user';
            
            if (jqxhr.responseJSON) {
                errorMsg = jqxhr.responseJSON.message || jqxhr.responseJSON.error || errorMsg;
                
                // Show validation errors if present
                if (jqxhr.responseJSON.errors) {
                    const errors = jqxhr.responseJSON.errors;
                    const errorList = Object.values(errors).flat().join('\n');
                    errorMsg += ':\n' + errorList;
                }
            } else if (jqxhr.status === 404) {
                errorMsg = 'Create endpoint not found. Route: /api/users';
            } else if (jqxhr.status === 500) {
                errorMsg = 'Server error occurred';
            }
            
            NativeApp.showError(errorMsg);
            
            console.group('❌ Create User Error');
            console.log('URL:', '/api/users');
            console.log('Status:', jqxhr.status);
            console.log('Response:', jqxhr.responseText);
            console.groupEnd();
        });
});

// Close modal on outside click
$(document).on('click', '.modal', function(e) {
    if (e.target === this) {
        closeUserModal();
    }
});
</script>
