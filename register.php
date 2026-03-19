<?php
// Harden session cookies for the form session
$__rb = function_exists('random_bytes');
if (!$__rb && function_exists('openssl_random_pseudo_bytes')) {
    function random_bytes($length) { return openssl_random_pseudo_bytes($length); }
}
$__rb = null;
$is_https = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
} else {
    session_set_cookie_params(0, '/', '', $is_https, true);
}
session_start();

// Security headers for the form page
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Also set CSRF cookie for double-submit fallback (not HttpOnly by design)
setcookie('csrf_token', $_SESSION['csrf_token'], 0, '/', '', $is_https, false);

$page_title = 'Register for Rhapathon - 2026 Edition';

// Include header
include_once 'includes/header.php';
?>

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- GSAP Animation Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

<!-- Custom Styles for Gradient Text and Dropdowns -->
<style>
/* Fade out animation for notifications */
@keyframes fadeOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-10px);
    }
}
.gradient-gold {
    color: #ffffff;
}

.wonder-text {
    font-family: 'Playfair Display', serif;
    letter-spacing: 0.02em;
}

.text-enhanced {
    text-shadow: none;
}

/* Custom dropdown styling */
.custom-dropdown {
    position: relative;
    z-index: 1;
    isolation: isolate;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #d8dee7;
    border-radius: 14px;
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    z-index: 21000;
    max-height: 150px;
    overflow-y: auto;
    overflow-x: hidden;
    margin-top: 4px;
}

.custom-dropdown.dropdown-active {
    z-index: 20000;
}

/* When any dropdown is open, lift its section above others */
.elevated-section { position: relative; z-index: 19000 !important; }

/* Ensure the Church section (with Zone dropdown) stacks above Network section */
#personalSection { position: relative; z-index: 40; }
#churchSection { position: relative; z-index: 100; }
#networkSection { position: relative; z-index: 20; }
#participationSection { position: relative; z-index: 30; }
#daySelectionSection { position: relative; z-index: 10; }
#feedbackSection { position: relative; z-index: 20; }
#submitSection { position: relative; z-index: 5; }
#registrationFormContainer,
#registrationForm,
.gsap-fade-up {
    overflow: visible;
}

.dropdown-option {
    padding: 8px 12px;
    cursor: pointer;
    transition: all 0.15s;
    font-size: 14px;
    color: #000080;
}

.dropdown-option:hover {
    background: #f5f5f5;
    color: #000080;
}

.dropdown-button {
    display: flex;
    justify-content: between;
    align-items: center;
    width: 100%;
    text-align: left;
    background: #ffffff;
    border: 1px solid #d8dee7;
    border-radius: 16px;
    padding: 14px 18px;
    font-size: 16px;
    font-weight: 500;
    color: #000080;
    transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
    box-shadow: none;
}

.dropdown-button:hover,
.dropdown-button:focus {
    border-color: #b8c2cf;
    background: #f8fafc;
    box-shadow: none;
    outline: none;
}

.dropdown-arrow {
    transition: transform 0.2s;
    margin-left: auto;
}

.dropdown-arrow.open {
    transform: rotate(180deg);
}

/* Custom Tab-style Radio Buttons */
.tabs {
    display: flex;
    gap: 12px;
}

.tab-group input {
    appearance: none;
}

.tab-group label {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 52px;
    height: 52px;
    padding: 0 1.1rem;
    line-height: 1.4;
    border: 1px solid #d8dee7;
    border-radius: 999px;
    cursor: pointer;
    font-weight: 700;
    position: relative;
    background-color: #ffffff;
    transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
    color: #000080;
    font-size: 14px;
    box-shadow: none;
}

@media (min-width: 640px) {
    .tab-group label {
        min-width: 56px;
        height: 56px;
        padding: 0 1.35rem;
        font-size: 16px;
    }
}

.tab-group label:hover {
    border-color: #b8c2cf;
    background: #f8fafc;
    color: #000080;
    transform: none;
    box-shadow: none;
}

.tab-group input:checked + label {
    border-color: #000080;
    background: #000080;
    color: #FFFFFF;
    scale: 1;
    box-shadow: none;
}

/* Enhanced Input Field Styling */
.enhanced-input {
    background: #ffffff;
    border: 1px solid #d8dee7;
    border-radius: 16px;
    padding: 14px 18px;
    font-size: 16px;
    font-weight: 500;
    color: #000080;
    transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
    box-shadow: none;
}

.enhanced-input:focus {
    outline: none;
    border-color: #000080;
    box-shadow: 0 0 0 3px rgba(0, 0, 128, 0.08);
    transform: none;
}

.enhanced-input:hover:not(:focus) {
    border-color: #b8c2cf;
    box-shadow: none;
}

.enhanced-input::placeholder {
    color: #9ca3af;
    font-weight: 400;
}

.field-stack {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.field-stack.hidden {
    display: none !important;
}

.field-hint {
    font-size: 0.875rem;
    line-height: 1.5;
    color: #64748b;
}

.phone-field-grid {
    display: grid;
    grid-template-columns: minmax(172px, 190px) minmax(0, 1fr);
    gap: 0.65rem;
    align-items: stretch;
}

.phone-code-wrapper {
    min-width: 0;
}

.phone-code-button {
    min-height: 52px;
    border-radius: 14px;
    padding: 0.75rem 0.9rem;
    font-size: 0.95rem;
    font-weight: 600;
}

.phone-code-button .dropdown-arrow {
    width: 1rem;
    height: 1rem;
}

.phone-local-input {
    min-height: 52px;
    border-radius: 14px;
    padding: 0.75rem 1rem;
}

.country-code-menu {
    width: 100%;
    max-width: 100%;
    max-height: 320px;
    padding: 0.35rem;
}

.country-code-menu:not(.hidden) {
    display: flex;
    flex-direction: column;
}

.country-search {
    width: 100%;
    border: 1px solid #d8dee7;
    border-radius: 12px;
    padding: 0.7rem 0.85rem;
    font-size: 0.9rem;
    color: #000080;
    margin-bottom: 0.4rem;
    background: #ffffff;
    position: sticky;
    top: 0;
    z-index: 2;
}

.country-search:focus {
    outline: none;
    border-color: #000080;
    box-shadow: 0 0 0 3px rgba(0, 0, 128, 0.08);
}

.country-code-options {
    overflow-y: auto;
    padding-top: 0.1rem;
}

.country-code-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
    border-radius: 12px;
    padding: 0.65rem 0.75rem;
    width: 100%;
    text-align: left;
}

.country-code-label {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.country-code-name {
    color: #0f172a;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.country-code-meta {
    color: #64748b;
    font-size: 0.76rem;
    display: none;
}

.country-code-value {
    color: #000080;
    font-weight: 600;
    white-space: nowrap;
    font-size: 0.92rem;
}

.country-code-empty {
    padding: 0.75rem 0.8rem;
    color: #64748b;
    font-size: 0.88rem;
}

@media (max-width: 900px) {
    .phone-field-grid {
        grid-template-columns: 1fr;
    }

    .country-code-menu {
        width: min(100%, calc(100vw - 2rem));
    }
}

@media (max-width: 639px) {
    .enhanced-input,
    .dropdown-button {
        min-height: 54px;
        padding: 0.9rem 1rem;
        font-size: 1rem;
    }

    .phone-code-button,
    .phone-local-input {
        min-height: 54px;
        padding: 0.85rem 1rem;
    }
}

/* Input with icon */
.input-wrapper {
    position: relative;
}

.input-wrapper .enhanced-input {
    padding-left: 44px;
}

.input-wrapper .input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    transition: color 0.3s;
}

.input-wrapper .enhanced-input:focus ~ .input-icon,
.input-wrapper .enhanced-input:hover ~ .input-icon {
    color: #D4AF37;
}

/* Day Selection Styling */
.day-selector {
    position: relative;
}

.day-label {
    cursor: pointer;
    border: 1px solid #d8dee7;
    border-radius: 18px;
    background: #ffffff;
    transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
    box-shadow: none;
    overflow: hidden;
}

.day-label:hover {
    border-color: #b8c2cf;
    background: #f8fafc;
    box-shadow: none;
    transform: none;
}

.day-content {
    padding: 16px 12px;
    text-align: center;
    position: relative;
    transition: all 0.3s ease;
}

.day-name {
    font-weight: 600;
    font-size: 14px;
    color: #000080;
    margin-bottom: 4px;
    transition: color 0.3s ease;
}

.day-date {
    font-size: 12px;
    color: #666666;
    font-weight: 500;
    transition: color 0.3s ease;
}

.day-checkbox:checked + .day-label {
    border-color: #000080;
    background: #f8fbff;
    box-shadow: none;
    transform: none;
}

.day-checkbox:checked + .day-label .day-name {
    color: #000080;
    font-weight: 700;
    position: relative;
    z-index: 1;
}

.day-checkbox:checked + .day-label .day-date {
    color: #334155;
}

/* Session Selection Styling */
.session-selection {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 12px;
    margin-top: 8px;
    transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
}

.session-checkbox {
    appearance: none;
    width: 16px;
    height: 16px;
    border: 1px solid #94a3b8;
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.9);
    cursor: pointer;
    position: relative;
    transition: all 0.2s ease;
}

.session-checkbox:checked {
    background: #000080;
    border-color: #000080;
}

.session-checkbox:checked::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.session-checkbox:hover {
    border-color: #B8941F;
    box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.2);
}

.day-checkbox:checked + .day-label::after {
    content: "✓";
    position: absolute;
    top: 8px;
    right: 8px;
    width: 20px;
    height: 20px;
    background: rgba(0, 0, 128, 0.08);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    color: #FFFFFF;
    animation: checkmarkPop 0.3s ease-out;
}

