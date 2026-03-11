<div class="min-h-screen flex items-center justify-center bg-indigo-600 p-xl">
    <div class="card bg-neutral-50 rounded-xl p-xl w-full max-w-md">
        <div class="text-center mb-xl">
            <div class="text-5xl mb-md">⚡</div>
            <h1 class="text-2xl font-bold mb-sm text-indigo-600">Create Account</h1>
            <p class="text-neutral-600">Join VelocityPhp today</p>
        </div>
        
        <form id="registerForm">
            <div class="mb-lg">
                <label class="label mb-xs">Full Name</label>
                <input type="text" name="name" class="input input-md" required placeholder="John Doe">
            </div>
            
            <div class="mb-lg">
                <label class="label mb-xs">Email</label>
                <input type="email" name="email" class="input input-md" required placeholder="your@email.com">
            </div>
            
            <div class="mb-lg">
                <label class="label mb-xs">Password</label>
                <input type="password" name="password" class="input input-md" required placeholder="••••••••">
                <p class="text-xs text-neutral-500 mt-xs">Minimum 8 characters</p>
            </div>
            
            <div class="mb-lg">
                <label class="label mb-xs">Confirm Password</label>
                <input type="password" name="password_confirmation" class="input input-md" required placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn btn-primary btn-md w-full">
                Create Account
            </button>
            
            <div class="alert alert-destructive mt-md hidden" id="register-alert"></div>
        </form>
        
        <div class="text-center mt-xl text-neutral-600">
            Already have an account? <a href="/login" class="text-indigo-600 font-semibold no-underline hover:underline">Login</a>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#registerForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const $alert = $('#register-alert');
        
        $btn.prop('disabled', true).text('Creating...');
        $alert.addClass('hidden').removeClass('block');
        
        $.ajax({
            url: '/api/auth/register',
            method: 'POST',
            data: $form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $alert.removeClass('alert-destructive hidden').addClass('alert-success block')
                          .text(response.message);
                    
                    setTimeout(() => {
                        window.location.href = response.data.redirect;
                    }, 1000);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                let errorMsg = response.message || 'Registration failed';
                
                if (response.errors) {
                    errorMsg += '<ul class="mt-xs">';
                    Object.values(response.errors).forEach(errors => {
                        errors.forEach(error => {
                            errorMsg += '<li>' + error + '</li>';
                        });
                    });
                    errorMsg += '</ul>';
                }
                
                $alert.html(errorMsg).removeClass('hidden').addClass('block');
                $btn.prop('disabled', false).text('Create Account');
            }
        });
    });
});
</script>
