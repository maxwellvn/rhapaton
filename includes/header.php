<?php
$__scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/register.php');
$__publicPrefix = strpos($__scriptPath, '/pages/') !== false ? '..' : '.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Rhapaton'; ?></title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($__publicPrefix . '/assets/videos/images/logo-rhapathon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($__publicPrefix . '/assets/videos/images/logo-rhapathon.png'); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#000080',      // Dark Blue for text elements
                        secondary: '#2d2d2d',    // Keep for hover states
                        accent: '#A07800',       // Gold for accents and highlights
                        light: '#F5F5F5',        // Light Gray for subtle backgrounds
                        border: '#e2e8f0',       // Keep existing border color
                        cta: '#FF0000',          // Red for call-to-action buttons
                        white: '#FFFFFF'         // White for main backgrounds
                    }
                }
            }
        }
    </script>
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

      @keyframes fadeIn {
          from {
              opacity: 0;
              transform: translateY(-10px);
          }
          to {
              opacity: 1;
              transform: translateY(0);
          }
      }

      .animate-fadeIn {
          animation: fadeIn 0.2s ease-out forwards;
      }

      #mainHeader {
          background: rgba(255, 255, 255, 0.98);
          border-bottom: 1px solid #e2e8f0;
          box-shadow: 0 1px 0 rgba(15, 23, 42, 0.06);
      }

      #mainHeader .brand-mark {
          border-radius: 14px;
      }

      #mainHeader #languageButton {
          background: #ffffff;
          border: 1px solid #d8dee7;
          border-radius: 999px;
          box-shadow: none;
      }

      #mainHeader #languageButton:hover {
          background: #f8fafc;
          border-color: #c7d0db;
          box-shadow: none;
          transform: none;
      }

      #mainHeader #languageDropdown {
          background: #ffffff;
          border: 1px solid #e2e8f0;
          border-radius: 18px;
          box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
      }

      #mainHeader .language-option {
          margin: 0.2rem 0.5rem;
          border-radius: 12px;
      }

      #mainHeader .language-option:hover {
          background: #f5f5f5 !important;
          color: #000080 !important;
      }

      /* Page Loader Styles (prefixed to avoid Tailwind/container conflicts) */
      #pageLoaderOverlay { transition: opacity .3s ease; }
      .loader-container {
        --uib-size: 35px;
        --uib-color: black;
        --uib-speed: .9s;
        --uib-stroke: 3.5px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        height: var(--uib-size);
        width: var(--uib-size);
      }
      .loader-line {
        position: absolute;
        top: calc(50% - var(--uib-stroke) / 2);
        left: 0;
        height: var(--uib-stroke);
        width: 100%;
        border-radius: calc(var(--uib-stroke) / 2);
        background-color: var(--uib-color);
        animation: loader-rotate var(--uib-speed) ease-in-out infinite;
        transition: background-color 0.3s ease;
      }
      .loader-line:nth-child(1) { animation-delay: calc(var(--uib-speed) * -0.375); }
      .loader-line:nth-child(2) { animation-delay: calc(var(--uib-speed) * -0.375); opacity: 0.8; }
      .loader-line:nth-child(3) { animation-delay: calc(var(--uib-speed) * -0.3); opacity: 0.6; }
      .loader-line:nth-child(4) { animation-delay: calc(var(--uib-speed) * -0.225); opacity: 0.4; }
      .loader-line:nth-child(5) { animation-delay: calc(var(--uib-speed) * -0.15); opacity: 0.2; }
      .loader-line:nth-child(6) { animation-delay: calc(var(--uib-speed) * -0.075); opacity: 0.1; }
      @keyframes loader-rotate { 0% { transform: rotate(0deg); } 100% { transform: rotate(180deg); } }
    </style>
