<?php
$title = "429 - Too Many Requests";
$description = "You have exceeded the rate limit. Please wait before trying again.";
$retryAfter = $retryAfter ?? 60;
?>
<div class="min-h-[60vh] flex items-center justify-center px-lg">
    <div class="text-center max-w-md">
        <div class="mb-lg">
            <span class="text-8xl font-bold text-orange-500">429</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900 mb-md">Too Many Requests</h1>
        <p class="text-neutral-600 mb-lg">You've made too many requests in a short period. Please slow down and try again.</p>
        
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-lg mb-xl">
            <div class="flex items-center justify-center gap-sm text-orange-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium">Try again in <span id="countdown"><?= $retryAfter ?></span> seconds</span>
            </div>
        </div>
        
        <div class="flex gap-md justify-center">
            <a href="/" class="inline-flex items-center px-lg py-md bg-neutral-900 text-white rounded-lg hover:bg-neutral-800 transition-colors">
                <svg class="w-5 h-5 mr-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Go Home
            </a>
            <button onclick="location.reload()" class="inline-flex items-center px-lg py-md border border-neutral-300 text-neutral-700 rounded-lg hover:bg-neutral-50 transition-colors">
                <svg class="w-5 h-5 mr-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Retry
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    var seconds = <?= $retryAfter ?>;
    var countdown = document.getElementById('countdown');
    if (countdown && seconds > 0) {
        var interval = setInterval(function() {
            seconds--;
            countdown.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(interval);
                location.reload();
            }
        }, 1000);
    }
})();
</script>
