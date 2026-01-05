<?php
$title = "503 - Service Unavailable";
$description = "The service is temporarily unavailable. Please try again later.";
?>
<div class="min-h-[60vh] flex items-center justify-center px-lg">
    <div class="text-center max-w-md">
        <div class="mb-lg">
            <span class="text-8xl font-bold text-purple-500">503</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900 mb-md">Service Unavailable</h1>
        <p class="text-neutral-600 mb-lg">We're currently performing maintenance or experiencing high traffic. Please try again in a few moments.</p>
        
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-lg mb-xl">
            <div class="flex items-center justify-center gap-sm text-purple-700">
                <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span class="font-medium">We'll be back shortly</span>
            </div>
        </div>
        
        <div class="flex gap-md justify-center">
            <button onclick="location.reload()" class="inline-flex items-center px-lg py-md bg-neutral-900 text-white rounded-lg hover:bg-neutral-800 transition-colors">
                <svg class="w-5 h-5 mr-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Try Again
            </button>
        </div>
    </div>
</div>