@keyframes checkmarkPop {
    0% {
        opacity: 0;
        transform: scale(0.5);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

/* Disabled/Past Days Styling */
.day-selector.disabled {
    opacity: 0.5;
    pointer-events: none;
}

.day-selector.disabled .day-label {
    background: #f5f5f5;
    border-color: #cccccc;
    cursor: not-allowed;
}

.day-selector.disabled .day-name,
.day-selector.disabled .day-date {
    color: #999999;
}

.day-selector.disabled .day-label:hover {
    border-color: #cccccc;
    box-shadow: none;
    transform: none;
}

/* Responsive adjustments for day selection */
@media (max-width: 640px) {
    .day-content {
        padding: 12px 8px;
    }
    
    .day-name {
        font-size: 13px;
    }
    
    .day-date {
        font-size: 11px;
    }
}

@media (min-width: 1024px) {
    .day-content {
        padding: 20px 16px;
    }
    
    .day-name {
        font-size: 15px;
    }
    
    .day-date {
        font-size: 13px;
    }
}


/* GSAP Animation Initial States */
.gsap-fade-up {
    opacity: 0;
    transform: translateY(50px);
}

.gsap-fade-down {
    opacity: 0;
    transform: translateY(-30px);
}

.gsap-fade-in {
    opacity: 0;
}

.gsap-scale-in {
    opacity: 0;
    transform: scale(0.8);
}

.gsap-slide-left {
    opacity: 0;
    transform: translateX(-50px);
}

.gsap-slide-right {
    opacity: 0;
    transform: translateX(50px);
}
</style>

<?php
?>

<!-- Background (Gold - pending new image) -->
<div class="fixed inset-0 z-0" style="background-color: #9B7A00;"></div>

<div class="relative z-10 min-h-screen pt-20 sm:pt-24 pb-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
        <!-- Event Header -->
        <div class="text-center px-4">
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-white mb-2 gsap-fade-down" id="headerTitle">
                Rhapathon with Pastor Chris
            </h1>
            <h2 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-2 sm:mb-3 tracking-[0.08em] uppercase gsap-scale-in" id="headerSubtitle">
                2026 Edition
            </h2>
            <div class="mb-4 sm:mb-6">
                <p class="text-lg sm:text-xl font-medium text-white gsap-fade-up" id="headerDate">
                    Monday 4th - Friday 8th May, 2026
                </p>
            </div>
            <p class="text-base sm:text-lg text-white/90 max-w-2xl mx-auto px-4 gsap-fade-up" id="headerDescription">
                Join us for an extraordinary time of refinement of vision in the completing of our divine mandate.
            </p>
        </div>
        
        <!-- Registration Form -->
        <div class="rounded-3xl border border-border bg-white p-4 shadow-sm sm:p-6 lg:p-8 gsap-fade-up" id="registrationFormContainer">
            <form class="space-y-6 sm:space-y-8" action="admin/submit_registration.php" method="POST" id="registrationForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="preferred_language" id="preferred_language" value="en">

                <!-- Personal Information Section -->
                <div class="space-y-6 gsap-fade-up" id="personalSection">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-border"></div>
                        </div>
                        <div class="relative flex justify-start">
                            <span class="bg-white pr-3 text-lg font-semibold text-primary flex items-center gap-2">
                                <i data-lucide="user-round" class="w-5 h-5 text-primary/60" aria-hidden="true"></i>
                                Personal Information
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Title -->
                        <div class="custom-dropdown field-stack">
                            <label for="title" class="block text-sm font-medium text-primary mb-1">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input type="hidden" id="title" name="title" required>
                            <button type="button" 
                                    id="titleDropdownBtn"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                                    class="dropdown-button w-full px-3 py-2 border border-border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                                <span id="titleSelected">Select Title</span>
                                <i data-lucide="chevron-down" class="dropdown-arrow w-4 h-4 text-gray-500" aria-hidden="true"></i>
                            </button>
                            <div id="titleDropdownMenu" class="dropdown-menu hidden">
                                <div class="dropdown-option" data-value="Pastor">Pastor</div>
                                <div class="dropdown-option" data-value="Deacon">Deacon</div>
                                <div class="dropdown-option" data-value="Deaconess">Deaconess</div>
                                <div class="dropdown-option" data-value="Brother">Brother</div>
                                <div class="dropdown-option" data-value="Sister">Sister</div>
                                <div class="dropdown-option" data-value="Evang.">Evang.</div>
                            </div>
                        </div>
                        
                        <!-- First Name -->
                        <div class="field-stack">
                            <label for="first_name" class="block text-sm font-medium text-primary mb-1">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input id="first_name" 
                                   name="first_name" 
                                   type="text" 
                                   required 
                                   autocomplete="given-name"
                                   autocapitalize="words"
                                   maxlength="80"
                                   placeholder="Enter first name"
                                   class="w-full enhanced-input">
                        </div>
                        
                        <!-- Last Name -->
                        <div class="field-stack">
                            <label for="last_name" class="block text-sm font-medium text-primary mb-1">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input id="last_name" 
                                   name="last_name" 
                                   type="text" 
                                   required 
                                   autocomplete="family-name"
                                   autocapitalize="words"
                                   maxlength="80"
                                   placeholder="Enter last name"
                                   class="w-full enhanced-input">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Email -->
                        <div class="field-stack">
                            <label for="email" class="block text-sm font-medium text-primary mb-1">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input id="email" 
                                   name="email" 
                                   type="email" 
                                   required 
                                   autocomplete="email"
                                   inputmode="email"
                                   spellcheck="false"
                                   placeholder="your.email@example.com"
                                   class="w-full enhanced-input">
                        </div>
                        
                        <!-- Phone Number with Country Code -->
                        <div class="field-stack">
                            <label for="phone_local" class="block text-sm font-medium text-primary mb-1">
                                Phone Number <span class="text-red-500">*</span>
                            </label>
                            <div class="phone-field-grid">
                                <!-- Country Code (styled like other dropdowns) -->
                                <div class="custom-dropdown phone-code-wrapper">
                                    <input type="hidden" id="country_code" name="country_code" required>
                                    <button type="button"
                                            id="countryCodeDropdownBtn"
                                            aria-haspopup="listbox"
                                            aria-expanded="false"
                                            class="dropdown-button phone-code-button w-full border border-border shadow-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                            title="Select country code">
                                        <span id="countryCodeSelected" class="truncate text-left">Select country code</span>
                                        <i data-lucide="chevron-down" class="dropdown-arrow w-4 h-4 text-gray-500" aria-hidden="true"></i>
                                    </button>
                                    <div id="countryCodeDropdownMenu" class="dropdown-menu country-code-menu hidden"></div>
                                </div>

                                <!-- Local phone number -->
                                <input id="phone_local" 
                                       name="phone_local_display"
                                       type="tel" 
                                       required 
                                       autocomplete="tel-national"
                                       inputmode="tel"
                                       maxlength="20"
                                       placeholder="Local number"
                                       class="enhanced-input phone-local-input w-full">
                                <input type="hidden" id="phone" name="phone">
                            </div>
                            <p class="field-hint">Choose your country or territory, then enter your phone number without the international prefix.</p>
                        </div>
                    </div>
                    
                    <!-- KingsChat Username -->
                    <div class="field-stack max-w-md">
                        <label for="kingschat_username" class="block text-sm font-medium text-primary mb-1">
                            KingsChat Username
                        </label>
                        <input id="kingschat_username" 
                               name="kingschat_username" 
                               type="text" 
                               autocomplete="nickname"
                               autocapitalize="none"
                               spellcheck="false"
                               placeholder="@username"
                               class="w-full enhanced-input">
                    </div>
                </div>
                
                <!-- Affiliation Selection -->
                <div class="space-y-6 gsap-fade-up" id="affiliationSection">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-border"></div>
                        </div>
                        <div class="relative flex justify-start">
                            <span class="bg-white pr-3 text-lg font-semibold text-primary flex items-center gap-2">
                                <i data-lucide="folders" class="w-5 h-5 text-primary/60" aria-hidden="true"></i>
                                Church or Network
                            </span>
                        </div>
                    </div>

                    <div class="field-stack">
                        <label id="affiliationQuestion" class="block text-sm font-medium text-primary mb-2">
                            Select how you want to register <span class="text-red-500">*</span>
                        </label>
                        <input type="hidden" id="affiliation_type" name="affiliation_type">
                        <div class="tabs">
                            <div class="tab-group">
                                <input type="radio"
                                       name="affiliation_choice"
                                       value="church"
                                       id="affiliation_church">
                                <label for="affiliation_church">
                                    <span>Church</span>
                                </label>
                            </div>
                            <div class="tab-group">
                                <input type="radio"
                                       name="affiliation_choice"
                                       value="network"
                                       id="affiliation_network">
                                <label for="affiliation_network">
                                    <span>Network</span>
                                </label>
                            </div>
                        </div>
                        <div id="affiliation_error" class="text-red-500 text-sm mt-2 hidden">
                            Please choose either Church or Network.
                        </div>
                    </div>
                </div>

                <!-- Church Information Section -->
                <div class="space-y-6 gsap-fade-up hidden" id="churchSection">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-border"></div>
                        </div>
                        <div class="relative flex justify-start">
                            <span class="bg-white pr-3 text-lg font-semibold text-primary flex items-center gap-2">
                                <i data-lucide="building-2" class="w-5 h-5 text-primary/60" aria-hidden="true"></i>
                                Church Information
                            </span>
                        </div>
                    </div>

                    <!-- Zone Selection -->
                    <div class="custom-dropdown field-stack">
                        <label for="zone" class="block text-sm font-medium text-primary mb-1">
                            Zone <span class="text-red-500">*</span>
                        </label>
                        <input type="hidden" id="zone" name="zone">
                        <button type="button"
                                id="zoneDropdownBtn"
                                aria-haspopup="listbox"
                                aria-expanded="false"
                                class="dropdown-button w-full px-3 py-2 border border-border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                            <span id="zoneSelected">Select your zone</span>
                            <i data-lucide="chevron-down" class="dropdown-arrow w-4 h-4 text-gray-500" aria-hidden="true"></i>
                        </button>
                        <div id="zoneDropdownMenu" class="dropdown-menu hidden">
                            <!-- Zone options will be populated dynamically -->
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Group -->
                        <div class="custom-dropdown field-stack">
                            <label for="group" class="block text-sm font-medium text-primary mb-1">
                                Group
                            </label>
                            <input type="hidden" id="group" name="group">
                            <button type="button"
                                    id="groupDropdownBtn"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                                    class="dropdown-button w-full px-3 py-2 border border-border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                                <span id="groupSelected">Select your group</span>
                                <i data-lucide="chevron-down" class="dropdown-arrow w-4 h-4 text-gray-500" aria-hidden="true"></i>
                            </button>
                            <div id="groupDropdownMenu" class="dropdown-menu hidden"></div>
                        </div>

                        <!-- Church -->
                        <div class="field-stack">
                            <label for="church" class="block text-sm font-medium text-primary mb-1">
                                Church
                            </label>
                            <input id="church"
                                   name="church"
                                   type="text"
                                   list="churchSuggestions"
                                   autocomplete="organization"
                                   autocapitalize="words"
                                   maxlength="120"
                                   placeholder="Enter your church name"
                                   class="w-full enhanced-input">
                            <datalist id="churchSuggestions"></datalist>
                        </div>
                    </div>
                </div>

                <!-- Network Information Section -->
                <div class="space-y-6 gsap-fade-up hidden" id="networkSection">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-border"></div>
                        </div>
                        <div class="relative flex justify-start">
                            <span class="bg-white pr-3 text-lg font-semibold text-primary flex items-center gap-2">
                                <i data-lucide="globe-2" class="w-5 h-5 text-primary/60" aria-hidden="true"></i>
                                Network Information
                            </span>
                        </div>
                    </div>
                    <!-- Network Selection -->
                    <div class="custom-dropdown field-stack">
                        <label for="network" class="block text-sm font-medium text-primary mb-1">
                            Network <span class="text-red-500">*</span>
                        </label>
                        <input type="hidden" id="network" name="network">
                        <button type="button"
                                id="networkDropdownBtn"
                                aria-haspopup="listbox"
                                aria-expanded="false"
                                class="dropdown-button w-full px-3 py-2 border border-border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                            <span id="networkSelected">Select your network</span>
                            <i data-lucide="chevron-down" class="dropdown-arrow w-4 h-4 text-gray-500" aria-hidden="true"></i>
                        </button>
                        <div id="networkDropdownMenu" class="dropdown-menu hidden">
                            <div class="dropdown-option" data-value="REON">REON</div>
                            <div class="dropdown-option" data-value="RIM">RIM</div>
                            <div class="dropdown-option" data-value="RIN">RIN</div>
                            <div class="dropdown-option" data-value="REACHOUT CAMPAIGNS">REACHOUT CAMPAIGNS</div>
                            <div class="dropdown-option" data-value="TNI">TNI</div>
                            <div class="dropdown-option" data-value="GYLF">GYLF</div>
                            <div class="dropdown-option" data-value="OTHER">OTHER</div>
                        </div>
                    </div>

                    <!-- Manual Network Input (shown when OTHER is selected) -->
                    <div id="manualNetworkContainer" class="hidden field-stack">
                        <label for="manual_network" class="block text-sm font-medium text-primary mb-1">
                            Please specify your network
                        </label>
                        <input id="manual_network"
                               name="manual_network"
                               type="text"
                               autocomplete="organization"
                               autocapitalize="words"
                               maxlength="120"
                               placeholder="Enter your network"
                               class="w-full enhanced-input">
                    </div>
                </div>
                
                <!-- Participation Section -->
                <div class="space-y-6 gsap-fade-up" id="participationSection">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-border"></div>
                        </div>
                        <div class="relative flex justify-start">
                            <span class="bg-white pr-3 text-lg font-semibold text-primary flex items-center gap-2">
                                <i data-lucide="badge-check" class="w-5 h-5 text-primary/60" aria-hidden="true"></i>
                                Participation
                            </span>
                        </div>
                    </div>
                    <div>
                        <label id="onsiteQuestion" class="block text-sm font-medium text-primary mb-2">
                            Will you be participating on-site for the Rhapathon conference at Asese? <span class="text-red-500">*</span>
                        </label>
                        <div class="tabs">
                            <div class="tab-group">
                                <input type="radio" 
                                       name="onsite_participation" 
                                       value="yes" 
                                       required 
                                       id="onsite_participation_yes">
                                <label for="onsite_participation_yes">
                                    <span>Yes</span>
                                </label>
                            </div>
                            <div class="tab-group">
                                <input type="radio" 
                                       name="onsite_participation" 
                                       value="no" 
                                       required 
                                       id="onsite_participation_no">
                                <label for="onsite_participation_no">
                                    <span>No</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Online participation follow-up (shown if Onsite = No) -->
                        <div id="onlineParticipationBlock" class="mt-5 hidden space-y-4 rounded-lg border border-border bg-gray-50 p-4 sm:p-5">
                            <label id="onlineQuestion" class="block text-sm font-medium text-primary mb-2">
                                Will you be participating online?
                            </label>
                            <div class="tabs">
                                <div class="tab-group">
                                    <input type="radio" 
                                           name="online_participation" 
                                           value="yes" 
                                           id="online_participation_yes">
                                    <label for="online_participation_yes">
                                        <span>Yes</span>
                                    </label>
                                </div>
                                <div class="tab-group">
                                    <input type="radio" 
                                           name="online_participation" 
                                           value="no" 
                                           id="online_participation_no">
                                    <label for="online_participation_no">
                                        <span>No</span>
                                    </label>
                                </div>
                            </div>
                            <p id="watchOnlineInfo" class="hidden rounded-md border border-border bg-white px-4 py-3 text-sm leading-relaxed text-gray-700 shadow-sm">
                                You can watch live on Rhapsody TV at <a href="https://rhapsodytv.live" target="_blank" rel="noopener" class="text-accent underline">rhapsodytv.live</a>.
                            </p>
                            <div id="online_error" class="text-red-500 text-sm mt-2 hidden">Please indicate your online participation choice.</div>
                        </div>
                    </div>
                </div>

                <!-- Day Selection Section -->
                <div class="space-y-6 gsap-fade-up" id="daySelectionSection">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-border"></div>
                        </div>
                        <div class="relative flex justify-start">
                            <span class="bg-white pr-3 text-lg font-semibold text-primary flex items-center gap-2">
                                <i data-lucide="calendar-days" class="w-5 h-5 text-primary/60" aria-hidden="true"></i>
                                <span id="daysHeader">Days</span>
                            </span>
                        </div>
                    </div>

                    <div>
                        <label id="selectDaysLabel" class="block text-sm font-medium text-primary mb-3">
                            Select your days <span class="text-red-500">*</span>
                            <span id="selectDaysNote" class="block text-xs text-gray-600 mt-1">At least one day required</span>
                        </label>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                            <!-- Monday -->
                            <div class="day-selector">
                                <input type="checkbox"
                                       id="day_monday"
                                       name="selected_days[]"
                                       value="monday"
                                       class="day-checkbox sr-only">
                                <label for="day_monday" class="day-label block">
                                    <div class="day-content">
                                        <div class="day-name">Monday</div>
                                        <div class="day-date">4th May</div>
                                    </div>
                                </label>
                            </div>

                            <!-- Tuesday -->
                            <div class="day-selector">
                                <input type="checkbox"
                                       id="day_tuesday"
                                       name="selected_days[]"
                                       value="tuesday"
                                       class="day-checkbox sr-only">
                                <label for="day_tuesday" class="day-label block">
                                    <div class="day-content">
                                        <div class="day-name">Tuesday</div>
                                        <div class="day-date">5th May</div>
                                    </div>
                                </label>
                                <!-- Session Selection for Tuesday -->
                                <div class="session-selection mt-3 space-y-2" id="tuesday_sessions" style="display: none;">
                                    <div class="text-xs font-medium text-primary mb-2">Select Sessions:</div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="tuesday_sessions[]" value="morning" class="session-checkbox mr-2">
                                        <span class="text-sm">Morning Session</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="tuesday_sessions[]" value="evening" class="session-checkbox mr-2">
                                        <span class="text-sm">Evening Session</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Wednesday -->
                            <div class="day-selector">
                                <input type="checkbox"
                                       id="day_wednesday"
                                       name="selected_days[]"
                                       value="wednesday"
                                       class="day-checkbox sr-only">
                                <label for="day_wednesday" class="day-label block">
                                    <div class="day-content">
                                        <div class="day-name">Wednesday</div>
                                        <div class="day-date">6th May</div>
                                    </div>
                                </label>
                                <!-- Session Selection for Wednesday -->
                                <div class="session-selection mt-3 space-y-2" id="wednesday_sessions" style="display: none;">
                                    <div class="text-xs font-medium text-primary mb-2">Select Sessions:</div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="wednesday_sessions[]" value="morning" class="session-checkbox mr-2">
                                        <span class="text-sm">Morning Session</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="wednesday_sessions[]" value="evening" class="session-checkbox mr-2">
                                        <span class="text-sm">Evening Session</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Thursday -->
                            <div class="day-selector">
                                <input type="checkbox"
                                       id="day_thursday"
                                       name="selected_days[]"
                                       value="thursday"
                                       class="day-checkbox sr-only">
                                <label for="day_thursday" class="day-label block">
                                    <div class="day-content">
                                        <div class="day-name">Thursday</div>
                                        <div class="day-date">7th May</div>
                                    </div>
                                </label>
                                <!-- Session Selection for Thursday -->
                                <div class="session-selection mt-3 space-y-2" id="thursday_sessions" style="display: none;">
                                    <div class="text-xs font-medium text-primary mb-2">Select Sessions:</div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="thursday_sessions[]" value="morning" class="session-checkbox mr-2">
                                        <span class="text-sm">Morning Session</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="thursday_sessions[]" value="evening" class="session-checkbox mr-2">
                                        <span class="text-sm">Evening Session</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Friday -->
                            <div class="day-selector">
                                <input type="checkbox"
                                       id="day_friday"
                                       name="selected_days[]"
                                       value="friday"
                                       class="day-checkbox sr-only">
                                <label for="day_friday" class="day-label block">
                                    <div class="day-content">
                                        <div class="day-name">Friday</div>
                                        <div class="day-date">8th May</div>
                                    </div>
                                </label>
                                <!-- Session Selection for Friday -->
                                <div class="session-selection mt-3 space-y-2" id="friday_sessions" style="display: none;">
                                    <div class="text-xs font-medium text-primary mb-2">Select Sessions:</div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="friday_sessions[]" value="morning" class="session-checkbox mr-2">
                                        <span class="text-sm">Morning Session</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="friday_sessions[]" value="evening" class="session-checkbox mr-2">
                                        <span class="text-sm">Evening Session</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Select All / Clear All buttons -->
                        <div class="flex flex-wrap gap-2 mt-4">
                            <button type="button"
                                    id="selectAllDays"
                                    class="group rounded-full border border-border bg-white px-4 py-2 text-sm font-medium text-primary transition-colors duration-200 hover:bg-light focus:outline-none focus:ring-2 focus:ring-primary/15">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="check" class="h-4 w-4 transition-transform duration-200 group-hover:scale-105" aria-hidden="true"></i>
                                    Select All Days
                                </span>
                            </button>
                            <button type="button"
                                    id="clearAllDays"
                                    class="group rounded-full border border-border bg-light px-4 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-primary/15">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="x" class="h-4 w-4 transition-transform duration-200 group-hover:rotate-90" aria-hidden="true"></i>
                                    Clear All
                                </span>
                            </button>
                        </div>

                        <!-- Hidden field for validation -->
                        <input type="hidden" id="days_validation" name="days_validation" required>
                        <div id="days_error" class="text-red-500 text-sm mt-2 hidden">
                            Please select at least one day to attend.
                        </div>
                    </div>
                </div>
                

                
                
                <!-- Additional Feedback -->
                <div class="space-y-2 gsap-fade-up" id="feedbackSection">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-border"></div>
                        </div>
                        <div class="relative flex justify-start">
                            <span class="bg-white pr-3 text-lg font-semibold text-primary flex items-center gap-2">
                                <i data-lucide="messages-square" class="w-5 h-5 text-primary/60" aria-hidden="true"></i>
                                <span id="feedbackLabel">Questions or feedback</span>
                            </span>
                        </div>
                    </div>
                    <textarea id="feedback" name="feedback" rows="4" class="w-full enhanced-input" placeholder="Share any questions or feedback..."></textarea>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 sm:pt-6 gsap-fade-up" id="submitSection">
                    <button type="submit" 
                            class="group w-full rounded-full bg-primary px-6 py-4 text-sm font-semibold text-white transition-colors duration-200 hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2 sm:w-auto sm:px-8 sm:text-base">
                        <span class="flex items-center justify-center gap-2">
                            <i data-lucide="badge-check" class="h-5 w-5 transition-transform duration-200 group-hover:scale-105" aria-hidden="true"></i>
                            <span id="submitButtonText">Register for Rhapathon</span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Back to Top Button -->
<button id="backToTop" class="fixed bottom-4 right-4 z-50 rounded-full border border-border bg-white p-2.5 text-primary shadow-sm transition-all duration-300 hover:bg-light focus:outline-none focus:ring-2 focus:ring-primary/15 focus:ring-offset-2 sm:bottom-6 sm:right-6 sm:p-3 opacity-0 invisible">
    <i data-lucide="arrow-up" class="w-5 h-5 sm:w-6 sm:h-6" aria-hidden="true"></i>
</button>

<!-- SweetAlert2 for styled notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JavaScript for Custom Dropdowns, Dynamic Zone Loading and Conditional Fields -->
<script>
    // Persist form draft to avoid data loss on reloads
    const DRAFT_KEY = 'reg_form_draft';
    const RETRY_KEY = 'reg_form_retry';

    function saveDraftFromForm(form) {
        const obj = {};
        new FormData(form).forEach((v,k) => {
            if (obj[k] === undefined) obj[k] = v; // ignore multi for simplicity
        });
        try { localStorage.setItem(DRAFT_KEY, JSON.stringify(obj)); } catch {}
    }

    function restoreDraftToForm(form) {
        try {
            const raw = localStorage.getItem(DRAFT_KEY);
            if (!raw) return false;
            const data = JSON.parse(raw);
            let restored = false;
            Object.keys(data).forEach(k => {
                const el = form.querySelector(`[name="${CSS.escape(k)}"]`);
                if (!el) return;
                if (el.type === 'checkbox' || el.type === 'radio') {
                    el.checked = !!data[k];
                } else {
                    el.value = data[k];
                }
                restored = true;
            });
            return restored;
        } catch { return false; }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('registrationForm');
        if (!form) return;

        // If flagged to retry after reload, restore and optionally auto-submit once
        const retry = localStorage.getItem(RETRY_KEY);
        if (retry === '1') {
            const didRestore = restoreDraftToForm(form);
            localStorage.removeItem(RETRY_KEY);
            if (didRestore) {
                // Do not auto-submit; let user review. Could auto-submit by calling form.dispatchEvent(new Event('submit'));
            }
        }

        // Save draft on change
        form.addEventListener('input', () => saveDraftFromForm(form));
    });
document.addEventListener('DOMContentLoaded', function() {
    // Utility: HTML escape to render server messages safely
    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Simple in-page i18n for this register page only
    let currentLang = (function(){ try { return localStorage.getItem('lang') || 'en'; } catch(e) { return 'en'; } })();
    const EVENT_YEAR = 2026;
    const EVENT_MONTH_INDEX = 4; // May is 4 in the JS Date API
    const EVENT_START_DATE = new Date(EVENT_YEAR, EVENT_MONTH_INDEX, 4);
    const EVENT_END_DATE = new Date(EVENT_YEAR, EVENT_MONTH_INDEX, 8);
    const EVENT_DAY_SCHEDULE = [
        { id: 'day_monday', date: new Date(EVENT_YEAR, EVENT_MONTH_INDEX, 4) },
        { id: 'day_tuesday', date: new Date(EVENT_YEAR, EVENT_MONTH_INDEX, 5) },
        { id: 'day_wednesday', date: new Date(EVENT_YEAR, EVENT_MONTH_INDEX, 6) },
        { id: 'day_thursday', date: new Date(EVENT_YEAR, EVENT_MONTH_INDEX, 7) },
        { id: 'day_friday', date: new Date(EVENT_YEAR, EVENT_MONTH_INDEX, 8) }
    ];

    function getOrdinal(day) {
        const mod10 = day % 10;
        const mod100 = day % 100;
        if (mod10 === 1 && mod100 !== 11) return `${day}st`;
        if (mod10 === 2 && mod100 !== 12) return `${day}nd`;
        if (mod10 === 3 && mod100 !== 13) return `${day}rd`;
        return `${day}th`;
    }

    function formatEventRange(lang) {
        if (!lang || lang === 'en') {
            return `Monday ${getOrdinal(EVENT_START_DATE.getDate())} - Friday ${getOrdinal(EVENT_END_DATE.getDate())} May, ${EVENT_YEAR}`;
        }

        try {
            const formatter = new Intl.DateTimeFormat(lang, {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });

            if (typeof formatter.formatRange === 'function') {
                return formatter.formatRange(EVENT_START_DATE, EVENT_END_DATE);
            }

            return `${formatter.format(EVENT_START_DATE)} - ${formatter.format(EVENT_END_DATE)}`;
        } catch (error) {
            return `Monday ${getOrdinal(EVENT_START_DATE.getDate())} - Friday ${getOrdinal(EVENT_END_DATE.getDate())} May, ${EVENT_YEAR}`;
        }
    }

    function getEventSubtitle(text) {
        const source = typeof text === 'string' && text.trim() !== '' ? text : '2026 Edition';
        return source.replace(/2025/g, String(EVENT_YEAR));
    }

    const i18n = {
        en: {
            header: {
                title: 'Rhapathon with Pastor Chris',
                subtitle: '2026 Edition',
                date: 'Monday 4th - Friday 8th May, 2026',
                desc: 'Join us for an extraordinary time of refinement of vision in the completing of our divine mandate.'
            },
            sections: {
                personal: 'Personal Information',
                church: 'Church Information',
                participation: 'Participation',
                days: 'Days'
            },
            labels: {
                title: 'Title',
                first_name: 'First Name',
                last_name: 'Last Name',
                email_address: 'Email Address',
                phone_number: 'Phone Number',
                kingschat_username: 'KingsChat Username',
                zone: 'Zone',
                group: 'Group',
                church: 'Church',
                participation_question: 'Will you be participating on-site for the Rhapathon conference at Asese?',
                online_participation_question: 'Will you be participating online?',
                select_days: 'Select your days',
                select_days_sessions: 'Select your days',
                sessions: 'Sessions',
                yes: 'Yes',
                no: 'No',
                feedback_label: 'Questions or feedback'
            },
            placeholders: {
                first_name: 'Enter first name',
                last_name: 'Enter last name',
                email: 'your.email@example.com',
                phone_local: 'Local number',
                kingschat: '@username',
                group: 'Enter your group',
                church: 'Enter your church name',
                feedback_placeholder: 'Share any questions or feedback...'
            },
            dropdowns: {
                select_title: 'Select Title',
                select_zone: 'Select your zone',
                select_code: 'Select code'
            },
            days: {
                monday: 'Monday',
                tuesday: 'Tuesday',
                wednesday: 'Wednesday',
                thursday: 'Thursday',
                friday: 'Friday'
            },
            buttons: {
                select_all: 'Select All Days',
                clear_all: 'Clear All',
                submit: 'Register for Rhapathon',
                submitting: 'Submitting...',
                confirm: 'Great!',
                ok: 'OK'
            },
            errors: {
                days_required: 'Please select at least one day to attend.',
            },
            notes: {
                days_note: 'At least one day required',
                watch_online: 'You can watch live on Rhapsody TV at <a href="https://rhapsodytv.live" target="_blank" rel="noopener" class="text-accent underline">rhapsodytv.live</a>.'
            },
            alerts: {
                success_title: 'Registration Successful!',
                error_title: 'Error'
            }
        },
        es: {
            header: {
                title: 'Rhapathon con el Pastor Chris',
                subtitle: 'Edición 2026',
                date: 'Lunes 4 - Viernes 8 de mayo de 2026',
                desc: 'Únase a nosotros para un tiempo extraordinario de refinamiento de visión en el cumplimiento de nuestro mandato divino.'
            },
            sections: {
                personal: 'Información Personal',
                church: 'Información de la Iglesia',
                participation: 'Participación',
                days: 'Días y Sesiones'
            },
            labels: {
                title: 'Título',
                first_name: 'Nombre',
                last_name: 'Apellido',
                email_address: 'Correo Electrónico',
                phone_number: 'Número de Teléfono',
                kingschat_username: 'Usuario de KingsChat',
                zone: 'Zona',
                group: 'Grupo',
                church: 'Iglesia',
                participation_question: '¿Participará de forma presencial en la conferencia Rhapathon en Asese?',
                select_days: 'Seleccione sus días',
                yes: 'Sí',
                no: 'No'
            },
            placeholders: {
                first_name: 'Ingrese nombre',
                last_name: 'Ingrese apellido',
                email: 'tu.email@ejemplo.com',
                phone_local: 'Número local',
                kingschat: '@usuario',
                group: 'Ingrese su grupo',
                church: 'Ingrese el nombre de su iglesia'
            },
            dropdowns: {
                select_title: 'Seleccione título',
                select_zone: 'Seleccione su zona',
                select_code: 'Seleccione código'
            },
            days: {
                monday: 'Lunes (noche)',
                tuesday: 'Martes (noche)',
                wednesday: 'Miércoles (noche)',
                thursday: 'Jueves (noche)',
                friday: 'Viernes (noche)'
            },
            buttons: {
                select_all: 'Seleccionar todos los días',
                clear_all: 'Limpiar',
                submit: 'Registrarse para Rhapathon',
                submitting: 'Enviando...',
                confirm: '¡Genial!',
                ok: 'OK'
            },
            errors: {
                days_required: 'Seleccione al menos un día para asistir.',
                sessions_required: 'Seleccione al menos una sesión para asistir.'
            },
            notes: {
                days_note: 'Se requiere al menos un día'
            },
            alerts: {
                success_title: '¡Registro Exitoso!',
                error_title: 'Error'
            }
        },
        fr: {
            header: {
                title: 'Rhapathon avec le Pasteur Chris',
                subtitle: 'Édition 2026',
                date: 'Lundi 4 - Vendredi 8 mai 2026',
                desc: 'Rejoignez-nous pour un moment extraordinaire d’affinement de la vision dans l’accomplissement de notre mandat divin.'
            },
            sections: {
                personal: 'Informations Personnelles',
                church: 'Informations de l’Église',
                participation: 'Participation',
                days: 'Jours et Sessions'
            },
            labels: {
                title: 'Titre',
                first_name: 'Prénom',
                last_name: 'Nom',
                email_address: 'Adresse Email',
                phone_number: 'Numéro de Téléphone',
                kingschat_username: 'Nom d’utilisateur KingsChat',
                zone: 'Zone',
                group: 'Groupe',
                church: 'Église',
                participation_question: 'Participerez-vous sur place à la conférence Rhapathon à Asese ?',
                select_days_sessions: 'Sélectionnez vos jours et sessions',
                sessions: 'Sessions',
                yes: 'Oui',
                no: 'Non'
            },
            placeholders: {
                first_name: 'Entrez le prénom',
                last_name: 'Entrez le nom',
                email: 'votre.email@exemple.com',
                phone_local: 'Numéro local',
                kingschat: '@identifiant',
                group: 'Entrez votre groupe',
                church: 'Entrez le nom de votre église'
            },
            dropdowns: {
                select_title: 'Sélectionner le titre',
                select_zone: 'Sélectionnez votre zone',
                select_code: 'Sélectionner le code'
            },
            days: {
                monday: 'Lundi',
                tuesday: 'Mardi',
                wednesday: 'Mercredi',
                thursday: 'Jeudi',
                friday: 'Vendredi'
            },
            buttons: {
                select_all: 'Sélectionner tous les jours',
                clear_all: 'Effacer',
                submit: 'S’inscrire à Rhapathon',
                submitting: 'Envoi...',
                confirm: 'Parfait !',
                ok: 'OK'
            },
            errors: {
                days_required: 'Veuillez sélectionner au moins un jour.',
                sessions_required: 'Veuillez sélectionner au moins une session.'
            },
            notes: {
                days_note: 'Au moins un jour et une session requis'
            },
            alerts: {
                success_title: 'Inscription Réussie !',
                error_title: 'Erreur'
            }
        },
        de: {
            header: {
                title: 'Rhapathon mit Pastor Chris',
                subtitle: 'Ausgabe 2026',
                date: 'Montag 4. – Freitag 8. Mai 2026',
                desc: 'Begleiten Sie uns zu einer außergewöhnlichen Zeit der Visionserneuerung bei der Erfüllung unseres göttlichen Auftrags.'
            },
            sections: {
                personal: 'Persönliche Informationen',
                church: 'Kircheninformationen',
                participation: 'Teilnahme',
                days: 'Tage & Sitzungen'
            },
            labels: {
                title: 'Anrede',
                first_name: 'Vorname',
                last_name: 'Nachname',
                email_address: 'E-Mail-Adresse',
                phone_number: 'Telefonnummer',
                kingschat_username: 'KingsChat Benutzername',
                zone: 'Zone',
                group: 'Gruppe',
                church: 'Gemeinde',
                participation_question: 'Nehmen Sie vor Ort an der Rhapathon-Konferenz in Asese teil?',
                select_days_sessions: 'Wählen Sie Ihre Tage und Sitzungen',
                sessions: 'Sitzungen',
                yes: 'Ja',
                no: 'Nein'
            },
            placeholders: {
                first_name: 'Vornamen eingeben',
                last_name: 'Nachnamen eingeben',
                email: 'ihre.email@beispiel.com',
                phone_local: 'Ortsnummer',
                kingschat: '@benutzername',
                group: 'Ihre Gruppe eingeben',
                church: 'Name Ihrer Gemeinde eingeben'
            },
            dropdowns: {
                select_title: 'Anrede wählen',
                select_zone: 'Ihre Zone wählen',
                select_code: 'Vorwahl wählen'
            },
            days: {
                monday: 'Montag',
                tuesday: 'Dienstag',
                wednesday: 'Mittwoch',
                thursday: 'Donnerstag',
                friday: 'Freitag'
            },
            buttons: {
                select_all: 'Alle Tage auswählen',
                clear_all: 'Leeren',
                submit: 'Für Rhapathon registrieren',
                submitting: 'Senden...',
                confirm: 'Super!',
                ok: 'OK'
            },
            errors: {
                days_required: 'Bitte wählen Sie mindestens einen Tag.',
                sessions_required: 'Bitte wählen Sie mindestens eine Sitzung.'
            },
            notes: {
                days_note: 'Mindestens ein Tag und eine Sitzung erforderlich'
            },
            alerts: {
                success_title: 'Registrierung erfolgreich!',
                error_title: 'Fehler'
            }
        },
        it: {
            header: {
                title: 'Rhapathon con il Pastore Chris',
                subtitle: 'Edizione 2026',
                date: 'Lunedì 4 - Venerdì 8 Maggio 2026',
                desc: 'Unisciti a noi per un momento straordinario di affinamento della visione nel compimento del nostro mandato divino.'
            },
            sections: {
                personal: 'Informazioni Personali',
                church: 'Informazioni della Chiesa',
                participation: 'Partecipazione',
                days: 'Giorni e Sessioni'
            },
            labels: {
                title: 'Titolo',
                first_name: 'Nome',
                last_name: 'Cognome',
                email_address: 'Indirizzo Email',
                phone_number: 'Numero di Telefono',
                kingschat_username: 'Username KingsChat',
                zone: 'Zona',
                group: 'Gruppo',
                church: 'Chiesa',
                participation_question: 'Parteciperai in presenza alla conferenza Rhapathon ad Asese?',
                select_days_sessions: 'Seleziona i tuoi giorni e sessioni',
                sessions: 'Sessioni',
                yes: 'Sì',
                no: 'No'
            },
            placeholders: {
                first_name: 'Inserisci il nome',
                last_name: 'Inserisci il cognome',
                email: 'tua.email@esempio.com',
                phone_local: 'Numero locale',
                kingschat: '@username',
                group: 'Inserisci il tuo gruppo',
                church: 'Inserisci il nome della chiesa'
            },
            dropdowns: {
                select_title: 'Seleziona titolo',
                select_zone: 'Seleziona la tua zona',
                select_code: 'Seleziona prefisso'
            },
            days: {
                monday: 'Lunedì',
                tuesday: 'Martedì',
                wednesday: 'Mercoledì',
                thursday: 'Giovedì',
                friday: 'Venerdì'
            },
            buttons: {
                select_all: 'Seleziona tutti i giorni',
                clear_all: 'Cancella',
                submit: 'Registrati a Rhapathon',
                submitting: 'Invio...',
                confirm: 'Perfetto!',
                ok: 'OK'
            },
            errors: {
                days_required: 'Seleziona almeno un giorno.',
                sessions_required: 'Seleziona almeno una sessione.'
            },
            notes: {
                days_note: 'È richiesto almeno un giorno e una sessione'
            },
            alerts: {
                success_title: 'Registrazione riuscita!',
                error_title: 'Errore'
            }
        },
        pt: {
            header: {
                title: 'Rhapathon com o Pastor Chris',
                subtitle: 'Edição 2026',
                date: 'Segunda 4 - Sexta 8 de maio de 2026',
                desc: 'Junte-se a nós para um tempo extraordinário de refinamento da visão no cumprimento do nosso mandato divino.'
            },
            sections: { personal: 'Informações Pessoais', church: 'Informações da Igreja', participation: 'Participação', days: 'Dias e Sessões' },
            labels: {
                title: 'Título', first_name: 'Nome', last_name: 'Sobrenome', email_address: 'E-mail', phone_number: 'Número de Telefone',
                kingschat_username: 'Usuário do KingsChat', zone: 'Zona', group: 'Grupo', church: 'Igreja',
                participation_question: 'Você participará presencialmente da conferência Rhapathon em Asese?',
                select_days_sessions: 'Selecione seus dias e sessões', sessions: 'Sessões', yes: 'Sim', no: 'Não'
            },
            placeholders: {
                first_name: 'Insira o nome', last_name: 'Insira o sobrenome', email: 'seu.email@exemplo.com', phone_local: 'Número local',
                kingschat: '@usuário', group: 'Insira seu grupo', church: 'Insira o nome da sua igreja'
            },
            dropdowns: { select_title: 'Selecionar título', select_zone: 'Selecione sua zona', select_code: 'Selecionar código' },
            days: { monday: 'Segunda', tuesday: 'Terça', wednesday: 'Quarta', thursday: 'Quinta', friday: 'Sexta' },
            buttons: { select_all: 'Selecionar todos os dias', clear_all: 'Limpar', submit: 'Registrar-se para o Rhapathon', submitting: 'Enviando...', confirm: 'Ótimo!', ok: 'OK' },
            errors: { days_required: 'Selecione pelo menos um dia para participar.', sessions_required: 'Selecione pelo menos uma sessão para participar.' },
            notes: { days_note: 'Pelo menos um dia e uma sessão são necessários' },
            alerts: { success_title: 'Registro bem-sucedido!', error_title: 'Erro' }
        },
        ar: {
            header: {
                title: 'رخباثون مع القس كريس',
                subtitle: 'نسخة 2026',
                date: 'الاثنين 4 - الجمعة 8 مايو 2026',
                desc: 'انضم إلينا لوقت استثنائي من صقل الرؤية في إتمام تكليفنا الإلهي.'
            },
            sections: { personal: 'المعلومات الشخصية', church: 'معلومات الكنيسة', participation: 'المشاركة', days: 'الأيام والجلسات' },
            labels: {
                title: 'اللقب', first_name: 'الاسم الأول', last_name: 'اسم العائلة', email_address: 'البريد الإلكتروني', phone_number: 'رقم الهاتف',
                kingschat_username: 'اسم مستخدم كينغز شات', zone: 'المنطقة', group: 'المجموعة', church: 'الكنيسة',
                participation_question: 'هل ستشارك حضورياً في مؤتمر رخباتون في أسيس؟',
                select_days_sessions: 'اختر أيامك وجلساتك', sessions: 'الجلسات', yes: 'نعم', no: 'لا'
            },
            placeholders: { first_name: 'أدخل الاسم', last_name: 'أدخل اسم العائلة', email: 'بريدك@مثال.com', phone_local: 'رقم محلي', kingschat: '@اسم_المستخدم', group: 'أدخل مجموعتك', church: 'أدخل اسم كنيستك' },
            dropdowns: { select_title: 'اختر اللقب', select_zone: 'اختر منطقتك', select_code: 'اختر الرمز' },
            days: { monday: 'الاثنين', tuesday: 'الثلاثاء', wednesday: 'الأربعاء', thursday: 'الخميس', friday: 'الجمعة' },
            buttons: { select_all: 'اختر كل الأيام', clear_all: 'مسح', submit: 'سجل في رخباتون', submitting: 'جاري الإرسال...', confirm: 'حسناً!', ok: 'حسناً' },
            errors: { days_required: 'يرجى اختيار يوم واحد على الأقل.', sessions_required: 'يرجى اختيار جلسة واحدة على الأقل.' },
            notes: { days_note: 'مطلوب يوم واحد على الأقل وجلسة واحدة' },
            alerts: { success_title: 'تم التسجيل بنجاح!', error_title: 'خطأ' }
        },
        ru: {
            header: {
                title: 'Рхапатон с пастором Крисом',
                subtitle: 'Издание 2026',
                date: 'Понедельник 4 – Пятница 8 мая 2026',
                desc: 'Присоединяйтесь к нам для удивительного времени уточнения видения в исполнении нашего Божественного поручения.'
            },
            sections: { personal: 'Личная информация', church: 'Информация о церкви', participation: 'Участие', days: 'Дни и сессии' },
            labels: {
                title: 'Обращение', first_name: 'Имя', last_name: 'Фамилия', email_address: 'Эл. почта', phone_number: 'Номер телефона',
                kingschat_username: 'Имя пользователя KingsChat', zone: 'Зона', group: 'Группа', church: 'Церковь',
                participation_question: 'Вы будете участвовать очно на конференции Rhapathon в Асесе?',
                select_days_sessions: 'Выберите дни и сессии', sessions: 'Сессии', yes: 'Да', no: 'Нет'
            },
            placeholders: { first_name: 'Введите имя', last_name: 'Введите фамилию', email: 'ваш.email@пример.com', phone_local: 'Местный номер', kingschat: '@имя', group: 'Введите вашу группу', church: 'Введите название церкви' },
            dropdowns: { select_title: 'Выберите обращение', select_zone: 'Выберите вашу зону', select_code: 'Выберите код' },
            days: { monday: 'Понедельник', tuesday: 'Вторник', wednesday: 'Среда', thursday: 'Четверг', friday: 'Пятница' },
            buttons: { select_all: 'Выбрать все дни', clear_all: 'Очистить', submit: 'Зарегистрироваться на Rhapathon', submitting: 'Отправка...', confirm: 'Отлично!', ok: 'OK' },
            errors: { days_required: 'Пожалуйста, выберите хотя бы один день.', sessions_required: 'Пожалуйста, выберите хотя бы одну сессию.' },
            notes: { days_note: 'Требуется как минимум один день и одна сессия' },
            alerts: { success_title: 'Регистрация успешна!', error_title: 'Ошибка' }
        },
        zh: {
            header: {
                title: '与克里斯牧师同在的 Rhapathon',
                subtitle: '2026 年版',
                date: '2026年5月4日（周一）至5月8日（周五）',
                desc: '加入我们，一同在完成神圣使命中精炼异象。'
            },
            sections: { personal: '个人信息', church: '教会信息', participation: '参与方式', days: '日期与场次' },
            labels: {
                title: '称谓', first_name: '名', last_name: '姓', email_address: '电子邮箱', phone_number: '电话号码',
                kingschat_username: 'KingsChat 用户名', zone: '区域', group: '小组', church: '教会',
                participation_question: '您是否会在 Asese 现场参加 Rhapathon 大会？',
                select_days_sessions: '选择您的日期与场次', sessions: '场次', yes: '是', no: '否'
            },
            placeholders: { first_name: '输入名字', last_name: '输入姓氏', email: 'your.email@示例.com', phone_local: '本地号码', kingschat: '@用户名', group: '输入您的小组', church: '输入您的教会名称' },
            dropdowns: { select_title: '选择称谓', select_zone: '选择您的区域', select_code: '选择区号' },
            days: { monday: '周一', tuesday: '周二', wednesday: '周三', thursday: '周四', friday: '周五' },
            buttons: { select_all: '选择所有日期', clear_all: '清除', submit: '报名参加 Rhapathon', submitting: '提交中...', confirm: '好的！', ok: '确定' },
            errors: { days_required: '请至少选择一天参加。', sessions_required: '请至少选择一场参加。' },
            notes: { days_note: '至少需要选择一天和一场' },
            alerts: { success_title: '注册成功！', error_title: '错误' }
        },
        ja: {
            header: {
                title: 'パスター・クリスと共に行う Rhapathon',
                subtitle: '2026 年版',
                date: '2026年5月4日（月）〜5月8日（金）',
                desc: '私たちの神から与えられた使命を全うするため、ビジョンを磨く特別な時にご参加ください。'
            },
            sections: { personal: '個人情報', church: '教会情報', participation: '参加', days: '日程とセッション' },
            labels: {
                title: '敬称', first_name: '名', last_name: '姓', email_address: 'メールアドレス', phone_number: '電話番号',
                kingschat_username: 'KingsChat ユーザー名', zone: 'ゾーン', group: 'グループ', church: '教会',
                participation_question: 'Aseseで開催されるRhapathonカンファレンスに現地参加しますか？',
                select_days_sessions: '日程とセッションを選択', sessions: 'セッション', yes: 'はい', no: 'いいえ'
            },
            placeholders: { first_name: '名を入力', last_name: '姓を入力', email: 'your.email@例.com', phone_local: '市内局番', kingschat: '@ユーザー名', group: 'グループ名を入力', church: '教会名を入力' },
            dropdowns: { select_title: '敬称を選択', select_zone: 'ゾーンを選択', select_code: '国番号を選択' },
            days: { monday: '月曜日', tuesday: '火曜日', wednesday: '水曜日', thursday: '木曜日', friday: '金曜日' },
            buttons: { select_all: '全日程を選択', clear_all: 'クリア', submit: 'Rhapathon に登録', submitting: '送信中...', confirm: '了解！', ok: 'OK' },
            errors: { days_required: '少なくとも1日を選択してください。', sessions_required: '少なくとも1つのセッションを選択してください。' },
            notes: { days_note: '少なくとも1日と1セッションが必要です' },
            alerts: { success_title: '登録成功！', error_title: 'エラー' }
        },
        ko: {
            header: {
                title: '크리스 목사와 함께하는 Rhapathon',
                subtitle: '2026 에디션',
                date: '2026년 5월 4일(월) - 5월 8일(금)',
                desc: '하나님의 사명을 완수함에 있어 비전을 다듬는 특별한 시간에 함께하세요.'
            },
            sections: { personal: '개인 정보', church: '교회 정보', participation: '참여', days: '날짜 및 세션' },
            labels: {
                title: '호칭', first_name: '이름', last_name: '성', email_address: '이메일', phone_number: '전화번호',
                kingschat_username: 'KingsChat 사용자명', zone: '존', group: '그룹', church: '교회',
                participation_question: 'Asese에서 열리는 Rhapathon 컨퍼런스에 현장 참여하시겠습니까?',
                select_days_sessions: '날짜와 세션을 선택', sessions: '세션', yes: '예', no: '아니오'
            },
            placeholders: { first_name: '이름 입력', last_name: '성 입력', email: 'your.email@example.com', phone_local: '지역 번호', kingschat: '@사용자명', group: '그룹 입력', church: '교회명 입력' },
            dropdowns: { select_title: '호칭 선택', select_zone: '존 선택', select_code: '국가 코드 선택' },
            days: { monday: '월요일', tuesday: '화요일', wednesday: '수요일', thursday: '목요일', friday: '금요일' },
            buttons: { select_all: '모든 날짜 선택', clear_all: '지우기', submit: 'Rhapathon 등록', submitting: '전송 중...', confirm: '좋아요!', ok: 'OK' },
            errors: { days_required: '최소 하루 이상 선택하세요.', sessions_required: '최소 한 세션을 선택하세요.' },
            notes: { days_note: '최소 하루 및 한 세션이 필요합니다' },
            alerts: { success_title: '등록 성공!', error_title: '오류' }
        },
        hi: {
            header: {
                title: 'पास्तर क्रिस के साथ Rhapathon',
                subtitle: '2026 संस्करण',
                date: 'सोमवार 4 - शुक्रवार 8 मई, 2026',
                desc: 'हमारे दिव्य आदेश को पूरा करने में दृष्टि के परिष्कार के एक विशिष्ट समय के लिए हमारे साथ जुड़ें।'
            },
            sections: { personal: 'व्यक्तिगत जानकारी', church: 'कलीसिया की जानकारी', participation: 'भागीदारी', days: 'दिन और सत्र' },
            labels: {
                title: 'उपाधि', first_name: 'पहला नाम', last_name: 'अंतिम नाम', email_address: 'ईमेल पता', phone_number: 'फ़ोन नंबर',
                kingschat_username: 'KingsChat उपयोगकर्ता नाम', zone: 'क्षेत्र', group: 'समूह', church: 'कलीसिया',
                participation_question: 'क्या आप Asese में Rhapathon सम्मेलन में ऑन-साइट भाग लेंगे?',
                select_days_sessions: 'अपने दिन और सत्र चुनें', sessions: 'सत्र', yes: 'हाँ', no: 'नहीं'
            },
            placeholders: { first_name: 'पहला नाम दर्ज करें', last_name: 'अंतिम नाम दर्ज करें', email: 'your.email@उदाहरण.com', phone_local: 'स्थानीय नंबर', kingschat: '@उपयोगकर्ता नाम', group: 'अपना समूह दर्ज करें', church: 'अपनी कलीसिया का नाम दर्ज करें' },
            dropdowns: { select_title: 'उपाधि चुनें', select_zone: 'अपना क्षेत्र चुनें', select_code: 'कोड चुनें' },
            days: { monday: 'सोमवार', tuesday: 'मंगलवार', wednesday: 'बुधवार', thursday: 'गुरुवार', friday: 'शुक्रवार' },
            buttons: { select_all: 'सभी दिन चुनें', clear_all: 'हटाएँ', submit: 'Rhapathon के लिए पंजीकरण', submitting: 'सबमिट हो रहा है...', confirm: 'ठीक है!', ok: 'OK' },
            errors: { days_required: 'कृपया कम से कम एक दिन चुनें।', sessions_required: 'कृपया कम से कम एक सत्र चुनें।' },
            notes: { days_note: 'कम से कम एक दिन और एक सत्र आवश्यक है' },
            alerts: { success_title: 'पंजीकरण सफल!', error_title: 'त्रुटि' }
        },
        tr: {
            header: {
                title: 'Pastor Chris ile Rhapathon',
                subtitle: '2026 Baskısı',
                date: 'Pazartesi 4 - Cuma 8 Mayıs 2026',
                desc: 'İlahi görevimizi tamamlarken vizyonun arınması için olağanüstü bir zamana katılın.'
            },
            sections: { personal: 'Kişisel Bilgiler', church: 'Kilise Bilgileri', participation: 'Katılım', days: 'Günler ve Oturumlar' },
            labels: {
                title: 'Unvan', first_name: 'Ad', last_name: 'Soyad', email_address: 'E‑posta Adresi', phone_number: 'Telefon Numarası',
                kingschat_username: 'KingsChat Kullanıcı Adı', zone: 'Bölge', group: 'Grup', church: 'Kilise',
                participation_question: 'Asese’deki Rhapathon konferansına yerinde katılacak mısınız?',
                select_days_sessions: 'Günlerinizi ve oturumlarınızı seçin', sessions: 'Oturumlar', yes: 'Evet', no: 'Hayır'
            },
            placeholders: { first_name: 'Ad girin', last_name: 'Soyad girin', email: 'epostanız@ornek.com', phone_local: 'Yerel numara', kingschat: '@kullaniciadi', group: 'Grubunuzu girin', church: 'Kilise adını girin' },
            dropdowns: { select_title: 'Unvan seçin', select_zone: 'Bölgenizi seçin', select_code: 'Kod seçin' },
            days: { monday: 'Pazartesi', tuesday: 'Salı', wednesday: 'Çarşamba', thursday: 'Perşembe', friday: 'Cuma' },
            buttons: { select_all: 'Tüm günleri seç', clear_all: 'Temizle', submit: 'Rhapathon’a Kayıt Ol', submitting: 'Gönderiliyor...', confirm: 'Harika!', ok: 'OK' },
            errors: { days_required: 'Lütfen en az bir gün seçin.', sessions_required: 'Lütfen en az bir oturum seçin.' },
            notes: { days_note: 'En az bir gün ve bir oturum gerekli' },
            alerts: { success_title: 'Kayıt başarılı!', error_title: 'Hata' }
        },
        nl: {
            header: {
                title: 'Rhapathon met Pastor Chris',
                subtitle: 'Editie 2026',
                date: 'Maandag 4 - Vrijdag 8 mei 2026',
                desc: 'Doe mee aan een bijzondere tijd van het verfijnen van de visie bij het volbrengen van onze goddelijke opdracht.'
            },
            sections: { personal: 'Persoonlijke Informatie', church: 'Kerkinformatie', participation: 'Deelname', days: 'Dagen en Sessies' },
            labels: {
                title: 'Titel', first_name: 'Voornaam', last_name: 'Achternaam', email_address: 'E‑mailadres', phone_number: 'Telefoonnummer',
                kingschat_username: 'KingsChat Gebruikersnaam', zone: 'Zone', group: 'Groep', church: 'Kerk',
                participation_question: 'Neemt u ter plaatse deel aan de Rhapathon-conferentie in Asese?',
                select_days_sessions: 'Selecteer uw dagen en sessies', sessions: 'Sessies', yes: 'Ja', no: 'Nee'
            },
            placeholders: { first_name: 'Voer voornaam in', last_name: 'Voer achternaam in', email: 'uw.email@voorbeeld.com', phone_local: 'Netnummer', kingschat: '@gebruikersnaam', group: 'Voer uw groep in', church: 'Voer uw kerknamen in' },
            dropdowns: { select_title: 'Kies titel', select_zone: 'Kies uw zone', select_code: 'Kies code' },
            days: { monday: 'Maandag', tuesday: 'Dinsdag', wednesday: 'Woensdag', thursday: 'Donderdag', friday: 'Vrijdag' },
            buttons: { select_all: 'Alle dagen selecteren', clear_all: 'Wissen', submit: 'Registreren voor Rhapathon', submitting: 'Bezig met verzenden...', confirm: 'Top!', ok: 'OK' },
            errors: { days_required: 'Selecteer minimaal één dag.', sessions_required: 'Selecteer minimaal één sessie.' },
            notes: { days_note: 'Minstens één dag en één sessie vereist' },
            alerts: { success_title: 'Registratie geslaagd!', error_title: 'Fout' }
        },
        pl: {
            header: {
                title: 'Rhapathon z Pastorem Chrisem',
                subtitle: 'Wydanie 2026',
                date: 'Poniedziałek 4 – Piątek 8 maja 2026',
                desc: 'Dołącz do nas na wyjątkowy czas udoskonalania wizji w wypełnianiu naszego Bożego mandatu.'
            },
            sections: { personal: 'Informacje Osobiste', church: 'Informacje o Kościele', participation: 'Udział', days: 'Dni i Sesje' },
            labels: {
                title: 'Tytuł', first_name: 'Imię', last_name: 'Nazwisko', email_address: 'Adres e‑mail', phone_number: 'Numer telefonu',
                kingschat_username: 'Nazwa użytkownika KingsChat', zone: 'Strefa', group: 'Grupa', church: 'Kościół',
                participation_question: 'Czy będziesz uczestniczyć na miejscu w konferencji Rhapathon w Asese?',
                select_days_sessions: 'Wybierz swoje dni i sesje', sessions: 'Sesje', yes: 'Tak', no: 'Nie'
            },
            placeholders: { first_name: 'Wpisz imię', last_name: 'Wpisz nazwisko', email: 'twoj.email@przyklad.com', phone_local: 'Numer lokalny', kingschat: '@nazwa', group: 'Wpisz swoją grupę', church: 'Wpisz nazwę kościoła' },
            dropdowns: { select_title: 'Wybierz tytuł', select_zone: 'Wybierz strefę', select_code: 'Wybierz kod' },
            days: { monday: 'Poniedziałek', tuesday: 'Wtorek', wednesday: 'Środa', thursday: 'Czwartek', friday: 'Piątek' },
            buttons: { select_all: 'Wybierz wszystkie dni', clear_all: 'Wyczyść', submit: 'Zarejestruj się na Rhapathon', submitting: 'Wysyłanie...', confirm: 'Świetnie!', ok: 'OK' },
            errors: { days_required: 'Wybierz co najmniej jeden dzień.', sessions_required: 'Wybierz co najmniej jedną sesję.' },
            notes: { days_note: 'Wymagany co najmniej jeden dzień i jedna sesja' },
            alerts: { success_title: 'Rejestracja zakończona sukcesem!', error_title: 'Błąd' }
        },
        uk: {
            header: { title: 'Rhapathon з пастором Крісом', subtitle: 'Випуск 2026', date: 'Понеділок 4 – П’ятниця 8 травня 2026', desc: 'Приєднуйтеся до нас у цей надзвичайний час удосконалення бачення у виконанні нашого Божественного доручення.' },
            sections: { personal: 'Особиста інформація', church: 'Інформація про церкву', participation: 'Участь', days: 'Дні та сесії' },
            labels: { title: 'Звертання', first_name: 'Ім’я', last_name: 'Прізвище', email_address: 'Електронна пошта', phone_number: 'Номер телефону', kingschat_username: 'Ім’я користувача KingsChat', zone: 'Зона', group: 'Група', church: 'Церква', participation_question: 'Ви братимете участь офлайн на конференції Rhapathon в Асесе?', select_days_sessions: 'Виберіть ваші дні та сесії', sessions: 'Сесії', yes: 'Так', no: 'Ні' },
            placeholders: { first_name: 'Введіть ім’я', last_name: 'Введіть прізвище', email: 'ваш.email@приклад.com', phone_local: 'Місцевий номер', kingschat: '@користувач', group: 'Введіть вашу групу', church: 'Введіть назву церкви' },
            dropdowns: { select_title: 'Оберіть звертання', select_zone: 'Оберіть вашу зону', select_code: 'Оберіть код' },
            days: { monday: 'Понеділок', tuesday: 'Вівторок', wednesday: 'Середа', thursday: 'Четвер', friday: 'П’ятниця' },
            buttons: { select_all: 'Вибрати всі дні', clear_all: 'Очистити', submit: 'Зареєструватися на Rhapathon', submitting: 'Надсилання...', confirm: 'Чудово!', ok: 'OK' },
            errors: { days_required: 'Будь ласка, виберіть принаймні один день.', sessions_required: 'Будь ласка, виберіть принаймні одну сесію.' },
            notes: { days_note: 'Потрібно щонайменше один день і одна сесія' },
            alerts: { success_title: 'Реєстрація успішна!', error_title: 'Помилка' }
        },
        ro: {
            header: { title: 'Rhapathon cu Pastorul Chris', subtitle: 'Ediția 2026', date: 'Luni 4 - Vineri 8 mai 2026', desc: 'Alăturați-vă nouă pentru un timp extraordinar de rafinare a viziunii în împlinirea mandatului nostru divin.' },
            sections: { personal: 'Informații Personale', church: 'Informații Biserică', participation: 'Participare', days: 'Zile și Sesiuni' },
            labels: { title: 'Titlu', first_name: 'Prenume', last_name: 'Nume', email_address: 'Adresă de email', phone_number: 'Număr de telefon', kingschat_username: 'Utilizator KingsChat', zone: 'Zonă', group: 'Grup', church: 'Biserică', participation_question: 'Veți participa fizic la conferința Rhapathon din Asese?', select_days_sessions: 'Selectați zilele și sesiunile', sessions: 'Sesiuni', yes: 'Da', no: 'Nu' },
            placeholders: { first_name: 'Introduceți prenumele', last_name: 'Introduceți numele', email: 'emailul.tău@exemplu.com', phone_local: 'Număr local', kingschat: '@utilizator', group: 'Introduceți grupul', church: 'Introduceți numele bisericii' },
            dropdowns: { select_title: 'Selectați titlul', select_zone: 'Selectați zona', select_code: 'Selectați codul' },
            days: { monday: 'Luni', tuesday: 'Marți', wednesday: 'Miercuri', thursday: 'Joi', friday: 'Vineri' },
            buttons: { select_all: 'Selectați toate zilele', clear_all: 'Ștergeți', submit: 'Înregistrare la Rhapathon', submitting: 'Se trimite...', confirm: 'Grozav!', ok: 'OK' },
            errors: { days_required: 'Selectați cel puțin o zi.', sessions_required: 'Selectați cel puțin o sesiune.' },
            notes: { days_note: 'Este necesară cel puțin o zi și o sesiune' },
            alerts: { success_title: 'Înregistrare reușită!', error_title: 'Eroare' }
        },
        el: {
            header: { title: 'Rhapathon με τον Πάστορα Κρις', subtitle: 'Έκδοση 2026', date: 'Δευτέρα 4 - Παρασκευή 8 Μαΐου 2026', desc: 'Ελάτε μαζί μας για έναν εξαιρετικό χρόνο ευθυγράμμισης του οράματος στην ολοκλήρωση της θείας αποστολής μας.' },
            sections: { personal: 'Προσωπικές Πληροφορίες', church: 'Πληροφορίες Εκκλησίας', participation: 'Συμμετοχή', days: 'Ημέρες & Συνεδρίες' },
            labels: { title: 'Τίτλος', first_name: 'Όνομα', last_name: 'Επώνυμο', email_address: 'Ηλεκτρονικό ταχυδρομείο', phone_number: 'Αριθμός τηλεφώνου', kingschat_username: 'Όνομα χρήστη KingsChat', zone: 'Ζώνη', group: 'Ομάδα', church: 'Εκκλησία', participation_question: 'Θα συμμετάσχετε δια ζώσης στο συνέδριο Rhapathon στο Asese;', select_days_sessions: 'Επιλέξτε ημέρες και συνεδρίες', sessions: 'Συνεδρίες', yes: 'Ναι', no: 'Όχι' },
            placeholders: { first_name: 'Εισάγετε όνομα', last_name: 'Εισάγετε επώνυμο', email: 'το.email@παράδειγμα.com', phone_local: 'Τοπικός αριθμός', kingschat: '@όνομα', group: 'Εισάγετε την ομάδα σας', church: 'Εισάγετε το όνομα της εκκλησίας' },
            dropdowns: { select_title: 'Επιλέξτε τίτλο', select_zone: 'Επιλέξτε ζώνη', select_code: 'Επιλέξτε κωδικό' },
            days: { monday: 'Δευτέρα', tuesday: 'Τρίτη', wednesday: 'Τετάρτη', thursday: 'Πέμπτη', friday: 'Παρασκευή' },
            buttons: { select_all: 'Επιλογή όλων των ημερών', clear_all: 'Εκκαθάριση', submit: 'Εγγραφή στο Rhapathon', submitting: 'Αποστολή...', confirm: 'Τέλεια!', ok: 'OK' },
            errors: { days_required: 'Παρακαλώ επιλέξτε τουλάχιστον μία ημέρα.', sessions_required: 'Παρακαλώ επιλέξτε τουλάχιστον μία συνεδρία.' },
            notes: { days_note: 'Απαιτείται τουλάχιστον μία ημέρα και μία συνεδρία' },
            alerts: { success_title: 'Επιτυχής εγγραφή!', error_title: 'Σφάλμα' }
        },
        hu: {
            header: { title: 'Rhapathon Chris pásztorral', subtitle: '2026-os kiadás', date: 'Hétfő 4. – Péntek 8. 2026. május', desc: 'Csatlakozzon hozzánk egy rendkívüli időre a látás finomításában isteni megbízatásunk betöltéséhez.' },
            sections: { personal: 'Személyes adatok', church: 'Gyülekezeti adatok', participation: 'Részvétel', days: 'Napok és szekciók' },
            labels: { title: 'Megszólítás', first_name: 'Keresztnév', last_name: 'Vezetéknév', email_address: 'E-mail cím', phone_number: 'Telefonszám', kingschat_username: 'KingsChat felhasználónév', zone: 'Zóna', group: 'Csoport', church: 'Gyülekezet', participation_question: 'Személyesen részt vesz az asese-i Rhapathon konferencián?', select_days_sessions: 'Válassza ki napjait és szekcióit', sessions: 'Szekciók', yes: 'Igen', no: 'Nem' },
            placeholders: { first_name: 'Adja meg a keresztnevet', last_name: 'Adja meg a vezetéknevet', email: 'az.on.email@pelda.com', phone_local: 'Helyi szám', kingschat: '@felhasznalonev', group: 'Adja meg csoportját', church: 'Adja meg a gyülekezet nevét' },
            dropdowns: { select_title: 'Megszólítás választása', select_zone: 'Válassza ki zónáját', select_code: 'Kód választása' },
            days: { monday: 'Hétfő', tuesday: 'Kedd', wednesday: 'Szerda', thursday: 'Csütörtök', friday: 'Péntek' },
            buttons: { select_all: 'Minden nap kiválasztása', clear_all: 'Törlés', submit: 'Regisztráció a Rhapathonra', submitting: 'Küldés...', confirm: 'Nagyszerű!', ok: 'OK' },
            errors: { days_required: 'Válasszon ki legalább egy napot.', sessions_required: 'Válasszon ki legalább egy szekciót.' },
            notes: { days_note: 'Legalább egy nap és egy szekció szükséges' },
            alerts: { success_title: 'Sikeres regisztráció!', error_title: 'Hiba' }
        },
        cs: {
            header: { title: 'Rhapathon s pastorem Chrisem', subtitle: 'Vydání 2026', date: 'Pondělí 4. – Pátek 8. května 2026', desc: 'Přidejte se k nám na mimořádný čas zdokonalování vize při naplňování našeho božského poslání.' },
            sections: { personal: 'Osobní údaje', church: 'Informace o církvi', participation: 'Účast', days: 'Dny a sezení' },
            labels: { title: 'Titul', first_name: 'Jméno', last_name: 'Příjmení', email_address: 'E-mailová adresa', phone_number: 'Telefonní číslo', kingschat_username: 'Uživatelské jméno KingsChat', zone: 'Zóna', group: 'Skupina', church: 'Církev', participation_question: 'Zúčastníte se osobně konference Rhapathon v Asese?', select_days_sessions: 'Vyberte si dny a sezení', sessions: 'Sezení', yes: 'Ano', no: 'Ne' },
            placeholders: { first_name: 'Zadejte jméno', last_name: 'Zadejte příjmení', email: 'vas.email@priklad.com', phone_local: 'Místní číslo', kingschat: '@uzivatel', group: 'Zadejte svou skupinu', church: 'Zadejte název církve' },
            dropdowns: { select_title: 'Vyberte titul', select_zone: 'Vyberte zónu', select_code: 'Vyberte kód' },
            days: { monday: 'Pondělí', tuesday: 'Úterý', wednesday: 'Středa', thursday: 'Čtvrtek', friday: 'Pátek' },
            buttons: { select_all: 'Vybrat všechny dny', clear_all: 'Vymazat', submit: 'Registrovat se na Rhapathon', submitting: 'Odesílání...', confirm: 'Skvělé!', ok: 'OK' },
            errors: { days_required: 'Vyberte alespoň jeden den.', sessions_required: 'Vyberte alespoň jedno sezení.' },
            notes: { days_note: 'Vyžadován nejméně jeden den a jedno sezení' },
            alerts: { success_title: 'Registrace úspěšná!', error_title: 'Chyba' }
        },
        sk: {
            header: { title: 'Rhapathon s pastorom Chrisom', subtitle: 'Vydanie 2026', date: 'Pondelok 4. – Piatok 8. mája 2026', desc: 'Pridajte sa k nám na výnimočný čas zdokonaľovania videnia pri napĺňaní nášho božského poslania.' },
            sections: { personal: 'Osobné údaje', church: 'Informácie o cirkvi', participation: 'Účasť', days: 'Dni a sedenia' },
            labels: { title: 'Titul', first_name: 'Meno', last_name: 'Priezvisko', email_address: 'E‑mailová adresa', phone_number: 'Telefónne číslo', kingschat_username: 'Používateľské meno KingsChat', zone: 'Zóna', group: 'Skupina', church: 'Cirkev', participation_question: 'Zúčastníte sa osobne konferencie Rhapathon v Asese?', select_days_sessions: 'Vyberte si dni a sedenia', sessions: 'Sedenia', yes: 'Áno', no: 'Nie' },
            placeholders: { first_name: 'Zadajte meno', last_name: 'Zadajte priezvisko', email: 'vas.email@priklad.com', phone_local: 'Miestne číslo', kingschat: '@používateľ', group: 'Zadajte svoju skupinu', church: 'Zadajte názov cirkvi' },
            dropdowns: { select_title: 'Vyberte titul', select_zone: 'Vyberte zónu', select_code: 'Vyberte kód' },
            days: { monday: 'Pondelok', tuesday: 'Utorok', wednesday: 'Streda', thursday: 'Štvrtok', friday: 'Piatok' },
            buttons: { select_all: 'Vybrať všetky dni', clear_all: 'Vymazať', submit: 'Registrovať sa na Rhapathon', submitting: 'Odosielanie...', confirm: 'Skvelé!', ok: 'OK' },
            errors: { days_required: 'Vyberte aspoň jeden deň.', sessions_required: 'Vyberte aspoň jedno sedenie.' },
            notes: { days_note: 'Vyžaduje sa aspoň jeden deň a jedno sedenie' },
            alerts: { success_title: 'Registrácia úspešná!', error_title: 'Chyba' }
        },
        sv: {
            header: { title: 'Rhapathon med Pastor Chris', subtitle: 'Utgåva 2026', date: 'Måndag 4 – Fredag 8 maj 2026', desc: 'Följ med oss för en enastående tid av förfining av visionen i att fullborda vårt gudomliga uppdrag.' },
            sections: { personal: 'Personlig information', church: 'Kyrkinformation', participation: 'Deltagande', days: 'Dagar och sessioner' },
            labels: { title: 'Titel', first_name: 'Förnamn', last_name: 'Efternamn', email_address: 'E‑postadress', phone_number: 'Telefonnummer', kingschat_username: 'KingsChat‑användarnamn', zone: 'Zon', group: 'Grupp', church: 'Församling', participation_question: 'Kommer du att delta på plats vid Rhapathon‑konferensen i Asese?', select_days_sessions: 'Välj dina dagar och sessioner', sessions: 'Sessioner', yes: 'Ja', no: 'Nej' },
            placeholders: { first_name: 'Ange förnamn', last_name: 'Ange efternamn', email: 'din.email@exempel.com', phone_local: 'Lokalt nummer', kingschat: '@användare', group: 'Ange din grupp', church: 'Ange din församling' },
            dropdowns: { select_title: 'Välj titel', select_zone: 'Välj din zon', select_code: 'Välj kod' },
            days: { monday: 'Måndag', tuesday: 'Tisdag', wednesday: 'Onsdag', thursday: 'Torsdag', friday: 'Fredag' },
            buttons: { select_all: 'Välj alla dagar', clear_all: 'Rensa', submit: 'Registrera dig för Rhapathon', submitting: 'Skickar...', confirm: 'Toppen!', ok: 'OK' },
            errors: { days_required: 'Välj minst en dag.', sessions_required: 'Välj minst en session.' },
            notes: { days_note: 'Minst en dag och en session krävs' },
            alerts: { success_title: 'Registrering lyckades!', error_title: 'Fel' }
        },
        da: {
            header: { title: 'Rhapathon med Pastor Chris', subtitle: '2026‑udgave', date: 'Mandag 4. – Fredag 8. maj 2026', desc: 'Vær med til en enestående tid med forfining af visionen i fuldførelsen af vores guddommelige mandat.' },
            sections: { personal: 'Personlige oplysninger', church: 'Kirkens oplysninger', participation: 'Deltagelse', days: 'Dage og sessioner' },
            labels: { title: 'Titel', first_name: 'Fornavn', last_name: 'Efternavn', email_address: 'E‑mailadresse', phone_number: 'Telefonnummer', kingschat_username: 'KingsChat‑brugernavn', zone: 'Zone', group: 'Gruppe', church: 'Kirke', participation_question: 'Deltager du fysisk i Rhapathon‑konferencen i Asese?', select_days_sessions: 'Vælg dine dage og sessioner', sessions: 'Sessioner', yes: 'Ja', no: 'Nej' },
            placeholders: { first_name: 'Indtast fornavn', last_name: 'Indtast efternavn', email: 'din.email@eksempel.com', phone_local: 'Lokalt nummer', kingschat: '@brugernavn', group: 'Indtast din gruppe', church: 'Indtast kirkens navn' },
            dropdowns: { select_title: 'Vælg titel', select_zone: 'Vælg din zone', select_code: 'Vælg kode' },
            days: { monday: 'Mandag', tuesday: 'Tirsdag', wednesday: 'Onsdag', thursday: 'Torsdag', friday: 'Fredag' },
            buttons: { select_all: 'Vælg alle dage', clear_all: 'Ryd', submit: 'Tilmeld dig Rhapathon', submitting: 'Sender...', confirm: 'Fint!', ok: 'OK' },
            errors: { days_required: 'Vælg mindst én dag.', sessions_required: 'Vælg mindst én session.' },
            notes: { days_note: 'Mindst én dag og én session påkrævet' },
            alerts: { success_title: 'Registrering lykkedes!', error_title: 'Fejl' }
        },
        fi: {
            header: { title: 'Rhapathon pastori Chrisin kanssa', subtitle: '2026 painos', date: 'Maanantai 4. – Perjantai 8. toukokuuta 2026', desc: 'Liity mukaan ainutlaatuiseen aikaan, jolloin hiomme näkemystä jumalallisen tehtävämme toteuttamiseksi.' },
            sections: { personal: 'Henkilötiedot', church: 'Seurakunnan tiedot', participation: 'Osallistuminen', days: 'Päivät ja istunnot' },
            labels: { title: 'Otsikko', first_name: 'Etunimi', last_name: 'Sukunimi', email_address: 'Sähköpostiosoite', phone_number: 'Puhelinnumero', kingschat_username: 'KingsChat‑käyttäjänimi', zone: 'Alue', group: 'Ryhmä', church: 'Seurakunta', participation_question: 'Osallistutko paikan päällä Rhapathon‑konferenssiin Asesessa?', select_days_sessions: 'Valitse päivät ja istunnot', sessions: 'Istunnot', yes: 'Kyllä', no: 'Ei' },
            placeholders: { first_name: 'Anna etunimi', last_name: 'Anna sukunimi', email: 'sinun.email@esimerkki.com', phone_local: 'Paikallinen numero', kingschat: '@käyttäjänimi', group: 'Anna ryhmäsi', church: 'Anna seurakunnan nimi' },
            dropdowns: { select_title: 'Valitse otsikko', select_zone: 'Valitse alueesi', select_code: 'Valitse koodi' },
            days: { monday: 'Maanantai', tuesday: 'Tiistai', wednesday: 'Keskiviikko', thursday: 'Torstai', friday: 'Perjantai' },
            buttons: { select_all: 'Valitse kaikki päivät', clear_all: 'Tyhjennä', submit: 'Ilmoittaudu Rhapathoniin', submitting: 'Lähetetään...', confirm: 'Hyvä!', ok: 'OK' },
            errors: { days_required: 'Valitse vähintään yksi päivä.', sessions_required: 'Valitse vähintään yksi istunto.' },
            notes: { days_note: 'Vähintään yksi päivä ja yksi istunto vaaditaan' },
            alerts: { success_title: 'Ilmoittautuminen onnistui!', error_title: 'Virhe' }
        },
        nb: {
            header: { title: 'Rhapathon med Pastor Chris', subtitle: '2026‑utgave', date: 'Mandag 4. – Fredag 8. mai 2026', desc: 'Bli med oss for en ekstraordinær tid med skjerping av visjonen i fullføringen av vårt gudgitte oppdrag.' },
            sections: { personal: 'Personopplysninger', church: 'Kirkens informasjon', participation: 'Deltakelse', days: 'Dager og økter' },
            labels: { title: 'Tittel', first_name: 'Fornavn', last_name: 'Etternavn', email_address: 'E‑postadresse', phone_number: 'Telefonnummer', kingschat_username: 'KingsChat‑brukernavn', zone: 'Sone', group: 'Gruppe', church: 'Kirke', participation_question: 'Vil du delta fysisk på Rhapathon‑konferansen i Asese?', select_days_sessions: 'Velg dine dager og økter', sessions: 'Økter', yes: 'Ja', no: 'Nei' },
            placeholders: { first_name: 'Skriv inn fornavn', last_name: 'Skriv inn etternavn', email: 'din.epost@eksempel.com', phone_local: 'Lokalnummer', kingschat: '@brukernavn', group: 'Skriv inn gruppen din', church: 'Skriv inn kirkens navn' },
            dropdowns: { select_title: 'Velg tittel', select_zone: 'Velg din sone', select_code: 'Velg kode' },
            days: { monday: 'Mandag', tuesday: 'Tirsdag', wednesday: 'Onsdag', thursday: 'Torsdag', friday: 'Fredag' },
            buttons: { select_all: 'Velg alle dager', clear_all: 'Tøm', submit: 'Registrer deg for Rhapathon', submitting: 'Sender...', confirm: 'Flott!', ok: 'OK' },
            errors: { days_required: 'Velg minst én dag.', sessions_required: 'Velg minst én økt.' },
            notes: { days_note: 'Minst én dag og én økt kreves' },
            alerts: { success_title: 'Registrering vellykket!', error_title: 'Feil' }
        },
        // Add translations for new African and other languages
        sw: {
            header: { title: 'Rhapathon na Mchungaji Chris', subtitle: 'Toleo la 2026', date: 'Jumatatu 4 - Ijumaa 8 Mei 2026', desc: 'Jiunge nasi kwa wakati wa kipekee wa kuboresha maono katika kukamilisha wajibu wetu wa kimungu.' },
            sections: { personal: 'Maelezo ya Kibinafsi', church: 'Maelezo ya Kanisa', participation: 'Kushiriki', days: 'Siku na Vikao' },
            labels: { title: 'Cheo', first_name: 'Jina la Kwanza', last_name: 'Jina la Mwisho', email_address: 'Anwani ya Barua pepe', phone_number: 'Nambari ya Simu', kingschat_username: 'Jina la Mtumiaji wa KingsChat', zone: 'Eneo', group: 'Kikundi', church: 'Kanisa', participation_question: 'Je, utashiriki mahali hapo kwenye mkutano wa Rhapathon huko Asese?', select_days_sessions: 'Chagua siku na vikao vyako', sessions: 'Vikao', yes: 'Ndiyo', no: 'Hapana' },
            placeholders: { first_name: 'Ingiza jina la kwanza', last_name: 'Ingiza jina la mwisho', email: 'barua.pepe@mfano.com', phone_local: 'Nambari ya ndani', kingschat: '@jina_la_mtumiaji', group: 'Ingiza kikundi chako', church: 'Ingiza jina la kanisa lako' },
            dropdowns: { select_title: 'Chagua cheo', select_zone: 'Chagua eneo lako', select_code: 'Chagua msimbo' },
            days: { monday: 'Jumatatu', tuesday: 'Jumanne', wednesday: 'Jumatano', thursday: 'Alhamisi', friday: 'Ijumaa' },
            buttons: { select_all: 'Chagua siku zote', clear_all: 'Futa', submit: 'Jiandikishe kwa Rhapathon', submitting: 'Inatuma...', confirm: 'Vizuri!', ok: 'Sawa' },
            errors: { days_required: 'Tafadhali chagua angalau siku moja.', sessions_required: 'Tafadhali chagua angalau kikao kimoja.' },
            notes: { days_note: 'Angalau siku moja na kikao kimoja kinahitajika' },
            alerts: { success_title: 'Usajili umefanikiwa!', error_title: 'Hitilafu' }
        },
        yo: {
            header: { title: 'Rhapathon pẹ̀lú Pastor Chris', subtitle: 'Ẹ̀dà 2026', date: 'Ọjọ́ Ajé 4 - Ọjọ́ Ẹtì 8 Oṣù Karùn-ún 2026', desc: 'Darapọ̀ mọ́ wa fún àkókò àkànṣe láti tún ìran náà ṣe nínú ìparí àṣẹ ọlọ́run wa.' },
            sections: { personal: 'Àlàyé Ti Ara Ẹni', church: 'Àlàyé Ìjọ', participation: 'Ìkópa', days: 'Àwọn Ọjọ́ àti Àwọn Ìpàdé' },
            labels: { title: 'Ọ̀yẹ', first_name: 'Orúkọ Àkọ́kọ́', last_name: 'Orúkọ Ìdílé', email_address: 'Àdírẹ́ẹ̀sì Ímeèlì', phone_number: 'Nọ́mbà Fóònù', kingschat_username: 'Orúkọ Òǹlò KingsChat', zone: 'Agbègbè', group: 'Ẹgbẹ́', church: 'Ìjọ', participation_question: 'Ṣé o máa kópa níbẹ̀ fún àpéjọ Rhapathon ní Asese?', select_days_sessions: 'Yan àwọn ọjọ́ àti àwọn ìpàdé rẹ', sessions: 'Àwọn Ìpàdé', yes: 'Bẹ́ẹ̀ni', no: 'Bẹ́ẹ̀kọ́' },
            placeholders: { first_name: 'Tẹ orúkọ àkọ́kọ́', last_name: 'Tẹ orúkọ ìdílé', email: 'ímeèlì.rẹ@àpẹẹrẹ.com', phone_local: 'Nọ́mbà agbègbè', kingschat: '@orúkọ_òǹlò', group: 'Tẹ ẹgbẹ́ rẹ', church: 'Tẹ orúkọ ìjọ rẹ' },
            dropdowns: { select_title: 'Yan ọ̀yẹ', select_zone: 'Yan agbègbè rẹ', select_code: 'Yan kóòdù' },
            days: { monday: 'Ọjọ́ Ajé', tuesday: 'Ọjọ́ Ìsẹ́gun', wednesday: 'Ọjọ́rú', thursday: 'Ọjọ́bọ', friday: 'Ọjọ́ Ẹtì' },
            buttons: { select_all: 'Yan gbogbo ọjọ́', clear_all: 'Parẹ́', submit: 'Forúkọsílẹ̀ fún Rhapathon', submitting: 'Ń rán lọ...', confirm: 'Ó dára!', ok: 'Ó dára' },
            errors: { days_required: 'Jọ̀wọ́ yan ọjọ́ kan tó kéré tán.', sessions_required: 'Jọ̀wọ́ yan ìpàdé kan tó kéré tán.' },
            notes: { days_note: 'Ọjọ́ kan tó kéré tán àti ìpàdé kan ṣe pàtàkì' },
            alerts: { success_title: 'Ìforúkọsílẹ̀ ti yọrí sí rere!', error_title: 'Àṣìṣe' }
        },
        ig: {
            header: { title: 'Rhapathon na Pastor Chris', subtitle: 'Mbipụta 2026', date: 'Mọnde 4 - Fraịde 8 Mee 2026', desc: 'Sonyere anyị maka oge pụrụ iche nke imeziwanye ọhụụ na imezu ọrụ Chineke nyere anyị.' },
            sections: { personal: 'Ozi Onwe Onye', church: 'Ozi Ụka', participation: 'Isonye', days: 'Ụbọchị na Nnọkọ' },
            labels: { title: 'Aha Ọkwa', first_name: 'Aha Mbụ', last_name: 'Aha Ikpeazụ', email_address: 'Adreesị Email', phone_number: 'Nọmba Ekwentị', kingschat_username: 'Aha Onye Ọrụ KingsChat', zone: 'Mpaghara', group: 'Otu', church: 'Ụka', participation_question: 'Ị ga-esonye na ebe a maka nnọkọ Rhapathon na Asese?', select_days_sessions: 'Họrọ ụbọchị na nnọkọ gị', sessions: 'Nnọkọ', yes: 'Ee', no: 'Mba' },
            placeholders: { first_name: 'Tinye aha mbụ', last_name: 'Tinye aha ikpeazụ', email: 'email.gị@ọmụmaatụ.com', phone_local: 'Nọmba mpaghara', kingschat: '@aha_onye_ọrụ', group: 'Tinye otu gị', church: 'Tinye aha ụka gị' },
            dropdowns: { select_title: 'Họrọ aha ọkwa', select_zone: 'Họrọ mpaghara gị', select_code: 'Họrọ koodu' },
            days: { monday: 'Mọnde', tuesday: 'Tuzde', wednesday: 'Wenezde', thursday: 'Tọzde', friday: 'Fraịde' },
            buttons: { select_all: 'Họrọ ụbọchị niile', clear_all: 'Kpochaa', submit: 'Debanye aha maka Rhapathon', submitting: 'Na-eziga...', confirm: 'Ọ dị mma!', ok: 'Ọ dị mma' },
            errors: { days_required: 'Biko họrọ ma ọ dịkarịa ala otu ụbọchị.', sessions_required: 'Biko họrọ ma ọ dịkarịa ala otu nnọkọ.' },
            notes: { days_note: 'Ma ọ dịkarịa ala otu ụbọchị na otu nnọkọ ka achọrọ' },
            alerts: { success_title: 'Ndebanye aha gara nke ọma!', error_title: 'Njehie' }
        },
        ha: {
            header: { title: 'Rhapathon da Pastor Chris', subtitle: 'Bugu na 2026', date: 'Litinin 4 - Jumma\'a 8 Mayu 2026', desc: 'Ku haɗu da mu don lokaci na musamman na gyare-gyaren hangen nesa wajen kammala aikin Allah da ya ba mu.' },
            sections: { personal: 'Bayanan Mutum', church: 'Bayanan Coci', participation: 'Shiga', days: 'Kwanaki da Tarurruka' },
            labels: { title: 'Lakabi', first_name: 'Suna na Farko', last_name: 'Suna na Ƙarshe', email_address: 'Adireshin Email', phone_number: 'Lambar Waya', kingschat_username: 'Sunan Mai amfani da KingsChat', zone: 'Yanki', group: 'Ƙungiya', church: 'Coci', participation_question: 'Za ku shiga a wurin taron Rhapathon a Asese?', select_days_sessions: 'Zaɓi kwanakinku da tarurruka', sessions: 'Tarurruka', yes: 'I', no: 'A\'a' },
            placeholders: { first_name: 'Shigar da suna na farko', last_name: 'Shigar da suna na ƙarshe', email: 'email.ku@misali.com', phone_local: 'Lambar gida', kingschat: '@sunan_mai_amfani', group: 'Shigar da ƙungiyarku', church: 'Shigar da sunan coci' },
            dropdowns: { select_title: 'Zaɓi lakabi', select_zone: 'Zaɓi yankinku', select_code: 'Zaɓi lambar' },
            days: { monday: 'Litinin', tuesday: 'Talata', wednesday: 'Laraba', thursday: 'Alhamis', friday: 'Jumma\'a' },
            buttons: { select_all: 'Zaɓi dukan kwanaki', clear_all: 'Share', submit: 'Yi rajista don Rhapathon', submitting: 'Ana aikawa...', confirm: 'Kyakkyawa!', ok: 'To' },
            errors: { days_required: 'Da fatan za a zaɓi aƙalla kwana ɗaya.', sessions_required: 'Da fatan za a zaɓi aƙalla taro ɗaya.' },
            notes: { days_note: 'Ana buƙatar aƙalla kwana ɗaya da taro ɗaya' },
            alerts: { success_title: 'Rajista ta yi nasara!', error_title: 'Kuskure' }
        },
        am: {
            header: { title: 'ራፓቶን ከፓስተር ክርስቶስ ጋር', subtitle: '2026 እትም', date: 'ሰኞ 4 - ዓርብ 8 ሜይ 2026', desc: 'የእግዚአብሔርን ትእዛዝ ለመፈጸም እይታችንን ለማጥራት ያለውን ልዩ ጊዜ ተቀላቀሉን።' },
            sections: { personal: 'የግል መረጃ', church: 'የቤተክርስቲያን መረጃ', participation: 'ተሳትፎ', days: 'ቀናት እና ክፍለ ጊዜዎች' },
            labels: { title: 'ማዕረግ', first_name: 'የመጀመሪያ ስም', last_name: 'የአባት ስም', email_address: 'የኢሜል አድራሻ', phone_number: 'የስልክ ቁጥር', kingschat_username: 'የኪንግስቻት ተጠቃሚ ስም', zone: 'ዞን', group: 'ቡድን', church: 'ቤተክርስቲያን', participation_question: 'በአሴሴ በሚካሄደው የራፓቶን ጉባኤ በአካል ይሳተፋሉ?', select_days_sessions: 'ቀናቶችዎን እና ክፍለ ጊዜዎችዎን ይምረጡ', sessions: 'ክፍለ ጊዜዎች', yes: 'አዎ', no: 'አይደለም' },
            placeholders: { first_name: 'የመጀመሪያ ስም ያስገቡ', last_name: 'የአባት ስም ያስገቡ', email: 'የእርስዎ.ኢሜል@ምሳሌ.com', phone_local: 'የአካባቢ ቁጥር', kingschat: '@ተጠቃሚ_ስም', group: 'ቡድንዎን ያስገቡ', church: 'የቤተክርስቲያን ስም ያስገቡ' },
            dropdowns: { select_title: 'ማዕረግ ይምረጡ', select_zone: 'ዞንዎን ይምረጡ', select_code: 'ኮድ ይምረጡ' },
            days: { monday: 'ሰኞ', tuesday: 'ማክሰኞ', wednesday: 'ረቡዕ', thursday: 'ሐሙስ', friday: 'ዓርብ' },
            buttons: { select_all: 'ሁሉንም ቀናት ይምረጡ', clear_all: 'አጽዳ', submit: 'ለራፓቶን ይመዝገቡ', submitting: 'በመላክ ላይ...', confirm: 'በጣም ጥሩ!', ok: 'እሺ' },
            errors: { days_required: 'እባክዎን ቢያንስ አንድ ቀን ይምረጡ።', sessions_required: 'እባክዎን ቢያንስ አንድ ክፍለ ጊዜ ይምረጡ።' },
            notes: { days_note: 'ቢያንስ አንድ ቀን እና አንድ ክፍለ ጊዜ ያስፈልጋል' },
            alerts: { success_title: 'ምዝገባ ተሳክቷል!', error_title: 'ስህተት' }
        },
        zu: {
            header: { title: 'I-Rhapathon no-Pastor Chris', subtitle: 'Ushicilelo luka-2026', date: 'ngoMsombuluko 4 - ngoLwesihlanu 8 Meyi 2026', desc: 'Zijoyine nathi esikhathini esikhethekile sokucwenga umbono ekupheleliseni umsebenzi wethu ongcwele.' },
            sections: { personal: 'Ulwazi Lomuntu Siqu', church: 'Ulwazi Lwebandla', participation: 'Ukubamba Iqhaza', days: 'Izinsuku Nezikhathi' },
            labels: { title: 'Isihloko', first_name: 'Igama Lokuqala', last_name: 'Isibongo', email_address: 'Ikheli le-Email', phone_number: 'Inombolo Yocingo', kingschat_username: 'Igama Lomsebenzisi we-KingsChat', zone: 'Indawo', group: 'Iqembu', church: 'Ibandla', participation_question: 'Ingabe uzobamba iqhaza lapho enkomfeni ye-Rhapathon e-Asese?', select_days_sessions: 'Khetha izinsuku zakho nezikhathi', sessions: 'Izikhathi', yes: 'Yebo', no: 'Cha' },
            placeholders: { first_name: 'Faka igama lokuqala', last_name: 'Faka isibongo', email: 'i-email.yakho@isibonelo.com', phone_local: 'Inombolo yendawo', kingschat: '@igama_lomsebenzisi', group: 'Faka iqembu lakho', church: 'Faka igama lebandla' },
            dropdowns: { select_title: 'Khetha isihloko', select_zone: 'Khetha indawo yakho', select_code: 'Khetha ikhodi' },
            days: { monday: 'uMsombuluko', tuesday: 'uLwesibili', wednesday: 'uLwesithathu', thursday: 'uLwesine', friday: 'uLwesihlanu' },
            buttons: { select_all: 'Khetha zonke izinsuku', clear_all: 'Sula', submit: 'Bhalisa ku-Rhapathon', submitting: 'Kuyathunyelwa...', confirm: 'Kuhle!', ok: 'Kulungile' },
            errors: { days_required: 'Sicela ukhethe okungenani usuku olulodwa.', sessions_required: 'Sicela ukhethe okungenani isikhathi esisodwa.' },
            notes: { days_note: 'Kudingeka okungenani usuku olulodwa nesikhathi esisodwa' },
            alerts: { success_title: 'Ukubhalisa kuphumelele!', error_title: 'Iphutha' }
        },
        af: {
            header: { title: 'Rhapathon met Pastor Chris', subtitle: '2026 Uitgawe', date: 'Maandag 4 - Vrydag 8 Mei 2026', desc: 'Sluit by ons aan vir \'n buitengewone tyd van verfyning van visie in die volbrenging van ons goddelike mandaat.' },
            sections: { personal: 'Persoonlike Inligting', church: 'Kerk Inligting', participation: 'Deelname', days: 'Dae en Sessies' },
            labels: { title: 'Titel', first_name: 'Voornaam', last_name: 'Van', email_address: 'E-pos Adres', phone_number: 'Telefoonnommer', kingschat_username: 'KingsChat Gebruikersnaam', zone: 'Sone', group: 'Groep', church: 'Kerk', participation_question: 'Sal jy ter plaatse deelneem aan die Rhapathon konferensie by Asese?', select_days_sessions: 'Kies jou dae en sessies', sessions: 'Sessies', yes: 'Ja', no: 'Nee' },
            placeholders: { first_name: 'Voer voornaam in', last_name: 'Voer van in', email: 'jou.epos@voorbeeld.com', phone_local: 'Plaaslike nommer', kingschat: '@gebruikersnaam', group: 'Voer jou groep in', church: 'Voer kerknaam in' },
            dropdowns: { select_title: 'Kies titel', select_zone: 'Kies jou sone', select_code: 'Kies kode' },
            days: { monday: 'Maandag', tuesday: 'Dinsdag', wednesday: 'Woensdag', thursday: 'Donderdag', friday: 'Vrydag' },
            buttons: { select_all: 'Kies alle dae', clear_all: 'Maak skoon', submit: 'Registreer vir Rhapathon', submitting: 'Besig om te stuur...', confirm: 'Uitstekend!', ok: 'Reg' },
            errors: { days_required: 'Kies asseblief ten minste een dag.', sessions_required: 'Kies asseblief ten minste een sessie.' },
            notes: { days_note: 'Ten minste een dag en een sessie word vereis' },
            alerts: { success_title: 'Registrasie suksesvol!', error_title: 'Fout' }
        },
        th: {
            header: { title: 'Rhapathon กับ Pastor Chris', subtitle: 'รุ่น 2026', date: 'วันจันทร์ที่ 4 - วันศุกร์ที่ 8 พฤษภาคม 2026', desc: 'ร่วมกับเราในเวลาพิเศษแห่งการปรับปรุงวิสัยทัศน์ในการทำให้พันธกิจศักดิ์สิทธิ์ของเราสำเร็จ' },
            sections: { personal: 'ข้อมูลส่วนตัว', church: 'ข้อมูลโบสถ์', participation: 'การเข้าร่วม', days: 'วันและช่วงเวลา' },
            labels: { title: 'คำนำหน้า', first_name: 'ชื่อ', last_name: 'นามสกุล', email_address: 'ที่อยู่อีเมล', phone_number: 'หมายเลขโทรศัพท์', kingschat_username: 'ชื่อผู้ใช้ KingsChat', zone: 'โซน', group: 'กลุ่ม', church: 'โบสถ์', participation_question: 'คุณจะเข้าร่วมงานประชุม Rhapathon ที่ Asese ด้วยตนเองหรือไม่?', select_days_sessions: 'เลือกวันและช่วงเวลาของคุณ', sessions: 'ช่วงเวลา', yes: 'ใช่', no: 'ไม่' },
            placeholders: { first_name: 'ใส่ชื่อ', last_name: 'ใส่นามสกุล', email: 'อีเมล.ของคุณ@ตัวอย่าง.com', phone_local: 'หมายเลขในท้องถิ่น', kingschat: '@ชื่อผู้ใช้', group: 'ใส่กลุ่มของคุณ', church: 'ใส่ชื่อโบสถ์' },
            dropdowns: { select_title: 'เลือกคำนำหน้า', select_zone: 'เลือกโซนของคุณ', select_code: 'เลือกรหัส' },
            days: { monday: 'วันจันทร์', tuesday: 'วันอังคาร', wednesday: 'วันพุธ', thursday: 'วันพฤหัสบดี', friday: 'วันศุกร์' },
            buttons: { select_all: 'เลือกทุกวัน', clear_all: 'ล้าง', submit: 'ลงทะเบียนสำหรับ Rhapathon', submitting: 'กำลังส่ง...', confirm: 'ดีเยี่ยม!', ok: 'ตกลง' },
            errors: { days_required: 'กรุณาเลือกอย่างน้อยหนึ่งวัน', sessions_required: 'กรุณาเลือกอย่างน้อยหนึ่งช่วงเวลา' },
            notes: { days_note: 'ต้องการอย่างน้อยหนึ่งวันและหนึ่งช่วงเวลา' },
            alerts: { success_title: 'ลงทะเบียนสำเร็จ!', error_title: 'ข้อผิดพลาด' }
        },
        vi: {
            header: { title: 'Rhapathon với Mục sư Chris', subtitle: 'Phiên bản 2026', date: 'Thứ Hai 4 - Thứ Sáu 8 tháng 5 năm 2026', desc: 'Hãy tham gia cùng chúng tôi trong thời gian đặc biệt tinh luyện tầm nhìn để hoàn thành sứ mệnh thiêng liêng của chúng ta.' },
            sections: { personal: 'Thông Tin Cá Nhân', church: 'Thông Tin Nhà Thờ', participation: 'Tham Gia', days: 'Ngày và Phiên' },
            labels: { title: 'Danh Hiệu', first_name: 'Tên', last_name: 'Họ', email_address: 'Địa Chỉ Email', phone_number: 'Số Điện Thoại', kingschat_username: 'Tên Người Dùng KingsChat', zone: 'Khu Vực', group: 'Nhóm', church: 'Nhà Thờ', participation_question: 'Bạn có tham gia trực tiếp hội nghị Rhapathon tại Asese không?', select_days_sessions: 'Chọn ngày và phiên của bạn', sessions: 'Phiên', yes: 'Có', no: 'Không' },
            placeholders: { first_name: 'Nhập tên', last_name: 'Nhập họ', email: 'email.của.bạn@vídụ.com', phone_local: 'Số địa phương', kingschat: '@tên_người_dùng', group: 'Nhập nhóm của bạn', church: 'Nhập tên nhà thờ' },
            dropdowns: { select_title: 'Chọn danh hiệu', select_zone: 'Chọn khu vực của bạn', select_code: 'Chọn mã' },
            days: { monday: 'Thứ Hai', tuesday: 'Thứ Ba', wednesday: 'Thứ Tư', thursday: 'Thứ Năm', friday: 'Thứ Sáu' },
            buttons: { select_all: 'Chọn tất cả các ngày', clear_all: 'Xóa', submit: 'Đăng ký cho Rhapathon', submitting: 'Đang gửi...', confirm: 'Tuyệt vời!', ok: 'OK' },
            errors: { days_required: 'Vui lòng chọn ít nhất một ngày.', sessions_required: 'Vui lòng chọn ít nhất một phiên.' },
            notes: { days_note: 'Cần ít nhất một ngày và một phiên' },
            alerts: { success_title: 'Đăng ký thành công!', error_title: 'Lỗi' }
        },
        // Add 10 more languages - Middle Eastern and South Asian
        he: {
            header: { title: 'רפתון עם הפסטור כריס', subtitle: 'מהדורת 2026', date: 'יום שני 4 - יום שישי 8 במאי 2026', desc: 'הצטרפו אלינו לזמן יוצא דופן של חידוד החזון בהשלמת המנדט האלוהי שלנו.' },
            sections: { personal: 'מידע אישי', church: 'מידע כנסייה', participation: 'השתתפות', days: 'ימים ומפגשים' },
            labels: { title: 'תואר', first_name: 'שם פרטי', last_name: 'שם משפחה', email_address: 'כתובת אימייל', phone_number: 'מספר טלפון', kingschat_username: 'שם משתמש KingsChat', zone: 'אזור', group: 'קבוצה', church: 'כנסייה', participation_question: 'האם תשתתפו במקום בוועידת רפתון באסזה?', select_days_sessions: 'בחרו את הימים והמפגשים שלכם', sessions: 'מפגשים', yes: 'כן', no: 'לא' },
            placeholders: { first_name: 'הזינו שם פרטי', last_name: 'הזינו שם משפחה', email: 'האימייל.שלכם@דוגמה.com', phone_local: 'מספר מקומי', kingschat: '@שם_משתמש', group: 'הזינו את הקבוצה שלכם', church: 'הזינו שם הכנסייה' },
            dropdowns: { select_title: 'בחרו תואר', select_zone: 'בחרו את האזור שלכם', select_code: 'בחרו קוד' },
            days: { monday: 'יום שני', tuesday: 'יום שלישי', wednesday: 'יום רביעי', thursday: 'יום חמישי', friday: 'יום שישי' },
            buttons: { select_all: 'בחרו את כל הימים', clear_all: 'נקו', submit: 'הרשמו לרפתון', submitting: 'שולח...', confirm: 'מעולה!', ok: 'אישור' },
            errors: { days_required: 'אנא בחרו לפחות יום אחד.', sessions_required: 'אנא בחרו לפחות מפגש אחד.' },
            notes: { days_note: 'נדרש לפחות יום אחד ומפגש אחד' },
            alerts: { success_title: 'הרשמה הצליחה!', error_title: 'שגיאה' }
        },
        fa: {
            header: { title: 'رپاتون با پاستور کریس', subtitle: 'نسخه ۲۰۲۶', date: 'دوشنبه ۴ - جمعه ۸ مه ۲۰۲۶', desc: 'به ما بپیوندید برای زمانی فوق‌العاده از تصفیه بینش در تکمیل مأموریت الهی‌مان.' },
            sections: { personal: 'اطلاعات شخصی', church: 'اطلاعات کلیسا', participation: 'مشارکت', days: 'روزها و جلسات' },
            labels: { title: 'عنوان', first_name: 'نام', last_name: 'نام خانوادگی', email_address: 'آدرس ایمیل', phone_number: 'شماره تلفن', kingschat_username: 'نام کاربری KingsChat', zone: 'منطقه', group: 'گروه', church: 'کلیسا', participation_question: 'آیا در محل در کنفرانس رپاتون در آسسه شرکت خواهید کرد؟', select_days_sessions: 'روزها و جلسات خود را انتخاب کنید', sessions: 'جلسات', yes: 'بله', no: 'خیر' },
            placeholders: { first_name: 'نام را وارد کنید', last_name: 'نام خانوادگی را وارد کنید', email: 'ایمیل.شما@مثال.com', phone_local: 'شماره محلی', kingschat: '@نام_کاربری', group: 'گروه خود را وارد کنید', church: 'نام کلیسا را وارد کنید' },
            dropdowns: { select_title: 'عنوان انتخاب کنید', select_zone: 'منطقه خود را انتخاب کنید', select_code: 'کد انتخاب کنید' },
            days: { monday: 'دوشنبه', tuesday: 'سه‌شنبه', wednesday: 'چهارشنبه', thursday: 'پنج‌شنبه', friday: 'جمعه' },
            buttons: { select_all: 'همه روزها را انتخاب کنید', clear_all: 'پاک کردن', submit: 'ثبت‌نام در رپاتون', submitting: 'در حال ارسال...', confirm: 'عالی!', ok: 'تأیید' },
            errors: { days_required: 'لطفاً حداقل یک روز انتخاب کنید.', sessions_required: 'لطفاً حداقل یک جلسه انتخاب کنید.' },
            notes: { days_note: 'حداقل یک روز و یک جلسه مورد نیاز است' },
            alerts: { success_title: 'ثبت‌نام موفق!', error_title: 'خطا' }
        },
        ur: {
            header: { title: 'پاسٹر کرس کے ساتھ رپاتھون', subtitle: '2026 ایڈیشن', date: 'پیر 4 - جمعہ 8 مئی 2026', desc: 'ہمارے الہی مینڈیٹ کی تکمیل میں وژن کی بہتری کے لیے ایک غیر معمولی وقت کے لیے ہمارے ساتھ شامل ہوں۔' },
            sections: { personal: 'ذاتی معلومات', church: 'چرچ کی معلومات', participation: 'شرکت', days: 'دن اور سیشنز' },
            labels: { title: 'ٹائٹل', first_name: 'پہلا نام', last_name: 'آخری نام', email_address: 'ای میل ایڈریس', phone_number: 'فون نمبر', kingschat_username: 'KingsChat صارف نام', zone: 'زون', group: 'گروپ', church: 'چرچ', participation_question: 'کیا آپ آسیسے میں رپاتھون کانفرنس میں موقع پر شرکت کریں گے؟', select_days_sessions: 'اپنے دن اور سیشنز منتخب کریں', sessions: 'سیشنز', yes: 'ہاں', no: 'نہیں' },
            placeholders: { first_name: 'پہلا نام درج کریں', last_name: 'آخری نام درج کریں', email: 'آپ_کا.ایمیل@مثال.com', phone_local: 'مقامی نمبر', kingschat: '@صارف_نام', group: 'اپنا گروپ درج کریں', church: 'چرچ کا نام درج کریں' },
            dropdowns: { select_title: 'ٹائٹل منتخب کریں', select_zone: 'اپنا زون منتخب کریں', select_code: 'کوڈ منتخب کریں' },
            days: { monday: 'پیر', tuesday: 'منگل', wednesday: 'بدھ', thursday: 'جمعرات', friday: 'جمعہ' },
            buttons: { select_all: 'تمام دن منتخب کریں', clear_all: 'صاف کریں', submit: 'رپاتھون کے لیے رجسٹر کریں', submitting: 'بھیجا جا رہا ہے...', confirm: 'بہترین!', ok: 'ٹھیک ہے' },
            errors: { days_required: 'براہ کرم کم از کم ایک دن منتخب کریں۔', sessions_required: 'براہ کرم کم از کم ایک سیشن منتخب کریں۔' },
            notes: { days_note: 'کم از کم ایک دن اور ایک سیشن درکار ہے' },
            alerts: { success_title: 'رجسٹریشن کامیاب!', error_title: 'خرابی' }
        },
        bn: {
            header: { title: 'পাস্টর ক্রিসের সাথে র‍্যাপাথন', subtitle: '২০২৬ সংস্করণ', date: 'সোমবার ৪ - শুক্রবার ৮ মে ২০২৬', desc: 'আমাদের ঐশ্বরিক আদেশ সম্পূর্ণ করার ক্ষেত্রে দৃষ্টিভঙ্গির পরিশোধনের একটি অসাধারণ সময়ের জন্য আমাদের সাথে যোগ দিন।' },
            sections: { personal: 'ব্যক্তিগত তথ্য', church: 'চার্চের তথ্য', participation: 'অংশগ্রহণ', days: 'দিন এবং সেশন' },
            labels: { title: 'উপাধি', first_name: 'প্রথম নাম', last_name: 'শেষ নাম', email_address: 'ইমেইল ঠিকানা', phone_number: 'ফোন নম্বর', kingschat_username: 'KingsChat ব্যবহারকারীর নাম', zone: 'অঞ্চল', group: 'গ্রুপ', church: 'চার্চ', participation_question: 'আপনি কি আসেসে র‍্যাপাথন সম্মেলনে স্থানীয়ভাবে অংশগ্রহণ করবেন?', select_days_sessions: 'আপনার দিন এবং সেশন নির্বাচন করুন', sessions: 'সেশন', yes: 'হ্যাঁ', no: 'না' },
            placeholders: { first_name: 'প্রথম নাম লিখুন', last_name: 'শেষ নাম লিখুন', email: 'আপনার.ইমেইল@উদাহরণ.com', phone_local: 'স্থানীয় নম্বর', kingschat: '@ব্যবহারকারী_নাম', group: 'আপনার গ্রুপ লিখুন', church: 'চার্চের নাম লিখুন' },
            dropdowns: { select_title: 'উপাধি নির্বাচন করুন', select_zone: 'আপনার অঞ্চল নির্বাচন করুন', select_code: 'কোড নির্বাচন করুন' },
            days: { monday: 'সোমবার', tuesday: 'মঙ্গলবার', wednesday: 'বুধবার', thursday: 'বৃহস্পতিবার', friday: 'শুক্রবার' },
            buttons: { select_all: 'সব দিন নির্বাচন করুন', clear_all: 'পরিষ্কার করুন', submit: 'র‍্যাপাথনের জন্য নিবন্ধন করুন', submitting: 'পাঠানো হচ্ছে...', confirm: 'চমৎকার!', ok: 'ঠিক আছে' },
            errors: { days_required: 'অনুগ্রহ করে অন্তত একটি দিন নির্বাচন করুন।', sessions_required: 'অনুগ্রহ করে অন্তত একটি সেশন নির্বাচন করুন।' },
            notes: { days_note: 'অন্তত একটি দিন এবং একটি সেশন প্রয়োজন' },
            alerts: { success_title: 'নিবন্ধন সফল!', error_title: 'ত্রুটি' }
        },
        ta: {
            header: { title: 'பாஸ்டர் கிறிஸ்துடன் ராபதான்', subtitle: '2026 பதிப்பு', date: 'திங்கள் 4 - வெள்ளி 8 மே 2026', desc: 'நமது தெய்வீக ஆணையை முடிப்பதில் பார்வையை சுத்திகரிக்கும் அசாதாரண நேரத்திற்கு எங்களுடன் சேருங்கள்.' },
            sections: { personal: 'தனிப்பட்ட தகவல்', church: 'சர்ச் தகவல்', participation: 'பங்கேற்பு', days: 'நாட்கள் மற்றும் அமர்வுகள்' },
            labels: { title: 'பட்டம்', first_name: 'முதல் பெயர்', last_name: 'கடைசி பெயர்', email_address: 'மின்னஞ்சல் முகவரி', phone_number: 'தொலைபேசி எண்', kingschat_username: 'KingsChat பயனர் பெயர்', zone: 'மண்டலம்', group: 'குழு', church: 'சர்ச்', participation_question: 'நீங்கள் ஆசேஸில் உள்ள ராபதான் மாநாட்டில் நேரில் பங்கேற்பீர்களா?', select_days_sessions: 'உங்கள் நாட்கள் மற்றும் அமர்வுகளை தேர்ந்தெடுக்கவும்', sessions: 'அமர்வுகள்', yes: 'ஆம்', no: 'இல்லை' },
            placeholders: { first_name: 'முதல் பெயரை உள்ளிடவும்', last_name: 'கடைசி பெயரை உள்ளிடவும்', email: 'உங்கள்.மின்னஞ்சல்@எடுத்துக்காட்டு.com', phone_local: 'உள்ளூர் எண்', kingschat: '@பயனர்_பெயர்', group: 'உங்கள் குழுவை உள்ளிடவும்', church: 'சர்ச் பெயரை உள்ளிடவும்' },
            dropdowns: { select_title: 'பட்டத்தை தேர்ந்தெடுக்கவும்', select_zone: 'உங்கள் மண்டலத்தை தேர்ந்தெடுக்கவும்', select_code: 'குறியீட்டை தேர்ந்தெடுக்கவும்' },
            days: { monday: 'திங்கள்', tuesday: 'செவ்வாய்', wednesday: 'புதன்', thursday: 'வியாழன்', friday: 'வெள்ளி' },
            buttons: { select_all: 'எல்லா நாட்களையும் தேர்ந்தெடுக்கவும்', clear_all: 'அழிக்கவும்', submit: 'ராபதானுக்கு பதிவு செய்யவும்', submitting: 'அனுப்பப்படுகிறது...', confirm: 'அருமை!', ok: 'சரி' },
            errors: { days_required: 'தயவுசெய்து குறைந்தது ஒரு நாளை தேர்ந்தெடுக்கவும்.', sessions_required: 'தயவுசெய்து குறைந்தது ஒரு அமர்வை தேர்ந்தெடுக்கவும்.' },
            notes: { days_note: 'குறைந்தது ஒரு நாள் மற்றும் ஒரு அமர்வு தேவை' },
            alerts: { success_title: 'பதிவு வெற்றிகரமானது!', error_title: 'பிழை' }
        },
        te: {
            header: { title: 'పాస్టర్ క్రిస్‌తో రాపథాన్', subtitle: '2026 ఎడిషన్', date: 'సోమవారం 4 - శుక్రవారం 8 మే 2026', desc: 'మన దైవిక ఆదేశాన్ని పూర్తి చేయడంలో దృష్టిని శుద్ధీకరించే అసాధారణ సమయం కోసం మాతో చేరండి.' },
            sections: { personal: 'వ్యక్తిగత సమాచారం', church: 'చర్చి సమాచారం', participation: 'భాగస్వామ్యం', days: 'రోజులు మరియు సెషన్లు' },
            labels: { title: 'బిరుదు', first_name: 'మొదటి పేరు', last_name: 'చివరి పేరు', email_address: 'ఇమెయిల్ చిరునామా', phone_number: 'ఫోన్ నంబర్', kingschat_username: 'KingsChat వినియోగదారు పేరు', zone: 'జోన్', group: 'గ్రూప్', church: 'చర్చి', participation_question: 'మీరు అసేసేలో రాపథాన్ సమావేశంలో స్థానికంగా పాల్గొంటారా?', select_days_sessions: 'మీ రోజులు మరియు సెషన్లను ఎంచుకోండి', sessions: 'సెషన్లు', yes: 'అవును', no: 'లేదు' },
            placeholders: { first_name: 'మొదటి పేరు నమోదు చేయండి', last_name: 'చివరి పేరు నమోదు చేయండి', email: 'మీ.ఇమెయిల్@ఉదాహరణ.com', phone_local: 'స్థానిక నంబర్', kingschat: '@వినియోగదారు_పేరు', group: 'మీ గ్రూప్ నమోదు చేయండి', church: 'చర్చి పేరు నమోదు చేయండి' },
            dropdowns: { select_title: 'బిరుదు ఎంచుకోండి', select_zone: 'మీ జోన్ ఎంచుకోండి', select_code: 'కోడ్ ఎంచుకోండి' },
            days: { monday: 'సోమవారం', tuesday: 'మంగళవారం', wednesday: 'బుధవారం', thursday: 'గురువారం', friday: 'శుక్రవారం' },
            buttons: { select_all: 'అన్ని రోజులను ఎంచుకోండి', clear_all: 'క్లియర్ చేయండి', submit: 'రాపథాన్ కోసం నమోదు చేసుకోండి', submitting: 'పంపుతోంది...', confirm: 'అద్భుతం!', ok: 'సరే' },
            errors: { days_required: 'దయచేసి కనీసం ఒక రోజును ఎంచుకోండి.', sessions_required: 'దయచేసి కనీసం ఒక సెషన్ను ఎంచుకోండి.' },
            notes: { days_note: 'కనీసం ఒక రోజు మరియు ఒక సెషన్ అవసరం' },
            alerts: { success_title: 'నమోదు విజయవంతం!', error_title: 'లోపం' }
        },
        ml: {
            header: { title: 'പാസ്റ്റർ ക്രിസിനൊപ്പം റാപ്പത്തൺ', subtitle: '2026 പതിപ്പ്', date: 'തിങ്കൾ 4 - വെള്ളി 8 മേയ് 2026', desc: 'നമ്മുടെ ദൈവിക ദൗത്യം പൂർത്തിയാക്കുന്നതിൽ ദർശനം ശുദ്ധീകരിക്കുന്ന അസാധാരണ സമയത്തിനായി ഞങ്ങളോടൊപ്പം ചേരുക.' },
            sections: { personal: 'വ്യക്തിഗത വിവരങ്ങൾ', church: 'പള്ളി വിവരങ്ങൾ', participation: 'പങ്കാളിത്തം', days: 'ദിവസങ്ങളും സെഷനുകളും' },
            labels: { title: 'പദവി', first_name: 'ആദ്യ പേര്', last_name: 'അവസാന പേര്', email_address: 'ഇമെയിൽ വിലാസം', phone_number: 'ഫോൺ നമ്പർ', kingschat_username: 'KingsChat ഉപയോക്തൃനാമം', zone: 'സോൺ', group: 'ഗ്രൂപ്പ്', church: 'പള്ളി', participation_question: 'നിങ്ങൾ അസേസേയിലെ റാപ്പത്തൺ കോൺഫറൻസിൽ സ്ഥലത്ത് പങ്കെടുക്കുമോ?', select_days_sessions: 'നിങ്ങളുടെ ദിവസങ്ങളും സെഷനുകളും തിരഞ്ഞെടുക്കുക', sessions: 'സെഷനുകൾ', yes: 'അതെ', no: 'ഇല്ല' },
            placeholders: { first_name: 'ആദ്യ പേര് നൽകുക', last_name: 'അവസാന പേര് നൽകുക', email: 'നിങ്ങളുടെ.ഇമെയിൽ@ഉദാഹരണം.com', phone_local: 'പ്രാദേശിക നമ്പർ', kingschat: '@ഉപയോക്തൃനാമം', group: 'നിങ്ങളുടെ ഗ്രൂപ്പ് നൽകുക', church: 'പള്ളിയുടെ പേര് നൽകുക' },
            dropdowns: { select_title: 'പദവി തിരഞ്ഞെടുക്കുക', select_zone: 'നിങ്ങളുടെ സോൺ തിരഞ്ഞെടുക്കുക', select_code: 'കോഡ് തിരഞ്ഞെടുക്കുക' },
            days: { monday: 'തിങ്കൾ', tuesday: 'ചൊവ്വ', wednesday: 'ബുധൻ', thursday: 'വ്യാഴം', friday: 'വെള്ളി' },
            buttons: { select_all: 'എല്ലാ ദിവസങ്ങളും തിരഞ്ഞെടുക്കുക', clear_all: 'മായ്ക്കുക', submit: 'റാപ്പത്തണിനായി രജിസ്റ്റർ ചെയ്യുക', submitting: 'അയയ്ക്കുന്നു...', confirm: 'മികച്ചത്!', ok: 'ശരി' },
            errors: { days_required: 'ദയവായി കുറഞ്ഞത് ഒരു ദിവസം തിരഞ്ഞെടുക്കുക.', sessions_required: 'ദയവായി കുറഞ്ഞത് ഒരു സെഷൻ തിരഞ്ഞെടുക്കുക.' },
            notes: { days_note: 'കുറഞ്ഞത് ഒരു ദിവസവും ഒരു സെഷനും ആവശ്യമാണ്' },
            alerts: { success_title: 'രജിസ്ട്രേഷൻ വിജയകരം!', error_title: 'പിശക്' }
        },
        kn: {
            header: { title: 'ಪಾಸ್ಟರ್ ಕ್ರಿಸ್ ಜೊತೆ ರಾಪಥಾನ್', subtitle: '2026 ಆವೃತ್ತಿ', date: 'ಸೋಮವಾರ 4 - ಶುಕ್ರವಾರ 8 ಮೇ 2026', desc: 'ನಮ್ಮ ದೈವಿಕ ಆದೇಶವನ್ನು ಪೂರ್ಣಗೊಳಿಸುವಲ್ಲಿ ದೃಷ್ಟಿಯ ಪರಿಷ್ಕರಣೆಯ ಅಸಾಧಾರಣ ಸಮಯಕ್ಕಾಗಿ ನಮ್ಮೊಂದಿಗೆ ಸೇರಿ.' },
            sections: { personal: 'ವೈಯಕ್ತಿಕ ಮಾಹಿತಿ', church: 'ಚರ್ಚ್ ಮಾಹಿತಿ', participation: 'ಭಾಗವಹಿಸುವಿಕೆ', days: 'ದಿನಗಳು ಮತ್ತು ಸೆಷನ್‌ಗಳು' },
            labels: { title: 'ಶೀರ್ಷಿಕೆ', first_name: 'ಮೊದಲ ಹೆಸರು', last_name: 'ಕೊನೆಯ ಹೆಸರು', email_address: 'ಇಮೇಲ್ ವಿಳಾಸ', phone_number: 'ಫೋನ್ ಸಂಖ್ಯೆ', kingschat_username: 'KingsChat ಬಳಕೆದಾರ ಹೆಸರು', zone: 'ವಲಯ', group: 'ಗುಂಪು', church: 'ಚರ್ಚ್', participation_question: 'ನೀವು ಅಸೇಸೆಯಲ್ಲಿರುವ ರಾಪಥಾನ್ ಸಮ್ಮೇಳನದಲ್ಲಿ ಸ್ಥಳೀಯವಾಗಿ ಭಾಗವಹಿಸುತ್ತೀರಾ?', select_days_sessions: 'ನಿಮ್ಮ ದಿನಗಳು ಮತ್ತು ಸೆಷನ್‌ಗಳನ್ನು ಆಯ್ಕೆ ಮಾಡಿ', sessions: 'ಸೆಷನ್‌ಗಳು', yes: 'ಹೌದು', no: 'ಇಲ್ಲ' },
            placeholders: { first_name: 'ಮೊದಲ ಹೆಸರನ್ನು ನಮೂದಿಸಿ', last_name: 'ಕೊನೆಯ ಹೆಸರನ್ನು ನಮೂದಿಸಿ', email: 'ನಿಮ್ಮ.ಇಮೇಲ್@ಉದಾಹರಣೆ.com', phone_local: 'ಸ್ಥಳೀಯ ಸಂಖ್ಯೆ', kingschat: '@ಬಳಕೆದಾರ_ಹೆಸರು', group: 'ನಿಮ್ಮ ಗುಂಪನ್ನು ನಮೂದಿಸಿ', church: 'ಚರ್ಚ್ ಹೆಸರನ್ನು ನಮೂದಿಸಿ' },
            dropdowns: { select_title: 'ಶೀರ್ಷಿಕೆ ಆಯ್ಕೆ ಮಾಡಿ', select_zone: 'ನಿಮ್ಮ ವಲಯವನ್ನು ಆಯ್ಕೆ ಮಾಡಿ', select_code: 'ಕೋಡ್ ಆಯ್ಕೆ ಮಾಡಿ' },
            days: { monday: 'ಸೋಮವಾರ', tuesday: 'ಮಂಗಳವಾರ', wednesday: 'ಬುಧವಾರ', thursday: 'ಗುರುವಾರ', friday: 'ಶುಕ್ರವಾರ' },
            buttons: { select_all: 'ಎಲ್ಲಾ ದಿನಗಳನ್ನು ಆಯ್ಕೆ ಮಾಡಿ', clear_all: 'ತೆರವುಗೊಳಿಸಿ', submit: 'ರಾಪಥಾನ್‌ಗಾಗಿ ನೋಂದಾಯಿಸಿ', submitting: 'ಕಳುಹಿಸುತ್ತಿದೆ...', confirm: 'ಅದ್ಭುತ!', ok: 'ಸರಿ' },
            errors: { days_required: 'ದಯವಿಟ್ಟು ಕನಿಷ್ಠ ಒಂದು ದಿನವನ್ನು ಆಯ್ಕೆ ಮಾಡಿ.', sessions_required: 'ದಯವಿಟ್ಟು ಕನಿಷ್ಠ ಒಂದು ಸೆಷನ್ ಆಯ್ಕೆ ಮಾಡಿ.' },
            notes: { days_note: 'ಕನಿಷ್ಠ ಒಂದು ದಿನ ಮತ್ತು ಒಂದು ಸೆಷನ್ ಅಗತ್ಯ' },
            alerts: { success_title: 'ನೋಂದಣಿ ಯಶಸ್ವಿ!', error_title: 'ದೋಷ' }
        },
        gu: {
            header: { title: 'પાસ્ટર ક્રિસ સાથે રાપાથોન', subtitle: '2026 આવૃત્તિ', date: 'સોમવાર 4 - શુક્રવાર 8 મે 2026', desc: 'આપણા દૈવી આદેશને પૂર્ણ કરવામાં દ્રષ્ટિની શુદ્ધિકરણના અસાધારણ સમય માટે અમારી સાથે જોડાઓ.' },
            sections: { personal: 'વ્યક્તિગત માહિતી', church: 'ચર્ચ માહિતી', participation: 'ભાગીદારી', days: 'દિવસો અને સેશન' },
            labels: { title: 'શીર્ષક', first_name: 'પ્રથમ નામ', last_name: 'છેલ્લું નામ', email_address: 'ઈમેઇલ સરનામું', phone_number: 'ફોન નંબર', kingschat_username: 'KingsChat વપરાશકર્તા નામ', zone: 'ઝોન', group: 'જૂથ', church: 'ચર્ચ', participation_question: 'શું તમે અસેસે ખાતેની રાપાથોન કોન્ફરન્સમાં સ્થળ પર ભાગ લેશો?', select_days_sessions: 'તમારા દિવસો અને સેશન પસંદ કરો', sessions: 'સેશન', yes: 'હા', no: 'ના' },
            placeholders: { first_name: 'પ્રથમ નામ દાખલ કરો', last_name: 'છેલ્લું નામ દાખલ કરો', email: 'તમારું.ઈમેઇલ@ઉદાહરણ.com', phone_local: 'સ્થાનિક નંબર', kingschat: '@વપરાશકર્તા_નામ', group: 'તમારું જૂથ દાખલ કરો', church: 'ચર્ચનું નામ દાખલ કરો' },
            dropdowns: { select_title: 'શીર્ષક પસંદ કરો', select_zone: 'તમારો ઝોન પસંદ કરો', select_code: 'કોડ પસંદ કરો' },
            days: { monday: 'સોમવાર', tuesday: 'મંગળવાર', wednesday: 'બુધવાર', thursday: 'ગુરુવાર', friday: 'શુક્રવાર' },
            buttons: { select_all: 'બધા દિવસો પસંદ કરો', clear_all: 'સાફ કરો', submit: 'રાપાથોન માટે નોંધણી કરો', submitting: 'મોકલી રહ્યા છીએ...', confirm: 'ઉત્તમ!', ok: 'બરાબર' },
            errors: { days_required: 'કૃપા કરીને ઓછામાં ઓછો એક દિવસ પસંદ કરો.', sessions_required: 'કૃપા કરીને ઓછામાં ઓછું એક સેશન પસંદ કરો.' },
            notes: { days_note: 'ઓછામાં ઓછો એક દિવસ અને એક સેશન જરૂરી છે' },
            alerts: { success_title: 'નોંધણી સફળ!', error_title: 'ભૂલ' }
        },
        mr: {
            header: { title: 'पास्टर ख्रिस सोबत रॅपथॉन', subtitle: '2026 आवृत्ती', date: 'सोमवार 4 - शुक्रवार 8 मे 2026', desc: 'आमच्या दैवी आदेशाची पूर्तता करताना दृष्टीच्या परिष्करणाच्या असामान्य वेळेसाठी आमच्यासोबत सामील व्हा.' },
            sections: { personal: 'वैयक्तिक माहिती', church: 'चर्च माहिती', participation: 'सहभाग', days: 'दिवस आणि सत्रे' },
            labels: { title: 'शीर्षक', first_name: 'पहिले नाव', last_name: 'आडनाव', email_address: 'ईमेल पत्ता', phone_number: 'फोन नंबर', kingschat_username: 'KingsChat वापरकर्ता नाव', zone: 'क्षेत्र', group: 'गट', church: 'चर्च', participation_question: 'तुम्ही असेसे येथील रॅपथॉन परिषदेत स्थानिकपणे सहभागी व्हाल का?', select_days_sessions: 'तुमचे दिवस आणि सत्रे निवडा', sessions: 'सत्रे', yes: 'होय', no: 'नाही' },
            placeholders: { first_name: 'पहिले नाव प्रविष्ट करा', last_name: 'आडनाव प्रविष्ट करा', email: 'तुमचा.ईमेल@उदाहरण.com', phone_local: 'स्थानिक नंबर', kingschat: '@वापरकर्ता_नाव', group: 'तुमचा गट प्रविष्ट करा', church: 'चर्चचे नाव प्रविष्ट करा' },
            dropdowns: { select_title: 'शीर्षक निवडा', select_zone: 'तुमचे क्षेत्र निवडा', select_code: 'कोड निवडा' },
            days: { monday: 'सोमवार', tuesday: 'मंगळवार', wednesday: 'बुधवार', thursday: 'गुरुवार', friday: 'शुक्रवार' },
            buttons: { select_all: 'सर्व दिवस निवडा', clear_all: 'साफ करा', submit: 'रॅपथॉनसाठी नोंदणी करा', submitting: 'पाठवत आहे...', confirm: 'उत्तम!', ok: 'ठीक आहे' },
            errors: { days_required: 'कृपया किमान एक दिवस निवडा.', sessions_required: 'कृपया किमान एक सत्र निवडा.' },
            notes: { days_note: 'किमान एक दिवस आणि एक सत्र आवश्यक आहे' },
            alerts: { success_title: 'नोंदणी यशस्वी!', error_title: 'त्रुटी' }
        },
        // Add 30 more diverse languages
        is: {
            header: { title: 'Rhapathon með Pastor Chris', subtitle: '2026 útgáfa', date: 'Mánudagur 4. - Föstudagur 8. september 2026', desc: 'Taktu þátt í óvenjulegum tíma til að betrumbæta sýn í að ljúka guðlegu umboði okkar.' },
            sections: { personal: 'Persónulegar upplýsingar', church: 'Kirkjuupplýsingar', participation: 'Þátttaka', days: 'Dagar og fundir' },
            labels: { title: 'Titill', first_name: 'Fornafn', last_name: 'Eftirnafn', email_address: 'Netfang', phone_number: 'Símanúmer', kingschat_username: 'KingsChat notandanafn', zone: 'Svæði', group: 'Hópur', church: 'Kirkja', participation_question: 'Muntu þú taka þátt á staðnum í Rhapathon ráðstefnunni í Asese?', select_days_sessions: 'Veldu þína daga og fundi', sessions: 'Fundir', yes: 'Já', no: 'Nei' },
            placeholders: { first_name: 'Sláðu inn fornafn', last_name: 'Sláðu inn eftirnafn', email: 'þitt.netfang@dæmi.com', phone_local: 'Staðbundið númer', kingschat: '@notandanafn', group: 'Sláðu inn þinn hóp', church: 'Sláðu inn nafn kirkju' },
            dropdowns: { select_title: 'Veldu titil', select_zone: 'Veldu þitt svæði', select_code: 'Veldu kóða' },
            days: { monday: 'Mánudagur', tuesday: 'Þriðjudagur', wednesday: 'Miðvikudagur', thursday: 'Fimmtudagur', friday: 'Föstudagur' },
            buttons: { select_all: 'Velja alla daga', clear_all: 'Hreinsa', submit: 'Skrá sig í Rhapathon', submitting: 'Sendir...', confirm: 'Frábært!', ok: 'Í lagi' },
            errors: { days_required: 'Vinsamlegast veldu að minnsta kosti einn dag.', sessions_required: 'Vinsamlegast veldu að minnsta kosti einn fund.' },
            notes: { days_note: 'Að minnsta kosti einn dagur og einn fundur þarf' },
            alerts: { success_title: 'Skráning tókst!', error_title: 'Villa' }
        },
        mt: {
            header: { title: 'Rhapathon ma\' Pastor Chris', subtitle: 'Edizzjoni 2026', date: 'It-Tnejn 4 - Il-Ġimgħa 8 Mejju 2026', desc: 'Ingħaqdu magħna għal ħin straordinarju ta\' rafinament tal-viżjoni fit-tlestija tal-mandat divin tagħna.' },
            sections: { personal: 'Informazzjoni Personali', church: 'Informazzjoni tal-Knisja', participation: 'Parteċipazzjoni', days: 'Jiem u Sessjonijiet' },
            labels: { title: 'Titlu', first_name: 'L-Ewwel Isem', last_name: 'L-Aħħar Isem', email_address: 'Indirizz tal-Email', phone_number: 'Numru tat-Telefon', kingschat_username: 'Isem tal-Utent KingsChat', zone: 'Żona', group: 'Grupp', church: 'Knisja', participation_question: 'Se tkun qed tipparteċipa fuq il-post fil-konferenza Rhapathon f\'Asese?', select_days_sessions: 'Agħżel il-jiem u s-sessjonijiet tiegħek', sessions: 'Sessjonijiet', yes: 'Iva', no: 'Le' },
            placeholders: { first_name: 'Daħħal l-ewwel isem', last_name: 'Daħħal l-aħħar isem', email: 'l-email.tiegħek@eżempju.com', phone_local: 'Numru lokali', kingschat: '@isem_utent', group: 'Daħħal il-grupp tiegħek', church: 'Daħħal l-isem tal-knisja' },
            dropdowns: { select_title: 'Agħżel titlu', select_zone: 'Agħżel iż-żona tiegħek', select_code: 'Agħżel kodiċi' },
            days: { monday: 'It-Tnejn', tuesday: 'It-Tlieta', wednesday: 'L-Erbgħa', thursday: 'Il-Ħamis', friday: 'Il-Ġimgħa' },
            buttons: { select_all: 'Agħżel il-jiem kollha', clear_all: 'Ħassar', submit: 'Irreġistra għar-Rhapathon', submitting: 'Qed jintbagħat...', confirm: 'Perfett!', ok: 'Tajjeb' },
            errors: { days_required: 'Jekk jogħġbok agħżel mill-inqas jum wieħed.', sessions_required: 'Jekk jogħġbok agħżel mill-inqas sessjoni waħda.' },
            notes: { days_note: 'Meħtieġ mill-inqas jum wieħed u sessjoni waħda' },
            alerts: { success_title: 'Reġistrazzjoni rnexxiet!', error_title: 'Żball' }
        },
        lv: {
            header: { title: 'Rhapathon ar mācītāju Krisu', subtitle: '2026. gada izdevums', date: 'Pirmdiena, 4. - Piektdiena, 8. maijs, 2026', desc: 'Pievienojieties mums īpašā vīzijas attīrīšanas laikā, pildot mūsu dievišķo mandātu.' },
            sections: { personal: 'Personīgā informācija', church: 'Baznīcas informācija', participation: 'Dalība', days: 'Dienas un sesijas' },
            labels: { title: 'Nosaukums', first_name: 'Vārds', last_name: 'Uzvārds', email_address: 'E-pasta adrese', phone_number: 'Tālruņa numurs', kingschat_username: 'KingsChat lietotājvārds', zone: 'Zona', group: 'Grupa', church: 'Baznīca', participation_question: 'Vai jūs piedalīsieties klātienē Rhapathon konferencē Asese?', select_days_sessions: 'Izvēlieties savas dienas un sesijas', sessions: 'Sesijas', yes: 'Jā', no: 'Nē' },
            placeholders: { first_name: 'Ievadiet vārdu', last_name: 'Ievadiet uzvārdu', email: 'jūsu.epasts@piemērs.com', phone_local: 'Vietējais numurs', kingschat: '@lietotājvārds', group: 'Ievadiet savu grupu', church: 'Ievadiet baznīcas nosaukumu' },
            dropdowns: { select_title: 'Izvēlieties nosaukumu', select_zone: 'Izvēlieties savu zonu', select_code: 'Izvēlieties kodu' },
            days: { monday: 'Pirmdiena', tuesday: 'Otrdiena', wednesday: 'Trešdiena', thursday: 'Ceturtdiena', friday: 'Piektdiena' },
            buttons: { select_all: 'Izvēlēties visas dienas', clear_all: 'Notīrīt', submit: 'Reģistrēties Rhapathon', submitting: 'Sūta...', confirm: 'Lieliski!', ok: 'Labi' },
            errors: { days_required: 'Lūdzu izvēlieties vismaz vienu dienu.', sessions_required: 'Lūdzu izvēlieties vismaz vienu sesiju.' },
            notes: { days_note: 'Nepieciešama vismaz viena diena un viena sesija' },
            alerts: { success_title: 'Reģistrācija veiksmīga!', error_title: 'Kļūda' }
        },
        lt: {
            header: { title: 'Rhapathon su pastoriumi Krisu', subtitle: '2026 leidimas', date: 'Pirmadienis 4 - Penktadienis 8 gegužės, 2026', desc: 'Prisijunkite prie mūsų išskirtiniam vizijos tobulinimo laikui, vykdant mūsų dieviškąjį mandatą.' },
            sections: { personal: 'Asmeninė informacija', church: 'Bažnyčios informacija', participation: 'Dalyvavimas', days: 'Dienos ir sesijos' },
            labels: { title: 'Pavadinimas', first_name: 'Vardas', last_name: 'Pavardė', email_address: 'El. pašto adresas', phone_number: 'Telefono numeris', kingschat_username: 'KingsChat vartotojo vardas', zone: 'Zona', group: 'Grupė', church: 'Bažnyčia', participation_question: 'Ar dalyvausite vietoje Rhapathon konferencijoje Asese?', select_days_sessions: 'Pasirinkite savo dienas ir sesijas', sessions: 'Sesijos', yes: 'Taip', no: 'Ne' },
            placeholders: { first_name: 'Įveskite vardą', last_name: 'Įveskite pavardę', email: 'jūsų.el.paštas@pavyzdys.com', phone_local: 'Vietinis numeris', kingschat: '@vartotojo_vardas', group: 'Įveskite savo grupę', church: 'Įveskite bažnyčios pavadinimą' },
            dropdowns: { select_title: 'Pasirinkite pavadinimą', select_zone: 'Pasirinkite savo zoną', select_code: 'Pasirinkite kodą' },
            days: { monday: 'Pirmadienis', tuesday: 'Antradienis', wednesday: 'Trečiadienis', thursday: 'Ketvirtadienis', friday: 'Penktadienis' },
            buttons: { select_all: 'Pasirinkti visas dienas', clear_all: 'Išvalyti', submit: 'Registruotis Rhapathon', submitting: 'Siunčiama...', confirm: 'Puiku!', ok: 'Gerai' },
            errors: { days_required: 'Prašome pasirinkti bent vieną dieną.', sessions_required: 'Prašome pasirinkti bent vieną sesiją.' },
            notes: { days_note: 'Reikalinga bent viena diena ir viena sesija' },
            alerts: { success_title: 'Registracija sėkminga!', error_title: 'Klaida' }
        },
        et: {
            header: { title: 'Rhapathon pastor Chrisiga', subtitle: '2026 väljaanne', date: 'Esmaspäev 4. - Reede 8. mai 2026', desc: 'Liituge meiega erakordse visiooni täiustamise ajaga meie jumaliku mandaadi täitmisel.' },
            sections: { personal: 'Isiklik teave', church: 'Kiriku teave', participation: 'Osalemine', days: 'Päevad ja sessioonid' },
            labels: { title: 'Tiitel', first_name: 'Eesnimi', last_name: 'Perekonnanimi', email_address: 'E-posti aadress', phone_number: 'Telefoninumber', kingschat_username: 'KingsChat kasutajanimi', zone: 'Tsoon', group: 'Grupp', church: 'Kirik', participation_question: 'Kas osalete kohapeal Rhapathon konverentsil Aseses?', select_days_sessions: 'Valige oma päevad ja sessioonid', sessions: 'Sessioonid', yes: 'Jah', no: 'Ei' },
            placeholders: { first_name: 'Sisestage eesnimi', last_name: 'Sisestage perekonnanimi', email: 'teie.email@näidis.com', phone_local: 'Kohalik number', kingschat: '@kasutajanimi', group: 'Sisestage oma grupp', church: 'Sisestage kiriku nimi' },
            dropdowns: { select_title: 'Valige tiitel', select_zone: 'Valige oma tsoon', select_code: 'Valige kood' },
            days: { monday: 'Esmaspäev', tuesday: 'Teisipäev', wednesday: 'Kolmapäev', thursday: 'Neljapäev', friday: 'Reede' },
            buttons: { select_all: 'Vali kõik päevad', clear_all: 'Tühjenda', submit: 'Registreeru Rhapathonile', submitting: 'Saadab...', confirm: 'Suurepärane!', ok: 'Olgu' },
            errors: { days_required: 'Palun valige vähemalt üks päev.', sessions_required: 'Palun valige vähemalt üks sessioon.' },
            notes: { days_note: 'Vaja on vähemalt ühte päeva ja ühte sessiooni' },
            alerts: { success_title: 'Registreerimine õnnestus!', error_title: 'Viga' }
        },
        // Continue with remaining 27 languages
        sl: {
            header: { title: 'Rhapathon s pastorjem Chrisom', subtitle: 'Izdaja 2026', date: 'Ponedeljek 4. - Petek 8. maj 2026', desc: 'Pridružite se nam za izjemen čas prečiščevanja vizije pri izpolnjevanju našega božanskega mandata.' },
            sections: { personal: 'Osebne informacije', church: 'Informacije o cerkvi', participation: 'Sodelovanje', days: 'Dnevi in seje' },
            labels: { title: 'Naslov', first_name: 'Ime', last_name: 'Priimek', email_address: 'E-poštni naslov', phone_number: 'Telefonska številka', kingschat_username: 'KingsChat uporabniško ime', zone: 'Cona', group: 'Skupina', church: 'Cerkev', participation_question: 'Ali boste sodelovali na kraju na konferenci Rhapathon v Asese?', select_days_sessions: 'Izberite svoje dneve in seje', sessions: 'Seje', yes: 'Da', no: 'Ne' },
            placeholders: { first_name: 'Vnesite ime', last_name: 'Vnesite priimek', email: 'vaša.pošta@primer.com', phone_local: 'Lokalna številka', kingschat: '@uporabniško_ime', group: 'Vnesite svojo skupino', church: 'Vnesite ime cerkve' },
            dropdowns: { select_title: 'Izberite naslov', select_zone: 'Izberite svojo cono', select_code: 'Izberite kodo' },
            days: { monday: 'Ponedeljek', tuesday: 'Torek', wednesday: 'Sreda', thursday: 'Četrtek', friday: 'Petek' },
            buttons: { select_all: 'Izberi vse dneve', clear_all: 'Počisti', submit: 'Registriraj se za Rhapathon', submitting: 'Pošiljam...', confirm: 'Odlično!', ok: 'V redu' },
            errors: { days_required: 'Prosimo izberite vsaj en dan.', sessions_required: 'Prosimo izberite vsaj eno sejo.' },
            notes: { days_note: 'Potreben je vsaj en dan in ena seja' },
            alerts: { success_title: 'Registracija uspešna!', error_title: 'Napaka' }
        },
        hr: {
            header: { title: 'Rhapathon s pastorom Chrisom', subtitle: 'Izdanje 2026', date: 'Ponedjeljak 4. - Petak 8. svibanj 2026', desc: 'Pridružite nam se za izvanredno vrijeme profinjavanja vizije u dovršavanju našeg božanskog mandata.' },
            sections: { personal: 'Osobne informacije', church: 'Informacije o crkvi', participation: 'Sudjelovanje', days: 'Dani i sesije' },
            labels: { title: 'Naslov', first_name: 'Ime', last_name: 'Prezime', email_address: 'E-mail adresa', phone_number: 'Broj telefona', kingschat_username: 'KingsChat korisničko ime', zone: 'Zona', group: 'Grupa', church: 'Crkva', participation_question: 'Hoćete li sudjelovati na licu mjesta na konferenciji Rhapathon u Asese?', select_days_sessions: 'Odaberite svoje dane i sesije', sessions: 'Sesije', yes: 'Da', no: 'Ne' },
            placeholders: { first_name: 'Unesite ime', last_name: 'Unesite prezime', email: 'vaš.email@primjer.com', phone_local: 'Lokalni broj', kingschat: '@korisničko_ime', group: 'Unesite svoju grupu', church: 'Unesite ime crkve' },
            dropdowns: { select_title: 'Odaberite naslov', select_zone: 'Odaberite svoju zonu', select_code: 'Odaberite kod' },
            days: { monday: 'Ponedjeljak', tuesday: 'Utorak', wednesday: 'Srijeda', thursday: 'Četvrtak', friday: 'Petak' },
            buttons: { select_all: 'Odaberi sve dane', clear_all: 'Obriši', submit: 'Registriraj se za Rhapathon', submitting: 'Šalje se...', confirm: 'Odlično!', ok: 'U redu' },
            errors: { days_required: 'Molimo odaberite najmanje jedan dan.', sessions_required: 'Molimo odaberite najmanje jednu sesiju.' },
            notes: { days_note: 'Potreban je najmanje jedan dan i jedna sesija' },
            alerts: { success_title: 'Registracija uspješna!', error_title: 'Greška' }
        },
        bg: {
            header: { title: 'Rhapathon с пастор Крис', subtitle: 'Издание 2026', date: 'Понеделник 4 - Петък 8 май 2026', desc: 'Присъединете се към нас за изключително време на пречистване на визията при изпълнението на нашия божествен мандат.' },
            sections: { personal: 'Лична информация', church: 'Информация за църквата', participation: 'Участие', days: 'Дни и сесии' },
            labels: { title: 'Титла', first_name: 'Собствено име', last_name: 'Фамилно име', email_address: 'Имейл адрес', phone_number: 'Телефонен номер', kingschat_username: 'KingsChat потребителско име', zone: 'Зона', group: 'Група', church: 'Църква', participation_question: 'Ще участвате ли на място в конференцията Rhapathon в Асесе?', select_days_sessions: 'Изберете вашите дни и сесии', sessions: 'Сесии', yes: 'Да', no: 'Не' },
            placeholders: { first_name: 'Въведете собствено име', last_name: 'Въведете фамилно име', email: 'вашият.имейл@пример.com', phone_local: 'Местен номер', kingschat: '@потребителско_име', group: 'Въведете вашата група', church: 'Въведете името на църквата' },
            dropdowns: { select_title: 'Изберете титла', select_zone: 'Изберете вашата зона', select_code: 'Изберете код' },
            days: { monday: 'Понеделник', tuesday: 'Вторник', wednesday: 'Сряда', thursday: 'Четвъртък', friday: 'Петък' },
            buttons: { select_all: 'Избери всички дни', clear_all: 'Изчисти', submit: 'Регистрирай се за Rhapathon', submitting: 'Изпраща се...', confirm: 'Отлично!', ok: 'Добре' },
            errors: { days_required: 'Моля изберете поне един ден.', sessions_required: 'Моля изберете поне една сесия.' },
            notes: { days_note: 'Необходими са поне един ден и една сесия' },
            alerts: { success_title: 'Регистрацията е успешна!', error_title: 'Грешка' }
        },
        // Southeast Asian languages
        id: {
            header: { title: 'Rhapathon dengan Pastor Chris', subtitle: 'Edisi 2026', date: 'Senin 4 - Jumat 8 Mei 2026', desc: 'Bergabunglah dengan kami untuk waktu luar biasa penyempurnaan visi dalam menyelesaikan mandat ilahi kita.' },
            sections: { personal: 'Informasi Pribadi', church: 'Informasi Gereja', participation: 'Partisipasi', days: 'Hari dan Sesi' },
            labels: { title: 'Gelar', first_name: 'Nama Depan', last_name: 'Nama Belakang', email_address: 'Alamat Email', phone_number: 'Nomor Telepon', kingschat_username: 'Nama Pengguna KingsChat', zone: 'Zona', group: 'Kelompok', church: 'Gereja', participation_question: 'Apakah Anda akan berpartisipasi di tempat konferensi Rhapathon di Asese?', select_days_sessions: 'Pilih hari dan sesi Anda', sessions: 'Sesi', yes: 'Ya', no: 'Tidak' },
            placeholders: { first_name: 'Masukkan nama depan', last_name: 'Masukkan nama belakang', email: 'email.anda@contoh.com', phone_local: 'Nomor lokal', kingschat: '@nama_pengguna', group: 'Masukkan kelompok Anda', church: 'Masukkan nama gereja' },
            dropdowns: { select_title: 'Pilih gelar', select_zone: 'Pilih zona Anda', select_code: 'Pilih kode' },
            days: { monday: 'Senin', tuesday: 'Selasa', wednesday: 'Rabu', thursday: 'Kamis', friday: 'Jumat' },
            buttons: { select_all: 'Pilih semua hari', clear_all: 'Hapus', submit: 'Daftar untuk Rhapathon', submitting: 'Mengirim...', confirm: 'Bagus!', ok: 'OK' },
            errors: { days_required: 'Silakan pilih setidaknya satu hari.', sessions_required: 'Silakan pilih setidaknya satu sesi.' },
            notes: { days_note: 'Setidaknya satu hari dan satu sesi diperlukan' },
            alerts: { success_title: 'Pendaftaran berhasil!', error_title: 'Kesalahan' }
        },
        ms: {
            header: { title: 'Rhapathon dengan Pastor Chris', subtitle: 'Edisi 2026', date: 'Isnin 4 - Jumaat 8 Mei 2026', desc: 'Sertai kami untuk masa luar biasa pemurnian visi dalam melengkapkan mandat ilahi kita.' },
            sections: { personal: 'Maklumat Peribadi', church: 'Maklumat Gereja', participation: 'Penyertaan', days: 'Hari dan Sesi' },
            labels: { title: 'Gelaran', first_name: 'Nama Pertama', last_name: 'Nama Akhir', email_address: 'Alamat Emel', phone_number: 'Nombor Telefon', kingschat_username: 'Nama Pengguna KingsChat', zone: 'Zon', group: 'Kumpulan', church: 'Gereja', participation_question: 'Adakah anda akan menyertai secara fizikal persidangan Rhapathon di Asese?', select_days_sessions: 'Pilih hari dan sesi anda', sessions: 'Sesi', yes: 'Ya', no: 'Tidak' },
            placeholders: { first_name: 'Masukkan nama pertama', last_name: 'Masukkan nama akhir', email: 'emel.anda@contoh.com', phone_local: 'Nombor tempatan', kingschat: '@nama_pengguna', group: 'Masukkan kumpulan anda', church: 'Masukkan nama gereja' },
            dropdowns: { select_title: 'Pilih gelaran', select_zone: 'Pilih zon anda', select_code: 'Pilih kod' },
            days: { monday: 'Isnin', tuesday: 'Selasa', wednesday: 'Rabu', thursday: 'Khamis', friday: 'Jumaat' },
            buttons: { select_all: 'Pilih semua hari', clear_all: 'Padam', submit: 'Daftar untuk Rhapathon', submitting: 'Menghantar...', confirm: 'Bagus!', ok: 'OK' },
            errors: { days_required: 'Sila pilih sekurang-kurangnya satu hari.', sessions_required: 'Sila pilih sekurang-kurangnya satu sesi.' },
            notes: { days_note: 'Sekurang-kurangnya satu hari dan satu sesi diperlukan' },
            alerts: { success_title: 'Pendaftaran berjaya!', error_title: 'Ralat' }
        },
        tl: {
            header: { title: 'Rhapathon kasama si Pastor Chris', subtitle: 'Edisyon 2026', date: 'Lunes 4 - Biyernes 8 Mayo 2026', desc: 'Sumama sa amin para sa isang pambihirang panahon ng pagpapabuti ng pananaw sa pagtupad ng aming banal na mandato.' },
            sections: { personal: 'Personal na Impormasyon', church: 'Impormasyon ng Simbahan', participation: 'Pakikilahok', days: 'Mga Araw at Session' },
            labels: { title: 'Titulo', first_name: 'Unang Pangalan', last_name: 'Apelyido', email_address: 'Email Address', phone_number: 'Numero ng Telepono', kingschat_username: 'KingsChat Username', zone: 'Sona', group: 'Grupo', church: 'Simbahan', participation_question: 'Makikipagparticipate ka ba nang personal sa Rhapathon conference sa Asese?', select_days_sessions: 'Piliin ang inyong mga araw at session', sessions: 'Mga Session', yes: 'Oo', no: 'Hindi' },
            placeholders: { first_name: 'Ilagay ang unang pangalan', last_name: 'Ilagay ang apelyido', email: 'inyong.email@halimbawa.com', phone_local: 'Lokal na numero', kingschat: '@username', group: 'Ilagay ang inyong grupo', church: 'Ilagay ang pangalan ng simbahan' },
            dropdowns: { select_title: 'Piliin ang titulo', select_zone: 'Piliin ang inyong sona', select_code: 'Piliin ang code' },
            days: { monday: 'Lunes', tuesday: 'Martes', wednesday: 'Miyerkules', thursday: 'Huwebes', friday: 'Biyernes' },
            buttons: { select_all: 'Piliin lahat ng araw', clear_all: 'Linisin', submit: 'Mag-register para sa Rhapathon', submitting: 'Nagpapadala...', confirm: 'Magaling!', ok: 'OK' },
            errors: { days_required: 'Pakipili ng kahit isang araw.', sessions_required: 'Pakipili ng kahit isang session.' },
            notes: { days_note: 'Kailangan ng kahit isang araw at isang session' },
            alerts: { success_title: 'Matagumpay ang pagkakaregister!', error_title: 'Error' }
        },
        // Remaining Asian languages
        km: { header: { title: 'Rhapathon ជាមួយ Pastor Chris', subtitle: 'ការបោះពុម្ព 2026', date: 'ថ្ងៃច័ន្ទ 4 - ថ្ងៃសុក្រ 8 ខែឧសភា 2026', desc: 'ចូលរួមជាមួយយើងសម្រាប់ពេលវេលាពិសេសនៃការកែលម្អចក្ខុវិស័យក្នុងការបំពេញបេសកកម្មដ៏ពិសិដ្ឋរបស់យើង។' }, sections: { personal: 'ព័ត៌មានផ្ទាល់ខ្លួន', church: 'ព័ត៌មានព្រះវិហារ', participation: 'ការចូលរួម', days: 'ថ្ងៃ និងវគ្គ' }, labels: { title: 'ចំណងជើង', first_name: 'នាមខ្លួន', last_name: 'នាមត្រកូល', email_address: 'អាសយដ្ឋានអ៊ីមែល', phone_number: 'លេខទូរស័ព្ទ', kingschat_username: 'ឈ្មោះអ្នកប្រើ KingsChat', zone: 'តំបន់', group: 'ក្រុម', church: 'ព្រះវិហារ', participation_question: 'តើអ្នកនឹងចូលរួមនៅកន្លែងក្នុងសន្និសីទ Rhapathon នៅ Asese ដែរឬទេ?', select_days_sessions: 'ជ្រើសរើសថ្ងៃ និងវគ្គរបស់អ្នក', sessions: 'វគ្គ', yes: 'បាទ/ចាស', no: 'ទេ' }, placeholders: { first_name: 'បញ្ចូលនាមខ្លួន', last_name: 'បញ្ចូលនាមត្រកូល', email: 'អ៊ីមែល.របស់អ្នក@ឧទាហរណ៍.com', phone_local: 'លេខក្នុងស្រុក', kingschat: '@ឈ្មោះអ្នកប្រើ', group: 'បញ្ចូលក្រុមរបស់អ្នក', church: 'បញ្ចូលឈ្មោះព្រះវិហារ' }, dropdowns: { select_title: 'ជ្រើសរើសចំណងជើង', select_zone: 'ជ្រើសរើសតំបន់របស់អ្នក', select_code: 'ជ្រើសរើសកូដ' }, days: { monday: 'ថ្ងៃច័ន្ទ', tuesday: 'ថ្ងៃអង្គារ', wednesday: 'ថ្ងៃពុធ', thursday: 'ថ្ងៃព្រហស្បតិ៍', friday: 'ថ្ងៃសុក្រ' }, buttons: { select_all: 'ជ្រើសរើសថ្ងៃទាំងអស់', clear_all: 'លុបចេញ', submit: 'ចុះឈ្មោះសម្រាប់ Rhapathon', submitting: 'កំពុងផ្ញើ...', confirm: 'ល្អ!', ok: 'យល់ព្រម' }, errors: { days_required: 'សូមជ្រើសរើសយ៉ាងហោចណាស់មួយថ្ងៃ។', sessions_required: 'សូមជ្រើសរើសយ៉ាងហោចណាស់មួយវគ្គ។' }, notes: { days_note: 'ត្រូវការយ៉ាងហោចណាស់មួយថ្ងៃ និងមួយវគ្គ' }, alerts: { success_title: 'ការចុះឈ្មោះបានជោគជ័យ!', error_title: 'កំហុស' } },
        lo: { header: { title: 'Rhapathon ກັບ Pastor Chris', subtitle: 'ສະບັບ 2026', date: 'ວັນຈັນ 4 - ວັນສຸກ 8 ພຶດສະພາ 2026', desc: 'ເຂົ້າຮ່ວມກັບພວກເຮົາສໍາລັບເວລາພິເສດຂອງການປັບປຸງວິໄສທັດໃນການສໍາເລັດພາລະກິດອັນສັກສິດຂອງພວກເຮົາ.' }, sections: { personal: 'ຂໍ້ມູນສ່ວນຕົວ', church: 'ຂໍ້ມູນໂບດ', participation: 'ການເຂົ້າຮ່ວມ', days: 'ມື້ ແລະ ກອງປະຊຸມ' }, labels: { title: 'ຫົວຂໍ້', first_name: 'ຊື່ແທ້', last_name: 'ນາມສະກຸນ', email_address: 'ທີ່ຢູ່ອີເມວ', phone_number: 'ເບີໂທລະສັບ', kingschat_username: 'ຊື່ຜູ້ໃຊ້ KingsChat', zone: 'ເຂດ', group: 'ກຸ່ມ', church: 'ໂບດ', participation_question: 'ທ່ານຈະເຂົ້າຮ່ວມຢູ່ບ່ອນໃນກອງປະຊຸມ Rhapathon ທີ່ Asese ບໍ?', select_days_sessions: 'ເລືອກມື້ ແລະ ກອງປະຊຸມຂອງທ່ານ', sessions: 'ກອງປະຊຸມ', yes: 'ແມ່ນ', no: 'ບໍ່' }, placeholders: { first_name: 'ປ້ອນຊື່ແທ້', last_name: 'ປ້ອນນາມສະກຸນ', email: 'ອີເມວ.ຂອງທ່ານ@ຕົວຢ່າງ.com', phone_local: 'ເບີທ້ອງຖິ່ນ', kingschat: '@ຊື່ຜູ້ໃຊ້', group: 'ປ້ອນກຸ່ມຂອງທ່ານ', church: 'ປ້ອນຊື່ໂບດ' }, dropdowns: { select_title: 'ເລືອກຫົວຂໍ້', select_zone: 'ເລືອກເຂດຂອງທ່ານ', select_code: 'ເລືອກລະຫັດ' }, days: { monday: 'ວັນຈັນ', tuesday: 'ວັນອັງຄານ', wednesday: 'ວັນພຸດ', thursday: 'ວັນພະຫັດ', friday: 'ວັນສຸກ' }, buttons: { select_all: 'ເລືອກທຸກມື້', clear_all: 'ລຶບລ້າງ', submit: 'ລົງທະບຽນສໍາລັບ Rhapathon', submitting: 'ກໍາລັງສົ່ງ...', confirm: 'ດີເລີດ!', ok: 'ຕົກລົງ' }, errors: { days_required: 'ກະລຸນາເລືອກຢ່າງນ້ອຍໜຶ່ງມື້.', sessions_required: 'ກະລຸນາເລືອກຢ່າງນ້ອຍໜຶ່ງກອງປະຊຸມ.' }, notes: { days_note: 'ຕ້ອງການຢ່າງນ້ອຍໜຶ່ງມື້ ແລະ ໜຶ່ງກອງປະຊຸມ' }, alerts: { success_title: 'ການລົງທະບຽນສໍາເລັດ!', error_title: 'ຄວາມຜິດພາດ' } },
        my: { header: { title: 'Pastor Chris နှင့် Rhapathon', subtitle: '2026 ထုတ်ဝေမှု', date: 'တနင်္လာ 4 - သောကြာ 8 မေ 2026', desc: 'ကျွန်ုပ်တို့၏ မြင့်မြတ်သော တာဝန်ကို ပြီးမြောက်ရာတွင် အမြင်ကို သန့်စင်ခြင်း၏ ထူးခြားသော အချိန်အတွက် ကျွန်ုပ်တို့နှင့် ပူးပေါင်းပါ။' }, sections: { personal: 'ကိုယ်ရေးကိုယ်တာ အချက်အလက်', church: 'ဘုရားကျောင်း အချက်အလက်', participation: 'ပါဝင်မှု', days: 'ရက်များနှင့် အစည်းအဝေးများ' }, labels: { title: 'ဘွဲ့', first_name: 'ပထမ နာမည်', last_name: 'နောက်ဆုံး နာမည်', email_address: 'အီးမေးလ် လိပ်စာ', phone_number: 'ဖုန်းနံပါတ်', kingschat_username: 'KingsChat အသုံးပြုသူ နာမည်', zone: 'ဇုန်', group: 'အုပ်စု', church: 'ဘုရားကျောင်း', participation_question: 'Asese တွင် Rhapathon ညီလာခံသို့ တက်ရောက်မည်လား?', select_days_sessions: 'သင်၏ ရက်များနှင့် အစည်းအဝေးများကို ရွေးချယ်ပါ', sessions: 'အစည်းအဝေးများ', yes: 'ဟုတ်ကဲ့', no: 'မဟုတ်ပါ' }, placeholders: { first_name: 'ပထမ နာမည် ထည့်ပါ', last_name: 'နောက်ဆုံး နာမည် ထည့်ပါ', email: 'သင်၏.အီးမေးလ်@ဥပမာ.com', phone_local: 'ဒေသဆိုင်ရာ နံပါတ်', kingschat: '@အသုံးပြုသူ_နာမည်', group: 'သင်၏ အုပ်စု ထည့်ပါ', church: 'ဘုရားကျောင်း နာမည် ထည့်ပါ' }, dropdowns: { select_title: 'ဘွဲ့ ရွေးချယ်ပါ', select_zone: 'သင်၏ ဇုန် ရွေးချယ်ပါ', select_code: 'ကုဒ် ရွေးချယ်ပါ' }, days: { monday: 'တနင်္လာ', tuesday: 'အင်္ဂါ', wednesday: 'ဗုဒ္ဓဟူး', thursday: 'ကြာသပတေး', friday: 'သောကြာ' }, buttons: { select_all: 'ရက်အားလုံး ရွေးချယ်ပါ', clear_all: 'ရှင်းလင်းပါ', submit: 'Rhapathon အတွက် မှတ်ပုံတင်ပါ', submitting: 'ပို့နေသည်...', confirm: 'ကောင်းပါသည်!', ok: 'အိုကေ' }, errors: { days_required: 'အနည်းဆုံး တစ်ရက် ရွေးချယ်ပါ။', sessions_required: 'အနည်းဆုံး တစ်ခု အစည်းအဝေး ရွေးချယ်ပါ။' }, notes: { days_note: 'အနည်းဆုံး တစ်ရက်နှင့် တစ်ခု အစည်းအဝေး လိုအပ်သည်' }, alerts: { success_title: 'မှတ်ပုံတင်ခြင်း အောင်မြင်သည်!', error_title: 'အမှား' } },
        si: { header: { title: 'Pastor Chris සමඟ Rhapathon', subtitle: '2026 සංස්කරණය', date: 'සඳුදා 4 - සිකුරාදා 8 මැයි 2026', desc: 'අපගේ දිව්‍ය වරම සම්පූර්ණ කිරීමේදී දර්ශනය පිරිපහදු කිරීමේ අසාමාන්‍ය කාලය සඳහා අප සමඟ එක්වන්න.' }, sections: { personal: 'පුද්ගලික තොරතුරු', church: 'පල්ලියේ තොරතුරු', participation: 'සහභාගීත්වය', days: 'දින සහ සැසි' }, labels: { title: 'ශීර්ෂය', first_name: 'පළමු නම', last_name: 'අවසාන නම', email_address: 'ඊමේල් ලිපිනය', phone_number: 'දුරකථන අංකය', kingschat_username: 'KingsChat පරිශීලක නම', zone: 'කලාපය', group: 'කණ්ඩායම', church: 'පල්ලිය', participation_question: 'ඔබ Asese හි Rhapathon සම්මේලනයට පෞද්ගලිකව සහභාගී වනවාද?', select_days_sessions: 'ඔබගේ දින සහ සැසි තෝරන්න', sessions: 'සැසි', yes: 'ඔව්', no: 'නැහැ' }, placeholders: { first_name: 'පළමු නම ඇතුළත් කරන්න', last_name: 'අවසාන නම ඇතුළත් කරන්න', email: 'ඔබගේ.ඊමේල්@උදාහරණය.com', phone_local: 'ප්‍රාදේශීය අංකය', kingschat: '@පරිශීලක_නම', group: 'ඔබගේ කණ්ඩායම ඇතුළත් කරන්න', church: 'පල්ලියේ නම ඇතුළත් කරන්න' }, dropdowns: { select_title: 'ශීර්ෂය තෝරන්න', select_zone: 'ඔබගේ කලාපය තෝරන්න', select_code: 'කේතය තෝරන්න' }, days: { monday: 'සඳුදා', tuesday: 'අඟහරුවාදා', wednesday: 'බදාදා', thursday: 'බ්‍රහස්පතින්දා', friday: 'සිකුරාදා' }, buttons: { select_all: 'සියලුම දින තෝරන්න', clear_all: 'ඉවත් කරන්න', submit: 'Rhapathon සඳහා ලියාපදිංචි වන්න', submitting: 'යවමින්...', confirm: 'විශිෂ්ට!', ok: 'හරි' }, errors: { days_required: 'කරුණාකර අවම වශයෙන් එක් දිනයක් තෝරන්න.', sessions_required: 'කරුණාකර අවම වශයෙන් එක් සැසියක් තෝරන්න.' }, notes: { days_note: 'අවම වශයෙන් එක් දිනයක් සහ එක් සැසියක් අවශ්‍ය වේ' }, alerts: { success_title: 'ලියාපදිංචිය සාර්ථකයි!', error_title: 'දෝෂය' } },
        ne: { header: { title: 'पास्टर क्रिससँग Rhapathon', subtitle: '2026 संस्करण', date: 'सोमबार 4 - शुक्रबार 8 मे 2026', desc: 'हाम्रो दिव्य आदेश पूरा गर्नमा दृष्टिकोणको परिष्करणको असाधारण समयको लागि हामीसँग सामेल हुनुहोस्।' }, sections: { personal: 'व्यक्तिगत जानकारी', church: 'चर्चको जानकारी', participation: 'सहभागिता', days: 'दिनहरू र सत्रहरू' }, labels: { title: 'उपाधि', first_name: 'पहिलो नाम', last_name: 'अन्तिम नाम', email_address: 'इमेल ठेगाना', phone_number: 'फोन नम्बर', kingschat_username: 'KingsChat प्रयोगकर्ता नाम', zone: 'क्षेत्र', group: 'समूह', church: 'चर्च', participation_question: 'के तपाईं Asese मा Rhapathon सम्मेलनमा स्थानीय रूपमा सहभागी हुनुहुन्छ?', select_days_sessions: 'आफ्ना दिनहरू र सत्रहरू छान्नुहोस्', sessions: 'सत्रहरू', yes: 'हो', no: 'होइन' }, placeholders: { first_name: 'पहिलो नाम प्रविष्ट गर्नुहोस्', last_name: 'अन्तिम नाम प्रविष्ट गर्नुहोस्', email: 'तपाईंको.इमेल@उदाहरण.com', phone_local: 'स्थानीय नम्बर', kingschat: '@प्रयोगकर्ता_नाम', group: 'आफ्नो समूह प्रविष्ट गर्नुहोस्', church: 'चर्चको नाम प्रविष्ट गर्नुहोस्' }, dropdowns: { select_title: 'उपाधि छान्नुहोस्', select_zone: 'आफ्नो क्षेत्र छान्नुहोस्', select_code: 'कोड छान्नुहोस्' }, days: { monday: 'सोमबार', tuesday: 'मंगलबार', wednesday: 'बुधबार', thursday: 'बिहिबार', friday: 'शुक्रबार' }, buttons: { select_all: 'सबै दिनहरू छान्नुहोस्', clear_all: 'खाली गर्नुहोस्', submit: 'Rhapathon को लागि दर्ता गर्नुहोस्', submitting: 'पठाउँदै...', confirm: 'उत्कृष्ट!', ok: 'ठीक छ' }, errors: { days_required: 'कृपया कम्तिमा एक दिन छान्नुहोस्।', sessions_required: 'कृपया कम्तिमा एक सत्र छान्नुहोस्।' }, notes: { days_note: 'कम्तिमा एक दिन र एक सत्र आवश्यक छ' }, alerts: { success_title: 'दर्ता सफल भयो!', error_title: 'त्रुटि' } },
        pa: { header: { title: 'ਪਾਸਟਰ ਕ੍ਰਿਸ ਨਾਲ Rhapathon', subtitle: '2026 ਐਡੀਸ਼ਨ', date: 'ਸੋਮਵਾਰ 4 - ਸ਼ੁੱਕਰਵਾਰ 8 ਮਈ 2026', desc: 'ਸਾਡੇ ਦਿਵਿਆਈ ਆਦੇਸ਼ ਨੂੰ ਪੂਰਾ ਕਰਨ ਵਿੱਚ ਦ੍ਰਿਸ਼ਟੀ ਦੇ ਸੁਧਾਰ ਦੇ ਅਸਾਧਾਰਨ ਸਮੇਂ ਲਈ ਸਾਡੇ ਨਾਲ ਸ਼ਾਮਲ ਹੋਵੋ।' }, sections: { personal: 'ਨਿੱਜੀ ਜਾਣਕਾਰੀ', church: 'ਚਰਚ ਦੀ ਜਾਣਕਾਰੀ', participation: 'ਭਾਗੀਦਾਰੀ', days: 'ਦਿਨ ਅਤੇ ਸੈਸ਼ਨ' }, labels: { title: 'ਸਿਰਲੇਖ', first_name: 'ਪਹਿਲਾ ਨਾਮ', last_name: 'ਆਖਰੀ ਨਾਮ', email_address: 'ਈਮੇਲ ਪਤਾ', phone_number: 'ਫੋਨ ਨੰਬਰ', kingschat_username: 'KingsChat ਵਰਤੋਂਕਾਰ ਨਾਮ', zone: 'ਜ਼ੋਨ', group: 'ਸਮੂਹ', church: 'ਚਰਚ', participation_question: 'ਕੀ ਤੁਸੀਂ Asese ਵਿੱਚ Rhapathon ਕਾਨਫਰੰਸ ਵਿੱਚ ਸਥਾਨਕ ਤੌਰ \'ਤੇ ਭਾਗ ਲਓਗੇ?', select_days_sessions: 'ਆਪਣੇ ਦਿਨ ਅਤੇ ਸੈਸ਼ਨ ਚੁਣੋ', sessions: 'ਸੈਸ਼ਨ', yes: 'ਹਾਂ', no: 'ਨਹੀਂ' }, placeholders: { first_name: 'ਪਹਿਲਾ ਨਾਮ ਦਾਖਲ ਕਰੋ', last_name: 'ਆਖਰੀ ਨਾਮ ਦਾਖਲ ਕਰੋ', email: 'ਤੁਹਾਡਾ.ਈਮੇਲ@ਉਦਾਹਰਨ.com', phone_local: 'ਸਥਾਨਕ ਨੰਬਰ', kingschat: '@ਵਰਤੋਂਕਾਰ_ਨਾਮ', group: 'ਆਪਣਾ ਸਮੂਹ ਦਾਖਲ ਕਰੋ', church: 'ਚਰਚ ਦਾ ਨਾਮ ਦਾਖਲ ਕਰੋ' }, dropdowns: { select_title: 'ਸਿਰਲੇਖ ਚੁਣੋ', select_zone: 'ਆਪਣਾ ਜ਼ੋਨ ਚੁਣੋ', select_code: 'ਕੋਡ ਚੁਣੋ' }, days: { monday: 'ਸੋਮਵਾਰ', tuesday: 'ਮੰਗਲਵਾਰ', wednesday: 'ਬੁੱਧਵਾਰ', thursday: 'ਵੀਰਵਾਰ', friday: 'ਸ਼ੁੱਕਰਵਾਰ' }, buttons: { select_all: 'ਸਾਰੇ ਦਿਨ ਚੁਣੋ', clear_all: 'ਸਾਫ਼ ਕਰੋ', submit: 'Rhapathon ਲਈ ਰਜਿਸਟਰ ਕਰੋ', submitting: 'ਭੇਜ ਰਿਹਾ ਹੈ...', confirm: 'ਬਹੁਤ ਵਧੀਆ!', ok: 'ਠੀਕ ਹੈ' }, errors: { days_required: 'ਕਿਰਪਾ ਕਰਕੇ ਘੱਟੋ ਘੱਟ ਇੱਕ ਦਿਨ ਚੁਣੋ।', sessions_required: 'ਕਿਰਪਾ ਕਰਕੇ ਘੱਟੋ ਘੱਟ ਇੱਕ ਸੈਸ਼ਨ ਚੁਣੋ।' }, notes: { days_note: 'ਘੱਟੋ ਘੱਟ ਇੱਕ ਦਿਨ ਅਤੇ ਇੱਕ ਸੈਸ਼ਨ ਦੀ ਲੋੜ ਹੈ' }, alerts: { success_title: 'ਰਜਿਸਟ੍ਰੇਸ਼ਨ ਸਫਲ!', error_title: 'ਗਲਤੀ' } },
        or: { header: { title: 'Pastor Chris ସହିତ Rhapathon', subtitle: '2026 ସଂସ୍କରଣ', date: 'ସୋମବାର 4 - ଶୁକ୍ରବାର 8 ମଇ 2026', desc: 'ଆମର ଦିବ୍ୟ ଆଦେଶ ସମ୍ପୂର୍ଣ୍ଣ କରିବାରେ ଦର୍ଶନର ପରିଶୋଧନର ଅସାଧାରଣ ସମୟ ପାଇଁ ଆମ ସହିତ ଯୋଗ ଦିଅନ୍ତୁ।' }, sections: { personal: 'ବ୍ୟକ୍ତିଗତ ସୂଚନା', church: 'ଚର୍ଚ୍ଚ ସୂଚନା', participation: 'ଅଂଶଗ୍ରହଣ', days: 'ଦିନ ଏବଂ ଅଧିବେଶନ' }, labels: { title: 'ଶୀର୍ଷକ', first_name: 'ପ୍ରଥମ ନାମ', last_name: 'ଶେଷ ନାମ', email_address: 'ଇମେଲ ଠିକଣା', phone_number: 'ଫୋନ ନମ୍ବର', kingschat_username: 'KingsChat ଉପଯୋଗକର୍ତ୍ତା ନାମ', zone: 'ଜୋନ୍', group: 'ଗୋଷ୍ଠୀ', church: 'ଚର୍ଚ୍ଚ', participation_question: 'ଆପଣ Asese ରେ Rhapathon ସମ୍ମିଳନୀରେ ସ୍ଥାନୀୟ ଭାବରେ ଅଂଶଗ୍ରହଣ କରିବେ କି?', select_days_sessions: 'ଆପଣଙ୍କର ଦିନ ଏବଂ ଅଧିବେଶନ ବାଛନ୍ତୁ', sessions: 'ଅଧିବେଶନ', yes: 'ହଁ', no: 'ନା' }, placeholders: { first_name: 'ପ୍ରଥମ ନାମ ପ୍ରବିଷ୍ଟ କରନ୍ତୁ', last_name: 'ଶେଷ ନାମ ପ୍ରବିଷ୍ଟ କରନ୍ତୁ', email: 'ଆପଣଙ୍କର.ଇମେଲ@ଉଦାହରଣ.com', phone_local: 'ସ୍ଥାନୀୟ ନମ୍ବର', kingschat: '@ଉପଯୋଗକର୍ତ୍ତା_ନାମ', group: 'ଆପଣଙ୍କର ଗୋଷ୍ଠୀ ପ୍ରବିଷ୍ଟ କରନ୍ତୁ', church: 'ଚର୍ଚ୍ଚର ନାମ ପ୍ରବିଷ୍ଟ କରନ୍ତୁ' }, dropdowns: { select_title: 'ଶୀର୍ଷକ ବାଛନ୍ତୁ', select_zone: 'ଆପଣଙ୍କର ଜୋନ୍ ବାଛନ୍ତୁ', select_code: 'କୋଡ୍ ବାଛନ୍ତୁ' }, days: { monday: 'ସୋମବାର', tuesday: 'ମଙ୍ଗଳବାର', wednesday: 'ବୁଧବାର', thursday: 'ଗୁରୁବାର', friday: 'ଶୁକ୍ରବାର' }, buttons: { select_all: 'ସମସ୍ତ ଦିନ ବାଛନ୍ତୁ', clear_all: 'ସଫା କରନ୍ତୁ', submit: 'Rhapathon ପାଇଁ ପଞ୍ଜୀକରଣ କରନ୍ତୁ', submitting: 'ପଠାଉଛି...', confirm: 'ଉତ୍କୃଷ୍ଟ!', ok: 'ଠିକ୍ ଅଛି' }, errors: { days_required: 'ଦୟାକରି ଅତି କମରେ ଗୋଟିଏ ଦିନ ବାଛନ୍ତୁ।', sessions_required: 'ଦୟାକରି ଅତି କମରେ ଗୋଟିଏ ଅଧିବେଶନ ବାଛନ୍ତୁ।' }, notes: { days_note: 'ଅତି କମରେ ଗୋଟିଏ ଦିନ ଏବଂ ଗୋଟିଏ ଅଧିବେଶନ ଆବଶ୍ୟକ' }, alerts: { success_title: 'ପଞ୍ଜୀକରଣ ସଫଳ!', error_title: 'ତ୍ରୁଟି' } },
        // More African languages
        xh: { header: { title: 'I-Rhapathon no-Pastor Chris', subtitle: 'Ushicilelo luka-2026', date: 'ngoMvulo 4 - ngoLwesihlanu 8 Meyi 2026', desc: 'Zijoyine nathi ngexesha elikhethekileyo lokucoca umbono ekugqibezeni umyalelo wethu ongcwele.' }, sections: { personal: 'Ulwazi Lomntu', church: 'Ulwazi Lwecawa', participation: 'Ukuthatha Inxaxheba', days: 'Iintsuku Neeseshoni' }, labels: { title: 'Isihloko', first_name: 'Igama Lokuqala', last_name: 'Ifani', email_address: 'Idilesi ye-imeyili', phone_number: 'Inombolo Yomnxeba', kingschat_username: 'Igama Lomsebenzisi we-KingsChat', zone: 'Ummandla', group: 'Iqela', church: 'Icawa', participation_question: 'Ngaba uza kuthatha inxaxheba ngqo kwinkomfa ye-Rhapathon e-Asese?', select_days_sessions: 'Khetha iintsuku zakho neeseshoni', sessions: 'Iiseshoni', yes: 'Ewe', no: 'Hayi' }, placeholders: { first_name: 'Faka igama lokuqala', last_name: 'Faka ifani', email: 'i-imeyili.yakho@umzekelo.com', phone_local: 'Inombolo yengingqi', kingschat: '@igama_lomsebenzisi', group: 'Faka iqela lakho', church: 'Faka igama lecawa' }, dropdowns: { select_title: 'Khetha isihloko', select_zone: 'Khetha ummandla wakho', select_code: 'Khetha ikhowudi' }, days: { monday: 'uMvulo', tuesday: 'uLwesibini', wednesday: 'uLwesithathu', thursday: 'uLwesine', friday: 'uLwesihlanu' }, buttons: { select_all: 'Khetha zonke iintsuku', clear_all: 'Cima', submit: 'Bhalisa ku-Rhapathon', submitting: 'Kuyathunyelwa...', confirm: 'Kuhle kakhulu!', ok: 'Kulungile' }, errors: { days_required: 'Nceda ukhethe ubuncinane usuku olunye.', sessions_required: 'Nceda ukhethe ubuncinane iseshoni enye.' }, notes: { days_note: 'Kufuneka ubuncinane usuku olunye neseshoni enye' }, alerts: { success_title: 'Ukubhalisa kuphumelele!', error_title: 'Impazamo' } },
        st: { header: { title: 'Rhapathon le Pastor Chris', subtitle: 'Khatiso ya 2026', date: 'Mantaha 4 - Labohlano 8 Motsheanong 2026', desc: 'Ikopanye le rona bakeng sa nako e ikhethang ya ho ntlafatsa pono ho phethahatsa bolaodi ba rona bo halalelang.' }, sections: { personal: 'Tlhahisoleseding ya Motho', church: 'Tlhahisoleseding ya Kereke', participation: 'Ho nka Karolo', days: 'Matsatsi le Dipuisano' }, labels: { title: 'Sehlooho', first_name: 'Lebitso la Pele', last_name: 'Lebitso la ho Qetela', email_address: 'Aterese ya Email', phone_number: 'Nomoro ya Mohala', kingschat_username: 'Lebitso la Mosebelisi wa KingsChat', zone: 'Sebaka', group: 'Sehlopha', church: 'Kereke', participation_question: 'Na o tla nka karolo sebakeng sa kopano ya Rhapathon ho Asese?', select_days_sessions: 'Khetha matsatsi a hao le dipuisano', sessions: 'Dipuisano', yes: 'E', no: 'Che' }, placeholders: { first_name: 'Kenya lebitso la pele', last_name: 'Kenya lebitso la ho qetela', email: 'email.ya.hao@mohlala.com', phone_local: 'Nomoro ya lehae', kingschat: '@lebitso_la_mosebelisi', group: 'Kenya sehlopha sa hao', church: 'Kenya lebitso la kereke' }, dropdowns: { select_title: 'Khetha sehlooho', select_zone: 'Khetha sebaka sa hao', select_code: 'Khetha khoutu' }, days: { monday: 'Mantaha', tuesday: 'Labobedi', wednesday: 'Laboraro', thursday: 'Labone', friday: 'Labohlano' }, buttons: { select_all: 'Khetha matsatsi ohle', clear_all: 'Hlakola', submit: 'Ingolise bakeng sa Rhapathon', submitting: 'E romelloa...', confirm: 'Ho molemo!', ok: 'Ho lokile' }, errors: { days_required: 'Ka kopo khetha bonyane letsatsi le le leng.', sessions_required: 'Ka kopo khetha bonyane puisano e le nngwe.' }, notes: { days_note: 'Ho hlokahala bonyane letsatsi le le leng le puisano e le nngwe' }, alerts: { success_title: 'Ngoliso e atlehile!', error_title: 'Phoso' } },
        tn: { header: { title: 'Rhapathon le Pastor Chris', subtitle: 'Kgatiso ya 2026', date: 'Mosupologo 4 - Labotlhano 8 Moranang 2026', desc: 'Ikopanye le rona mo nakong e e kgethegileng ya go tokafatsa pono mo go fediseng taelo ya rona e e boitshepo.' }, sections: { personal: 'Tshedimosetso ya Motho', church: 'Tshedimosetso ya Kereke', participation: 'Go Tsaya Karolo', days: 'Malatsi le Dipuisano' }, labels: { title: 'Setlhogo', first_name: 'Leina la Ntlha', last_name: 'Leina la Bofelo', email_address: 'Aterese ya Imeile', phone_number: 'Nomoro ya Mogala', kingschat_username: 'Leina la Modirisi wa KingsChat', zone: 'Kgaolo', group: 'Setlhopha', church: 'Kereke', participation_question: 'A o tla tsaya karolo mo lefelong la kopano ya Rhapathon kwa Asese?', select_days_sessions: 'Tlhopha malatsi a gago le dipuisano', sessions: 'Dipuisano', yes: 'Ee', no: 'Nnyaa' }, placeholders: { first_name: 'Tsenya leina la ntlha', last_name: 'Tsenya leina la bofelo', email: 'imeile.ya.gago@sekai.com', phone_local: 'Nomoro ya selegae', kingschat: '@leina_la_modirisi', group: 'Tsenya setlhopha sa gago', church: 'Tsenya leina la kereke' }, dropdowns: { select_title: 'Tlhopha setlhogo', select_zone: 'Tlhopha kgaolo ya gago', select_code: 'Tlhopha khodo' }, days: { monday: 'Mosupologo', tuesday: 'Labobedi', wednesday: 'Laboraro', thursday: 'Labone', friday: 'Labotlhano' }, buttons: { select_all: 'Tlhopha malatsi otlhe', clear_all: 'Phimola', submit: 'Ikwadise mo go Rhapathon', submitting: 'Go romelwa...', confirm: 'Go siame!', ok: 'Go siame' }, errors: { days_required: 'Ka kopo tlhopha bonyane letsatsi le le lengwe.', sessions_required: 'Ka kopo tlhopha bonyane puisano e le nngwe.' }, notes: { days_note: 'Go tlhokega bonyane letsatsi le le lengwe le puisano e le nngwe' }, alerts: { success_title: 'Kwadiso e atlehile!', error_title: 'Phoso' } },
        // Continue with remaining languages - Middle Eastern and others
        ku: { header: { title: 'Rhapathon bi Pastor Chris re', subtitle: 'Weşana 2026', date: 'Duşem 4 - În 8 Gulan 2026', desc: 'Ji bo demek taybet a paqijkirina dîtina di temamkirina fermana me ya îlahî de bi me re bibe hevpar.' }, sections: { personal: 'Agahiyên Kesane', church: 'Agahiyên Dêr', participation: 'Beşdarî', days: 'Roj û Danişîn' }, labels: { title: 'Sernav', first_name: 'Navê Yekem', last_name: 'Navê Dawî', email_address: 'Navnîşana Email', phone_number: 'Hejmara Têlefon', kingschat_username: 'Navê Bikarhêner KingsChat', zone: 'Herêm', group: 'Kom', church: 'Dêr', participation_question: 'Hûn ê di konferansa Rhapathon a li Asese de bi xwe re beşdar bibin?', select_days_sessions: 'Roj û danişînên xwe hilbijêrin', sessions: 'Danişîn', yes: 'Erê', no: 'Na' }, placeholders: { first_name: 'Navê yekem binivîsin', last_name: 'Navê dawî binivîsin', email: 'email.we@nimûne.com', phone_local: 'Hejmara herêmî', kingschat: '@navê_bikarhêner', group: 'Koma xwe binivîsin', church: 'Navê dêr binivîsin' }, dropdowns: { select_title: 'Sernav hilbijêrin', select_zone: 'Herêma xwe hilbijêrin', select_code: 'Kod hilbijêrin' }, days: { monday: 'Duşem', tuesday: 'Sêşem', wednesday: 'Çarşem', thursday: 'Pêncşem', friday: 'În' }, buttons: { select_all: 'Hemû rojan hilbijêrin', clear_all: 'Paqij bike', submit: 'Ji bo Rhapathon tomar bike', submitting: 'Tê şandin...', confirm: 'Pir baş!', ok: 'Baş e' }, errors: { days_required: 'Ji kerema xwe kêmî yek roj hilbijêrin.', sessions_required: 'Ji kerema xwe kêmî yek danişîn hilbijêrin.' }, notes: { days_note: 'Kêmî yek roj û yek danişîn pêwîst e' }, alerts: { success_title: 'Tomarkirin bi ser ket!', error_title: 'Çewtî' } },
        az: { header: { title: 'Pastor Chris ilə Rhapathon', subtitle: '2026 Nəşri', date: 'Bazar ertəsi 4 - Cümə 8 May 2026', desc: 'İlahi mandatımızı tamamlamaqda görmənin təmizlənməsinin fövqəladə vaxtı üçün bizə qoşulun.' }, sections: { personal: 'Şəxsi Məlumat', church: 'Kilsə Məlumatı', participation: 'İştirak', days: 'Günlər və Sessiyalar' }, labels: { title: 'Başlıq', first_name: 'Ad', last_name: 'Soyad', email_address: 'Email Ünvanı', phone_number: 'Telefon Nömrəsi', kingschat_username: 'KingsChat İstifadəçi Adı', zone: 'Zona', group: 'Qrup', church: 'Kilsə', participation_question: 'Asesedə Rhapathon konfransında yerində iştirak edəcəksiniz?', select_days_sessions: 'Günlərinizi və sessiyalarınızı seçin', sessions: 'Sessiyalar', yes: 'Bəli', no: 'Xeyr' }, placeholders: { first_name: 'Adınızı daxil edin', last_name: 'Soyadınızı daxil edin', email: 'sizin.email@nümunə.com', phone_local: 'Yerli nömrə', kingschat: '@istifadəçi_adı', group: 'Qrupunuzu daxil edin', church: 'Kilsə adını daxil edin' }, dropdowns: { select_title: 'Başlıq seçin', select_zone: 'Zonanızı seçin', select_code: 'Kod seçin' }, days: { monday: 'Bazar ertəsi', tuesday: 'Çərşənbə axşamı', wednesday: 'Çərşənbə', thursday: 'Cümə axşamı', friday: 'Cümə' }, buttons: { select_all: 'Bütün günləri seç', clear_all: 'Təmizlə', submit: 'Rhapathon üçün qeydiyyat', submitting: 'Göndərilir...', confirm: 'Əla!', ok: 'Yaxşı' }, errors: { days_required: 'Xahiş edirəm ən azı bir gün seçin.', sessions_required: 'Xahiş edirəm ən azı bir sessiya seçin.' }, notes: { days_note: 'Ən azı bir gün və bir sessiya tələb olunur' }, alerts: { success_title: 'Qeydiyyat uğurlu!', error_title: 'Xəta' } },
        ka: { header: { title: 'Rhapathon პასტორ კრისთან', subtitle: '2026 გამოცემა', date: 'ორშაბათი 4 - პარასკევი 8 მაისი 2026', desc: 'შემოუერთდით ჩვენს ღვთაებრივ მანდატის შესრულებაში ხედვის გაწმენდის განსაკუთრებულ დროს.' }, sections: { personal: 'პირადი ინფორმაცია', church: 'ეკლესიის ინფორმაცია', participation: 'მონაწილეობა', days: 'დღეები და სესიები' }, labels: { title: 'წოდება', first_name: 'სახელი', last_name: 'გვარი', email_address: 'ელფოსტის მისამართი', phone_number: 'ტელეფონის ნომერი', kingschat_username: 'KingsChat მომხმარებლის სახელი', zone: 'ზონა', group: 'ჯგუფი', church: 'ეკლესია', participation_question: 'მონაწილეობას მიიღებთ ადგილზე Rhapathon კონფერენციაში Asese-ში?', select_days_sessions: 'აირჩიეთ თქვენი დღეები და სესიები', sessions: 'სესიები', yes: 'დიახ', no: 'არა' }, placeholders: { first_name: 'შეიყვანეთ სახელი', last_name: 'შეიყვანეთ გვარი', email: 'თქვენი.ელფოსტა@მაგალითი.com', phone_local: 'ადგილობრივი ნომერი', kingschat: '@მომხმარებლის_სახელი', group: 'შეიყვანეთ თქვენი ჯგუფი', church: 'შეიყვანეთ ეკლესიის სახელი' }, dropdowns: { select_title: 'აირჩიეთ წოდება', select_zone: 'აირჩიეთ თქვენი ზონა', select_code: 'აირჩიეთ კოდი' }, days: { monday: 'ორშაბათი', tuesday: 'სამშაბათი', wednesday: 'ოთხშაბათი', thursday: 'ხუთშაბათი', friday: 'პარასკევი' }, buttons: { select_all: 'აირჩიეთ ყველა დღე', clear_all: 'გასუფთავება', submit: 'რეგისტრაცია Rhapathon-ისთვის', submitting: 'იგზავნება...', confirm: 'შესანიშნავი!', ok: 'კარგი' }, errors: { days_required: 'გთხოვთ აირჩიოთ მინიმუმ ერთი დღე.', sessions_required: 'გთხოვთ აირჩიოთ მინიმუმ ერთი სესია.' }, notes: { days_note: 'საჭიროა მინიმუმ ერთი დღე და ერთი სესია' }, alerts: { success_title: 'რეგისტრაცია წარმატებულია!', error_title: 'შეცდომა' } },
        cy: { header: { title: 'Rhapathon gyda\'r Pastor Chris', subtitle: 'Argraffiad 2026', date: 'Dydd Llun 4 - Dydd Gwener 8 Mai 2026', desc: 'Ymunwch â ni am amser eithriadol o buro\'r weledigaeth wrth gwblhau ein mandad dwyfol.' }, sections: { personal: 'Gwybodaeth Bersonol', church: 'Gwybodaeth Eglwys', participation: 'Cyfranogiad', days: 'Dyddiau a Sesiynau' }, labels: { title: 'Teitl', first_name: 'Enw Cyntaf', last_name: 'Cyfenw', email_address: 'Cyfeiriad E-bost', phone_number: 'Rhif Ffôn', kingschat_username: 'Enw Defnyddiwr KingsChat', zone: 'Parth', group: 'Grŵp', church: 'Eglwys', participation_question: 'A fyddwch chi\'n cymryd rhan yn y man yn y gynhadledd Rhapathon yn Asese?', select_days_sessions: 'Dewiswch eich dyddiau a\'ch sesiynau', sessions: 'Sesiynau', yes: 'Ydw', no: 'Nac ydw' }, placeholders: { first_name: 'Rhowch yr enw cyntaf', last_name: 'Rhowch y cyfenw', email: 'eich.ebost@enghraifft.com', phone_local: 'Rhif lleol', kingschat: '@enw_defnyddiwr', group: 'Rhowch eich grŵp', church: 'Rhowch enw\'r eglwys' }, dropdowns: { select_title: 'Dewiswch deitl', select_zone: 'Dewiswch eich parth', select_code: 'Dewiswch god' }, days: { monday: 'Dydd Llun', tuesday: 'Dydd Mawrth', wednesday: 'Dydd Mercher', thursday: 'Dydd Iau', friday: 'Dydd Gwener' }, buttons: { select_all: 'Dewis pob dydd', clear_all: 'Clirio', submit: 'Cofrestru ar gyfer Rhapathon', submitting: 'Yn anfon...', confirm: 'Ardderchog!', ok: 'Iawn' }, errors: { days_required: 'Dewiswch o leiaf un diwrnod.', sessions_required: 'Dewiswch o leiaf un sesiwn.' }, notes: { days_note: 'Mae angen o leiaf un diwrnod ac un sesiwn' }, alerts: { success_title: 'Cofrestriad llwyddiannus!', error_title: 'Gwall' } },
        eu: { header: { title: 'Rhapathon Pastor Chris-ekin', subtitle: '2026 Edizioa', date: 'Astelehena 4 - Ostirala 8 Maiatza 2026', desc: 'Gure jainkozko agindua betetzean ikuspegia garbiztatzeko denbora berezia izateko gurekin bat egin.' }, sections: { personal: 'Informazio Pertsonala', church: 'Elizaren Informazioa', participation: 'Parte-hartzea', days: 'Egunak eta Saioak' }, labels: { title: 'Izenburua', first_name: 'Lehen Izena', last_name: 'Azken Izena', email_address: 'Email Helbidea', phone_number: 'Telefono Zenbakia', kingschat_username: 'KingsChat Erabiltzaile Izena', zone: 'Eremua', group: 'Taldea', church: 'Eliza', participation_question: 'Asese-ko Rhapathon konferentzian tokian bertan parte hartuko duzu?', select_days_sessions: 'Hautatu zure egunak eta saioak', sessions: 'Saioak', yes: 'Bai', no: 'Ez' }, placeholders: { first_name: 'Sartu lehen izena', last_name: 'Sartu azken izena', email: 'zure.emaila@adibidea.com', phone_local: 'Tokiko zenbakia', kingschat: '@erabiltzaile_izena', group: 'Sartu zure taldea', church: 'Sartu elizaren izena' }, dropdowns: { select_title: 'Hautatu izenburua', select_zone: 'Hautatu zure eremua', select_code: 'Hautatu kodea' }, days: { monday: 'Astelehena', tuesday: 'Asteartea', wednesday: 'Asteazkena', thursday: 'Osteguna', friday: 'Ostirala' }, buttons: { select_all: 'Hautatu egun guztiak', clear_all: 'Garbitu', submit: 'Eman izena Rhapathon-erako', submitting: 'Bidaltzen...', confirm: 'Bikaina!', ok: 'Ados' }, errors: { days_required: 'Mesedez hautatu gutxienez egun bat.', sessions_required: 'Mesedez hautatu gutxienez saio bat.' }, notes: { days_note: 'Gutxienez egun bat eta saio bat behar da' }, alerts: { success_title: 'Izen-ematea arrakastatsua!', error_title: 'Errorea' } },
        // Remaining African languages
        ve: { header: { title: 'Rhapathon na Pastor Chris', subtitle: 'Mbuno ya 2026', date: 'Mugivhela 4 - Muḽavhela 8 Shundunthule 2026', desc: 'Tanganelanani na ri kha tshifhinga tsha u khethekanaho tsha u khwiniṱisa tshivhono kha u fhedzisela mulayo washu wa Mudzimu.' }, sections: { personal: 'Mafhungo a Munwe', church: 'Mafhungo a Kereke', participation: 'U dzhenelela', days: 'Maduvha na Miṱangano' }, labels: { title: 'Thahelelo', first_name: 'Dzina ḽa u Thoma', last_name: 'Dzina ḽa u Fhedzisela', email_address: 'Adresi ya Email', phone_number: 'Nomboro ya Lutingo', kingschat_username: 'Dzina ḽa Mushumisi wa KingsChat', zone: 'Vhukati', group: 'Tshigwada', church: 'Kereke', participation_question: 'Ndi uri ni ḓo dzhenelela kha fhethu kha musangano wa Rhapathon kha Asese?', select_days_sessions: 'Nangani maduvha avho na miṱangano', sessions: 'Miṱangano', yes: 'Ee', no: 'Hai' }, placeholders: { first_name: 'Dzhenisani dzina ḽa u thoma', last_name: 'Dzhenisani dzina ḽa u fhedzisela', email: 'email.yavho@muhlalo.com', phone_local: 'Nomboro ya vhukati', kingschat: '@dzina_ḽa_mushumisi', group: 'Dzhenisani tshigwada tshavho', church: 'Dzhenisani dzina ḽa kereke' }, dropdowns: { select_title: 'Nangani thahelelo', select_zone: 'Nangani vhukati havho', select_code: 'Nangani khodo' }, days: { monday: 'Mugivhela', tuesday: 'Ḽavhuvhili', wednesday: 'Ḽavhuraru', thursday: 'Ḽavhuṋa', friday: 'Muḽavhela' }, buttons: { select_all: 'Nangani maduvha oṱhe', clear_all: 'Thuthani', submit: 'Ṅwalisani kha Rhapathon', submitting: 'Khou rumela...', confirm: 'Zwavhuḓi!', ok: 'Ndi zwone' }, errors: { days_required: 'Ri humbela uri ni nange sa tshi na duvha ḽithihi.', sessions_required: 'Ri humbela uri ni nange sa tshi na muṱangano muthihi.' }, notes: { days_note: 'Hu ṱoḓea sa tshi na duvha ḽithihi na muṱangano muthihi' }, alerts: { success_title: 'Ṅwaliselo yo kona!', error_title: 'Phosho' } },
        ts: { header: { title: 'Rhapathon na Pastor Chris', subtitle: 'Nkandziyiso wa 2026', date: 'Musumbhunuku 4 - Mugqivela 8 Mudyaxihi 2026', desc: 'Mi hlangane na hina eka nkarhi wo hlawuleka wo antswisa xivono eka ku hetisa vulawuri bya hina bya Xikwembu.' }, sections: { personal: 'Mahungu ya Munhu', church: 'Mahungu ya Kereke', participation: 'Ku nghenelela', days: 'Masiku na Mihlangano' }, labels: { title: 'Nhlokomhaka', first_name: 'Vito ra Sungula', last_name: 'Vito ra Makumu', email_address: 'Adirese ya Email', phone_number: 'Nomboro ya Riqingho', kingschat_username: 'Vito ra Mutirhisi wa KingsChat', zone: 'Xifundzha', group: 'Ntlawa', church: 'Kereke', participation_question: 'Xana u ta nghenelela eka ndhawu eka nhlangano wa Rhapathon eka Asese?', select_days_sessions: 'Hlawula masiku ya wena na mihlangano', sessions: 'Mihlangano', yes: 'Ina', no: 'E-e' }, placeholders: { first_name: 'Nghenisa vito ra sungula', last_name: 'Nghenisa vito ra makumu', email: 'email.ya.wena@xikombiso.com', phone_local: 'Nomboro ya le kaya', kingschat: '@vito_ra_mutirhisi', group: 'Nghenisa ntlawa ya wena', church: 'Nghenisa vito ra kereke' }, dropdowns: { select_title: 'Hlawula nhlokomhaka', select_zone: 'Hlawula xifundzha xa wena', select_code: 'Hlawula khodi' }, days: { monday: 'Musumbhunuku', tuesday: 'Ravumbirhi', wednesday: 'Ravunharhu', thursday: 'Ravumune', friday: 'Mugqivela' }, buttons: { select_all: 'Hlawula masiku hinkwawo', clear_all: 'Sula', submit: 'Tsarisa eka Rhapathon', submitting: 'Ku rhumeriwa...', confirm: 'Swa saseka!', ok: 'Swa lulamile' }, errors: { days_required: 'Kombela u hlawula ku nga si na siku rin\'we.', sessions_required: 'Kombela u hlawula ku nga si na nhlangano wun\'we.' }, notes: { days_note: 'Ku laveka ku nga si na siku rin\'we na nhlangano wun\'we' }, alerts: { success_title: 'Ku tsarisa ku hundzukile!', error_title: 'Xihoxo' } },
        ss: { header: { title: 'Rhapathon ne-Pastor Chris', subtitle: 'Lishicilelo la-2026', date: 'Msombuluko 4 - Mgqibelo 8 Inkhwekhweti 2026', desc: 'Joyina nati ngalesi sikhatsi lesihle sekucoca umbono ekupheleliseni umyalo wetfu longcwele.' }, sections: { personal: 'Lwati Lomuntu', church: 'Lwati Lwebandla', participation: 'Kubamba Likhono', days: 'Emalanga neMihlangano' }, labels: { title: 'Sihloko', first_name: 'Ligama Lekucala', last_name: 'Ligama Lekugcina', email_address: 'Likheli le-Email', phone_number: 'Inombolo Yelucingo', kingschat_username: 'Ligama Lomsebenti wa-KingsChat', zone: 'Indvuko', group: 'Libandla', church: 'Bandla', participation_question: 'Ngabe utawubamba likhono lapho eMhlanganisweni wa-Rhapathon e-Asese?', select_days_sessions: 'Khetsa emalanga akho nemihlangano', sessions: 'Mihlangano', yes: 'Yebo', no: 'Cha' }, placeholders: { first_name: 'Faka ligama lekucala', last_name: 'Faka ligama lekugcina', email: 'i-email.yakho@sibonelo.com', phone_local: 'Inombolo yendawo', kingschat: '@ligama_lomsebenti', group: 'Faka libandla lakho', church: 'Faka ligama lebandla' }, dropdowns: { select_title: 'Khetsa sihloko', select_zone: 'Khetsa indvuko yakho', select_code: 'Khetsa likhodi' }, days: { monday: 'Msombuluko', tuesday: 'Lesibili', wednesday: 'Lesitsatfu', thursday: 'Lesine', friday: 'Mgqibelo' }, buttons: { select_all: 'Khetsa emalanga onkhe', clear_all: 'Sula', submit: 'Bhalisa ku-Rhapathon', submitting: 'Kuyatfunyelwa...', confirm: 'Kuhle!', ok: 'Kulungile' }, errors: { days_required: 'Sicela ukhetse buncane lilanga linye.', sessions_required: 'Sicela ukhetse buncane umhlangano munye.' }, notes: { days_note: 'Kudvingeka buncane lilanga linye neumhlangano munye' }, alerts: { success_title: 'Kubhalisa kuphumelele!', error_title: 'Liphutsa' } },
        nr: { header: { title: 'I-Rhapathon lo-Pastor Chris', subtitle: 'Uhlobo lwa-2026', date: 'Mvulo 4 - Sihlanu 8 Nhlaba 2026', desc: 'Zihlanganise nathi ngesikhathi esibalulekile sokucwenga umbono ekupheleliseni umyalelo wethu ongcwele.' }, sections: { personal: 'Ulwazi Lomuntu', church: 'Ulwazi Lwebandla', participation: 'Ukuzibandakanya', days: 'Izinsuku Nezikhathi' }, labels: { title: 'Isihloko', first_name: 'Ibizo Lokuqala', last_name: 'Isibongo', email_address: 'Ikheli le-Email', phone_number: 'Inombolo Yocingo', kingschat_username: 'Ibizo Lomsebenzisi we-KingsChat', zone: 'Indawo', group: 'Iqembu', church: 'Ibandla', participation_question: 'Uzobamba iqhaza lapho enkomfeni ye-Rhapathon e-Asese?', select_days_sessions: 'Khetha izinsuku zakho nezikhathi', sessions: 'Izikhathi', yes: 'Yebo', no: 'Cha' }, placeholders: { first_name: 'Faka ibizo lokuqala', last_name: 'Faka isibongo', email: 'i-email.yakho@isibonelo.com', phone_local: 'Inombolo yendawo', kingschat: '@ibizo_lomsebenzisi', group: 'Faka iqembu lakho', church: 'Faka ibizo lebandla' }, dropdowns: { select_title: 'Khetha isihloko', select_zone: 'Khetha indawo yakho', select_code: 'Khetha ikhodi' }, days: { monday: 'Mvulo', tuesday: 'Sibili', wednesday: 'Sithathu', thursday: 'Sine', friday: 'Sihlanu' }, buttons: { select_all: 'Khetha zonke izinsuku', clear_all: 'Sula', submit: 'Bhalisa ku-Rhapathon', submitting: 'Kuyathunyelwa...', confirm: 'Kuhle!', ok: 'Kulungile' }, errors: { days_required: 'Sicela ukhethe okungenani usuku olulodwa.', sessions_required: 'Sicela ukhethe okungenani isikhathi esisodwa.' }, notes: { days_note: 'Kudingeka okungenani usuku olulodwa nesikhathi esisodwa' }, alerts: { success_title: 'Ukubhalisa kuphumelele!', error_title: 'Iphutha' } }
        }

    Object.entries(i18n).forEach(([lang, copy]) => {
        if (!copy.header) return;
        copy.header.subtitle = getEventSubtitle(copy.header.subtitle);
        copy.header.date = formatEventRange(lang);
    });

    function setLabelText(forId, text) {
        const el = document.querySelector(`label[for="${forId}"]`);
        if (!el) return;
        const hasRequired = !!el.querySelector('span.text-red-500');
        el.innerHTML = '';
        el.append(document.createTextNode(text + ' '));
        if (hasRequired) {
            const star = document.createElement('span');
            star.className = 'text-red-500';
            star.textContent = '*';
            el.appendChild(star);
        }
    }

    // Country to language mapping for automatic detection (same as header)
    const displayMap = { en: 'English', es: 'Español', fr: 'Français', de: 'Deutsch', it: 'Italiano', pt: 'Português', ar: 'العربية', ru: 'Русский', zh: '中文', ja: '日本語', ko: '한국어', hi: 'हिन्दी', tr: 'Türkçe', nl: 'Nederlands', pl: 'Polski', uk: 'Українська', ro: 'Română', el: 'Ελληνικά', hu: 'Magyar', cs: 'Čeština', sk: 'Slovenčina', sv: 'Svenska', da: 'Dansk', fi: 'Suomi', nb: 'Norsk', sw: 'Kiswahili', yo: 'Yorùbá', ig: 'Igbo', ha: 'Hausa', am: 'አማርኛ', zu: 'isiZulu', af: 'Afrikaans', th: 'ไทย', vi: 'Tiếng Việt', he: 'עברית', fa: 'فارسی', ur: 'اردو', bn: 'বাংলা', ta: 'தமிழ்', te: 'తెలుగు', ml: 'മലയാളം', kn: 'ಕನ್ನಡ', gu: 'ગુજરાતી', mr: 'मराठी', is: 'Íslenska', mt: 'Malti', lv: 'Latviešu', lt: 'Lietuvių', et: 'Eesti', sl: 'Slovenščina', hr: 'Hrvatski', bg: 'Български', id: 'Bahasa Indonesia', ms: 'Bahasa Melayu', tl: 'Filipino', km: 'ខ្មែរ', lo: 'ລາວ', my: 'မြန်မာ', si: 'සිංහල', ne: 'नेपाली', pa: 'ਪੰਜਾਬੀ', or: 'ଓଡ଼ିଆ', xh: 'isiXhosa', st: 'Sesotho', tn: 'Setswana', ve: 'Tshivenda', ts: 'Xitsonga', ss: 'SiSwati', nr: 'isiNdebele', ku: 'Kurdî', az: 'Azərbaycan', ka: 'ქართული', cy: 'Cymraeg', eu: 'Euskera' };

    const countryToLanguageMap = {
        // North America
        'US': 'en', 'CA': 'en', 'MX': 'es', 'GT': 'es', 'HN': 'es', 'SV': 'es',
        'NI': 'es', 'CR': 'es', 'PA': 'es', 'CU': 'es', 'DO': 'es', 'PR': 'es',

        // South America
        'BR': 'pt', 'AR': 'es', 'CL': 'es', 'CO': 'es', 'PE': 'es', 'VE': 'es',
        'EC': 'es', 'BO': 'es', 'PY': 'es', 'UY': 'es', 'GY': 'en', 'SR': 'nl',

        // Europe
        'GB': 'en', 'IE': 'en', 'FR': 'fr', 'DE': 'de', 'IT': 'it', 'ES': 'es',
        'PT': 'pt', 'NL': 'nl', 'BE': 'nl', 'LU': 'fr', 'CH': 'de', 'AT': 'de',
        'SE': 'sv', 'NO': 'nb', 'DK': 'da', 'FI': 'fi', 'IS': 'is', 'PL': 'pl',
        'CZ': 'cs', 'SK': 'sk', 'HU': 'hu', 'RO': 'ro', 'BG': 'bg', 'GR': 'el',
        'TR': 'tr', 'RU': 'ru', 'UA': 'uk', 'BY': 'ru', 'MD': 'ro', 'BA': 'bs',
        'HR': 'hr', 'SI': 'sl', 'ME': 'sr', 'MK': 'mk', 'AL': 'sq', 'RS': 'sr',
        'XK': 'sq', 'EE': 'et', 'LV': 'lv', 'LT': 'lt', 'MT': 'mt', 'CY': 'el',

        // Asia
        'CN': 'zh', 'TW': 'zh', 'HK': 'zh', 'MO': 'zh', 'JP': 'ja', 'KR': 'ko',
        'KP': 'ko', 'IN': 'hi', 'PK': 'ur', 'BD': 'bn', 'NP': 'ne', 'LK': 'si',
        'TH': 'th', 'VN': 'vi', 'MY': 'ms', 'SG': 'en', 'PH': 'tl', 'ID': 'id',
        'KH': 'km', 'LA': 'lo', 'MM': 'my', 'IR': 'fa', 'IQ': 'ar', 'SA': 'ar',
        'YE': 'ar', 'OM': 'ar', 'AE': 'ar', 'QA': 'ar', 'BH': 'ar', 'KW': 'ar',
        'JO': 'ar', 'LB': 'ar', 'SY': 'ar', 'PS': 'ar', 'IL': 'he', 'JO': 'ar',
        'EG': 'ar', 'SD': 'ar', 'LY': 'ar', 'TN': 'ar', 'DZ': 'ar', 'MA': 'ar',
        'EH': 'ar', 'MR': 'ar', 'AZ': 'az', 'GE': 'ka', 'AM': 'hy', 'KZ': 'kk',

        // Africa
        'ZA': 'af', 'NG': 'en', 'EG': 'ar', 'ET': 'am', 'KE': 'sw', 'TZ': 'sw',
        'UG': 'sw', 'RW': 'rw', 'BI': 'rn', 'CD': 'fr', 'CG': 'fr', 'GA': 'fr',
        'CM': 'fr', 'TD': 'fr', 'CF': 'fr', 'GQ': 'es', 'SN': 'fr', 'GM': 'en',
        'GN': 'fr', 'SL': 'en', 'LR': 'en', 'CI': 'fr', 'BF': 'fr', 'TG': 'fr',
        'BJ': 'fr', 'GH': 'en', 'CV': 'pt', 'ST': 'pt', 'GQ': 'es', 'GA': 'fr',
        'AO': 'pt', 'MZ': 'pt', 'MW': 'en', 'ZM': 'en', 'ZW': 'en', 'BW': 'tn',
        'NA': 'en', 'ZW': 'en', 'LS': 'st', 'SZ': 'ss', 'MG': 'mg', 'MU': 'en',
        'SC': 'fr', 'KM': 'ar', 'DJ': 'ar', 'SO': 'so', 'ER': 'ti', 'SD': 'ar',
        'SS': 'en', 'LY': 'ar', 'TN': 'ar', 'DZ': 'ar', 'MA': 'ar', 'TN': 'ar',

        // Oceania
        'AU': 'en', 'NZ': 'en', 'FJ': 'en', 'PG': 'en', 'SB': 'en', 'VU': 'fr',
        'NC': 'fr', 'PF': 'fr', 'WS': 'en', 'TO': 'en', 'TV': 'en', 'KI': 'en'
    };

    function applyI18n(lang) {
        currentLang = i18n[lang] ? lang : 'en';
        const t = i18n[currentLang];

        // Update hidden language field for form submission
        const langField = document.getElementById('preferred_language');
        if (langField) {
            langField.value = currentLang;
        }
        // Header
        const ht = document.getElementById('headerTitle'); if (ht) ht.textContent = t.header.title;
        const hs = document.getElementById('headerSubtitle'); if (hs) hs.textContent = t.header.subtitle;
        const hd = document.getElementById('headerDate'); if (hd) hd.textContent = t.header.date;
        const hdsc = document.getElementById('headerDescription'); if (hdsc) hdsc.textContent = t.header.desc;

        // Section headings
        const s1 = document.querySelector('#personalSection h3'); if (s1) s1.textContent = t.sections.personal;
        const s2 = document.querySelector('#churchSection h3'); if (s2) s2.textContent = t.sections.church;
        const s3 = document.querySelector('#participationSection h3'); if (s3) s3.textContent = t.sections.participation;
        const s4 = document.getElementById('daysHeader'); if (s4) s4.textContent = t.sections.days;

        // Labels
        setLabelText('title', t.labels.title);
        setLabelText('first_name', t.labels.first_name);
        setLabelText('last_name', t.labels.last_name);
        setLabelText('email', t.labels.email_address);
        setLabelText('phone_local', t.labels.phone_number);
        setLabelText('kingschat_username', t.labels.kingschat_username);
        setLabelText('zone', t.labels.zone);
        setLabelText('group', t.labels.group);
        setLabelText('church', t.labels.church);

        // Participation question and Yes/No
        const tf = i18n.en;
        const pq = document.getElementById('onsiteQuestion');
        if (pq) pq.innerHTML = `${(t.labels && t.labels.participation_question) || tf.labels.participation_question} <span class="text-red-500">*</span>`;
        const aq = document.getElementById('affiliationQuestion');
        if (aq) aq.innerHTML = `${(t.labels && t.labels.affiliation_choice) || 'Select how you want to register'} <span class="text-red-500">*</span>`;
        const affiliationChurchLabel = document.querySelector('label[for="affiliation_church"] span');
        if (affiliationChurchLabel) affiliationChurchLabel.textContent = (t.labels && t.labels.affiliation_church) || 'Church';
        const affiliationNetworkLabel = document.querySelector('label[for="affiliation_network"] span');
        if (affiliationNetworkLabel) affiliationNetworkLabel.textContent = (t.labels && t.labels.affiliation_network) || 'Network';
        const yesLbl = document.querySelector('label[for="onsite_participation_yes"] span'); if (yesLbl) yesLbl.textContent = (t.labels && t.labels.yes) || tf.labels.yes;
        const noLbl = document.querySelector('label[for="onsite_participation_no"] span'); if (noLbl) noLbl.textContent = (t.labels && t.labels.no) || tf.labels.no;

        // Online participation follow-up text
        const oq = document.getElementById('onlineQuestion'); if (oq) oq.textContent = (t.labels && t.labels.online_participation_question) || 'Will you be participating online?';
        const oYesLbl = document.querySelector('label[for="online_participation_yes"] span'); if (oYesLbl) oYesLbl.textContent = (t.labels && t.labels.yes) || tf.labels.yes;
        const oNoLbl = document.querySelector('label[for="online_participation_no"] span'); if (oNoLbl) oNoLbl.textContent = (t.labels && t.labels.no) || tf.labels.no;
        const watchInfo = document.getElementById('watchOnlineInfo'); if (watchInfo) watchInfo.innerHTML = (t.notes && t.notes.watch_online) || 'You can watch live on Rhapsody TV at <a href="https://rhapsodytv.live" target="_blank" rel="noopener" class="text-accent underline">rhapsodytv.live</a>.';

        // Day selection label + note
        const dsel = document.getElementById('selectDaysLabel'); if (dsel) dsel.childNodes[0].nodeValue = (t.labels.select_days_sessions || t.labels.select_days) + ' ';
        const dnote = document.getElementById('selectDaysNote'); if (dnote) dnote.textContent = (t.notes && t.notes.days_note) ? t.notes.days_note : 'At least one day required';

        // Placeholders
        const pf = document.getElementById('first_name'); if (pf) pf.setAttribute('placeholder', t.placeholders.first_name);
        const pl = document.getElementById('last_name'); if (pl) pl.setAttribute('placeholder', t.placeholders.last_name);
        const pe = document.getElementById('email'); if (pe) pe.setAttribute('placeholder', t.placeholders.email);
        const pn = document.getElementById('phone_local'); if (pn) pn.setAttribute('placeholder', t.placeholders.phone_local);
        const pk = document.getElementById('kingschat_username'); if (pk) pk.setAttribute('placeholder', t.placeholders.kingschat);
        const pc = document.getElementById('church'); if (pc) pc.setAttribute('placeholder', t.placeholders.church);
        const pmn = document.getElementById('manual_network'); if (pmn) pmn.setAttribute('placeholder', (t.placeholders && t.placeholders.network_manual) || 'Enter your network');

        // Feedback label/placeholder
        const fbLabel = document.getElementById('feedbackLabel'); if (fbLabel) fbLabel.textContent = (t.labels && t.labels.feedback_label) || 'Questions or feedback';
        const fb = document.getElementById('feedback'); if (fb) fb.setAttribute('placeholder', (t.placeholders && t.placeholders.feedback_placeholder) || 'Share any questions or feedback...');

        // Dropdown placeholders
        const titleInput = document.getElementById('title');
        const dt = document.getElementById('titleSelected'); if (dt && titleInput && !titleInput.value) dt.textContent = t.dropdowns.select_title;
        const zoneInput = document.getElementById('zone');
        const dz = document.getElementById('zoneSelected'); if (dz && zoneInput && !zoneInput.value) dz.textContent = t.dropdowns.select_zone;
        const groupInput = document.getElementById('group');
        const dg = document.getElementById('groupSelected'); if (dg && groupInput && !groupInput.value) dg.textContent = (t.dropdowns && t.dropdowns.select_group) || 'Select your group';
        const networkInputForLabel = document.getElementById('network');
        const dn = document.getElementById('networkSelected'); if (dn && networkInputForLabel && !networkInputForLabel.value) dn.textContent = (t.dropdowns && t.dropdowns.select_network) || 'Select your network';
        const dc = document.getElementById('countryCodeSelected');
        const cc = document.getElementById('country_code');
        if (dc && cc && !cc.value) dc.textContent = t.dropdowns.select_code;

        // Day names
        const mapDays = [
            { id: 'day_monday', key: 'monday' },
            { id: 'day_tuesday', key: 'tuesday' },
            { id: 'day_wednesday', key: 'wednesday' },
            { id: 'day_thursday', key: 'thursday' },
            { id: 'day_friday', key: 'friday' }
        ];
        mapDays.forEach(({id, key}) => {
            const label = document.querySelector(`label[for="${id}"] .day-name`);
            if (label) label.textContent = t.days[key];
        });


        // Buttons
        const bSel = document.getElementById('selectAllDays'); if (bSel) bSel.textContent = t.buttons.select_all;
        const bClr = document.getElementById('clearAllDays'); if (bClr) bClr.textContent = t.buttons.clear_all;
        const bSub = document.getElementById('submitButtonText'); if (bSub) bSub.textContent = t.buttons.submit;

        // Errors
        const eDays = document.getElementById('days_error'); if (eDays) eDays.textContent = t.errors.days_required;
        const eSess = document.getElementById('sessions_error'); if (eSess) eSess.textContent = t.errors.sessions_required;
        const eAff = document.getElementById('affiliation_error'); if (eAff) eAff.textContent = (t.errors && t.errors.affiliation_required) || 'Please choose either Church or Network.';
    }

    // Apply at load
    applyI18n(currentLang);

    // React to language changes from header
    document.addEventListener('language-change', function(ev){
        const lang = ev.detail && ev.detail.lang || 'en';
        console.log('Language change event received:', lang); // Debug log
        applyI18n(lang);
    });
    
    // Also listen for storage changes in case language is changed in another tab
    window.addEventListener('storage', function(e) {
        if (e.key === 'lang' && e.newValue) {
            console.log('Language storage change detected:', e.newValue); // Debug log
            applyI18n(e.newValue);
        }
    });

    function clearOpenDropdownState() {
        document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.add('hidden'));
        document.querySelectorAll('.dropdown-arrow').forEach(arrow => arrow.classList.remove('open'));
        document.querySelectorAll('.custom-dropdown button[aria-expanded="true"]').forEach(btn => btn.setAttribute('aria-expanded', 'false'));
        document.querySelectorAll('.dropdown-active').forEach(el => el.classList.remove('dropdown-active'));
        document.querySelectorAll('.elevated-section').forEach(el => el.classList.remove('elevated-section'));
    }
    
    // Custom Dropdown Functionality
    function initCustomDropdown(buttonId, menuId, hiddenInputId, selectedId) {
        const button = document.getElementById(buttonId);
        const menu = document.getElementById(menuId);
        const hiddenInput = document.getElementById(hiddenInputId);
        const selectedSpan = document.getElementById(selectedId);
        const arrow = button.querySelector('.dropdown-arrow');
        const dropdownRoot = button.closest('.custom-dropdown');
        let elevatedSection = null;

        function closeMenu() {
            menu.classList.add('hidden');
            arrow.classList.remove('open');
            button.setAttribute('aria-expanded', 'false');
            if (dropdownRoot) dropdownRoot.classList.remove('dropdown-active');
            if (elevatedSection) {
                elevatedSection.classList.remove('elevated-section');
                elevatedSection = null;
            }
        }
        
        // Toggle dropdown
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const isOpen = !menu.classList.contains('hidden');
            
            // Close all dropdowns first
            clearOpenDropdownState();
            
            if (!isOpen) {
                menu.classList.remove('hidden');
                arrow.classList.add('open');
                button.setAttribute('aria-expanded', 'true');
                if (dropdownRoot) dropdownRoot.classList.add('dropdown-active');
                // Elevate the closest animated section (which is a stacking context)
                elevatedSection = button.closest('.gsap-fade-up') || button.closest('[id$="Section"]');
                if (elevatedSection) elevatedSection.classList.add('elevated-section');
            } else {
                closeMenu();
            }
        });
        
        // Handle option selection
        menu.addEventListener('click', function(e) {
            if (e.target.classList.contains('dropdown-option')) {
                const value = e.target.getAttribute('data-value');
                const text = e.target.textContent;

                hiddenInput.value = value;
                selectedSpan.textContent = text;
                closeMenu();

                // Dispatch change event to trigger any listeners
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        // Close on outside click or Escape
        document.addEventListener('click', function(ev) {
            if (!menu.classList.contains('hidden')) {
                if (!button.contains(ev.target) && !menu.contains(ev.target)) {
                    closeMenu();
                }
            }
        });
        document.addEventListener('keydown', function(ev) {
            if (ev.key === 'Escape' && !menu.classList.contains('hidden')) {
                closeMenu();
            }
        });
    }

    function initCountryCodeDropdown(buttonId, menuId, hiddenInputId, selectedId) {
        const button = document.getElementById(buttonId);
        const menu = document.getElementById(menuId);
        const hiddenInput = document.getElementById(hiddenInputId);
        const selectedSpan = document.getElementById(selectedId);
        const arrow = button.querySelector('.dropdown-arrow');
        const dropdownRoot = button.closest('.custom-dropdown');
        let elevatedSection = null;
        let options = [];

        const searchInput = document.createElement('input');
        searchInput.type = 'search';
        searchInput.className = 'country-search';
        searchInput.placeholder = 'Search country or code';
        searchInput.setAttribute('aria-label', 'Search country code');
        menu.appendChild(searchInput);

        const optionsContainer = document.createElement('div');
        optionsContainer.id = 'countryCodeOptions';
        optionsContainer.className = 'country-code-options';
        menu.appendChild(optionsContainer);

        const emptyState = document.createElement('div');
        emptyState.className = 'country-code-empty hidden';
        emptyState.textContent = 'No country code matches your search.';
        menu.appendChild(emptyState);

        function closeMenu() {
            menu.classList.add('hidden');
            arrow.classList.remove('open');
            button.setAttribute('aria-expanded', 'false');
            if (dropdownRoot) dropdownRoot.classList.remove('dropdown-active');
            if (elevatedSection) {
                elevatedSection.classList.remove('elevated-section');
                elevatedSection = null;
            }
        }

        function renderOptions(filter = '') {
            const query = filter.trim().toLowerCase();
            const filtered = !query ? options : options.filter((item) => {
                const haystack = `${item.name} ${item.code} ${item.iso2}`.toLowerCase();
                return haystack.includes(query);
            });

            optionsContainer.innerHTML = '';
            filtered.forEach((item) => {
                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'dropdown-option country-code-option';
                option.dataset.value = item.code;
                option.dataset.label = `${item.name} (${item.code})`;
                option.innerHTML = `
                    <span class="country-code-label">
                        <span class="country-code-name">${esc(item.name)}</span>
                        <span class="country-code-meta">${esc(item.iso2)}</span>
                    </span>
                    <span class="country-code-value">${esc(item.code)}</span>
                `;
                optionsContainer.appendChild(option);
            });

            emptyState.classList.toggle('hidden', filtered.length !== 0);
        }

        function longestCommonPrefix(values) {
            if (!values.length) return '';
            let prefix = values[0];
            for (let i = 1; i < values.length; i += 1) {
                while (values[i].indexOf(prefix) !== 0 && prefix) {
                    prefix = prefix.slice(0, -1);
                }
                if (!prefix) break;
            }
            return prefix;
        }

        button.addEventListener('click', function(e) {
            e.preventDefault();
            const isOpen = !menu.classList.contains('hidden');

            clearOpenDropdownState();

            if (isOpen) {
                closeMenu();
                return;
            }

            menu.classList.remove('hidden');
            arrow.classList.add('open');
            button.setAttribute('aria-expanded', 'true');
            if (dropdownRoot) dropdownRoot.classList.add('dropdown-active');
            elevatedSection = button.closest('.gsap-fade-up') || button.closest('[id$="Section"]');
            if (elevatedSection) elevatedSection.classList.add('elevated-section');
            searchInput.value = '';
            renderOptions();
            setTimeout(() => searchInput.focus(), 0);
        });

        searchInput.addEventListener('input', function() {
            renderOptions(searchInput.value);
        });

        menu.addEventListener('click', function(e) {
            const option = e.target.closest('.country-code-option');
            if (!option) return;

            hiddenInput.value = option.dataset.value;
            selectedSpan.textContent = option.dataset.value;
            button.title = option.dataset.label;
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            closeMenu();
        });

        document.addEventListener('click', function(ev) {
            if (!menu.classList.contains('hidden') && !button.contains(ev.target) && !menu.contains(ev.target)) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', function(ev) {
            if (ev.key === 'Escape' && !menu.classList.contains('hidden')) {
                closeMenu();
            }
        });

        if (dropdownRoot) {
            dropdownRoot.addEventListener('focusout', function(ev) {
                if (menu.classList.contains('hidden')) return;
                const nextTarget = ev.relatedTarget;
                if (!nextTarget || !dropdownRoot.contains(nextTarget)) {
                    closeMenu();
                }
            });
        }

        return {
            setOptions(list) {
                const grouped = new Map();
                (Array.isArray(list) ? list : []).forEach((item) => {
                    if (!item || !item.name || !item.code) return;
                    const key = item.iso2 || item.name;
                    if (!grouped.has(key)) grouped.set(key, []);
                    grouped.get(key).push(item);
                });

                options = Array.from(grouped.values()).map((items) => {
                    const sorted = items.slice().sort((a, b) => a.code.localeCompare(b.code));
                    const base = sorted[0];
                    const prefix = longestCommonPrefix(sorted.map((item) => item.code));

                    if (prefix && prefix !== '+' && /\d/.test(prefix)) {
                        return { ...base, code: prefix };
                    }

                    return sorted.reduce((best, item) => {
                        const bestDigits = best.code.replace(/\D/g, '');
                        const itemDigits = item.code.replace(/\D/g, '');
                        if (itemDigits.length < bestDigits.length) return item;
                        if (itemDigits.length === bestDigits.length && item.code < best.code) return item;
                        return best;
                    }, base);
                }).sort((a, b) => a.name.localeCompare(b.name));
                renderOptions();
            }
        };
    }
    
    // Initialize title dropdown
    initCustomDropdown('titleDropdownBtn', 'titleDropdownMenu', 'title', 'titleSelected');
    
    // Initialize zone/group/network dropdowns
    initCustomDropdown('zoneDropdownBtn', 'zoneDropdownMenu', 'zone', 'zoneSelected');
    initCustomDropdown('groupDropdownBtn', 'groupDropdownMenu', 'group', 'groupSelected');
    initCustomDropdown('networkDropdownBtn', 'networkDropdownMenu', 'network', 'networkSelected');

    const affiliationTypeInput = document.getElementById('affiliation_type');
    const affiliationChurch = document.getElementById('affiliation_church');
    const affiliationNetwork = document.getElementById('affiliation_network');
    const affiliationError = document.getElementById('affiliation_error');
    const churchSection = document.getElementById('churchSection');
    const networkSection = document.getElementById('networkSection');
    const zoneInput = document.getElementById('zone');
    const zoneMenu = document.getElementById('zoneDropdownMenu');
    const zoneSelected = document.getElementById('zoneSelected');
    const groupInput = document.getElementById('group');
    const groupMenu = document.getElementById('groupDropdownMenu');
    const groupSelected = document.getElementById('groupSelected');
    const groupButton = document.getElementById('groupDropdownBtn');
    const churchInput = document.getElementById('church');
    const churchSuggestions = document.getElementById('churchSuggestions');
    const networkInput = document.getElementById('network');
    const networkSelected = document.getElementById('networkSelected');
    const manualNetworkContainer = document.getElementById('manualNetworkContainer');
    const manualNetworkInput = document.getElementById('manual_network');
    const tf = i18n.en;
    let churchDirectory = { zones: [], groupsByZone: {}, churchesByZone: {} };

    function defaultGroupLabel() {
        const t = i18n[currentLang] || tf;
        return (t.dropdowns && t.dropdowns.select_group) || 'Select your group';
    }

    function defaultNetworkLabel() {
        const t = i18n[currentLang] || tf;
        return (t.dropdowns && t.dropdowns.select_network) || 'Select your network';
    }

    function setGroupButtonState(disabled, label) {
        if (!groupButton) return;
        groupButton.disabled = disabled;
        groupButton.classList.toggle('opacity-60', disabled);
        groupButton.classList.toggle('cursor-not-allowed', disabled);
        if (groupSelected && typeof label === 'string') {
            groupSelected.textContent = label;
        }
    }

    function populateSimpleOptions(menu, values) {
        if (!menu) return;
        menu.innerHTML = '';
        values.forEach((value) => {
            const option = document.createElement('div');
            option.className = 'dropdown-option';
            option.setAttribute('data-value', value);
            option.textContent = value;
            menu.appendChild(option);
        });
    }

    function updateChurchSuggestions(zoneName) {
        if (!churchSuggestions) return;
        churchSuggestions.innerHTML = '';
        (churchDirectory.churchesByZone[zoneName] || []).forEach((value) => {
            const option = document.createElement('option');
            option.value = value;
            churchSuggestions.appendChild(option);
        });
    }

    function updateGroupOptions(zoneName) {
        const groups = churchDirectory.groupsByZone[zoneName] || [];
        groupInput.value = '';
        if (groups.length > 0) {
            populateSimpleOptions(groupMenu, groups);
            setGroupButtonState(false, defaultGroupLabel());
        } else {
            groupMenu.innerHTML = '';
            setGroupButtonState(true, 'No saved groups for this zone');
        }
        updateChurchSuggestions(zoneName);
    }

    function clearChurchFields() {
        zoneInput.value = '';
        zoneSelected.textContent = (i18n[currentLang]?.dropdowns?.select_zone) || tf.dropdowns.select_zone;
        groupInput.value = '';
        churchInput.value = '';
        groupMenu.innerHTML = '';
        setGroupButtonState(true, defaultGroupLabel());
        updateChurchSuggestions('');
    }

    function clearNetworkFields() {
        networkInput.value = '';
        networkSelected.textContent = defaultNetworkLabel();
        manualNetworkContainer.classList.add('hidden');
        manualNetworkInput.required = false;
        manualNetworkInput.value = '';
    }

    function updateManualNetworkVisibility() {
        const showManual = networkInput.value === 'OTHER';
        manualNetworkContainer.classList.toggle('hidden', !showManual);
        manualNetworkInput.required = showManual;
        if (!showManual) {
            manualNetworkInput.value = '';
        }
    }

    function updateAffiliationVisibility() {
        const choice = affiliationChurch && affiliationChurch.checked
            ? 'church'
            : affiliationNetwork && affiliationNetwork.checked
                ? 'network'
                : '';

        affiliationTypeInput.value = choice;
        if (affiliationError) affiliationError.classList.add('hidden');

        if (choice === 'church') {
            churchSection.classList.remove('hidden');
            networkSection.classList.add('hidden');
            churchSection.style.opacity = '1';
            churchSection.style.transform = 'translateY(0)';
            zoneInput.required = true;
            clearNetworkFields();
        } else if (choice === 'network') {
            networkSection.classList.remove('hidden');
            churchSection.classList.add('hidden');
            networkSection.style.opacity = '1';
            networkSection.style.transform = 'translateY(0)';
            zoneInput.required = false;
            clearChurchFields();
            updateManualNetworkVisibility();
        } else {
            churchSection.classList.add('hidden');
            networkSection.classList.add('hidden');
            zoneInput.required = false;
            clearNetworkFields();
        }
    }

    networkInput.addEventListener('change', updateManualNetworkVisibility);

    zoneInput.addEventListener('change', function() {
        updateGroupOptions(this.value);
    });

    if (affiliationChurch) affiliationChurch.addEventListener('change', updateAffiliationVisibility);
    if (affiliationNetwork) affiliationNetwork.addEventListener('change', updateAffiliationVisibility);
    updateAffiliationVisibility();
    updateManualNetworkVisibility();

    // Form validation
    const registrationForm = document.getElementById('registrationForm');
    const submitBtn = registrationForm ? registrationForm.querySelector('button[type="submit"]') : null;
    const submitBtnText = document.getElementById('submitButtonText');

    function setSubmitButtonState(label, disabled) {
        if (submitBtnText && typeof label === 'string') {
            submitBtnText.textContent = label;
        }
        if (submitBtn) {
            submitBtn.disabled = !!disabled;
            submitBtn.style.opacity = disabled ? '0.7' : '1';
        }
    }

    // Function to check for duplicate email/KingsChat
    async function checkForDuplicates(email, kingschat) {
        // Show checking indicator
        const originalText = submitBtnText ? submitBtnText.textContent : 'Register for Rhapathon';
        setSubmitButtonState('Checking for duplicates...', true);

        try {
            const formData = new FormData();
            formData.append('action', 'check_duplicates');
            formData.append('email', email || '');
            formData.append('kingschat_username', kingschat || '');

            const response = await fetch('admin/check_duplicates.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            return result;
        } catch (error) {
            console.error('Duplicate check failed:', error);
            return { success: true }; // Allow submission if check fails
        } finally {
            // Restore button state
            setSubmitButtonState(originalText, false);
        }
    }

    registrationForm.addEventListener('submit', async function(e) {
        const affiliationType = affiliationTypeInput.value;
        const networkValue = networkInput.value;
        const zoneValue = zoneInput.value;
        const emailValue = document.getElementById('email').value;
        const kingschatValue = document.getElementById('kingschat_username').value;

        // Check for duplicates first
        if (emailValue || kingschatValue) {
            const duplicateCheck = await checkForDuplicates(emailValue, kingschatValue);
            if (!duplicateCheck.success) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Duplicate Registration',
                    html: duplicateCheck.message,
                    confirmButtonColor: '#000080'
                });
                return false;
            }
        }

        if (!affiliationType) {
            e.preventDefault();
            if (affiliationError) affiliationError.classList.remove('hidden');
            return false;
        }

        if (affiliationType === 'church') {
            if (!zoneValue || !zoneValue.trim()) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Zone Required',
                    text: 'Please select your zone to continue.',
                    confirmButtonColor: '#000080'
                });
                return false;
            }
            clearNetworkFields();
        }

        if (affiliationType === 'network') {
            if (!networkValue || !networkValue.trim()) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Network Required',
                    text: 'Please select your network to continue.',
                    confirmButtonColor: '#000080'
                });
                return false;
            }

            if (networkValue === 'OTHER' && (!manualNetworkInput.value || !manualNetworkInput.value.trim())) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Network Required',
                    text: 'Please specify your network when selecting OTHER.',
                    confirmButtonColor: '#000080'
                }).then(() => {
                    manualNetworkInput.focus();
                });
                return false;
            }

            clearChurchFields();
        }
    });

    setGroupButtonState(true, defaultGroupLabel());

    fetch('api/church_directory.php')
        .then(response => response.json())
        .then(data => {
            if (!data || !data.success) {
                throw new Error('Invalid church directory response');
            }

            churchDirectory = {
                zones: Array.isArray(data.zones) ? data.zones : [],
                groupsByZone: data.groupsByZone || {},
                churchesByZone: data.churchesByZone || {}
            };

            populateSimpleOptions(zoneMenu, churchDirectory.zones);
        })
        .catch(error => {
            console.error('Error loading church directory:', error);
            setGroupButtonState(true, 'Unable to load groups');
        });

    const countryCodeDropdown = initCountryCodeDropdown('countryCodeDropdownBtn', 'countryCodeDropdownMenu', 'country_code', 'countryCodeSelected');

    // Load country calling codes and populate custom menu
    fetch('data/country_codes.json')
        .then(r => r.json())
        .then(list => {
            const prepared = Array.isArray(list)
                ? list
                    .filter(item => item && item.code && item.name)
                    .sort((a, b) => a.name.localeCompare(b.name))
                : [];

            countryCodeDropdown.setOptions(prepared);

            const preferred = prepared.find(item => item.iso2 === 'NG')
                || prepared.find(item => item.iso2 === 'US')
                || prepared[0];

            if (preferred) {
                document.getElementById('country_code').value = preferred.code;
                document.getElementById('countryCodeSelected').textContent = preferred.code;
                document.getElementById('countryCodeDropdownBtn').title = `${preferred.name} (${preferred.code})`;
            }
        })
        .catch(err => console.error('Error loading country codes:', err));
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-dropdown')) {
            clearOpenDropdownState();
        }
    });
    
    
    // Day Selection Functionality
    const dayCheckboxes = document.querySelectorAll('.day-checkbox');
    const selectAllBtn = document.getElementById('selectAllDays');
    const clearAllBtn = document.getElementById('clearAllDays');
    const daysValidation = document.getElementById('days_validation');
    const daysError = document.getElementById('days_error');
    const form = document.getElementById('registrationForm');

    // Online participation toggle based on onsite answer
    const onsiteYes = document.getElementById('onsite_participation_yes');
    const onsiteNo = document.getElementById('onsite_participation_no');
    const onlineBlock = document.getElementById('onlineParticipationBlock');
    const onlineYes = document.getElementById('online_participation_yes');
    const onlineNo = document.getElementById('online_participation_no');
    const watchOnlineInfo = document.getElementById('watchOnlineInfo');
    const phoneLocalInput = document.getElementById('phone_local');

    if (phoneLocalInput) {
        phoneLocalInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^\d\s()+-]/g, '');
        });
    }

    function updateWatchOnlineVisibility() {
        if (!watchOnlineInfo) return;
        const show = onlineYes && onlineYes.checked && onlineBlock && !onlineBlock.classList.contains('hidden');
        watchOnlineInfo.classList.toggle('hidden', !show);
    }

    function updateOnlineVisibility() {
        if (!onlineBlock) return;
        const show = onsiteNo && onsiteNo.checked;
        if (show) {
            onlineBlock.classList.remove('hidden');
            if (onlineYes) onlineYes.required = true;
            if (onlineNo) onlineNo.required = true;
        } else {
            onlineBlock.classList.add('hidden');
            if (onlineYes) { onlineYes.required = false; onlineYes.checked = false; }
            if (onlineNo) { onlineNo.required = false; onlineNo.checked = false; }
            const onlineError = document.getElementById('online_error'); if (onlineError) onlineError.classList.add('hidden');
        }
        updateWatchOnlineVisibility();
    }
    // Days section visibility based on onsite answer
    const daySelectionSection = document.getElementById('daySelectionSection');
    function updateDaysVisibility() {
        if (!daySelectionSection) return;
        const show = onsiteYes && onsiteYes.checked;
        if (show) {
            daySelectionSection.classList.remove('hidden');
            // Re-enable validation when shown
            daysValidation.required = true;
        } else {
            daySelectionSection.classList.add('hidden');
            // Clear all day selections when hidden
            dayCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
                updateSessionVisibility(checkbox);
            });
            // Disable validation and clear errors when hidden
            daysValidation.required = false;
            daysValidation.value = '';
            daysError.classList.add('hidden');
        }
    }

    if (onsiteYes) onsiteYes.addEventListener('change', updateOnlineVisibility);
    if (onsiteNo) onsiteNo.addEventListener('change', updateOnlineVisibility);
    if (onsiteYes) onsiteYes.addEventListener('change', updateDaysVisibility);
    if (onsiteNo) onsiteNo.addEventListener('change', updateDaysVisibility);
    if (onlineYes) onlineYes.addEventListener('change', () => {
        const e = document.getElementById('online_error');
        if (e) e.classList.add('hidden');
        updateWatchOnlineVisibility();
    });
    if (onlineNo) onlineNo.addEventListener('change', () => {
        const e = document.getElementById('online_error');
        if (e) e.classList.add('hidden');
        updateWatchOnlineVisibility();
    });
    updateOnlineVisibility();
    updateDaysVisibility();
    updateWatchOnlineVisibility();
    
    // Update validation field when days are selected/deselected
    function updateDaysValidation() {
        const selectedDays = Array.from(dayCheckboxes).filter(cb => cb.checked);

        if (selectedDays.length > 0) {
            // Check if selected days that require sessions have at least one session selected
            let allSessionsValid = true;
            const sessionDays = ['tuesday', 'wednesday', 'thursday', 'friday'];

            selectedDays.forEach(day => {
                if (sessionDays.includes(day.value)) {
                    const sessionDiv = document.getElementById(day.value + '_sessions');
                    if (sessionDiv) {
                        const sessionCheckboxes = sessionDiv.querySelectorAll('input[type="checkbox"]:checked');
                        if (sessionCheckboxes.length === 0) {
                            allSessionsValid = false;
                        }
                    }
                }
            });

            if (allSessionsValid) {
                daysValidation.value = selectedDays.map(cb => cb.value).join(',');
                daysError.classList.add('hidden');
                return true;
            } else {
                daysValidation.value = '';
                daysError.classList.remove('hidden');
                daysError.textContent = 'Please select at least one session for each selected day.';
                return false;
            }
        } else {
            daysValidation.value = '';
            daysError.classList.remove('hidden');
            daysError.textContent = 'Please select at least one day to attend.';
            return false;
        }
    }
    
    // Add event listeners to all day checkboxes
    dayCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateDaysValidation();
            updateSessionVisibility(this);
        });
    });

    // Session visibility management
    function updateSessionVisibility(checkbox) {
        const dayValue = checkbox.value;
        const sessionDiv = document.getElementById(dayValue + '_sessions');

        if (sessionDiv) {
            if (checkbox.checked) {
                sessionDiv.style.display = 'block';
                // Make session selection required and add validation listeners
                const sessionCheckboxes = sessionDiv.querySelectorAll('input[type="checkbox"]');
                sessionCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', updateDaysValidation);
                });
            } else {
                sessionDiv.style.display = 'none';
                // Clear session selections when day is unchecked
                const sessionCheckboxes = sessionDiv.querySelectorAll('input[type="checkbox"]');
                sessionCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            }
        }
    }

    // Select All Days functionality
    selectAllBtn.addEventListener('click', function(e) {
        e.preventDefault();
        dayCheckboxes.forEach(checkbox => {
            checkbox.checked = true;
            updateSessionVisibility(checkbox);
        });
        updateDaysValidation();
    });

    // Clear All Days functionality
    clearAllBtn.addEventListener('click', function(e) {
        e.preventDefault();
        dayCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
            updateSessionVisibility(checkbox);
        });
        updateDaysValidation();
    });

    
    // Helper function to create session error element
    function createSessionError() {
        const errorDiv = document.createElement('div');
        errorDiv.id = 'session_error';
        errorDiv.className = 'text-red-500 text-sm mt-2';
        const daysSection = document.getElementById('daySelectionSection');
        if (daysSection) {
            daysSection.appendChild(errorDiv);
        }
        return errorDiv;
    }

    // Form submission validation and handling
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Always prevent default submission

        // Validate days only if onsite participation is selected
        const onsiteChecked = document.querySelector('input[name="onsite_participation"]:checked');
        let isValidDays = true;
        let isValidSessions = true;

        if (onsiteChecked && onsiteChecked.value === 'yes') {
            isValidDays = updateDaysValidation();

            // Additional session validation for form submission
            const selectedDays = document.querySelectorAll('input[name="selected_days[]"]:checked');
            const daysWithSessions = ['tuesday', 'wednesday', 'thursday', 'friday'];

            selectedDays.forEach(dayCheckbox => {
                const dayValue = dayCheckbox.value;
                if (daysWithSessions.includes(dayValue)) {
                    const sessionCheckboxes = document.querySelectorAll(`input[name="${dayValue}_sessions[]"]:checked`);
                    if (sessionCheckboxes.length === 0) {
                        isValidSessions = false;
                        // Show session selection for this day
                        const sessionContainer = document.getElementById(dayValue + '_sessions');
                        if (sessionContainer) {
                            sessionContainer.style.display = 'block';
                            sessionContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        return false;
                    }
                }
            });
        }

        // Validate online participation if onsite = no
        const onlineChoice = document.querySelector('input[name="online_participation"]:checked');
        const onlineError = document.getElementById('online_error');
        let isValidOnline = true;
        if (onsiteChecked && onsiteChecked.value === 'no') {
            if (!onlineChoice) {
                isValidOnline = false;
                if (onlineError) onlineError.classList.remove('hidden');
                document.getElementById('onlineParticipationBlock')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                if (onlineError) onlineError.classList.add('hidden');
            }
        } else {
            if (onlineError) onlineError.classList.add('hidden');
        }

        if (!isValidDays) {
            // Scroll to day selection section
            document.querySelector('#days_error').scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            return false;
        }
        if (!isValidSessions) {
            // Show session validation error
            const sessionError = document.getElementById('session_error') || createSessionError();
            sessionError.textContent = 'Please select at least one session for each selected day.';
            sessionError.classList.remove('hidden');
            return false;
        } else {
            const sessionError = document.getElementById('session_error');
            if (sessionError) sessionError.classList.add('hidden');
        }
        if (!isValidOnline) return false;
        
        // Show loading state
        try {
            setSubmitButtonState((i18n[currentLang] || i18n.en).buttons.submitting, true);
        } catch (e) {
            setSubmitButtonState('Submitting...', true);
        }
        
        // Prepare form data (compose full phone)
        const code = (document.getElementById('country_code').value || '').trim();
        const local = (document.getElementById('phone_local').value || '').replace(/[^\d\s()+-]/g, '').trim();
        const hiddenPhone = document.getElementById('phone');
        hiddenPhone.value = code ? `${code} ${local}` : local;
        const formData = new FormData(form);
        
        // Submit form via AJAX
        fetch('admin/submit_registration.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessMessage(data.message, data.data);
                form.reset();
                updateDaysValidation(); // Reset validation
                try { localStorage.removeItem('reg_form_draft'); } catch {}
            } else {
                if (data.code === 'csrf_invalid') {
                    // Preserve draft and reload the page to refresh CSRF
                    saveDraftFromForm(form);
                    try { localStorage.setItem('reg_form_retry', '1'); } catch {}
                    Swal.fire({
                        icon: 'info',
                        title: 'Session expired',
                        text: 'We will reload the page and restore your inputs.',
                        confirmButtonColor: '#000080'
                    }).then(() => { window.location.reload(); });
                } else {
                    showErrorMessage(data.message, data.errors || []);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorMessage('An error occurred. Please try again.');
        })
        .finally(() => {
            // Reset button state
            try {
                setSubmitButtonState((i18n[currentLang] || i18n.en).buttons.submit, false);
            } catch (e) {
                setSubmitButtonState('Register for Rhapathon', false);
            }
        });
    });
    } else {
        console.log('Form not found!');
    }

    // Function to show success message (SweetAlert2)
    function showSuccessMessage(message, data) {
        const params = new URLSearchParams();
        if (data && data.registration_id) params.set('registration_id', data.registration_id);
        if (data && data.name) params.set('name', data.name);
        params.set('lang', currentLang || 'en');
        window.location.href = `registration-success.php?${params.toString()}`;
    }
    
    // Function to show error message (SweetAlert2)
    function showErrorMessage(message, errors = []) {
        const t = (i18n[currentLang] || i18n.en);
        const safeMessage = esc(message || 'An error occurred.');
        const errorItems = Array.isArray(errors)
            ? errors
                .map(item => typeof item === 'string' ? item.trim() : '')
                .filter(Boolean)
                .map(item => `<li class="text-left">${esc(item)}</li>`)
                .join('')
            : '';
        const detailsHtml = errorItems
            ? `<ul class="mt-4 list-disc pl-5 space-y-2 text-sm sm:text-base text-gray-700">${errorItems}</ul>`
            : '';
        Swal.fire({
            icon: 'error',
            title: t.alerts.error_title,
            html: `<p class="text-sm sm:text-base text-gray-700">${safeMessage}</p>${detailsHtml}`,
            confirmButtonText: t.buttons.ok,
            confirmButtonColor: '#000080',
            background: '#ffffff',
            color: '#111827',
        });
    }
    
    // Back to Top Button Functionality
    const backToTopButton = document.getElementById('backToTop');
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopButton.classList.remove('opacity-0', 'invisible');
            backToTopButton.classList.add('opacity-100', 'visible');
        } else {
            backToTopButton.classList.add('opacity-0', 'invisible');
            backToTopButton.classList.remove('opacity-100', 'visible');
        }
    });
    
    // Smooth scroll to top when clicked
    backToTopButton.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // GSAP Header Animations
    function initHeaderAnimations() {
        // Create a timeline for sequenced animations
        const tl = gsap.timeline({ delay: 0.1 });
        
        // Animate header title (fade down with elastic)
        tl.to("#headerTitle", {
            duration: 0.6,
            opacity: 1,
            y: 0,
            ease: "elastic.out(1, 0.5)"
        })
        
        // Animate wonder edition subtitle (scale in with elastic bounce)
        .to("#headerSubtitle", {
            duration: 0.8,
            opacity: 1,
            scale: 1,
            ease: "elastic.out(1, 0.6)"
        }, "-=0.4")
        
        // Animate date (fade up with elastic)
        .to("#headerDate", {
            duration: 0.5,
            opacity: 1,
            y: 0,
            ease: "elastic.out(1, 0.4)"
        }, "-=0.2")
        
        // Animate description (fade up with elastic)
        .to("#headerDescription", {
            duration: 0.5,
            opacity: 1,
            y: 0,
            ease: "elastic.out(1, 0.4)",
            onComplete: initFormAnimations  // Trigger form animations after header completes
        }, "-=0.3");
        

    }
    
    // GSAP Form Animations
    function initFormAnimations() {
        // Create a timeline for form section animations
        const formTl = gsap.timeline({ delay: 0.1 });
        
        // Animate form container first with elastic
        formTl.to("#registrationFormContainer", {
            duration: 0.6,
            opacity: 1,
            y: 0,
            ease: "elastic.out(1, 0.5)"
        })
        
        // Animate each section with staggered timing and elastic easing
        .to("#personalSection", {
            duration: 0.5,
            opacity: 1,
            y: 0,
            ease: "elastic.out(1, 0.4)"
        }, "-=0.3")

        .to("#affiliationSection", {
            duration: 0.5,
            opacity: 1,
            y: 0,
            ease: "elastic.out(1, 0.4)"
        }, "-=0.3")
        
        .to("#churchSection", {
            duration: 0.5,
            opacity: 1,
            y: 0,
            ease: "elastic.out(1, 0.4)"
        }, "-=0.3")

        .to("#networkSection", {
            duration: 0.5,
            opacity: 1,
            y: 0,
            ease: "elastic.out(1, 0.4)"
        }, "-=0.3")

        .to("#participationSection", {
            duration: 0.5,
            opacity: 1,
            y: 0,
            ease: "elastic.out(1, 0.4)"
        }, "-=0.3")

        .to("#daySelectionSection", {
            duration: 0.5,
            opacity: 1,
            y: 0,
            ease: "elastic.out(1, 0.4)"
        }, "-=0.3")
        
        // Animate feedback section before submit
        .to("#feedbackSection", {
            duration: 0.5,
            opacity: 1,
            y: 0,
            ease: "elastic.out(1, 0.4)"
        }, "-=0.3")
        
        
        .to("#submitSection", {
            duration: 0.6,
            opacity: 1,
            y: 0,
            ease: "elastic.out(1, 0.7)"
        }, "-=0.2");
        
        // Add subtle hover animations to form sections
        gsap.set([
            "#personalSection", 
            "#affiliationSection",
            "#churchSection", 
            "#networkSection",
            "#participationSection",
            "#daySelectionSection",
            "#feedbackSection"
        ], {
            onMouseEnter: function() {
                gsap.to(this.targets()[0], {
                    duration: 0.3,
                    scale: 1.02,
                    ease: "power2.out"
                });
            },
            onMouseLeave: function() {
                gsap.to(this.targets()[0], {
                    duration: 0.3,
                    scale: 1,
                    ease: "power2.out"
                });
            }
        });
    }
    
    // Initialize header animations
    initHeaderAnimations();

    // Disable only the event dates that have already passed.
    function disablePastDays() {
        const today = new Date();
        const startOfToday = new Date(today.getFullYear(), today.getMonth(), today.getDate());

        EVENT_DAY_SCHEDULE.forEach(day => {
            const dayElement = document.getElementById(day.id);
            if (!dayElement) return;
            const daySelector = dayElement.closest('.day-selector');

            // If the day has passed, disable it
            if (day.date < startOfToday) {
                daySelector.classList.add('disabled');
                dayElement.disabled = true;
                dayElement.checked = false; // Uncheck if it was checked

                // Also disable session checkboxes if they exist
                const sessionContainer = document.getElementById(day.id.replace('day_', '') + '_sessions');
                if (sessionContainer) {
                    sessionContainer.style.display = 'none';
                    const sessionCheckboxes = sessionContainer.querySelectorAll('input[type="checkbox"]');
                    sessionCheckboxes.forEach(checkbox => {
                        checkbox.disabled = true;
                        checkbox.checked = false;
                    });
                }
            }
        });

        // Update Select All/Clear All buttons to exclude disabled days
        updateSelectAllButtons();
    }

    // Function to update Select All and Clear All button functionality
    function updateSelectAllButtons() {
        const selectAllBtn = document.getElementById('selectAllDays');
        const clearAllBtn = document.getElementById('clearAllDays');

        if (selectAllBtn && clearAllBtn) {
            // Update Select All to only select enabled days
            selectAllBtn.addEventListener('click', function() {
                const enabledCheckboxes = document.querySelectorAll('.day-checkbox:not(:disabled)');
                enabledCheckboxes.forEach(checkbox => {
                    checkbox.checked = true;
                    // Trigger session display for checked days
                    const dayName = checkbox.id.replace('day_', '');
                    const sessionContainer = document.getElementById(dayName + '_sessions');
                    if (sessionContainer) {
                        sessionContainer.style.display = 'block';
                    }
                });
                updateDaysValidation();
            });

            // Clear All functionality remains the same
            clearAllBtn.addEventListener('click', function() {
                const allCheckboxes = document.querySelectorAll('.day-checkbox');
                allCheckboxes.forEach(checkbox => {
                    if (!checkbox.disabled) {
                        checkbox.checked = false;
                        // Hide session selection for unchecked days
                        const dayName = checkbox.id.replace('day_', '');
                        const sessionContainer = document.getElementById(dayName + '_sessions');
                        if (sessionContainer) {
                            sessionContainer.style.display = 'none';
                        }
                    }
                });
                updateDaysValidation();
            });
        }
    }

    // Days validation is handled by the more comprehensive function above

    // Initialize day disabling when DOM is loaded
    disablePastDays();

    // Add event listeners to day checkboxes to update validation
    document.querySelectorAll('.day-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateDaysValidation);
    });
});
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?> 