</head>
<body class="bg-light min-h-screen">
    <!-- Global Page Loader Overlay -->
    <div id="pageLoaderOverlay" class="fixed inset-0 z-[999999] flex items-center justify-center bg-white">
      <div class="loader-container">
        <div class="loader-line"></div>
        <div class="loader-line"></div>
        <div class="loader-line"></div>
        <div class="loader-line"></div>
        <div class="loader-line"></div>
        <div class="loader-line"></div>
      </div>
    </div>
    <header id="mainHeader" class="fixed top-0 left-0 right-0 z-50 transition-all duration-200 bg-white border-b border-border">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 sm:h-20">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="<?php echo htmlspecialchars($__publicPrefix . '/register'); ?>" class="flex items-center">
                        <div class="brand-mark overflow-hidden">
                            <img src="<?php echo htmlspecialchars($__publicPrefix . '/assets/videos/images/logo-rhapathon.png'); ?>" 
                                 alt="Rhapathon Logo" 
                                 class="h-10 sm:h-12 md:h-14 w-auto object-contain">
                        </div>
                    </a>
                </div>
                
                <!-- Navigation and Language Dropdown -->
                <div class="flex items-center gap-3 sm:gap-6">
                    <!-- Custom Language Dropdown -->
                    <div class="relative">
                        <button id="languageButton" 
                                class="bg-white border border-border rounded-full px-3 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm text-primary font-medium focus:outline-none focus:ring-2 focus:ring-primary/10 transition-colors duration-200 cursor-pointer flex items-center gap-2 sm:gap-3">
                            <i data-lucide="languages" class="w-4 h-4 sm:w-5 sm:h-5 text-primary" aria-hidden="true"></i>
                            <span id="selectedLanguage" class="hidden sm:inline">English</span>
                            <span id="selectedLanguageMobile" class="sm:hidden">EN</span>
                            <i data-lucide="chevron-down" class="w-3 h-3 sm:w-4 sm:h-4 transition-transform duration-200" id="dropdownArrow" aria-hidden="true"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div id="languageDropdown" class="hidden absolute right-0 mt-3 w-56 sm:w-64 bg-white border border-border rounded-2xl shadow-lg z-50 overflow-y-auto animate-fadeIn" data-no-translate>
                            <div id="languageOptions" class="py-2">
                                <div class="px-4 py-3 text-sm text-secondary">Loading languages...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Custom Dropdown JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        window.renderLucideIcons = function() {
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        };
        window.renderLucideIcons();

        window.addEventListener('load', function() {
            const overlay = document.getElementById('pageLoaderOverlay');
            if (overlay) {
                overlay.style.opacity = '0';
                setTimeout(() => overlay.remove(), 320);
            }
        });

        const apiBase = <?php echo json_encode(rtrim($__publicPrefix, '/') . '/api'); ?>;
        const languageButton = document.getElementById('languageButton');
        const languageDropdown = document.getElementById('languageDropdown');
        const languageOptions = document.getElementById('languageOptions');
        const selectedLanguage = document.getElementById('selectedLanguage');
        const selectedLanguageMobile = document.getElementById('selectedLanguageMobile');
        const dropdownArrow = document.getElementById('dropdownArrow');

        languageButton.setAttribute('data-no-translate', '');

        const supportedLanguages = new Map();
        const translationCache = new Map();
        const textEntries = [];
        const attrEntries = [];

        function meaningfulText(text) {
            return typeof text === 'string' && text.trim().length > 1;
        }

        function shortCode(lang) {
            return String(lang || 'en').slice(0, 2).toUpperCase();
        }

        function setSelected(lang) {
            const item = supportedLanguages.get(lang) || { language: 'en', name: 'English' };
            selectedLanguage.textContent = item.name || item.language;
            selectedLanguageMobile.textContent = shortCode(item.language);
            document.documentElement.lang = item.language;
        }

        function positionLanguageDropdown() {
            const rect = languageButton.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            const spacing = 12;
            const maxDropdownHeight = 320;
            const spaceBelow = viewportHeight - rect.bottom - spacing;
            const spaceAbove = rect.top - spacing;
            const openUpward = spaceBelow < 220 && spaceAbove > spaceBelow;
            const availableHeight = Math.max(140, Math.min(maxDropdownHeight, openUpward ? spaceAbove : spaceBelow));

            languageDropdown.style.maxHeight = availableHeight + 'px';
            languageDropdown.style.top = openUpward ? 'auto' : '100%';
            languageDropdown.style.bottom = openUpward ? 'calc(100% + 12px)' : 'auto';
            languageDropdown.style.marginTop = openUpward ? '0' : '0.75rem';
        }

        function rememberLanguage(lang) {
            try {
                localStorage.setItem('lang', lang);
                localStorage.setItem('preferred_language', lang);
            } catch (e) {}
        }

        function getStoredLanguage() {
            try {
                return localStorage.getItem('preferred_language') || localStorage.getItem('lang') || 'en';
            } catch (e) {
                return 'en';
            }
        }

        function collectTranslatableContent() {
            if (textEntries.length || attrEntries.length) {
                return;
            }

            const walker = document.createTreeWalker(
                document.body,
                NodeFilter.SHOW_TEXT,
                {
                    acceptNode(node) {
                        if (!node.parentElement) {
                            return NodeFilter.FILTER_REJECT;
                        }
                        if (node.parentElement.closest('[data-no-translate]')) {
                            return NodeFilter.FILTER_REJECT;
                        }
                        if (['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEXTAREA', 'OPTION'].includes(node.parentElement.tagName)) {
                            return NodeFilter.FILTER_REJECT;
                        }
                        if (!meaningfulText(node.textContent || '')) {
                            return NodeFilter.FILTER_REJECT;
                        }
                        return NodeFilter.FILTER_ACCEPT;
                    }
                }
            );

            let node;
            while ((node = walker.nextNode())) {
                textEntries.push({ node, source: node.textContent });
            }

            document.querySelectorAll('[placeholder],[title],[aria-label]').forEach((element) => {
                if (element.closest('[data-no-translate]')) {
                    return;
                }
                ['placeholder', 'title', 'aria-label'].forEach((attribute) => {
                    const value = element.getAttribute(attribute);
                    if (meaningfulText(value)) {
                        attrEntries.push({ element, attribute, source: value });
                    }
                });
            });
        }

        async function translateTexts(texts, lang) {
            const cacheKey = lang + '::' + texts.join('\u241E');
            if (translationCache.has(cacheKey)) {
                return translationCache.get(cacheKey);
            }

            const response = await fetch(apiBase + '/translate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ texts, target: lang, source: 'en' })
            });
            const json = await response.json();
            if (!response.ok || !json.ok || !Array.isArray(json.texts)) {
                throw new Error(json.error || 'Translation failed');
            }

            translationCache.set(cacheKey, json.texts);
            return json.texts;
        }

        function restoreOriginalContent() {
            collectTranslatableContent();
            textEntries.forEach((entry) => {
                entry.node.textContent = entry.source;
            });
            attrEntries.forEach((entry) => {
                entry.element.setAttribute(entry.attribute, entry.source);
            });
        }

        async function translateGenericPage(lang) {
            collectTranslatableContent();

            if (lang === 'en') {
                restoreOriginalContent();
                return;
            }

            const sourceTexts = textEntries.map((entry) => entry.source);
            const sourceAttrs = attrEntries.map((entry) => entry.source);

            const translatedTexts = sourceTexts.length ? await translateTexts(sourceTexts, lang) : [];
            const translatedAttrs = sourceAttrs.length ? await translateTexts(sourceAttrs, lang) : [];

            textEntries.forEach((entry, index) => {
                entry.node.textContent = translatedTexts[index] ?? entry.source;
            });
            attrEntries.forEach((entry, index) => {
                entry.element.setAttribute(entry.attribute, translatedAttrs[index] ?? entry.source);
            });
        }

        function renderLanguageOptions() {
            const items = Array.from(supportedLanguages.values());

            if (items.length === 0) {
                languageOptions.innerHTML = '<div class="px-4 py-3 text-sm text-secondary">No languages available.</div>';
                return;
            }

            languageOptions.innerHTML = items.map((item) => `
                <button type="button"
                        class="language-option w-full text-left flex items-center justify-between gap-3 rounded-xl border border-border px-3 py-2 text-sm text-primary hover:bg-light transition-colors duration-150"
                        data-lang="${item.language}">
                    <span>${item.name}</span>
                    <span class="text-xs text-secondary">${shortCode(item.language)}</span>
                </button>
            `).join('');
        }

        async function loadSupportedLanguages() {
            const response = await fetch(apiBase + '/translation_languages.php?target=en');
            const json = await response.json();
            if (!response.ok || !json.ok || !Array.isArray(json.languages)) {
                throw new Error(json.error || 'Could not load supported languages');
            }

            supportedLanguages.clear();
            supportedLanguages.set('en', { language: 'en', name: 'English' });
            json.languages.forEach((item) => {
                if (item && item.language) {
                    supportedLanguages.set(item.language, {
                        language: item.language,
                        name: item.name || item.language
                    });
                }
            });
            renderLanguageOptions();
        }

        async function changeLanguage(lang) {
            const nextLang = supportedLanguages.has(lang) ? lang : 'en';

            rememberLanguage(nextLang);
            setSelected(nextLang);
            languageDropdown.classList.add('hidden');
            dropdownArrow.classList.remove('rotate-180');

            document.dispatchEvent(new CustomEvent('language-change', { detail: { lang: nextLang } }));

            if (typeof window.applyI18n !== 'function') {
                await translateGenericPage(nextLang);
            }
        }

        window.changeLanguage = changeLanguage;

        languageButton.addEventListener('click', function(e) {
            e.preventDefault();
            languageDropdown.classList.toggle('hidden');
            dropdownArrow.classList.toggle('rotate-180');
            if (!languageDropdown.classList.contains('hidden')) {
                positionLanguageDropdown();
            }
        });

        languageOptions.addEventListener('click', function(e) {
            const option = e.target.closest('[data-lang]');
            if (!option) {
                return;
            }
            e.preventDefault();
            changeLanguage(option.getAttribute('data-lang')).catch((error) => {
                console.error('Language switch failed:', error);
            });
        });

        document.addEventListener('click', function(e) {
            if (!languageButton.contains(e.target) && !languageDropdown.contains(e.target)) {
                languageDropdown.classList.add('hidden');
                dropdownArrow.classList.remove('rotate-180');
            }
        });

        window.addEventListener('resize', function() {
            if (!languageDropdown.classList.contains('hidden')) {
                positionLanguageDropdown();
            }
        });

        loadSupportedLanguages()
            .then(() => {
                const savedLanguage = getStoredLanguage();
                setSelected(savedLanguage);
                if (savedLanguage !== 'en') {
                    return changeLanguage(savedLanguage);
                }
                restoreOriginalContent();
                return null;
            })
            .catch((error) => {
                console.error('Failed to initialize language selector:', error);
                supportedLanguages.set('en', { language: 'en', name: 'English' });
                renderLanguageOptions();
                setSelected('en');
            });
    });
    </script>
    
    <main class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"> 
