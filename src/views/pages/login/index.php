<div class="min-h-screen flex items-center justify-center bg-indigo-600 p-xl">
    <div class="card bg-neutral-50 rounded-xl p-xl w-full max-w-md">
        <div class="text-center mb-xl">
            <div class="text-5xl mb-md">⚡</div>
            <h1 class="text-2xl font-bold mb-sm text-indigo-600">VelocityPhp</h1>
            <p class="text-neutral-600">Ultra-secure login</p>
        </div>
        
        <form id="loginForm">
            <div class="mb-lg">
                <label class="label mb-xs">Email</label>
                <input type="email" name="email" class="input input-md" required autocomplete="email" placeholder="your@email.com">
            </div>
            
            <div class="mb-lg">
                <label class="label mb-xs">Password</label>
                <input type="password" name="password" class="input input-md" required autocomplete="current-password" placeholder="••••••••">
            </div>
            
            <div class="flex justify-between items-center mb-lg">
                <label class="flex items-center gap-xs cursor-pointer">
                    <input type="checkbox" name="remember" class="checkbox">
                    <span class="text-sm text-neutral-600">Remember me (30 days)</span>
                </label>
                <a href="/forgot-password" class="text-sm text-indigo-600 no-underline hover:underline">Forgot password?</a>
            </div>
            
            <button type="submit" class="btn btn-primary btn-md w-full">
                Login
            </button>
            
            <div class="alert alert-destructive mt-md hidden" id="login-alert"></div>
        </form>
        
        <div class="text-center mt-xl text-neutral-600">
            Don't have an account? <a href="/register" class="text-indigo-600 font-semibold no-underline hover:underline">Register</a>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const $alert = $('#login-alert');
        
        $btn.prop('disabled', true).text('Logging in...');
        $alert.addClass('hidden').removeClass('block');
        
        $.ajax({
            url: '/api/auth/login',
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
                $alert.text(response.message || 'Login failed').removeClass('hidden').addClass('block');
                $btn.prop('disabled', false).text('Login');
            }
        });
    });
});
</script>
