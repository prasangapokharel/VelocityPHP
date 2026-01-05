<?php
$title = "500 - Server Error";
$description = "An internal server error occurred.";
?>
<div class="min-h-[60vh] flex items-center justify-center px-lg">
    <div class="text-center max-w-md">
        <div class="mb-lg">
            <span class="text-8xl font-bold text-red-500">500</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900 mb-md">Server Error</h1>
        <p class="text-neutral-600 mb-xl">Something went wrong on our end. Our team has been notified and is working to fix the issue. Please try again later.</p>
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
                Try Again
            </button>
        </div>
    </div>
</div>
