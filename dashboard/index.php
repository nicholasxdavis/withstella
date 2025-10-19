<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#9c7ead">
    <title>Stella AI Dashboard</title>
    
    <link rel="icon" href="https://placehold.co/32x32/9c7ead/FFFFFF?text=S" type="image/png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        // Load configuration from API (works reliably)
        window.STRIPE_CONFIG_LOADED = false;
        
        // Fetch config on page load
        (async function loadConfig() { 
            try {
                const response = await fetch('/api/config.php');
                const data = await response.json();
                if (data.success && data.config) {
                    window.STRIPE_PK = data.config.stripe.publishableKey;
                    window.STRIPE_PROD = data.config.stripe.isProduction;
                    window.STRIPE_PRICE_ID = data.config.stripe.priceId;
                    window.STRIPE_CONFIG_LOADED = true;
                }
            } catch (error) {
                console.error('Failed to load config:', error);
            }
        })();
    </script>
    <!-- Geist font loaded locally -->
    <style>
        @font-face {
            font-family: 'Geist';
            src: url('../ui/fonts/Geist-Regular.ttf') format('truetype');
            font-weight: 400;
            font-style: normal;
        }
        @font-face {
            font-family: 'Geist';
            src: url('../ui/fonts/Geist-Medium.ttf') format('truetype');
            font-weight: 500;
            font-style: normal;
        }
        @font-face {
            font-family: 'Geist';
            src: url('../ui/fonts/Geist-Bold.ttf') format('truetype');
            font-weight: 700;
            font-style: normal;
        }
        body {
            font-family: 'Geist', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
    </style>
    <script>
        // Tailwind Play CDN hotfix for dynamic, JS-injected HTML
        // Safelist the utilities we use so CSS is generated before content injection
        tailwind = window.tailwind || {};
        tailwind.config = {
            safelist: [
                { pattern: /(sm|md|lg|xl):?(grid|flex|block|hidden)/ },
                { pattern: /(grid|inline-grid)/ },
                { pattern: /(grid-cols|col-span|row-span)-\d+/ },
                { pattern: /(gap|space-(x|y))-(0|1|2|3|4|6|8|10|12)/ },
                { pattern: /(p|px|py|pt|pb|pl|pr|m|mx|my|mt|mb|ml|mr)-(0|1|2|3|4|6|8|10|12)/ },
                { pattern: /(w|h|max-w|max-h|min-w|min-h)-(\d+|full|screen)/ },
                { pattern: /text-(xs|sm|base|lg|xl|2xl|3xl|4xl)/ },
                { pattern: /font-(thin|light|normal|medium|semibold|bold|black)/ },
                { pattern: /rounded(-(sm|md|lg|xl|2xl|full))?/ },
                { pattern: /border(-(0|2|4|8))?/ },
                { pattern: /justify-(start|center|end|between|around|evenly)/ },
                { pattern: /items-(start|center|end|stretch|baseline)/ },
                { pattern: /content-(center|between|around|evenly)/ },
                { pattern: /(bg|text|border)-(white|black|gray-\d+|neutral-\d+|red-\d+|yellow-\d+|green-\d+|blue-\d+|indigo-\d+|purple-\d+|pink-\d+)/ },
                { pattern: /(bg|text|border)-\[[^\]]+\]/ },
                { pattern: /(overflow|truncate|whitespace)-(auto|hidden|clip|ellipsis|normal|nowrap)/ },
                { pattern: /shadow(-(sm|md|lg|xl|2xl))?/ },
                { pattern: /(sticky|relative|absolute|fixed|static)/ },
                { pattern: /(top|right|bottom|left)-(0|1|2|3|4|6|8|10)/ },
                // Visibility helpers we rely on for tabs/sections
                'hidden','block','inline-block','sr-only'
            ]
        };
    </script>
    <!-- Note: Using Tailwind CDN for development. For production, install Tailwind CSS locally -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@10.0.1/dist/css/shepherd.css"/>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/shepherd.js@10.0.1/dist/js/shepherd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <script type="text/javascript">
        (function(){
            emailjs.init("bHnfaYAMCqIRNjinh"); // EmailJS Public Key
        })();
    </script>
    
    <!-- Global error handler to suppress passkey errors from extensions -->
    <script>
        window.addEventListener('error', function(event) {
            // Suppress passkey errors from browser extensions
            if (event.filename && (event.filename.includes('passkey') || event.filename.includes('extension'))) {
                event.preventDefault();
                return true;
            }
        }, true);
        
        // Suppress unhandled promise rejections from extensions
        window.addEventListener('unhandledrejection', function(event) {
            if (event.reason && event.reason.stack && 
                (event.reason.stack.includes('passkey') || event.reason.stack.includes('extension'))) {
                event.preventDefault();
            }
        });
    </script>
    
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-SPY1G645JS"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-SPY1G645JS');
    </script>

    <style>
        /* ------------------------- */
        /* --- Root Variables    --- */
        /* ------------------------- */
        :root {
            --primary: #9c7ead;
            --primary-dark: #826a94;
            --primary-light: #b692c7;
            --light-bg: #000000;
            --card-dark: #0a0a0a;
            --surface: #0a0a0a;
            --text-primary: #ededed;
            --text-secondary: #a19e97;
            --border-dark: #232323;
        }

        /* Custom Styles for Shepherd.js Tour */
        .shepherd-element {
            background: #1a1a1a;
            border-radius: 12px;
            border: 1px solid var(--border-dark);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .shepherd-header {
            background: #9c7ead;
            padding: 0.75rem 1rem;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        .shepherd-title {
            color: #1a1a1a;
            font-weight: 600;
        }
        .shepherd-cancel-icon {
            color: white;
        }
        .shepherd-text {
            color: var(--text-secondary);
            padding: 1rem;
        }
        .shepherd-button {
            background: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: 9999px;
            font-weight: 600;
            text-transform: none;
            letter-spacing: normal;
            font-size: 14px;
        }
        .shepherd-button:not(:disabled):hover {
             background: var(--primary-dark);
        }
        .shepherd-button-secondary {
            background: #333;
        }
        .shepherd-button-secondary:not(:disabled):hover {
            background: #555;
        }
        .shepherd-arrow::before {
            background: #1a1a1a;
        }
        .shepherd-highlight {
            border-radius: 9999px; /* Pill shape for buttons */
        }
        /* Make the highlight for the list item less rounded */
        [data-shepherd-step-id="step-3"].shepherd-target + .shepherd-highlight {
            border-radius: 24px;
        }
        
        /* ------------------------- */
        /* --- Base Styles         --- */
        /* ------------------------- */
        body {
            background: var(--light-bg);
            font-family: 'Geist', sans-serif;
            color: var(--text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ------------------------- */
        /* --- Components        --- */
        /* ------------------------- */
        .glass-card {
            background: var(--surface);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-dark);
            border-radius: 1rem; /* 16px */
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .glass-card:hover:not(.no-hover) {
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
            border-color: rgba(156, 126, 173, 0.4);
        }
        
        /* Updated Button Styles based on reference */
        .glow-btn { 
            position: relative; 
            overflow: hidden; 
            transition: all 0.3s ease; 
            transform: translateZ(0); 
        }
        .glow-btn:active { 
            transform: scale(0.97); 
            transition-duration: 0.1s; 
        }
        .glow-btn::before {
            content: ''; position: absolute; top: 50%; left: 50%;
            width: 0; height: 0;
            background: radial-gradient(circle, #9c7ead 0%, rgba(156, 126, 173, 0) 70%);
            border-radius: 50%; transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
            pointer-events: none; opacity: 0; z-index: 0;
        }
        .glow-btn:hover::before { 
            width: 250px; 
            height: 250px; 
            opacity: 0.3; 
        }
        .glow-btn > * { 
            position: relative; 
            z-index: 1; 
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            color: white;
            font-weight: 600;
        }
        
        .btn-secondary {
            background: var(--card-dark);
            border: 1px solid var(--border-dark);
            color: var(--text-primary);
        }
        .btn-secondary:hover {
            border-color: rgba(156, 126, 173, 0.4);
        }

        .btn-primary:disabled, .btn-secondary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .btn-primary:disabled:hover::before, .btn-secondary:disabled:hover::before {
             width: 0; 
             height: 0; 
             opacity: 0; 
        }
        
        /* ------------------------- */
        /* --- Logo Icon         --- */
        /* ------------------------- */
        .logo-icon { position: relative; width: 1em; height: 1em; filter: drop-shadow(0 0 6px #9c7ead); }
        .logo-icon-ray { position: absolute; top: 50%; left: 50%; width: 100%; height: 2px; background: linear-gradient(to right, rgba(156, 126, 173, 0), #9c7ead, rgba(156, 126, 173, 0)); transform-origin: center; }
        .logo-icon-ray:first-child { transform: translate(-50%, -50%) rotate(45deg); }
        .logo-icon-ray:last-child { transform: translate(-50%, -50%) rotate(-45deg); }
        
        /* ------------------------- */
        /* --- Brand Kit Cards   --- */
        /* ------------------------- */
        .brand-kit-card {
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .brand-kit-card .brand-kit-logo {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 65%;
            max-height: 65%;
            opacity: 0.05;
            pointer-events: none;
            transition: opacity 0.3s ease, transform 0.3s ease;
            object-fit: contain;
        }

        .brand-kit-card:hover .brand-kit-logo {
            opacity: 0.1;
            transform: translate(-50%, -50%) scale(1.05);
        }

        .brand-kit-card .card-menu-btn {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            z-index: 20;
            opacity: 0;
            transition: opacity 0.3s ease;
            background-color: rgba(0,0,0,0.3);
            border-radius: 9999px;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-kit-card:hover .card-menu-btn {
            opacity: 1;
        }

        .card-menu {
            position: absolute;
            top: calc(100% + 5px); /* Position below the button */
            right: 0;
            z-index: 30;
            display: none; /* Initially hidden */
        }

        .card-menu-btn.active + .card-menu {
            display: block; /* Show on active */
        }

        /* ------------------------- */
        /* --- Navigation        --- */
        /* ------------------------- */
        .nav-item {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .active-nav {
            background: linear-gradient(90deg, rgba(156, 126, 173, 0.2) 0%, rgba(156, 126, 173, 0.05) 100%);
            border-left: 3px solid var(--primary);
        }
        
        /* ------------------------- */
        /* --- Form Elements     --- */
        /* ------------------------- */
        input, textarea, select {
            transition: all 0.3s ease;
            background: #1a1a1a;
            border: 1px solid var(--border-dark);
            color: var(--text-primary);
        }
        
        input:focus, textarea:focus, select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(156, 126, 173, 0.2);
            outline: none;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            cursor: pointer;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .file-input-label {
            display: block;
            padding: 10px 15px;
            border: 1px solid var(--border-dark);
            border-radius: 8px;
            background-color: #1a1a1a;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .file-input-label:hover {
            border-color: var(--primary);
        }
        #logo-preview {
            display: none;
            margin-top: 10px;
            max-width: 100px;
            max-height: 100px;
            border-radius: 8px;
            border: 1px solid var(--border-dark);
            background: #111;
        }
        
        /* ------------------------- */
        /* --- Modals            --- */
        /* ------------------------- */
        .modal {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: var(--card-dark);
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            transform: translateY(20px) scale(0.95);
            transition: all 0.3s ease;
        }
        
        .modal.active .modal-content {
            transform: translateY(0) scale(1);
        }
        
        .modal-header {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-dark);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body { padding: 24px; }
        
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border-dark);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        /* ------------------------- */
        /* --- UI Elements       --- */
        /* ------------------------- */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            background-color: rgba(156, 126, 173, 0.15);
            color: var(--primary);
        }
        
        .progress-bar {
            background-color: var(--border-dark);
            border-radius: 8px;
            overflow: hidden;
            height: 10px;
        }

        .progress-bar-fill {
            background-color: var(--primary);
            height: 100%;
            transition: width 0.5s ease-in-out;
        }
        
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            background-color: rgba(156, 126, 173, 0.2);
            color: var(--primary);
        }
        
        #toast-notification {
            position: fixed; bottom: 20px; left: 50%;
            transform: translateX(-50%) translateY(150%);
            padding: 12px 24px; border-radius: 8px;
            color: white; font-weight: 500; z-index: 100;
            transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        #toast-notification.show { transform: translateX(-50%) translateY(0); }
        #toast-notification.success { background-color: var(--primary); }
        #toast-notification.error { background-color: var(--primary-dark); }
        #toast-notification.info { background-color: var(--primary); }

        /* ------------------------- */
        /* --- Page Layout       --- */
        /* ------------------------- */
        .page { display: none; }
        .page.active { display: block; }
        .page-view { display: none; }
        .page-view.active { display: block; }


        /* Solid headers */
        .md\:hidden.fixed, .hidden.md\:flex {
            background: #0a0a0a;
        }

        .hidden.md\:flex {
             border-bottom: 1px solid var(--border-dark);
        }
        
        /* ------------------------- */
        /* --- Animations        --- */
        /* ------------------------- */
        .fade-in { animation: fadeIn 0.3s ease-in forwards; }
        .slide-in { animation: slideIn 0.3s ease-out forwards; }
        .pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

        @keyframes tourGlow {
            0%, 100% { box-shadow: 0 0 8px 2px rgba(156, 126, 173, 0); }
            50% { box-shadow: 0 0 12px 4px rgba(156, 126, 173, 0.7); }
        }
        .tour-glow {
            animation: tourGlow 2s infinite;
            border-radius: 24px; /* match the item's border-radius */
        }
        
        /* Getting Started Animations */
        @keyframes slideOutUp {
            from { opacity: 1; transform: translateY(0); max-height: 1000px; }
            to { opacity: 0; transform: translateY(-20px); max-height: 0; }
        }
        @keyframes checkmark {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }
        .task-complete-animation {
            animation: checkmark 0.4s ease-out;
        }
        #getting-started-card.hiding {
            animation: slideOutUp 0.8s ease-out forwards;
        }


        /* ------------------------- */
        /* --- Toggle Switch     --- */
        /* ------------------------- */
        .switch { position: relative; display: inline-block; width: 50px; height: 28px; flex-shrink: 0; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #333; border-radius: 28px; transition: background-color 0.4s; }
        .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); transition: transform 0.4s ease, box-shadow 0.4s ease; }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(22px); box-shadow: 0 1px 3px rgba(0,0,0,0.4); }
        input:disabled + .slider { cursor: not-allowed; }


        /* ------------------------- */
        /* --- Asset Hub         --- */
        /* ------------------------- */
        #modal-asset-upload-area { border: 2px dashed var(--border-dark); transition: all 0.3s ease; }
        #modal-asset-upload-area.drag-over { border-color: var(--primary); background-color: rgba(156, 126, 173, 0.1); }
        .asset-card { transition: all 0.3s ease; cursor: pointer; position: relative; }
        .asset-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .asset-card.menu-open { z-index: 10; }
        
        /* NEW: Styles for light background asset cards */
        .asset-card.light-bg {
            background-color: #0a0a0a;
        }
        .asset-card.light-bg p {
            color: #f9fafb; /* A dark gray for better readability */
        }
        .asset-card.light-bg .card-menu-btn {
            color: #6b7280; /* A medium gray */
        }
        .asset-card.light-bg .card-menu-btn:hover {
            color: #1f2937;
        }
        .asset-card.light-bg .aspect-square {
            background-color: #e5e7eb; /* Light gray background for the image container */
        }

        .asset-card-actions { position: absolute; top: 0.5rem; right: 0.5rem; z-index: 1; }
        .asset-card-menu {
            position: absolute; top: 100%; right: 0;
            opacity: 0; visibility: hidden; transform: translateY(10px);
            transition: all 0.3s ease;
            z-index: 20;
        }
        .asset-card-actions:hover .asset-card-menu,
        .asset-card-actions .card-menu-btn.active + .asset-card-menu {
            opacity: 1; visibility: visible; transform: translateY(0);
        }

        /* ------------------------- */
        /* --- Scrollbar         --- */
        /* ------------------------- */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--card-dark); }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #555; }

        /* ------------------------- */
        /* --- Loading Spinner   --- */
        /* ------------------------- */
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* ------------------------- */
        /* --- Mobile Responsive --- */
        /* ------------------------- */
        
        /* Mobile-first responsive design */
        @media (max-width: 768px) {
            /* Mobile sidebar improvements */
            #mobile-sidebar {
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
            }
            
            #mobile-sidebar aside {
                width: 280px;
                box-shadow: 0 0 50px rgba(0, 0, 0, 0.8);
            }
            
            /* Mobile header improvements */
            header.md\\:hidden {
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                background: rgba(10, 10, 10, 0.95);
                border-bottom: 1px solid var(--border-dark);
            }
            
            /* Mobile content padding */
            main {
                padding-top: 80px !important;
            }
            
            #pages-container {
                padding: 1rem !important;
                max-width: 100% !important;
            }
            
            /* Mobile page titles */
            #page-title {
                font-size: 1.5rem;
                margin-bottom: 1rem;
                text-align: center;
            }
            
            /* Mobile header improvements */
            header.hidden.md\\:flex {
                padding: 1rem 1.5rem;
            }
            
            /* Mobile dashboard stats */
            .dashboard-stats {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            /* Mobile brand kit grid */
            .brand-kits-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            /* Mobile asset grid */
            .assets-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            
            /* Mobile activity feed */
            .activity-feed {
                max-height: 300px;
                overflow-y: auto;
            }
            
            /* Mobile search and filters */
            .search-filters {
                flex-direction: column;
                gap: 1rem;
            }
            
            .search-filters input,
            .search-filters select {
                width: 100%;
            }
            
            /* Mobile cards and components */
            .glass-card {
                margin-bottom: 1rem;
                border-radius: 12px;
            }
            
            /* Mobile buttons - larger touch targets */
            .glow-btn {
                min-height: 44px;
                padding: 12px 16px;
                font-size: 16px;
            }
            
            /* Mobile navigation items */
            .nav-item {
                min-height: 48px;
                padding: 12px 16px;
                font-size: 16px;
            }
            
            /* Mobile tables - horizontal scroll */
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border-radius: 12px;
            }
            
            .table-container table {
                min-width: 600px;
            }
            
            /* Mobile modals */
            .modal-content {
                margin: 1rem;
                max-height: calc(100vh - 2rem);
                overflow-y: auto;
                border-radius: 16px;
            }
            
            .modal-header {
                padding: 1.5rem 1.5rem 1rem 1.5rem;
                border-bottom: 1px solid var(--border-dark);
            }
            
            .modal-body {
                padding: 1.5rem;
            }
            
            .modal-footer {
                padding: 1rem 1.5rem 1.5rem 1.5rem;
                border-top: 1px solid var(--border-dark);
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .modal-footer .glow-btn {
                width: 100%;
                justify-content: center;
            }
            
            /* Mobile forms */
            input, select, textarea {
                font-size: 16px; /* Prevents zoom on iOS */
                padding: 12px 16px;
                border-radius: 8px;
                min-height: 44px; /* Touch-friendly height */
            }
            
            /* Mobile form labels */
            label {
                font-size: 14px;
                font-weight: 500;
                margin-bottom: 8px;
                display: block;
            }
            
            /* Mobile form spacing */
            .space-y-4 > * + * {
                margin-top: 1rem;
            }
            
            /* Mobile file inputs */
            .file-input-wrapper {
                min-height: 44px;
            }
            
            .file-input-label {
                padding: 12px 16px;
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            /* Mobile select styling */
            select {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
                background-position: right 12px center;
                background-repeat: no-repeat;
                background-size: 16px;
                padding-right: 40px;
                appearance: none;
            }
            
            /* Mobile textarea */
            textarea {
                resize: vertical;
                min-height: 100px;
            }
            
            /* Mobile checkbox and switch */
            input[type="checkbox"] {
                min-height: 20px;
                min-width: 20px;
            }
            
            .switch {
                min-height: 44px;
                display: flex;
                align-items: center;
            }
            
            /* Mobile grid improvements */
            .grid {
                gap: 1rem;
            }
            
            .grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3 {
                grid-template-columns: 1fr;
            }
            
            .grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-4 {
                grid-template-columns: 1fr;
            }
            
            /* Mobile brand kit cards */
            .brand-kit-card {
                min-height: 200px;
            }
            
            .brand-kit-card .card-menu-btn {
                opacity: 1; /* Always visible on mobile */
                background-color: rgba(0,0,0,0.6);
            }
            
            /* Mobile asset cards */
            .asset-card {
                min-height: 120px;
            }
            
            .asset-card-actions .card-menu-btn {
                opacity: 1; /* Always visible on mobile */
            }
            
            /* Mobile stats cards */
            .stat-icon {
                width: 48px;
                height: 48px;
                font-size: 1.5rem;
            }
            
            /* Mobile text sizing */
            h1 { font-size: 1.5rem; }
            h2 { font-size: 1.25rem; }
            h3 { font-size: 1.125rem; }
            
            /* Mobile spacing */
            .space-y-6 > * + * {
                margin-top: 1.5rem;
            }
            
            .space-y-4 > * + * {
                margin-top: 1rem;
            }
            
            /* Mobile toast positioning */
            .toast {
                left: 1rem;
                right: 1rem;
                width: auto;
                max-width: none;
            }
            
            /* Mobile confirmation modal */
            .confirmation-modal .modal-content {
                margin: 2rem 1rem;
                max-width: none;
            }
            
            /* Mobile team member cards */
            .team-member-card {
                padding: 1rem;
                border-radius: 12px;
            }
            
            /* Mobile API key cards */
            .api-key-card {
                padding: 1rem;
                border-radius: 12px;
            }
            
            /* Mobile governance rules */
            .governance-rule {
                padding: 1rem;
                border-radius: 12px;
            }
        }
        
        /* Tablet responsive design */
        @media (min-width: 769px) and (max-width: 1024px) {
            #pages-container {
                padding: 1.5rem;
            }
            
            .grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3 {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-4 {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .modal-content {
                margin: 2rem;
                max-width: 500px;
            }
        }
        
        /* Touch-friendly interactions */
        @media (hover: none) and (pointer: coarse) {
            /* Remove hover effects on touch devices */
            .glass-card:hover:not(.no-hover) {
                transform: none;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                border-color: var(--border-dark);
            }
            
            .glow-btn:hover::before {
                width: 0;
                height: 0;
                opacity: 0;
            }
            
            .brand-kit-card:hover .brand-kit-logo {
                opacity: 0.05;
                transform: translate(-50%, -50%);
            }
            
            .brand-kit-card:hover .card-menu-btn {
                opacity: 1;
            }
            
            .asset-card-actions:hover .asset-card-menu {
                opacity: 0;
                visibility: hidden;
                transform: translateY(-10px);
            }
            
            /* Make touch targets larger */
            button, .nav-item, .card-menu-btn {
                min-height: 44px;
                min-width: 44px;
            }
            
            /* Always show interactive elements on touch devices */
            .card-menu-btn {
                opacity: 1 !important;
            }
            
            .asset-card-actions .card-menu-btn {
                opacity: 1 !important;
            }
        }
        
        /* Landscape mobile optimization */
        @media (max-width: 768px) and (orientation: landscape) {
            .modal-content {
                margin: 0.5rem;
                max-height: calc(100vh - 1rem);
            }
            
            .modal-header {
                padding: 1rem 1.5rem 0.5rem 1.5rem;
            }
            
            .modal-body {
                padding: 1rem 1.5rem;
            }
            
            .modal-footer {
                padding: 0.5rem 1.5rem 1rem 1.5rem;
            }
        }
        
        /* High DPI mobile screens */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .glass-card {
                border-width: 0.5px;
            }
            
            .modal-content {
                border-width: 0.5px;
            }
        }
        
        /* Mobile-specific utility classes */
        .mobile-view .glass-card:hover:not(.no-hover) {
            transform: none;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border-color: var(--border-dark);
        }
        
        .touch-device .glow-btn:hover::before {
            width: 0;
            height: 0;
            opacity: 0;
        }
        
        .touch-device .card-menu-btn {
            opacity: 1 !important;
        }
        
        /* Mobile loading states */
        @media (max-width: 768px) {
            .loading-spinner {
                width: 24px;
                height: 24px;
            }
            
            .loading-text {
                font-size: 14px;
            }
        }
        
        /* Mobile accessibility improvements */
        @media (max-width: 768px) {
            /* Focus indicators for mobile */
            button:focus,
            input:focus,
            select:focus,
            textarea:focus {
                outline: 2px solid var(--primary);
                outline-offset: 2px;
            }
            
            /* High contrast mode support */
            @media (prefers-contrast: high) {
                .glass-card {
                    border: 2px solid var(--text-primary);
                }
                
                .glow-btn {
                    border: 2px solid var(--text-primary);
                }
            }
            
            /* Reduced motion support */
            @media (prefers-reduced-motion: reduce) {
                * {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
            }
        }
    </style>
</head>

<body class="min-h-screen antialiased">
    <div class="flex h-screen">
        
        <aside class="w-64 border-r p-4 hidden md:flex flex-col h-screen" style="background: var(--card-dark); border-color: var(--border-dark);">
            <div class="flex items-center space-x-3 mb-8">
                <div class="logo-icon text-3xl"><span class="logo-icon-ray"></span><span class="logo-icon-ray"></span></div>
                <h1 class="text-xl font-bold text-white">Stella.</h1>
            </div>
            
            <nav class="space-y-1">
                <div class="nav-item active-nav p-3 rounded-3xl flex items-center space-x-3 text-white" data-page="dashboard">
                    <i class="fas fa-tachometer-alt w-5 text-center"></i>
                    <span>Dashboard</span>
                </div>
                <div class="nav-item p-3 rounded-3xl flex items-center space-x-3 text-white" data-page="brand-kits">
                    <i class="fas fa-swatchbook w-5 text-center"></i>
                    <span>Your Kits</span>
                </div>
                <div class="nav-item p-3 rounded-3xl flex items-center space-x-3 text-white" data-page="asset-hub">
                    <i class="fas fa-folder-open w-5 text-center"></i>
                    <span>Asset Hub</span>
                </div>
                <div class="nav-item p-3 rounded-3xl flex items-center space-x-3 text-white" data-page="team-settings" id="team-settings-nav" style="display: none;">
                    <i class="fas fa-users-cog w-5 text-center"></i>
                    <span>Team Settings</span>
                </div>
                <div class="nav-item p-3 rounded-3xl flex items-center justify-between text-white" data-page="api" data-requires-pro="true">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-code w-5 text-center"></i>
                        <span>Stella Api</span>
                    </div>
                    <span class="pro-badge text-xs px-2 py-1 rounded-full font-semibold" style="background-color: #9c7ead;">PRO</span>
                </div>
                <div class="nav-item p-3 rounded-3xl flex items-center justify-between text-white" data-page="governance" data-requires-pro="true">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-shield-alt w-5 text-center"></i>
                        <span>Governance</span>
                    </div>
                    <span class="pro-badge text-xs px-2 py-1 rounded-full font-semibold" style="background-color: #9c7ead;">PRO</span>
                </div>
                <div class="nav-item p-3 rounded-3xl flex items-center justify-between text-white" data-page="analytics" data-requires-pro="true">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-chart-line w-5 text-center"></i>
                        <span>Analytics</span>
                    </div>
                    <span class="pro-badge text-xs px-2 py-1 rounded-full font-semibold" style="background-color: #9c7ead;">PRO</span>
                </div>
                <div class="nav-item p-3 rounded-3xl flex items-center space-x-3 text-white" data-page="billing">
                    <i class="fas fa-credit-card w-5 text-center"></i>
                    <span>Billing</span>
                </div>
                <div class="nav-item p-3 rounded-3xl flex items-center space-x-3 text-white" data-page="support">
                    <i class="fas fa-question-circle w-5 text-center"></i>
                    <span>Support</span>
                </div>
            </nav>
            
            <footer class="mt-auto text-center text-xs text-white/40 pt-6">
                © 2025 Stella by <a href="https://www.blacnova.net" target="_blank" rel="noopener noreferrer" class="hover:text-primary transition-colors">Blacnova</a>
            </footer>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="md:hidden fixed top-0 left-0 right-0 border-b p-4 z-10 flex justify-between items-center" style="border-color: var(--border-dark);">
                <button id="mobile-menu-button" class="text-white hover:text-primary text-xl">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="flex items-center space-x-3">
                    <div class="text-right">
                        <p class="font-medium text-sm text-[#f9fafb]" id="mobile-user-name">User</p>
                        <p class="text-xs text-[#f9fafb]/95" id="mobile-user-email">client@example.com</p>
                    </div>
                    <img src="https://placehold.co/40x40/1a1a1a/9c7ead?text=U" alt="User" class="w-8 h-8 rounded-full">
                </div>
            </header>
            
            <div id="mobile-sidebar" class="fixed inset-0 bg-black bg-opacity-70 z-20 hidden md:hidden">
                <aside class="w-64 h-full border-r p-4 flex flex-col" style="background: var(--card-dark); border-color: var(--border-dark);">
                    <div class="flex justify-between items-center mb-8">
                         <div class="logo-icon text-3xl"><span class="logo-icon-ray"></span><span class="logo-icon-ray"></span></div>
                        <button id="close-mobile-menu" class="text-white hover:text-primary text-2xl">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <nav class="space-y-1 flex-1">
                        <div class="nav-item active-nav p-3 rounded-3xl flex items-center space-x-3 text-white" data-page="dashboard">
                            <i class="fas fa-tachometer-alt w-5 text-center"></i>
                            <span>Dashboard</span>
                        </div>
                        <div class="nav-item p-3 rounded-3xl flex items-center space-x-3 text-white" data-page="brand-kits">
                            <i class="fas fa-swatchbook w-5 text-center"></i>
                            <span>Your Kits</span>
                        </div>
                        <div class="nav-item p-3 rounded-3xl flex items-center space-x-3 text-white" data-page="asset-hub">
                            <i class="fas fa-folder-open w-5 text-center"></i>
                            <span>Asset Hub</span>
                        </div>
                        <div class="nav-item p-3 rounded-3xl flex items-center space-x-3 text-white" data-page="team-settings" id="team-settings-nav-mobile" style="display: none;">
                            <i class="fas fa-users-cog w-5 text-center"></i>
                            <span>Team Settings</span>
                        </div>
                        <div class="nav-item p-3 rounded-3xl flex items-center justify-between text-white" data-page="api" data-requires-pro="true">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-code w-5 text-center"></i>
                                <span>Stella Api</span>
                            </div>
                            <span class="pro-badge text-xs px-2 py-1 rounded-full font-semibold" style="background-color: #9c7ead;">PRO</span>
                        </div>
                        <div class="nav-item p-3 rounded-3xl flex items-center justify-between text-white" data-page="governance" data-requires-pro="true">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-shield-alt w-5 text-center"></i>
                                <span>Governance</span>
                            </div>
                            <span class="pro-badge text-xs px-2 py-1 rounded-full font-semibold" style="background-color: #9c7ead;">PRO</span>
                        </div>
                        <div class="nav-item p-3 rounded-3xl flex items-center justify-between text-white" data-page="analytics" data-requires-pro="true">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-chart-line w-5 text-center"></i>
                                <span>Analytics</span>
                            </div>
                            <span class="pro-badge text-xs px-2 py-1 rounded-full font-semibold" style="background-color: #9c7ead;">PRO</span>
                        </div>
                        <div class="nav-item p-3 rounded-3xl flex items-center space-x-3 text-white" data-page="billing">
                            <i class="fas fa-credit-card w-5 text-center"></i>
                            <span>Billing</span>
                        </div>
                        <div class="nav-item p-3 rounded-3xl flex items-center space-x-3 text-white" data-page="support">
                            <i class="fas fa-question-circle w-5 text-center"></i>
                            <span>Support</span>
                        </div>
                    </nav>
                    <div class="pt-4 border-t border-[var(--border-dark)]">
                        <div id="mobile-logout-btn" class="nav-item p-3 rounded-3xl flex items-center space-x-3 text-white">
                            <i class="fas fa-sign-out-alt w-5 text-center"></i>
                            <span>Logout</span>
                        </div>
                    </div>
                    <footer class="text-center text-xs text-white/40 pt-6">
                        © 2025 Stella by <a href="https://www.blacnova.net" target="_blank" rel="noopener noreferrer" class="hover:text-primary transition-colors">Blacnova</a>
                    </footer>
                </aside>
            </div>

            <header class="hidden md:flex justify-between items-center p-6">
                <h1 id="page-title" class="text-2xl font-bold text-[#f9fafb]">Dashboard</h1>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="font-medium text-[#f9fafb]" id="desktop-user-name">User</p>
                        <p class="text-sm text-[#f9fafb]/95" id="desktop-user-email">client@example.com</p>
                    </div>
                    <img src="https://placehold.co/40x40/1a1a1a/9c7ead?text=U" alt="User" class="w-10 h-10 rounded-full">
                    <button id="logout-btn" class="text-[#f9fafb]/95 hover:text-primary" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto pt-20 md:pt-0" style="background-color: #f9fafb;">
                <div id="pages-container" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    
                    <section id="dashboard-page" class="page active">
                        </section>
                    
                    <section id="brand-kits-page" class="page">
                        </section>
                    
                    <section id="asset-hub-page" class="page">
                        </section>
                    
                    <section id="team-settings-page" class="page">
                        </section>
                    
                    <section id="api-page" class="page">
                        </section>
                    
                    <section id="governance-page" class="page">
                        </section>
                    
                    <section id="analytics-page" class="page">
                        </section>
                    
                    <section id="billing-page" class="page">
                        </section>
                    
                    <section id="support-page" class="page">
                        </section>
                </div>
            </main>
        </div>
    </div>

    <div id="welcome-modal" class="modal">
        <div class="modal-content">
            <div class="modal-body text-center p-8">
                <div class="logo-icon text-5xl mx-auto mb-4"><span class="logo-icon-ray"></span><span class="logo-icon-ray"></span></div>
                <h3 class="text-2xl font-bold text-white mb-2">Welcome to Stella!</h3>
                <p class="text-[var(--text-secondary)] mb-6">Let's take a quick tour to get you started.</p>
            </div>
            <div class="modal-footer justify-center bg-white/5">
                <button id="skip-tour-btn" class="glow-btn btn-secondary px-6 py-2 rounded-full">Skip for now</button>
                <button id="start-tour-btn" class="glow-btn btn-primary px-6 py-2 rounded-full">Start Tour</button>
            </div>
        </div>
    </div>


    <div id="new-brand-kit-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="text-xl font-medium">Create New Brand Kit</h3>
                <button class="modal-close text-gray-400 hover:text-primary text-3xl font-light">&times;</button>
            </div>
            <div class="modal-body">
                <form class="space-y-4" onsubmit="return false;">
                    <div>
                        <label for="kit-name" class="block text-sm font-medium mb-1">Kit Name</label>
                        <input type="text" id="kit-name" name="kit-name" class="w-full rounded-lg px-4 py-2" placeholder="e.g., Primary Brand">
                    </div>
                    <div>
                        <label for="kit-description" class="block text-sm font-medium mb-1">Description</label>
                        <textarea id="kit-description" name="kit-description" rows="3" class="w-full rounded-lg px-4 py-2" placeholder="A short description of this brand kit."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Kit Logo (Optional)</label>
                        <div class="file-input-wrapper w-full">
                             <label for="kit-logo-input" class="file-input-label">Choose File</label>
                             <input type="file" id="kit-logo-input" accept="image/*">
                        </div>
                        <img id="logo-preview" src="" alt="Logo Preview">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="glow-btn btn-secondary px-4 py-2 rounded-full modal-close">Cancel</button>
                <button id="create-kit-btn" class="glow-btn btn-primary px-4 py-2 rounded-full">Create Kit</button>
            </div>
        </div>
    </div>

    <div id="edit-brand-kit-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="text-xl font-medium">Edit Brand Kit</h3>
                <button class="modal-close text-gray-400 hover:text-primary text-3xl font-light">&times;</button>
            </div>
            <div class="modal-body">
                <form class="space-y-4" onsubmit="return false;">
                    <input type="hidden" id="edit-kit-id">
                    <div>
                        <label for="edit-kit-name" class="block text-sm font-medium mb-1">Kit Name</label>
                        <input type="text" id="edit-kit-name" class="w-full rounded-lg px-4 py-2">
                    </div>
                    <div>
                        <label for="edit-kit-description" class="block text-sm font-medium mb-1">Description</label>
                        <textarea id="edit-kit-description" rows="3" class="w-full rounded-lg px-4 py-2"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Logo</label>
                        <div class="file-input-wrapper w-full">
                            <label for="edit-kit-logo-input" class="file-input-label">Choose New Logo</label>
                            <input type="file" id="edit-kit-logo-input" accept="image/*">
                        </div>
                        <img id="edit-logo-preview" src="" alt="Logo Preview" class="mt-2" style="display: none; max-width: 100px; max-height: 100px; border-radius: 8px;">
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-3xl">
                        <div class="flex-1 pr-4">
                            <p class="font-medium">Make Private</p>
                            <p class="text-xs text-[var(--text-secondary)]">Only you and admins will be able to see this kit.</p>
                        </div>
                        <label class="switch"><input type="checkbox" id="edit-kit-private"><span class="slider"></span></label>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-between">
                <button id="delete-kit-btn" class="glow-btn px-4 py-2 rounded-full text-[#9c7ead] hover:text-red-500 hover:bg-red-500/10">Delete Kit</button>
                <div>
                    <button class="glow-btn btn-secondary px-4 py-2 rounded-full modal-close">Cancel</button>
                    <button id="save-kit-changes-btn" class="glow-btn btn-primary px-4 py-2 rounded-full">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    
    <div id="edit-member-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="text-xl font-medium">Edit Team Member</h3>
                <button class="modal-close text-gray-400 hover:text-primary text-3xl font-light">&times;</button>
            </div>
            <div class="modal-body">
                <form class="space-y-4" onsubmit="return false;">
                    <input type="hidden" id="edit-member-id">
                    <div>
                        <p class="text-sm text-[var(--text-secondary)]">Editing</p>
                        <p id="edit-member-email" class="font-medium text-white"></p>
                    </div>
                    <div>
                        <label for="edit-member-name" class="block text-sm font-medium mb-1">Display Name</label>
                        <input type="text" id="edit-member-name" class="w-full rounded-lg px-4 py-2" placeholder="Enter display name">
                    </div>
                    <div>
                        <label for="edit-member-role" class="block text-sm font-medium mb-1">Role</label>
                        <select id="edit-member-role" class="w-full rounded-lg px-4 py-2" style="background: #1a1a1a; border: 1px solid var(--border-dark); color: white;">
                            <option value="Admin">Admin</option>
                            <option value="Team Member">Team Member</option>
                            <option value="Viewer">Viewer</option>
                        </select>
                        <div id="role-info" class="bg-green-500/10 border border-green-500/30 rounded-lg p-3 mt-2">
                            <p class="text-xs text-green-300">
                                <i class="fas fa-info-circle mr-1"></i>
                                Role description will appear here
                            </p>
                        </div>
                    </div>
                    <div class="border-t border-[var(--border-dark)] pt-4">
                        <p class="font-medium text-white mb-2">Member Actions</p>
                        <div class="space-y-2">
                            <button id="ban-ip-btn" class="glow-btn btn-secondary w-full text-left p-3 rounded-lg flex items-center">
                                <i class="fas fa-gavel mr-3"></i>Ban IP Address
                            </button>
                            <button id="reset-password-btn" class="glow-btn btn-secondary w-full text-left p-3 rounded-lg flex items-center">
                                <i class="fas fa-key mr-3"></i>Reset Password
                            </button>
                            <button id="view-activity-btn" class="glow-btn btn-secondary w-full text-left p-3 rounded-lg flex items-center">
                                <i class="fas fa-chart-line mr-3"></i>View Activity
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="glow-btn btn-secondary px-4 py-2 rounded-full modal-close">Cancel</button>
                <button id="save-member-changes-btn" class="glow-btn btn-primary px-4 py-2 rounded-full">
                    <i class="fas fa-save mr-2"></i>Save Changes
                </button>
            </div>
        </div>
    </div>


    <div id="upload-asset-detail-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="text-xl font-medium">Upload New Asset</h3>
                <button class="modal-close text-gray-400 hover:text-primary text-3xl font-light">&times;</button>
            </div>
            <div class="modal-body">
                <form class="space-y-4" onsubmit="return false;">
                    <div id="modal-asset-upload-area" class="rounded-3xl p-6 text-center text-[var(--text-secondary)] border-2 border-dashed border-[var(--border-dark)] transition-all ease-in-out">
                        <p><i class="fas fa-cloud-upload-alt text-3xl mb-2"></i></p>
                        <p>Drag & Drop file here or</p>
                        <div class="file-input-wrapper w-auto mt-2">
                             <label for="modal-asset-file-input" class="file-input-label inline-block">Choose File</label>
                             <input type="file" id="modal-asset-file-input">
                        </div>
                    </div>
                     <img id="asset-upload-preview" src="" alt="Asset Preview" class="mt-2 mx-auto" style="display: none; max-width: 150px; max-height: 150px; border-radius: 8px;">
                    <div>
                        <label for="asset-upload-name" class="block text-sm font-medium mb-1">Asset Name</label>
                        <input type="text" id="asset-upload-name" class="w-full rounded-lg px-4 py-2" placeholder="e.g., primary-logo.svg">
                    </div>
                    <div>
                        <label for="asset-upload-category" class="block text-sm font-medium mb-1">Category</label>
                        <select id="asset-upload-category" class="w-full rounded-lg px-4 py-2">
                            <option value="other">Other</option>
                            <option value="logos">Logos</option>
                            <option value="marketing">Marketing</option>
                            <option value="documents">Documents</option>
                        </select>
                    </div>
                     <div>
                        <label for="asset-upload-kit" class="block text-sm font-medium mb-1">Add to Brand Kit (Optional)</label>
                        <select id="asset-upload-kit" class="w-full rounded-lg px-4 py-2">
                            <option value="">None</option>
                            </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="glow-btn btn-secondary px-4 py-2 rounded-full modal-close">Cancel</button>
                <button id="save-asset-btn" class="glow-btn btn-primary px-4 py-2 rounded-full">Save Asset</button>
            </div>
        </div>
    </div>

    <div id="invite-member-modal" class="modal">
        <div class="modal-content">
           <div class="modal-header">
               <h3 class="text-xl font-medium">Invite New Member</h3>
               <button class="modal-close text-gray-400 hover:text-primary text-3xl font-light">&times;</button>
           </div>
           <div class="modal-body">
               <form class="space-y-4" id="invite-form" onsubmit="event.preventDefault();">
                   <div>
                       <label for="invite-email" class="block text-sm font-medium mb-1">Email Address</label>
                       <input type="email" id="invite-email" required class="w-full rounded-lg px-4 py-2" placeholder="name@company.com">
                       <div id="email-validation" class="text-xs mt-1 hidden"></div>
                   </div>
                   <div>
                       <label for="invite-role" class="block text-sm font-medium mb-1">Role</label>
                       <select id="invite-role" class="w-full rounded-lg px-4 py-2" style="background: #1a1a1a; border: 1px solid var(--border-dark); color: white;">
                           <option value="Team Member">Team Member</option>
                           <option value="Admin">Admin</option>
                           <option value="Viewer">Viewer (View Only)</option>
                       </select>
                       <div class="bg-green-500/10 border border-green-500/30 rounded-lg p-3 mt-2">
                           <p class="text-xs text-green-300">
                               <i class="fas fa-info-circle mr-1"></i>
                               <strong>Team Member:</strong> Can download assets and create content<br/>
                               <strong>Admin:</strong> Full access including team management and settings<br/>
                               <strong>Viewer:</strong> Can only view, cannot download or create
                           </p>
                       </div>
                   </div>
                   <div>
                       <label for="invite-message" class="block text-sm font-medium mb-1">Personal Message (Optional)</label>
                       <textarea id="invite-message" class="w-full rounded-lg px-4 py-2" rows="3" placeholder="Add a personal message to the invitation..."></textarea>
                   </div>
               </form>
           </div>
           <div class="modal-footer">
               <button class="glow-btn btn-secondary px-4 py-2 rounded-full modal-close">Cancel</button>
               <button id="send-invite-btn" class="glow-btn btn-primary px-4 py-2 rounded-full">
                   <i class="fas fa-paper-plane mr-2"></i>Send Invite
               </button>
           </div>
       </div>
   </div>
   
   <div id="governance-rule-modal" class="modal">
        <div class="modal-content">
           <div class="modal-header">
               <h3 class="text-xl font-medium">Create Governance Rule</h3>
               <button class="modal-close text-gray-400 hover:text-primary text-3xl font-light">&times;</button>
           </div>
           <div class="modal-body">
               <form class="space-y-4" id="governance-rule-form" onsubmit="event.preventDefault();">
                   <div>
                       <label for="rule-name" class="block text-sm font-medium mb-1">Rule Name</label>
                       <input type="text" id="rule-name" required class="w-full rounded-lg px-4 py-2" placeholder="e.g., No Negative Words">
                   </div>
                   <div>
                       <label for="rule-description" class="block text-sm font-medium mb-1">Description (Optional)</label>
                       <input type="text" id="rule-description" class="w-full rounded-lg px-4 py-2" placeholder="Brief description of this rule">
                   </div>
                   <div>
                       <label for="rule-type" class="block text-sm font-medium mb-1">Rule Type</label>
                       <select id="rule-type" class="w-full rounded-lg px-4 py-2" style="background: #1a1a1a; border: 1px solid var(--border-dark); color: white;">
                           <option value="forbidden_words">Forbidden Words</option>
                           <option value="required_words">Required Words</option>
                           <option value="tone">Tone Check</option>
                           <option value="max_length">Maximum Length</option>
                           <option value="min_length">Minimum Length</option>
                           <option value="regex_pattern">Regex Pattern</option>
                       </select>
                   </div>
                   <div>
                       <label for="rule-value" class="block text-sm font-medium mb-1">Rule Value</label>
                       <textarea id="rule-value" required class="w-full rounded-lg px-4 py-2" rows="3" placeholder="Enter words separated by commas or a number for length rules"></textarea>
                       <p class="text-xs text-[var(--text-secondary)] mt-1" id="rule-value-hint">
                           Enter comma-separated words (e.g., bad, terrible, awful)
                       </p>
                   </div>
                   <div>
                       <label for="rule-severity" class="block text-sm font-medium mb-1">Severity</label>
                       <select id="rule-severity" class="w-full rounded-lg px-4 py-2" style="background: #1a1a1a; border: 1px solid var(--border-dark); color: white;">
                           <option value="warning">Warning (Allow with warning)</option>
                           <option value="error">Error (Block content)</option>
                       </select>
                   </div>
               </form>
           </div>
           <div class="modal-footer">
               <button class="glow-btn btn-secondary px-4 py-2 rounded-full modal-close">Cancel</button>
               <button id="create-rule-btn" class="glow-btn btn-primary px-4 py-2 rounded-full">Create Rule</button>
           </div>
       </div>
   </div>
   
   <div id="api-key-modal" class="modal">
        <div class="modal-content">
           <div class="modal-header">
               <h3 class="text-xl font-medium">Create API Key</h3>
               <button class="modal-close text-gray-400 hover:text-primary text-3xl font-light">&times;</button>
           </div>
           <div class="modal-body">
               <form class="space-y-4" id="api-key-form" onsubmit="event.preventDefault();">
                   <div>
                       <label for="api-key-name" class="block text-sm font-medium mb-1">Key Name</label>
                       <input type="text" id="api-key-name" required class="w-full rounded-lg px-4 py-2" placeholder="e.g., Production API Key">
                       <p class="text-xs text-[var(--text-secondary)] mt-1">
                           Choose a descriptive name to identify where this key is used
                       </p>
                   </div>
               </form>
           </div>
           <div class="modal-footer">
               <button class="glow-btn btn-secondary px-4 py-2 rounded-full modal-close">Cancel</button>
               <button id="create-api-key-btn" class="glow-btn btn-primary px-4 py-2 rounded-full">Generate Key</button>
           </div>
       </div>
   </div>
   
   <div id="api-key-display-modal" class="modal">
        <div class="modal-content">
           <div class="modal-header">
               <h3 class="text-xl font-medium">API Key Created!</h3>
               <button class="modal-close text-gray-400 hover:text-primary text-3xl font-light">&times;</button>
           </div>
           <div class="modal-body">
               <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4 mb-4">
                   <p class="text-yellow-500 text-sm">
                       <strong>⚠️ Important:</strong> Copy this API key now. You won't be able to see it again!
                   </p>
               </div>
               <div>
                   <label class="block text-sm font-medium mb-1">Your API Key</label>
                   <div class="flex items-center space-x-2">
                       <input type="text" id="new-api-key-value" readonly class="flex-1 rounded-lg px-4 py-2 font-mono text-sm" style="background: #1a1a1a; border: 1px solid var(--border-dark); color: #10b981;">
                       <button onclick="copyApiKey()" class="glow-btn btn-secondary px-4 py-2 rounded-lg">
                           <i class="fas fa-copy mr-2"></i>Copy
                       </button>
                   </div>
               </div>
           </div>
           <div class="modal-footer">
               <button class="glow-btn btn-primary px-4 py-2 rounded-full modal-close">I've Saved It</button>
           </div>
       </div>
   </div>

   <div id="activity-modal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
           <div class="modal-header">
               <h3 class="text-xl font-medium">All Activity</h3>
               <button class="modal-close text-gray-400 hover:text-primary text-3xl font-light">&times;</button>
           </div>
           <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
                <div id="all-activity-list" class="space-y-2">
                    <p class="text-center text-[var(--text-secondary)] py-4">Loading activities...</p>
                </div>
           </div>
           <div class="modal-footer">
               <button onclick="downloadActivityLog()" class="glow-btn btn-secondary px-4 py-2 rounded-full">
                   <i class="fas fa-download mr-2"></i>Download CSV
               </button>
               <button class="glow-btn btn-primary px-4 py-2 rounded-full modal-close">Close</button>
           </div>
       </div>
   </div>

   <div id="confirmation-modal" class="modal">
        <div class="modal-content">
           <div class="modal-header">
               <h3 id="confirmation-title" class="text-xl font-medium">Confirmation</h3>
               <button class="modal-close text-gray-400 hover:text-primary text-3xl font-light">&times;</button>
           </div>
           <div class="modal-body">
                <p id="confirmation-message" class="text-[var(--text-secondary)]"></p>
                <div id="confirmation-body"></div>
                <div id="confirmation-btn-container"></div>
           </div>
           <div class="modal-footer">
               <button id="confirmation-ok-btn" class="glow-btn btn-primary px-4 py-2 rounded-full modal-close">OK</button>
           </div>
       </div>
   </div>

   <div id="share-modal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
           <div class="modal-header">
               <h3 class="text-xl font-medium">
                   <i class="fas fa-share-alt text-primary mr-2"></i>
                   <span id="share-modal-title">Share</span>
               </h3>
               <button class="modal-close text-gray-400 hover:text-primary text-3xl font-light">&times;</button>
           </div>
           <div class="modal-body">
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-[var(--text-secondary)] mb-4">
                            <span id="share-item-name" class="font-semibold text-white"></span> will be shared with the people you invite below.
                        </p>
                    </div>
                    
                    <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium flex items-center">
                                <i class="fas fa-link text-primary mr-2"></i>
                                Shareable Link
                            </label>
                            <span id="share-link-status" class="text-xs px-2 py-1 bg-green-500/20 text-green-500 rounded-full">Active</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <input type="text" id="share-link-input" readonly class="flex-1 rounded-lg px-3 py-2 bg-black/30 text-sm" value="https://stella.app/share/abc123">
                            <button id="copy-share-link-btn" class="glow-btn btn-secondary px-4 py-2 rounded-lg text-sm">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <p class="text-xs text-[var(--text-secondary)] mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Anyone with this link can view this content
                        </p>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium block mb-2">
                            <i class="fas fa-user-plus text-primary mr-2"></i>
                            Invite Collaborators
                        </label>
                        <div class="flex items-center space-x-2 mb-3">
                            <input type="email" id="share-invite-email" placeholder="name@company.com" class="flex-1 rounded-lg px-3 py-2 text-sm">
                            <select id="share-permission-select" class="rounded-lg px-3 py-2 bg-[var(--card-dark)] border border-[var(--border-dark)] text-sm">
                                <option value="view">Can View</option>
                                <option value="edit">Can Edit</option>
                            </select>
                            <button id="send-share-invite-btn" class="glow-btn btn-primary px-4 py-2 rounded-lg text-sm">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <div id="shared-with-list" class="space-y-2 max-h-40 overflow-y-auto">
                            <!-- Shared collaborators will appear here -->
                        </div>
                    </div>
                    
                    <div class="p-3 bg-primary/10 rounded-lg border border-primary/20">
                        <div class="flex items-start space-x-2">
                            <i class="fas fa-shield-alt text-primary mt-1"></i>
                            <div class="flex-1">
                                <p class="text-xs font-medium text-white mb-1">Privacy & Security</p>
                                <p class="text-xs text-[var(--text-secondary)]">All shared content is encrypted and you can revoke access at any time.</p>
                            </div>
                        </div>
                    </div>
                </div>
           </div>
           <div class="modal-footer">
               <button class="glow-btn btn-secondary px-4 py-2 rounded-full modal-close">Close</button>
               <button id="revoke-share-btn" class="glow-btn px-4 py-2 rounded-full" style="background: #ef4444; color: white;">
                   <i class="fas fa-ban mr-2"></i>Revoke All Access
               </button>
           </div>
       </div>
   </div>
    
    <div id="toast-notification"></div>

    <script type="module">
        // --- APPLICATION SCOPE ---
        // Global state variables, populated after login
        let brandKits = [];
        let assets = [];
        let teamMembers = [];
        let pendingInvites = [];
        let activities = [];
        let gettingStartedState = {};
        let userPrefix = '';

        document.addEventListener('DOMContentLoaded', () => {
            // --- DATA & STATE MANAGEMENT ---
            const ls = {
                get: (key, fallback) => JSON.parse(localStorage.getItem(key)) || fallback,
                set: (key, value) => localStorage.setItem(key, JSON.stringify(value)),
            };

            // --- MOBILE GESTURES & TOUCH INTERACTIONS ---
            let touchStartX = 0;
            let touchStartY = 0;
            let touchEndX = 0;
            let touchEndY = 0;
            let isMobileMenuOpen = false;

            // Mobile sidebar swipe gestures
            function setupMobileGestures() {
                const mobileSidebar = document.getElementById('mobile-sidebar');
                const mobileMenuButton = document.getElementById('mobile-menu-button');
                const closeMobileMenu = document.getElementById('close-mobile-menu');

                if (!mobileSidebar || !mobileMenuButton) return;

                // Touch start
                document.addEventListener('touchstart', (e) => {
                    touchStartX = e.changedTouches[0].screenX;
                    touchStartY = e.changedTouches[0].screenY;
                }, { passive: true });

                // Touch end
                document.addEventListener('touchend', (e) => {
                    touchEndX = e.changedTouches[0].screenX;
                    touchEndY = e.changedTouches[0].screenY;
                    handleSwipe();
                }, { passive: true });

                // Swipe detection
                function handleSwipe() {
                    const deltaX = touchEndX - touchStartX;
                    const deltaY = touchEndY - touchStartY;
                    const minSwipeDistance = 50;

                    // Horizontal swipe detection
                    if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > minSwipeDistance) {
                        if (deltaX > 0 && touchStartX < 50 && !isMobileMenuOpen) {
                            // Swipe right from left edge - open menu
                            openMobileMenu();
                        } else if (deltaX < 0 && isMobileMenuOpen) {
                            // Swipe left - close menu
                            closeMobileMenuHandler();
                        }
                    }
                }

                // Mobile menu functions
                function openMobileMenu() {
                    mobileSidebar.classList.remove('hidden');
                    isMobileMenuOpen = true;
                    document.body.style.overflow = 'hidden';
                }

                function closeMobileMenuHandler() {
                    mobileSidebar.classList.add('hidden');
                    isMobileMenuOpen = false;
                    document.body.style.overflow = '';
                }

                // Event listeners
                mobileMenuButton.addEventListener('click', openMobileMenu);
                closeMobileMenu.addEventListener('click', closeMobileMenuHandler);
                
                // Close menu when clicking outside
                mobileSidebar.addEventListener('click', (e) => {
                    if (e.target === mobileSidebar) {
                        closeMobileMenuHandler();
                    }
                });

                // Close menu when navigating
                const navItems = mobileSidebar.querySelectorAll('.nav-item');
                navItems.forEach(item => {
                    item.addEventListener('click', () => {
                        setTimeout(closeMobileMenuHandler, 300);
                    });
                });
            }

            // Mobile-optimized modal handling
            function setupMobileModals() {
                const modals = document.querySelectorAll('.modal');
                
                modals.forEach(modal => {
                    // Close modal on outside click
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) {
                            modal.classList.remove('active');
                            document.body.style.overflow = '';
                        }
                    });

                    // Close modal on escape key
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && modal.classList.contains('active')) {
                            modal.classList.remove('active');
                            document.body.style.overflow = '';
                        }
                    });

                    // Prevent body scroll when modal is open
                    const modalCloseButtons = modal.querySelectorAll('.modal-close');
                    modalCloseButtons.forEach(btn => {
                        btn.addEventListener('click', () => {
                            modal.classList.remove('active');
                            document.body.style.overflow = '';
                        });
                    });
                });
            }

            // Mobile table horizontal scroll
            function setupMobileTables() {
                const tables = document.querySelectorAll('table');
                
                tables.forEach(table => {
                    const container = table.closest('.table-container') || table.parentElement;
                    if (container) {
                        container.style.overflowX = 'auto';
                        container.style.webkitOverflowScrolling = 'touch';
                        
                        // Add scroll indicator
                        if (window.innerWidth <= 768) {
                            const scrollIndicator = document.createElement('div');
                            scrollIndicator.className = 'scroll-indicator';
                            scrollIndicator.innerHTML = '<i class="fas fa-arrows-alt-h"></i> Scroll horizontally';
                            scrollIndicator.style.cssText = `
                                position: absolute;
                                top: 10px;
                                right: 10px;
                                background: rgba(0,0,0,0.7);
                                color: white;
                                padding: 4px 8px;
                                border-radius: 4px;
                                font-size: 12px;
                                z-index: 10;
                                opacity: 0.7;
                            `;
                            container.style.position = 'relative';
                            container.appendChild(scrollIndicator);
                            
                            // Hide indicator after scroll
                            container.addEventListener('scroll', () => {
                                scrollIndicator.style.opacity = '0';
                                setTimeout(() => scrollIndicator.remove(), 1000);
                            });
                        }
                    }
                });
            }

            // Mobile form optimizations
            function setupMobileForms() {
                const inputs = document.querySelectorAll('input, select, textarea');
                
                inputs.forEach(input => {
                    // Prevent zoom on iOS
                    if (input.type !== 'range') {
                        input.style.fontSize = '16px';
                    }
                    
                    // Add focus states for mobile
                    input.addEventListener('focus', () => {
                        input.style.transform = 'scale(1.02)';
                        input.style.transition = 'transform 0.2s ease';
                    });
                    
                    input.addEventListener('blur', () => {
                        input.style.transform = 'scale(1)';
                    });
                });
            }

            // Mobile card interactions
            function setupMobileCards() {
                const cards = document.querySelectorAll('.glass-card, .brand-kit-card, .asset-card');
                
                cards.forEach(card => {
                    // Add touch feedback
                    card.addEventListener('touchstart', () => {
                        card.style.transform = 'scale(0.98)';
                        card.style.transition = 'transform 0.1s ease';
                    }, { passive: true });
                    
                    card.addEventListener('touchend', () => {
                        card.style.transform = 'scale(1)';
                    }, { passive: true });
                });
            }

            // Initialize mobile features
            function initMobileFeatures() {
                if (window.innerWidth <= 768) {
                    setupMobileGestures();
                    setupMobileModals();
                    setupMobileTables();
                    setupMobileForms();
                    setupMobileCards();
                }
            }

            // Run on load and resize
            initMobileFeatures();
            window.addEventListener('resize', () => {
                initMobileFeatures();
            });

            // Mobile-specific optimizations
            function optimizeForMobile() {
                // Disable hover effects on touch devices
                if ('ontouchstart' in window) {
                    document.body.classList.add('touch-device');
                }
                
                // Optimize scroll performance
                document.body.style.webkitOverflowScrolling = 'touch';
                
                // Prevent zoom on double tap
                let lastTouchEnd = 0;
                document.addEventListener('touchend', function (event) {
                    const now = (new Date()).getTime();
                    if (now - lastTouchEnd <= 300) {
                        event.preventDefault();
                    }
                    lastTouchEnd = now;
                }, false);
                
                // Add mobile-specific classes
                if (window.innerWidth <= 768) {
                    document.body.classList.add('mobile-view');
                }
            }

            // Initialize mobile optimizations
            optimizeForMobile();
            window.addEventListener('resize', () => {
                optimizeForMobile();
            });

            // --- NAVIGATION PERMISSIONS ---
            function updateNavigationPermissions() {
                const hasAdmin = hasAdminPermissions();
                const teamSettingsNav = document.getElementById('team-settings-nav');
                const teamSettingsNavMobile = document.getElementById('team-settings-nav-mobile');
                
                if (teamSettingsNav) {
                    teamSettingsNav.style.display = hasAdmin ? 'flex' : 'none';
                }
                if (teamSettingsNavMobile) {
                    teamSettingsNavMobile.style.display = hasAdmin ? 'flex' : 'none';
                }
                
                // Update team settings page admin sections
                const inviteMemberBtn = document.getElementById('invite-member-btn');
                const workspacePermissionsSection = document.getElementById('workspace-permissions-section');
                const downloadRequestsSection = document.getElementById('download-requests-section');
                
                if (inviteMemberBtn) {
                    inviteMemberBtn.style.display = hasAdmin ? 'flex' : 'none';
                }
                if (workspacePermissionsSection) {
                    workspacePermissionsSection.style.display = hasAdmin ? 'block' : 'none';
                }
                if (downloadRequestsSection) {
                    downloadRequestsSection.style.display = hasAdmin ? 'block' : 'none';
                }
                
                // Update other admin-only sections
                const brandGuidelinesSection = document.getElementById('brand-guidelines-section');
                const pricingButton = document.getElementById('pricing-button');
                const dashboardActions = document.getElementById('dashboard-actions');
                
                if (brandGuidelinesSection) {
                    brandGuidelinesSection.style.display = hasAdmin ? 'block' : 'none';
                }
                if (pricingButton) {
                    pricingButton.style.display = hasAdmin ? 'inline-flex' : 'none';
                }
                if (dashboardActions) {
                    dashboardActions.style.display = hasAdmin ? 'flex' : 'none';
                }
            }

            function getUserDataKey(baseKey) {
                return `${userPrefix}_${baseKey}`;
            }

            // Helper function to get auth headers for API requests
            function getAuthHeaders() {
                const user = JSON.parse(sessionStorage.getItem('stella_user') || '{}');
                const headers = {};
                if (user.id) {
                    headers['X-User-ID'] = user.id.toString();
                }
                return headers;
            }

            // Helper function for clipboard fallback (older browsers)
            function fallbackCopyToClipboard(text) {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    showToast('URL copied to clipboard!', 'success');
                } catch (err) {
                    showToast('Failed to copy. URL: ' + text.substring(0, 50) + '...', 'error');
                }
                document.body.removeChild(textArea);
            }

            // Helper function to convert Nextcloud share URL to download URL
            function getAssetPreviewUrl(fileUrl) {
                // If it's a Nextcloud share URL (contains /s/), append /download for direct access
                if (fileUrl && fileUrl.includes('/s/')) {
                    return fileUrl.replace(/\/$/, '') + '/download';
                }
                return fileUrl;
            }

            // Helper function to reload assets from database
            async function reloadAssetsFromDatabase() {
                try {
                    const response = await fetch('/api/assets.php?action=list', {
                        headers: getAuthHeaders()
                    });
                    const data = await response.json();
                    if (data.success && data.assets) {
                        assets = data.assets.map(asset => {
                            // Use public_url from API (handles token generation)
                            const publicUrl = asset.public_url || null;
                            const proxyUrl = `/api/image_proxy.php?id=${asset.id}`;
                            const downloadUrl = `/api/image_proxy.php?id=${asset.id}&download=true`;
                            
                            return {
                                id: asset.id,
                                name: asset.name,
                                url: publicUrl, // Public share link for copying
                                downloadUrl: downloadUrl, // Authenticated download
                                previewUrl: proxyUrl, // Authenticated preview
                                shareToken: asset.share_token,
                                type: asset.type,
                                size: (asset.file_size / 1024).toFixed(2) + ' KB',
                                category: 'other'
                            };
                        });
                        ls.set(getUserDataKey('stella_assets'), assets);
                        return true;
                    }
                    return false;
                } catch (error) {
                    console.error('Failed to reload assets:', error);
                    return false;
                }
            }

            // Helper function to update asset thumbnails with Nextcloud file info
            async function updateAssetThumbnails() {
                try {
                    showToast('Starting thumbnail update...', 'info');
                    
                    // Step 1: Check if columns exist
                    const checkResponse = await fetch('/api/update_assets.php?action=check-columns');
                    const checkData = await checkResponse.json();
                    
                    if (!checkData.success) {
                        showToast('Failed to check database structure', 'error');
                        return;
                    }
                    
                    // Step 2: Add missing columns if needed
                    if (!checkData.has_nextcloud_file_id || !checkData.has_nextcloud_etag) {
                        showToast('Adding missing database columns...', 'info');
                        const addResponse = await fetch('/api/update_assets.php?action=add-columns');
                        const addData = await addResponse.json();
                        
                        if (!addData.success) {
                            showToast('Failed to add database columns', 'error');
                            return;
                        }
                    }
                    
                    // Step 3: Update existing assets
                    showToast('Updating assets with Nextcloud file info...', 'info');
                    const updateResponse = await fetch('/api/update_assets.php?action=update-assets');
                    const updateData = await updateResponse.json();
                    
                    if (updateData.success) {
                        showToast(`Updated ${updateData.updated_count} assets successfully!`, 'success');
                        
                        // Refresh the assets to show updated thumbnails
                        await loadAssetsFromAPI();
                        renderAssets();
                        
                        if (updateData.errors && updateData.errors.length > 0) {
                            console.warn('Some assets could not be updated:', updateData.errors);
                        }
                    } else {
                        showToast('Failed to update assets: ' + updateData.message, 'error');
                    }
                    
                } catch (error) {
                    console.error('Error updating thumbnails:', error);
                    showToast('Error updating thumbnails: ' + error.message, 'error');
                }
            }

            // Helper function to delete asset from database and Nextcloud
            async function deleteAsset(assetId) {
                try {
                    const response = await fetch(`/api/assets.php?action=delete&id=${assetId}`, {
                        headers: getAuthHeaders()
                    });
                    const data = await response.json();
                    if (data.success) {
                        // Remove from local state
                        assets = assets.filter(asset => asset.id !== assetId);
                        ls.set(getUserDataKey('stella_assets'), assets);
                        
                        // Remove saved URL from localStorage
                        const savedUrls = JSON.parse(localStorage.getItem('stella_asset_urls') || '{}');
                        delete savedUrls[assetId];
                        localStorage.setItem('stella_asset_urls', JSON.stringify(savedUrls));
                        
                        // Also remove from brand kits
                        brandKits.forEach(kit => {
                            if (kit.assets) {
                                kit.assets = kit.assets.filter(asset => asset.id !== assetId);
                            }
                        });
                        ls.set(getUserDataKey('stella_brandKits'), brandKits);
                        
                        // Use global refresh function
                        if (window.refreshAssetHub) {
                            window.refreshAssetHub();
                        }
                        renderBrandKits();
                        updateDashboardStats();
                        showToast('Asset deleted successfully.', 'success');
                        return true;
                    } else {
                        showToast('Failed to delete asset: ' + (data.message || 'Unknown error'), 'error');
                        return false;
                    }
                } catch (error) {
                    console.error('Failed to delete asset:', error);
                    showToast('Failed to delete asset. Please try again.', 'error');
                    return false;
                }
            }

            async function loadUserWorkspace(user) {
                userPrefix = user.email;

                // Load user plan from database first
                await loadUserPlanFromAPI();

                // Load user-specific data from localStorage (for offline data)
                teamMembers = ls.get(getUserDataKey('stella_teamMembers'), []);
                pendingInvites = ls.get(getUserDataKey('stella_pendingInvites'), []);
                activities = ls.get(getUserDataKey('stella_activities'), []);
                gettingStartedState = ls.get(getUserDataKey('stella_getting_started'), {
                    kitCreated: false, assetUploaded: false, teamInvited: false, completed: false, dismissed: false
                });

                // Load activities from API
                loadActivitiesFromAPI();

                // Load team members and pending invites from database API to ensure we have the latest data
                await loadTeamMembersFromAPI();
                await loadPendingInvitesFromAPI();
                
                // Update navigation permissions based on user role
                updateNavigationPermissions();
                
                // Update Pro badge visibility based on effective Pro access
                updateProBadgeVisibility();

                // Load assets from database API (syncs across devices)
                try {
                    const response = await fetch('/api/assets.php?action=list', {
                        headers: getAuthHeaders()
                    });
                    const data = await response.json();
                    if (data.success && data.assets) {
                        // Map database assets to match our format
                        assets = data.assets.map(asset => {
                            const shareUrl = asset.file_url;
                            const downloadUrl = shareUrl && shareUrl.includes('/s/') 
                                ? shareUrl.replace(/\/$/, '') + '/download' 
                                : shareUrl;
                            
                            return {
                                id: asset.id,
                                name: asset.name,
                                url: shareUrl,
                                downloadUrl: downloadUrl,
                                previewUrl: downloadUrl, // Use download URL as preview
                                type: asset.type,
                                size: (asset.file_size / 1024).toFixed(2) + ' KB',
                                category: 'other' // TODO: Add category to database
                            };
                        });
                        // Cache in localStorage for quick access
                        ls.set(getUserDataKey('stella_assets'), assets);
                    } else {
                        // Fallback to localStorage if API fails
                        assets = ls.get(getUserDataKey('stella_assets'), []);
                    }
                } catch (error) {
                    console.error('Failed to load assets from database:', error);
                    // Fallback to localStorage
                    assets = ls.get(getUserDataKey('stella_assets'), []);
                }

                // Load brand kits from database API (syncs across devices)
                try {
                    const response = await fetch('/api/brand_kits.php?action=list', {
                        headers: getAuthHeaders()
                    });
                    const data = await response.json();
                    if (data.success && data.brand_kits) {
                        // Map database brand kits to match our format
                        brandKits = data.brand_kits.map(kit => {
                            // Get logo asset ID from the logo_url if it's stored
                            // For now, logo_url contains the file URL, we need to find the asset
                            let logoUrl = null;
                            if (kit.logo_url) {
                                // If logo_url contains an asset ID pattern or we need to look it up
                                // For simplicity, check if it's a proxy URL or generate one
                                logoUrl = kit.logo_url.includes('/image_proxy.php') 
                                    ? kit.logo_url 
                                    : kit.logo_url; // Keep as is for now
                            }
                            
                            return {
                                id: kit.id,
                                name: kit.name,
                                description: kit.description,
                                logoUrl: logoUrl,
                                isPrivate: false,
                                assets: kit.assets ? kit.assets.map(asset => {
                                // Use public_url from API
                                const publicUrl = asset.public_url || null;
                                const proxyUrl = `/api/image_proxy.php?id=${asset.id}`;
                                const downloadUrl = `/api/image_proxy.php?id=${asset.id}&download=true`;
                                
                                return {
                                    id: asset.id,
                                    name: asset.name,
                                    url: publicUrl, // Public share link
                                    downloadUrl: downloadUrl, // Authenticated download
                                    previewUrl: proxyUrl, // Authenticated preview
                                    shareToken: asset.share_token,
                                    type: asset.type,
                                    size: (asset.file_size / 1024).toFixed(2) + ' KB'
                                };
                            }) : []
                        };
                        });
                        // Cache in localStorage for quick access
                        ls.set(getUserDataKey('stella_brandKits'), brandKits);
                    } else {
                        // Fallback to localStorage if API fails
                        brandKits = ls.get(getUserDataKey('stella_brandKits'), []);
                    }
                } catch (error) {
                    console.error('Failed to load brand kits from database:', error);
                    // Fallback to localStorage
                    brandKits = ls.get(getUserDataKey('stella_brandKits'), []);
                }

                // Render all dynamic parts of the UI
                renderBrandKits();
                renderTeamMembers();
                renderActivities();
                updateDashboardStats();
                renderGettingStarted();
                renderTeamOverview();
                initializeProductTour(user);
                
                // Render assets after loading
                if (window.refreshAssetHub) {
                    window.refreshAssetHub();
                }
            }

            // --- TEMPLATE INJECTION ---
            function injectPageContent() {
                document.getElementById('dashboard-page').innerHTML = `
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 text-center md:text-left">
                        <div>
                            <h2 class="text-3xl font-bold text-gray-900">Welcome back, User!</h2>
                            <p class="text-gray-600 mt-1">Here's a snapshot of your workspace.</p>
                        </div>
                        <div id="dashboard-actions" class="flex items-center space-x-2 mt-4 md:mt-0 mx-auto md:mx-0" style="display: none;">
                             <button class="glow-btn btn-secondary px-3 md:px-4 py-1.5 md:py-2 rounded-full flex items-center text-sm md:text-base" data-modal-target="new-brand-kit-modal">
                                 <i class="fas fa-swatchbook mr-2"></i> New Brand Kit
                             </button>
                             <button class="glow-btn btn-primary px-3 md:px-4 py-1.5 md:py-2 rounded-full flex items-center text-sm md:text-base" data-modal-target="upload-asset-detail-modal">
                                 <i class="fas fa-upload mr-2"></i> Upload Asset
                             </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2">
                            <div id="getting-started-card" class="glass-card p-6 mb-6">
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <h2 class="text-xl font-bold">Getting Started</h2>
                                        <p class="text-xs text-[var(--text-secondary)] mt-1">Complete these steps to unlock Stella's full potential</p>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <span id="getting-started-progress" class="text-sm text-[var(--text-secondary)]">0 of 3 complete</span>
                                        <button id="dismiss-getting-started" class="text-[var(--text-secondary)] hover:text-white transition-colors" title="Dismiss">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div id="getting-started-list" class="space-y-4">
                                    </div>
                            </div>
                            <div class="glass-card p-6">
                                <div class="flex justify-between items-center mb-4"><h2 class="text-xl font-bold">Recent Activity</h2><button onclick="openActivityModal()" class="glow-btn btn-secondary px-3 py-1 rounded-full text-sm">View All</button></div>
                                <div id="recent-activity-list" class="space-y-2"></div>
                            </div>
                        </div>
                        <div class="lg:col-span-1 space-y-6">
                            <div class="glass-card p-6">
                                <div class="flex justify-between items-center mb-4"><h2 class="text-xl font-bold">Team Overview</h2>${hasAdminPermissions() ? '<button class="glow-btn btn-secondary px-3 py-1 rounded-full text-sm" onclick="document.querySelector(\'.nav-item[data-page=\\\'team-settings\\\']\').click()">Manage</button>' : ''}</div>
                                <div class="flex items-center space-x-4 p-3 bg-white/5 rounded-3xl mb-4">
                                    <div class="stat-icon text-2xl w-12 h-12"><i class="fas fa-users"></i></div>
                                    <div>
                                        <p id="team-overview-count" class="text-3xl font-bold">0</p>
                                        <p class="text-sm text-[var(--text-secondary)]">Total Members</p>
                                    </div>
                                </div>
                                <div id="team-overview-list" class="space-y-3"></div>
                            </div>
                            <div class="glass-card p-6">
                                <div class="flex justify-between items-center mb-4"><h2 class="text-xl font-bold">Usage Snapshot</h2><a href="https://www.stellabusiness.com/pricing" target="_blank" rel="noopener noreferrer" class="glow-btn btn-secondary px-3 py-1 rounded-full text-sm no-underline" id="pricing-button" style="display: none;">Pricing</a></div>
                                <div class="space-y-5">
                                    <div>
                                        <div class="flex justify-between items-baseline mb-1"><p class="font-medium text-sm">Brand Kits</p><p class="text-lg font-bold" id="usage-kits-count">0 <span class="text-sm font-normal text-[var(--text-secondary)]">/ 1</span></p></div>
                                        <div class="progress-bar"><div id="usage-kits-progress" class="progress-bar-fill" style="width: 0%;"></div></div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between items-baseline mb-1"><p class="font-medium text-sm">Team Members</p><p class="text-lg font-bold" id="usage-members-count">1 <span class="text-sm font-normal text-[var(--text-secondary)]">/ 2</span></p></div>
                                        <div class="progress-bar"><div id="usage-members-progress" class="progress-bar-fill" style="width: 50%;"></div></div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between items-baseline mb-1"><p class="font-medium text-sm">Storage</p><p class="text-lg font-bold" id="usage-storage-count">0 MB <span class="text-sm font-normal text-[var(--text-secondary)]">/ 1 GB</span></p></div>
                                        <div class="progress-bar"><div id="usage-storage-progress" class="progress-bar-fill" style="width: 0%;"></div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                document.getElementById('brand-kits-page').innerHTML = `
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                         <h1 class="text-2xl font-bold text-gray-900 mb-4 md:mb-0 text-center md:text-left">Kits</h1>
                     </div>
                    <div id="brand-kits-overview" class="page-view active">
                         <div id="brand-kits-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                             <button class="glow-btn glass-card no-hover aspect-square rounded-2xl flex flex-col items-center justify-center border-2 border-dashed border-[var(--border-dark)] hover:border-primary transition-colors" data-modal-target="new-brand-kit-modal">
                                 <i class="fas fa-plus text-4xl text-[var(--text-secondary)] mb-4"></i>
                                 <h3 class="font-bold">Create New Kit</h3>
                             </button>
                         </div>
                    </div>
                    <div id="brand-kit-detail-view" class="page-view">
                        </div>
                `;
                document.getElementById('asset-hub-page').innerHTML = `
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-900 mb-4 md:mb-0 text-center md:text-left">Your Assets</h1>
                        <button data-modal-target="upload-asset-detail-modal" class="glow-btn btn-primary px-4 py-2 rounded-full flex items-center justify-center">
                            <i class="fas fa-upload mr-2"></i><span>Upload</span>
                        </button>
                    </div>
                    <div class="space-y-6">
                         <div class="glass-card p-6 rounded-2xl">
                             <div class="flex justify-end items-center mb-4 gap-4">
                                 <div class="flex-1 sm:flex-none">
                                     <select id="asset-category-filter" class="w-full text-sm bg-[#1a1a1a] border border-[var(--border-dark)] text-white rounded-full px-3 py-2 appearance-none cursor-pointer hover:border-primary transition-colors">
                                         <option value="all">All Categories</option>
                                         <option value="logos">Logos</option>
                                         <option value="marketing">Marketing</option>
                                         <option value="documents">Documents</option>
                                         <option value="other">Other</option>
                                     </select>
                                 </div>
                             </div>
                             <div id="asset-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"></div>
                             <div id="asset-empty-state" class="text-center py-10 text-[var(--text-secondary)]"><i class="fas fa-folder-open text-4xl mb-4"></i><p>No assets uploaded yet.</p></div>
                         </div>
                    </div>
                `;
                 // Initialize team settings page with basic content
                 document.getElementById('team-settings-page').innerHTML = `
                    <h1 class="text-2xl font-bold mb-6 text-gray-900 text-center md:text-left">Settings</h1>
                    <div class="space-y-6">
                        <div class="glass-card p-6 rounded-2xl">
                            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                                <h2 class="text-xl font-bold mb-4 md:mb-0 text-center md:text-left">Manage Members</h2>
                                <button class="glow-btn btn-primary px-3 md:px-4 py-1 md:py-2 rounded-full flex items-center text-sm md:text-base" data-modal-target="invite-member-modal" id="invite-member-btn" style="display: none;"><i class="fas fa-user-plus mr-2"></i> Invite Member</button>
                            </div>
                            <div class="table-container overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="border-b border-[var(--border-dark)]">
                                            <th class="p-3 text-sm font-medium text-[var(--text-secondary)]">Name</th>
                                            <th class="p-3 hidden sm:table-cell text-sm font-medium text-[var(--text-secondary)]">Email</th>
                                            <th class="p-3 text-sm font-medium text-[var(--text-secondary)]">Role</th>
                                            <th class="p-3 text-right text-sm font-medium text-[var(--text-secondary)]">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="team-members-tbody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="glass-card p-6 rounded-2xl" id="workspace-permissions-section" style="display: none;">
                            <h2 class="text-xl font-bold mb-6 text-center md:text-left">Workspace Permissions</h2>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center p-3 bg-white/5 rounded-3xl">
                                    <div class="flex-1 pr-4">
                                        <p class="font-medium">Team Members Can Upload</p>
                                        <p class="text-xs text-[var(--text-secondary)]">Allow team members to upload new assets</p>
                                    </div>
                                    <label class="switch"><input type="checkbox" checked><span class="slider"></span></label>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-white/5 rounded-3xl">
                                    <div class="flex-1 pr-4">
                                        <p class="font-medium">Team Members Can Create Kits</p>
                                        <p class="text-xs text-[var(--text-secondary)]">Allow team members to create new kits</p>
                                    </div>
                                    <label class="switch"><input type="checkbox" checked><span class="slider"></span></label>
                                </div>
                                 <div class="flex justify-between items-center p-3 bg-white/5 rounded-3xl">
                                    <div class="flex-1 pr-4">
                                        <p class="font-medium">Public Asset Sharing</p>
                                        <p class="text-xs text-[var(--text-secondary)]">Allow generating public share links for assets</p>
                                    </div>
                                    <label class="switch"><input type="checkbox" checked><span class="slider"></span></label>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-white/5 rounded-3xl">
                                    <div class="flex-1 pr-4">
                                        <p class="font-medium">Require Download Approval</p>
                                        <p class="text-xs text-[var(--text-secondary)]">Team members must request permission to download assets</p>
                                    </div>
                                    <label class="switch"><input type="checkbox"><span class="slider"></span></label>
                                </div>
                            </div>
                        </div>
                        <div class="glass-card p-6 rounded-2xl" id="download-requests-section" style="display: none;">
                            <h2 class="text-xl font-bold mb-6 text-center md:text-left">Download Requests</h2>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="border-b border-[var(--border-dark)]">
                                            <th class="p-3">Asset</th>
                                            <th class="p-3 hidden sm:table-cell">Requester</th>
                                            <th class="p-3">Status</th>
                                            <th class="p-3 hidden md:table-cell">Requested</th>
                                            <th class="p-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="download-requests-tbody">
                                        <tr>
                                            <td colspan="5" class="p-8 text-center text-[var(--text-secondary)]">
                                                <i class="fas fa-download text-4xl mb-4"></i>
                                                <p>No download requests yet</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
                 document.getElementById('governance-page').innerHTML = `
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 text-center md:text-left">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Content Governance</h1>
                            <p class="text-gray-600 mt-1">Define rules to ensure brand consistency (Optional)</p>
                        </div>
                        <button id="add-governance-rule-btn" class="glow-btn btn-primary px-4 py-2 rounded-full flex items-center mt-4 md:mt-0">
                            <i class="fas fa-plus mr-2"></i> Add Rule
                        </button>
                    </div>
                    <div class="space-y-6">
                        <div class="glass-card p-6 rounded-2xl">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h2 class="text-xl font-bold">Governance Rules</h2>
                                    <p class="text-sm text-[var(--text-secondary)] mt-1">Set up rules to validate content via API (optional for API usage)</p>
                                </div>
                            </div>
                            <div id="governance-rules-list" class="space-y-4">
                                <p class="text-center text-[var(--text-secondary)] py-8">No governance rules defined yet. Click "Add Rule" to create your first rule.</p>
                                        </div>
                                        </div>
                        
                        <div class="glass-card p-6 rounded-2xl">
                            <h2 class="text-xl font-bold mb-4 text-center md:text-left">Rule Types</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                                    <div class="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center mb-3">
                                        <i class="fas fa-ban text-primary"></i>
                                        </div>
                                    <h3 class="font-semibold mb-1">Forbidden Words</h3>
                                    <p class="text-sm text-[var(--text-secondary)]">Block specific words or phrases from content</p>
                                        </div>
                                <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                                    <div class="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center mb-3">
                                            <i class="fas fa-check-circle text-primary"></i>
                                    </div>
                                    <h3 class="font-semibold mb-1">Required Words</h3>
                                    <p class="text-sm text-[var(--text-secondary)]">Ensure specific terms are included</p>
                                </div>
                                <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                                    <div class="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center mb-3">
                                        <i class="fas fa-smile text-primary"></i>
                                        </div>
                                    <h3 class="font-semibold mb-1">Tone Check</h3>
                                    <p class="text-sm text-[var(--text-secondary)]">Maintain positive or professional tone</p>
                                        </div>
                                <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                                    <div class="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center mb-3">
                                        <i class="fas fa-ruler text-primary"></i>
                                    </div>
                                    <h3 class="font-semibold mb-1">Length Limits</h3>
                                    <p class="text-sm text-[var(--text-secondary)]">Set minimum and maximum content length</p>
                                </div>
                                <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                                    <div class="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center mb-3">
                                        <i class="fas fa-code text-primary"></i>
                                        </div>
                                    <h3 class="font-semibold mb-1">Pattern Matching</h3>
                                    <p class="text-sm text-[var(--text-secondary)]">Use regex for advanced validation</p>
                                        </div>
                                <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                                    <div class="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center mb-3">
                                        <i class="fas fa-exclamation-triangle text-primary"></i>
                                    </div>
                                    <h3 class="font-semibold mb-1">Severity Levels</h3>
                                    <p class="text-sm text-[var(--text-secondary)]">Set warnings or blocking errors</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="glass-card p-6 rounded-2xl" id="brand-guidelines-section" style="display: none;">
                            <h2 class="text-xl font-bold mb-6">Brand Guidelines</h2>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center p-4 bg-white/5 rounded-xl">
                                    <div class="flex-1">
                                        <p class="font-medium">Content Approval Workflow</p>
                                        <p class="text-xs text-[var(--text-secondary)] mt-1">Require approval before publishing content</p>
                                    </div>
                                    <label class="switch"><input type="checkbox"><span class="slider"></span></label>
                                </div>
                                <div class="flex justify-between items-center p-4 bg-white/5 rounded-xl">
                                    <div class="flex-1">
                                        <p class="font-medium">Enforce Governance on Upload</p>
                                        <p class="text-xs text-[var(--text-secondary)] mt-1">Run governance checks when uploading assets</p>
                                    </div>
                                    <label class="switch"><input type="checkbox"><span class="slider"></span></label>
                                </div>
                                <div class="flex justify-between items-center p-4 bg-white/5 rounded-xl">
                                    <div class="flex-1">
                                        <p class="font-medium">Asset Naming Convention</p>
                                        <p class="text-xs text-[var(--text-secondary)] mt-1">Require specific file naming format</p>
                                    </div>
                                    <label class="switch"><input type="checkbox"><span class="slider"></span></label>
                                </div>
                                <div class="flex justify-between items-center p-4 bg-white/5 rounded-xl">
                                    <div class="flex-1">
                                        <p class="font-medium">Audit Logging</p>
                                        <p class="text-xs text-[var(--text-secondary)] mt-1">Track all changes and access to assets</p>
                                    </div>
                                    <label class="switch"><input type="checkbox" checked><span class="slider"></span></label>
                                </div>
                                <div class="flex justify-between items-center p-4 bg-white/5 rounded-xl">
                                    <div class="flex-1">
                                        <p class="font-medium">Watermark Protected Assets</p>
                                        <p class="text-xs text-[var(--text-secondary)] mt-1">Add watermark to shared assets for protection</p>
                            </div>
                                    <label class="switch"><input type="checkbox"><span class="slider"></span></label>
                        </div>
                                <div class="flex justify-between items-center p-4 bg-white/5 rounded-xl">
                                    <div class="flex-1">
                                        <p class="font-medium">Expiring Share Links</p>
                                        <p class="text-xs text-[var(--text-secondary)] mt-1">Public share links expire after set period</p>
                                </div>
                                    <label class="switch"><input type="checkbox"><span class="slider"></span></label>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                 document.getElementById('api-page').innerHTML = `
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 text-center md:text-left">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">API Access</h1>
                            <p class="text-gray-600 mt-1">Integrate Stella into your workflows</p>
                        </div>
                        <div class="flex gap-3 mt-4 md:mt-0">
                            <button onclick="document.getElementById('api-key-modal').classList.add('active')" class="glow-btn btn-primary px-4 py-2 rounded-full flex items-center">
                                <i class="fas fa-plus mr-2"></i> Generate API Key
                            </button>
                        </div>
                                    </div>
                    <div class="space-y-6">
                        <div class="glass-card p-6 rounded-2xl">
                            <h2 class="text-xl font-bold mb-4">Base URL</h2>
                            <div class="bg-black/50 p-4 rounded-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <code class="text-green-400 text-sm sm:text-base break-all">${window.location.origin}</code>
                                <button onclick="navigator.clipboard.writeText('${window.location.origin}'); showToast('✓ URL copied!', 'success')" class="glow-btn btn-secondary px-3 py-1 rounded-lg text-sm whitespace-nowrap">
                                    <i class="fas fa-copy mr-1"></i>Copy
                                </button>
                        </div>
                    </div>
                    
                        <div class="glass-card p-6 rounded-2xl">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-xl font-bold">Your API Keys</h2>
                            </div>
                            <div id="api-keys-list" class="space-y-4">
                                <p class="text-center text-[var(--text-secondary)] py-8">Loading...</p>
                            </div>
                        </div>
                        
                        <div class="glass-card p-6 rounded-2xl">
                            <h2 class="text-xl font-bold mb-4 text-center md:text-left">Getting Started with Stella API</h2>
                            <p class="text-[var(--text-secondary)] mb-4 text-center md:text-left">
                                Use Stella's API to validate and store content from anywhere - whether it's a custom blog dashboard, 
                                content management system, or any other application. Governance rules are optional but powerful.
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                                    <div class="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center mb-3">
                                        <i class="fas fa-check-circle text-primary"></i>
                            </div>
                                    <h3 class="font-semibold mb-1">Validate Content</h3>
                                    <p class="text-sm text-[var(--text-secondary)]">Check content against your governance rules</p>
                        </div>
                                <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                                    <div class="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center mb-3">
                                        <i class="fas fa-save text-primary"></i>
                            </div>
                                    <h3 class="font-semibold mb-1">Store Content</h3>
                                    <p class="text-sm text-[var(--text-secondary)]">Save to brand kits or assets</p>
                        </div>
                                <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                                    <div class="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center mb-3">
                                        <i class="fas fa-shield-alt text-primary"></i>
                            </div>
                                    <h3 class="font-semibold mb-1">Optional Governance</h3>
                                    <p class="text-sm text-[var(--text-secondary)]">Use governance rules when needed</p>
                    </div>
                        </div>
                    </div>
                    
                        <div class="glass-card p-6 rounded-2xl">
                            <h2 class="text-xl font-bold mb-4">API Endpoints</h2>
                            <div class="space-y-4">
                                <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="font-semibold text-lg">Validate Content</h3>
                                        <span class="px-3 py-1 bg-purple-500/20 text-purple-400 rounded-full text-xs font-semibold">POST</span>
                                </div>
                                    <div class="bg-black/50 p-2 rounded mb-3 flex items-center justify-between">
                                        <code class="text-sm text-green-400">${window.location.origin}/api/api_content.php?action=validate</code>
                                        <button onclick="navigator.clipboard.writeText('${window.location.origin}/api/api_content.php?action=validate'); showToast('✓ Copied!', 'success')" class="text-primary hover:text-primary-light ml-2">
                                            <i class="fas fa-copy"></i>
                                        </button>
                            </div>
                                    <p class="text-sm text-[var(--text-secondary)] mb-3">Validate content against your governance rules without storing it.</p>
                                    <details class="text-sm">
                                        <summary class="cursor-pointer text-primary hover:text-primary-light font-semibold mb-2">Show Example</summary>
                                        <pre class="bg-black/50 p-4 rounded-lg overflow-x-auto text-xs"><code>{
  "content": "Your content to validate here..."
}</code></pre>
                                    </details>
                                </div>
                        
                                <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="font-semibold text-lg">Store Content</h3>
                                        <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-xs font-semibold">POST</span>
                                </div>
                                    <div class="bg-black/50 p-2 rounded mb-3 flex items-center justify-between">
                                        <code class="text-sm text-green-400">${window.location.origin}/api/api_content.php?action=store</code>
                                        <button onclick="navigator.clipboard.writeText('${window.location.origin}/api/api_content.php?action=store'); showToast('✓ Copied!', 'success')" class="text-primary hover:text-primary-light ml-2">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                </div>
                                    <p class="text-sm text-[var(--text-secondary)] mb-3">Store content as an asset or in a brand kit. Optionally validate first.</p>
                                    <details class="text-sm">
                                        <summary class="cursor-pointer text-primary hover:text-primary-light font-semibold mb-2">Show Example</summary>
                                        <pre class="bg-black/50 p-4 rounded-lg overflow-x-auto text-xs"><code>{
  "content": "Your content here...",
  "name": "Blog Post Title",
  "description": "Blog post description",
  "storage_type": "asset",
  "skip_validation": false
}

// To store in a brand kit:
{
  "content": "Your content here...",
  "name": "Brand Content",
  "storage_type": "brand_kit",
  "brand_kit_id": 123
}</code></pre>
                                    </details>
                                </div>
                        
                                <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="font-semibold text-lg">Validate & Store (Combined)</h3>
                                        <span class="px-3 py-1 bg-purple-500/20 text-purple-400 rounded-full text-xs font-semibold">POST</span>
                                </div>
                                    <div class="bg-black/50 p-2 rounded mb-3 flex items-center justify-between">
                                        <code class="text-sm text-green-400">${window.location.origin}/api/api_content.php?action=validate-and-store</code>
                                        <button onclick="navigator.clipboard.writeText('${window.location.origin}/api/api_content.php?action=validate-and-store'); showToast('✓ Copied!', 'success')" class="text-primary hover:text-primary-light ml-2">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                </div>
                                    <p class="text-sm text-[var(--text-secondary)] mb-3">Validate and store content in one API call. Only stores if validation passes.</p>
                                    <details class="text-sm">
                                        <summary class="cursor-pointer text-primary hover:text-primary-light font-semibold mb-2">Show Example</summary>
                                        <pre class="bg-black/50 p-4 rounded-lg overflow-x-auto text-xs"><code>{
  "content": "Your content here...",
  "name": "Validated Content",
  "description": "Optional description",
  "storage_type": "asset"
}</code></pre>
                                    </details>
                                </div>
                            </div>
                        </div>
                        
                        <div class="glass-card p-6 rounded-2xl">
                            <h2 class="text-xl font-bold mb-4">Governance Rule Management</h2>
                            <div class="space-y-4">
                                <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="font-semibold">Create Rule</h3>
                                        <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-xs font-semibold">POST</span>
                                        </div>
                                    <code class="text-sm text-[var(--text-secondary)]">/api/governance.php</code>
                                        </div>
                                <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="font-semibold">List Rules</h3>
                                        <span class="px-3 py-1 bg-purple-500/20 text-purple-400 rounded-full text-xs font-semibold">GET</span>
                                    </div>
                                    <code class="text-sm text-[var(--text-secondary)]">/api/governance.php?path=list</code>
                                </div>
                                <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="font-semibold">Update/Delete Rule</h3>
                                        <span class="px-3 py-1 bg-purple-500/20 text-purple-400 rounded-full text-xs font-semibold">PUT/DELETE</span>
                                        </div>
                                    <code class="text-sm text-[var(--text-secondary)]">/api/governance.php?id={rule_id}</code>
                                        </div>
                                    </div>
                                </div>

                        <div class="glass-card p-6 rounded-2xl">
                            <h2 class="text-xl font-bold mb-4">Authentication</h2>
                            <p class="text-[var(--text-secondary)] mb-4">
                                All API requests require authentication. Use your session cookies or pass authorization headers.
                            </p>
                            <div class="bg-black/50 p-4 rounded-lg">
                                <code class="text-xs text-green-400">
                                    // Using session (from same domain)<br/>
                                    fetch('/api/api_content.php?action=validate', {<br/>
                                    &nbsp;&nbsp;method: 'POST',<br/>
                                    &nbsp;&nbsp;credentials: 'include',<br/>
                                    &nbsp;&nbsp;body: JSON.stringify({ content: '...' })<br/>
                                    })
                                </code>
                                        </div>
                                        </div>
                        
                        <div class="glass-card p-6 rounded-2xl">
                            <h2 class="text-xl font-bold mb-4">Use Cases</h2>
                            <div class="space-y-3">
                                <div class="p-4 bg-white/5 rounded-xl">
                                    <h3 class="font-semibold mb-2"><i class="fas fa-pen text-primary mr-2"></i>Blog Writing Dashboard</h3>
                                    <p class="text-sm text-[var(--text-secondary)]">Validate blog posts ensure they match your brand's tone and don't contain negative words before publishing.</p>
                                    </div>
                                <div class="p-4 bg-white/5 rounded-xl">
                                    <h3 class="font-semibold mb-2"><i class="fas fa-envelope text-primary mr-2"></i>Email Campaign Tool</h3>
                                    <p class="text-sm text-[var(--text-secondary)]">Check email content for compliance and brand consistency before sending.</p>
                                </div>
                                <div class="p-4 bg-white/5 rounded-xl">
                                    <h3 class="font-semibold mb-2"><i class="fas fa-share-alt text-primary mr-2"></i>Social Media Manager</h3>
                                    <p class="text-sm text-[var(--text-secondary)]">Ensure social media posts meet character limits and brand guidelines.</p>
                                        </div>
                                <div class="p-4 bg-white/5 rounded-xl">
                                    <h3 class="font-semibold mb-2"><i class="fas fa-robot text-primary mr-2"></i>Content Generation Tools</h3>
                                    <p class="text-sm text-[var(--text-secondary)]">Store generated content directly into your Stella account for team access.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                `;
                 document.getElementById('analytics-page').innerHTML = `
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 text-center md:text-left">
                                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Team Analytics</h1>
                            <p class="text-gray-600 mt-1">Monitor team activity and asset performance</p>
                                        </div>
                        <div class="flex items-center space-x-2 mt-4 md:mt-0 mx-auto md:mx-0">
                            <select id="analytics-period" onchange="loadTeamAnalytics()" class="text-sm bg-[#1a1a1a] border border-[var(--border-dark)] text-white rounded-full px-4 py-2 appearance-none cursor-pointer hover:border-primary transition-colors">
                                <option value="7">Last 7 Days</option>
                                <option value="30">Last 30 Days</option>
                                <option value="all">All Time</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        <div class="glass-card p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="stat-icon text-2xl w-12 h-12"><i class="fas fa-users"></i></div>
                                        </div>
                            <p class="text-3xl font-bold" id="stat-total-members">${teamMembers.length + 1}</p>
                            <p class="text-sm text-[var(--text-secondary)] mt-1">Total Team Members</p>
                                        </div>
                        
                        <div class="glass-card p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="stat-icon text-2xl w-12 h-12"><i class="fas fa-chart-line"></i></div>
                                    </div>
                            <p class="text-3xl font-bold" id="stat-total-activities">${activities.length}</p>
                            <p class="text-sm text-[var(--text-secondary)] mt-1">Total Activities</p>
                                    </div>
                        
                        <div class="glass-card p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="stat-icon text-2xl w-12 h-12"><i class="fas fa-download"></i></div>
                                </div>
                            <p class="text-3xl font-bold" id="stat-total-downloads">0</p>
                            <p class="text-sm text-[var(--text-secondary)] mt-1">Asset Downloads</p>
                                        </div>
                    
                        <div class="glass-card p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="stat-icon text-2xl w-12 h-12"><i class="fas fa-share-alt"></i></div>
                                        </div>
                            <p class="text-3xl font-bold" id="stat-total-shares">0</p>
                            <p class="text-sm text-[var(--text-secondary)] mt-1">Public Shares</p>
                                    </div>
                                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <div class="lg:col-span-2 glass-card p-6">
                            <div class="flex justify-between items-center mb-6">
                                <h2 class="text-xl font-bold">Team Activity Graph</h2>
                                <div class="flex items-center gap-2 text-xs">
                                    <button class="px-2 py-1 rounded border border-white/10 hover:bg-white/5" data-activity-days="7">7d</button>
                                    <button class="px-2 py-1 rounded border border-white/10 hover:bg-white/5" data-activity-days="14">14d</button>
                                    <button class="px-2 py-1 rounded border border-white/10 hover:bg-white/5" data-activity-days="30">30d</button>
                                </div>
                            </div>
                            <div style="height: 300px; max-height: 300px; position: relative;">
                                <canvas id="activity-chart"></canvas>
                            </div>
                            <div id="activity-chart-status" class="text-xs text-[var(--text-secondary)] mt-2"></div>
                        </div>
                        
                        <div class="glass-card p-6">
                            <h2 class="text-xl font-bold mb-6">Most Active Members</h2>
                            <div id="top-members-list" class="space-y-4">
                                <p class="text-center text-[var(--text-secondary)] py-4 text-sm">No activity data yet</p>
                                        </div>
                                    </div>
                                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="glass-card p-6">
                            <h2 class="text-xl font-bold mb-6">Recent Team Actions</h2>
                            <div id="analytics-recent-activities" class="space-y-3">
                                <p class="text-center text-[var(--text-secondary)] py-4 text-sm">No recent activity</p>
                            </div>
                        </div>
                        
                        <div class="glass-card p-6">
                            <h2 class="text-xl font-bold mb-6">Asset Breakdown</h2>
                            <div id="asset-breakdown" class="space-y-4">
                                <div>
                                    <div class="flex justify-between mb-2">
                                        <span class="text-sm font-medium">Total Assets</span>
                                        <span class="text-sm font-bold" id="asset-total">0</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-bar-fill" style="width: 100%; background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between mb-2">
                                        <span class="text-sm font-medium">In Kits</span>
                                        <span class="text-sm font-bold" id="asset-in-kits">0</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div id="asset-in-kits-bar" class="progress-bar-fill" style="width: 0%;"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between mb-2">
                                        <span class="text-sm font-medium">Standalone</span>
                                        <span class="text-sm font-bold" id="asset-standalone">0</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div id="asset-standalone-bar" class="progress-bar-fill" style="width: 0%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                 document.getElementById('billing-page').innerHTML = `
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 text-center md:text-left">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Billing & Usage</h1>
                            <p class="text-gray-600 mt-1">Manage your subscription and monitor usage</p>
                        </div>
                    </div>
                    
                    <!-- Current Plan & Usage -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <div class="lg:col-span-2 glass-card p-6 rounded-2xl">
                            <h2 class="text-xl font-bold mb-4">Current Plan</h2>
                            <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl border border-[var(--border-dark)]">
                                <div>
                                    <h3 class="text-lg font-semibold" id="current-plan-name">Free Trial</h3>
                                    <p class="text-sm text-[var(--text-secondary)]" id="current-plan-description">1 kit, 2 users, 1GB storage</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold" id="current-plan-price">$0</p>
                                    <p class="text-xs text-[var(--text-secondary)]">per month</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="glass-card p-6 rounded-2xl">
                            <h2 class="text-xl font-bold mb-4">Usage This Month</h2>
                            <div class="space-y-4">
                                <div>
                                    <div class="flex justify-between mb-2">
                                        <span class="text-sm font-medium">Storage Used</span>
                                        <span class="text-sm font-bold" id="storage-used">0 GB</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div id="storage-progress" class="progress-bar-fill" style="width: 0%; background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);"></div>
                                    </div>
                                    <p class="text-xs text-[var(--text-secondary)] mt-1" id="storage-limit">of 1 GB used</p>
                                </div>
                                <div>
                                    <div class="flex justify-between mb-2">
                                        <span class="text-sm font-medium">Brand Kits</span>
                                        <span class="text-sm font-bold" id="kits-used">0</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div id="kits-progress" class="progress-bar-fill" style="width: 0%; background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);"></div>
                                    </div>
                                    <p class="text-xs text-[var(--text-secondary)] mt-1" id="kits-limit">of 1 kit used</p>
                                </div>
                                <div>
                                    <div class="flex justify-between mb-2">
                                        <span class="text-sm font-medium">Team Members</span>
                                        <span class="text-sm font-bold" id="users-used">1</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div id="users-progress" class="progress-bar-fill" style="width: 0%; background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);"></div>
                                    </div>
                                    <p class="text-xs text-[var(--text-secondary)] mt-1" id="users-limit">of 2 users used</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pricing Plans -->
                    <div class="glass-card p-6 rounded-2xl mb-6">
                        <h2 class="text-xl font-bold mb-6 text-center md:text-left">Choose Your Plan</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Free Trial Plan -->
                            <div class="p-6 bg-white/5 rounded-xl border border-[var(--border-dark)] relative">
                                <div class="text-center mb-6">
                                    <h3 class="text-xl font-bold mb-2">Free Trial</h3>
                                    <div class="text-3xl font-bold mb-2">$0</div>
                                    <p class="text-sm text-[var(--text-secondary)]">No credit card required</p>
                                </div>
                                <ul class="space-y-3 mb-6">
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-400 mr-3"></i>
                                        <span class="text-sm">1 Brand Kit</span>
                                    </li>
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-400 mr-3"></i>
                                        <span class="text-sm">2 Team Members</span>
                                    </li>
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-400 mr-3"></i>
                                        <span class="text-sm">1GB Storage</span>
                                    </li>
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-400 mr-3"></i>
                                        <span class="text-sm">Standard Support</span>
                                    </li>
                                </ul>
                                <button id="current-plan-btn" class="w-full py-2 px-4 rounded-lg border border-[var(--border-dark)] text-[var(--text-secondary)] cursor-not-allowed" disabled>
                                    Current Plan
                                </button>
                            </div>
                            
                            <!-- Pro Plan -->
                            <div class="p-6 bg-gradient-to-br from-primary/10 to-primary/5 rounded-xl border-2 border-primary/30 relative">
                                <div class="absolute top-4 right-4">
                                    <span class="bg-primary text-white text-xs font-bold px-2 py-1 rounded-full">POPULAR</span>
                                </div>
                                <div class="text-center mb-6">
                                    <h3 class="text-xl font-bold mb-2">Pro</h3>
                                    <div class="text-3xl font-bold mb-2">$15</div>
                                    <p class="text-sm text-[var(--text-secondary)]">per month</p>
                                </div>
                                <ul class="space-y-3 mb-6">
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-400 mr-3"></i>
                                        <span class="text-sm">Unlimited Brand Kits</span>
                                    </li>
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-400 mr-3"></i>
                                        <span class="text-sm">Unlimited Team Members</span>
                                    </li>
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-400 mr-3"></i>
                                        <span class="text-sm">Unlimited Storage</span>
                                    </li>
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-400 mr-3"></i>
                                        <span class="text-sm">Priority Support</span>
                                    </li>
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-400 mr-3"></i>
                                        <span class="text-sm">Advanced Analytics</span>
                                    </li>
                                </ul>
                                <button id="upgrade-to-pro-btn" class="w-full glow-btn btn-primary py-2 px-4 rounded-lg">
                                    Upgrade to Pro
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Billing History -->
                    <div class="glass-card p-6 rounded-2xl">
                        <h2 class="text-xl font-bold mb-4">Billing History</h2>
                        <div id="billing-history" class="space-y-3">
                            <div class="text-center text-[var(--text-secondary)] py-8">
                                <i class="fas fa-receipt text-4xl mb-4 opacity-50"></i>
                                <p>No billing history yet</p>
                            </div>
                        </div>
                    </div>
                `;
                 document.getElementById('support-page').innerHTML = `
                    <h1 class="text-2xl font-bold mb-6 text-gray-900 text-center md:text-left">Get Help</h1>
                    <div class="glass-card p-6 rounded-2xl">
                        <h2 class="text-xl font-bold">Contact Support</h2>
                        <p class="text-[var(--text-secondary)] mt-2 mb-4">Need help with something? Send us a message and we'll get back to you as soon as possible.</p>
                        <div class="text-center bg-white/5 p-3 rounded-xl mb-6 text-sm">
                            <p><i class="fas fa-info-circle text-primary mr-2"></i>Our team typically responds within <span class="font-bold text-white">24 hours</span>.</p>
                        </div>
                        <form id="support-form" class="space-y-4" onsubmit="event.preventDefault();">
                            <div>
                                <label for="support-email" class="block text-sm font-medium mb-1">Your Email</label>
                                <input type="email" id="support-email" required class="w-full rounded-lg px-4 py-2">
                            </div>
                            <div>
                                <label for="support-subject" class="block text-sm font-medium mb-1">Subject</label>
                                <input type="text" id="support-subject" required class="w-full rounded-lg px-4 py-2">
                            </div>
                            <div>
                                <label for="support-message" class="block text-sm font-medium mb-1">Message</label>
                                <textarea id="support-message" rows="5" required class="w-full rounded-lg px-4 py-2"></textarea>
                            </div>
                            <div class="text-right"><button type="submit" class="glow-btn btn-primary px-6 py-2 rounded-full">Send Message</button></div>
                        </form>
                    </div>
                `;
            }

            // --- UI MODULES ---
            
            // Check if user has effective Pro access (own plan or inherited from Pro owner)
            function hasEffectiveProAccess() {
                const currentUser = JSON.parse(sessionStorage.getItem('stella_user') || '{}');
                const userPlan = currentUser.plan_type || 'free';
                
                // If user is Pro, they have access
                if (userPlan === 'pro') {
                    return true;
                }
                
                // Check if user is a team member (has workspace_owner_id)
                // Team members inherit Pro benefits from their owner
                if (currentUser.workspace_owner_id && currentUser.owner_plan === 'pro') {
                    return true;
                }
                
                return false;
            }
            
            // Update Pro badge visibility based on effective Pro access
            function updateProBadgeVisibility() {
                const hasPro = hasEffectiveProAccess();
                const proBadges = document.querySelectorAll('.pro-badge');
                
                // Hide PRO badges if user has Pro access (own or inherited)
                proBadges.forEach(badge => {
                    if (hasPro) {
                        badge.style.display = 'none';
                    } else {
                        badge.style.display = '';
                    }
                });
            }

            function setupNavigation() {
                const navItems = document.querySelectorAll('.nav-item');
                const pages = document.querySelectorAll('.page');
                const pageTitle = document.getElementById('page-title');
                
                navItems.forEach(item => {
                    item.addEventListener('click', () => {
                        const pageId = item.getAttribute('data-page');
                        const requiresPro = item.getAttribute('data-requires-pro') === 'true';
                        
                        // Check if page requires Pro and user doesn't have access
                        if (requiresPro && !hasEffectiveProAccess()) {
                            // Show upgrade prompt instead of navigating
                            showProUpgradePrompt(item.querySelector('span')?.textContent || 'this feature');
                            return;
                        }
                        
                        // Update nav active state for all navs (desktop and mobile)
                        navItems.forEach(nav => {
                            if (nav.getAttribute('data-page') === pageId) {
                                nav.classList.add('active-nav');
                            } else {
                                nav.classList.remove('active-nav');
                            }
                        });
                        
                        // Switch active page
                        pages.forEach(page => {
                            page.classList.toggle('active', page.id === `${pageId}-page`);
                        });
                        
                        pageTitle.textContent = item.querySelector('span')?.textContent || '';

                        // Load page-specific data
                        if (pageId === 'governance') {
                            loadGovernanceRules();
                        } else if (pageId === 'api') {
                            loadApiKeys();
                        } else if (pageId === 'analytics') {
                            loadAnalyticsData();
                        } else if (pageId === 'team-settings') {
                            loadTeamMembersFromAPI();
                            loadPendingInvitesFromAPI();
                            updateNavigationPermissions();
                        }

                        // Close mobile sidebar on navigation
                        document.getElementById('mobile-sidebar').classList.add('hidden');
                    });
                });
            }

            function setupMobileMenu() {
                const openBtn = document.getElementById('mobile-menu-button');
                const closeBtn = document.getElementById('close-mobile-menu');
                const sidebar = document.getElementById('mobile-sidebar');

                openBtn.addEventListener('click', () => sidebar.classList.remove('hidden'));
                closeBtn.addEventListener('click', () => sidebar.classList.add('hidden'));
                sidebar.addEventListener('click', (e) => {
                    if (e.target === sidebar) sidebar.classList.add('hidden');
                });
            }
            
            function setupModals() {
                const modals = document.querySelectorAll('.modal');
                
                // Open modals via data-modal-target
                document.body.addEventListener('click', (e) => {
                    const targetButton = e.target.closest('[data-modal-target]');
                    if (targetButton) {
                        const modalId = targetButton.dataset.modalTarget;
                        const modal = document.getElementById(modalId);
                        if(modal) {
                            modal.classList.add('active');

                            // Pre-fill kit dropdown if uploading from kit detail view
                            if (modalId === 'upload-asset-detail-modal') {
                                const kitDropdown = modal.querySelector('#asset-upload-kit');
                                populateBrandKitDropdowns(kitDropdown);
                                
                                const kitId = targetButton.dataset.kitId;
                                if (kitId) {
                                    kitDropdown.value = kitId;
                                    kitDropdown.disabled = true;
                                } else {
                                    kitDropdown.disabled = false;
                                    kitDropdown.value = '';
                                }
                            }
                        }
                    }
                });


                // Close modals
                modals.forEach(modal => {
                    modal.addEventListener('click', e => {
                        if (e.target === modal || e.target.closest('.modal-close')) {
                            modal.classList.remove('active');
                        }
                    });
                });

                // Enhanced Invite Member Modal Logic
                const sendInviteBtn = document.getElementById('send-invite-btn');
                    const emailInput = document.getElementById('invite-email');
                    const roleSelect = document.getElementById('invite-role');
                const messageInput = document.getElementById('invite-message');
                const emailValidation = document.getElementById('email-validation');

                // Real-time email validation
                emailInput.addEventListener('input', () => {
                    validateEmailInput();
                });

                sendInviteBtn.addEventListener('click', async () => {
                    if (!validateInviteForm()) {
                        return;
                    }

                    const email = emailInput.value.trim().toLowerCase();
                        const role = roleSelect.value;
                    const message = messageInput.value.trim();
                        const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                    
                    if (!currentUser) {
                        showToast('Please log in to send invitations', 'error');
                        return;
                    }
                        
                        // Check if already invited or already a member
                    if (pendingInvites.some(inv => inv.email.toLowerCase() === email)) {
                        showToast('This user has already been invited.', 'warning');
                            return;
                        }
                        
                    if (teamMembers.some(mem => mem.email.toLowerCase() === email)) {
                        showToast('This user is already a team member.', 'warning');
                        return;
                    }

                    // Check if trying to invite self
                    if (email === currentUser.email.toLowerCase()) {
                        showToast('You cannot invite yourself to the team.', 'warning');
                            return;
                        }
                        
                    // Check user limit before sending invite
                    if (!checkUsageLimit('users')) {
                        showToast('You\'ve reached your user limit. Upgrade to Pro for more team members!', 'error');
                        return;
                    }
                        
                        // Disable button while sending
                        sendInviteBtn.disabled = true;
                    sendInviteBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';
                        
                        try {
                            // Generate unique invite token
                            const inviteToken = Array.from({length: 32}, () => 
                                Math.random().toString(36)[2] || '0'
                            ).join('');
                            
                            // Save invite to database
                            const response = await fetch('/api/team.php?action=create-invite', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                'X-User-ID': currentUser.id,
                                'X-User-Email': currentUser.email
                                },
                                body: JSON.stringify({
                                    email: email,
                                    token: inviteToken,
                                role: role,
                                message: message
                                })
                            });
                            
                            const data = await response.json();
                            
                            if (!data.success) {
                                throw new Error(data.message || 'Failed to create invite');
                            }
                            
                            // Send invitation email via EmailJS with token in URL
                            const inviteUrl = `${window.location.origin}/invite-accept.html?token=${inviteToken}`;
                            
                            await emailjs.send(
                                'service_7cdagjc', // Service ID
                                'template_8k9lg7d', // Team Invite Template ID
                                {
                                    to_name: email.split('@')[0], // Name from email
                                    user_email: email, // Try user_email
                                    to_email: email,   // Also send as to_email
                                    reply_to: email,   // And reply_to
                                    Greeting: `You've been invited to join ${currentUser.full_name}'s Stella workspace!`,
                                Message: message || `${currentUser.full_name} (${currentUser.email}) has invited you to collaborate on their brand assets and files.`,
                                    ActionURL: inviteUrl,
                                from_name: currentUser.full_name,
                                role: role
                                }
                            );
                            
                            // Add to pending invites UI
                            pendingInvites.push({
                                email: email,
                            role: role,
                            invitedAt: new Date().toISOString(),
                            invitedBy: currentUser.full_name
                        });
                        
                        // Save to local storage
                        ls.set(getUserDataKey('stella_pendingInvites'), pendingInvites);
                        
                        // Update UI
                        renderTeamMembers();
                        renderTeamOverview();
                        
                        // Update usage tracking
                        updateUsage('users');
                        
                        logActivity('fa-user-plus', 'Invited team member', `Invited ${email} as ${role}`, new Date().toLocaleTimeString());
                            
                            if (!gettingStartedState.teamInvited) {
                                gettingStartedState.teamInvited = true;
                                ls.set(getUserDataKey('stella_getting_started'), gettingStartedState);
                                renderGettingStarted();
                                showToast('✅ Perfect! You invited a team member!', 'success');
                        } else {
                            showToast(`Invitation sent to ${email} successfully!`, 'success');
                            }

                        // Clear form and close modal
                        clearInviteForm();
                            document.getElementById('invite-member-modal').classList.remove('active');
                        
                        } catch (error) {
                        console.error('Error sending invite:', error);
                        showToast(`Failed to send invitation: ${error.message}`, 'error');
                        } finally {
                        // Re-enable button
                            sendInviteBtn.disabled = false;
                        sendInviteBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Send Invite';
                    }
                });

                // Enhanced validation functions
                function validateEmailInput() {
                    const email = emailInput.value.trim();
                    const validationDiv = emailValidation;
                    
                    if (!email) {
                        validationDiv.classList.add('hidden');
                        return true;
                    }
                    
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    const isValid = emailRegex.test(email);
                    
                    if (!isValid) {
                        validationDiv.innerHTML = '<i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>Please enter a valid email address';
                        validationDiv.className = 'text-xs mt-1 text-yellow-500';
                        validationDiv.classList.remove('hidden');
                        return false;
                    }
                    
                    // Check if already invited
                    if (pendingInvites.some(inv => inv.email.toLowerCase() === email.toLowerCase())) {
                        validationDiv.innerHTML = '<i class="fas fa-info-circle text-green-500 mr-1"></i>This user has already been invited';
                        validationDiv.className = 'text-xs mt-1 text-green-500';
                        validationDiv.classList.remove('hidden');
                        return false;
                    }
                    
                    // Check if already a member
                    if (teamMembers.some(mem => mem.email.toLowerCase() === email.toLowerCase())) {
                        validationDiv.innerHTML = '<i class="fas fa-user-check text-green-500 mr-1"></i>This user is already a team member';
                        validationDiv.className = 'text-xs mt-1 text-green-500';
                        validationDiv.classList.remove('hidden');
                        return false;
                    }
                    
                    validationDiv.innerHTML = '<i class="fas fa-check-circle text-green-500 mr-1"></i>Email looks good!';
                    validationDiv.className = 'text-xs mt-1 text-green-500';
                    validationDiv.classList.remove('hidden');
                    return true;
                }

                function validateInviteForm() {
                    const email = emailInput.value.trim();
                    const role = roleSelect.value;
                    
                    if (!email) {
                        showToast('Please enter an email address', 'error');
                        emailInput.focus();
                        return false;
                    }
                    
                    if (!emailInput.checkValidity()) {
                        showToast('Please enter a valid email address', 'error');
                        emailInput.focus();
                        return false;
                    }
                    
                    if (!role) {
                        showToast('Please select a role for the team member', 'error');
                        roleSelect.focus();
                        return false;
                    }
                    
                    return true;
                }

                function clearInviteForm() {
                    emailInput.value = '';
                    roleSelect.value = 'Team Member';
                    messageInput.value = '';
                    emailValidation.classList.add('hidden');
                }

                 // Support Form Modal Logic
                const supportForm = document.getElementById('support-form');
                supportForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                     if(supportForm.checkValidity()) {
                        const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                        if (!currentUser) {
                            showToast('Please log in to submit a support ticket', 'error');
                            return;
                        }
                        
                        const formData = {
                            subject: document.getElementById('support-subject').value,
                            message: document.getElementById('support-message').value,
                            email: document.getElementById('support-email').value,
                            priority: 'medium'
                        };
                        
                        try {
                            const response = await fetch('/api/tickets.php?action=create', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-User-ID': currentUser.id,
                                    'X-User-Email': currentUser.email
                                },
                                body: JSON.stringify(formData)
                            });
                            
                            const data = await response.json();
                            
                            if (data.success) {
                                showConfirmationModal("Ticket Created", `Thank you! Your support ticket #${data.ticket_id} has been created. We'll get back to you shortly.`);
                         supportForm.reset();
                            } else {
                                showToast('Failed to create ticket: ' + data.message, 'error');
                            }
                        } catch (error) {
                            console.error('Error creating ticket:', error);
                            showToast('Failed to create ticket. Please try again.', 'error');
                        }
                    }
                });
                
                // Governance Rule Management
                const addRuleBtn = document.getElementById('add-governance-rule-btn');
                if (addRuleBtn) {
                    addRuleBtn.addEventListener('click', () => {
                        document.getElementById('governance-rule-modal').classList.add('active');
                    });
                }
                
                const createRuleBtn = document.getElementById('create-rule-btn');
                if (createRuleBtn) {
                    createRuleBtn.addEventListener('click', async () => {
                        const name = document.getElementById('rule-name').value;
                        const description = document.getElementById('rule-description').value;
                        const ruleType = document.getElementById('rule-type').value;
                        const ruleValue = document.getElementById('rule-value').value;
                        const severity = document.getElementById('rule-severity').value;
                        
                        if (!name || !ruleValue) {
                            showToast('Please fill in all required fields', 'error');
                            return;
                        }
                        
                        createRuleBtn.disabled = true;
                        createRuleBtn.textContent = 'Creating...';
                        
                        try {
                            // Format rule_value based on type
                            let formattedValue = ruleValue;
                            if (ruleType === 'forbidden_words' || ruleType === 'required_words') {
                                formattedValue = JSON.stringify(ruleValue.split(',').map(w => w.trim()));
                            } else if (ruleType === 'tone') {
                                const words = ruleValue.split(',').map(w => w.trim());
                                formattedValue = JSON.stringify({type: 'positive', avoid: words});
                            }
                            
                            const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                            const response = await fetch('/api/governance.php', {
                                method: 'POST',
                                credentials: 'include',
                                headers: { 
                                    'Content-Type': 'application/json',
                                    'X-User-ID': currentUser.id
                                },
                                body: JSON.stringify({
                                    name,
                                    description,
                                    rule_type: ruleType,
                                    rule_value: formattedValue,
                                    severity,
                                    enabled: true
                                })
                            });
                            
                            const data = await response.json();
                            
                            if (data.success) {
                                showToast('✓ Governance rule created successfully!', 'success');
                                document.getElementById('governance-rule-modal').classList.remove('active');
                                document.getElementById('rule-name').value = '';
                                document.getElementById('rule-description').value = '';
                                document.getElementById('rule-value').value = '';
                                loadGovernanceRules();
                            } else {
                                showToast('Failed to create rule: ' + data.message, 'error');
                            }
                        } catch (error) {
                            console.error('Error creating rule:', error);
                            showToast('Failed to create rule. Please try again.', 'error');
                        } finally {
                            createRuleBtn.disabled = false;
                            createRuleBtn.textContent = 'Create Rule';
                        }
                    });
                }
                
                // Update rule value hint based on rule type
                const ruleTypeSelect = document.getElementById('rule-type');
                if (ruleTypeSelect) {
                    ruleTypeSelect.addEventListener('change', () => {
                        const hint = document.getElementById('rule-value-hint');
                        const ruleType = ruleTypeSelect.value;
                        
                        switch (ruleType) {
                            case 'forbidden_words':
                                hint.textContent = 'Enter comma-separated words (e.g., bad, terrible, awful)';
                                break;
                            case 'required_words':
                                hint.textContent = 'Enter comma-separated words that must be present';
                                break;
                            case 'tone':
                                hint.textContent = 'Enter negative words to avoid (e.g., hate, terrible, awful)';
                                break;
                            case 'max_length':
                                hint.textContent = 'Enter maximum character count (e.g., 280 for Twitter)';
                                break;
                            case 'min_length':
                                hint.textContent = 'Enter minimum character count (e.g., 100)';
                                break;
                            case 'regex_pattern':
                                hint.textContent = 'Enter a regex pattern (e.g., /^[A-Z].*/)';
                                break;
                        }
                    });
                }
                
                // API Key Management
                const createApiKeyBtn = document.getElementById('create-api-key-btn');
                if (createApiKeyBtn) {
                    createApiKeyBtn.addEventListener('click', async () => {
                        const name = document.getElementById('api-key-name').value;
                        
                        if (!name) {
                            showToast('Please enter a key name', 'error');
                            return;
                        }
                        
                        createApiKeyBtn.disabled = true;
                        createApiKeyBtn.textContent = 'Generating...';
                        
                        try {
                            const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                            const response = await fetch('/api/api_keys.php', {
                                method: 'POST',
                                credentials: 'include',
                                headers: { 
                                    'Content-Type': 'application/json',
                                    'X-User-ID': currentUser.id,
                                    'X-User-Email': currentUser.email
                                },
                                body: JSON.stringify({ name })
                            });
                            
                            const data = await response.json();
                            
                            if (data.success) {
                                document.getElementById('api-key-modal').classList.remove('active');
                                document.getElementById('new-api-key-value').value = data.key.api_key;
                                document.getElementById('api-key-display-modal').classList.add('active');
                                document.getElementById('api-key-name').value = '';
                                loadApiKeys();
                            } else {
                                showToast('Failed to create API key: ' + data.message, 'error');
                            }
                        } catch (error) {
                            console.error('Error creating API key:', error);
                            showToast('Failed to create API key. Please try again.', 'error');
                        } finally {
                            createApiKeyBtn.disabled = false;
                            createApiKeyBtn.textContent = 'Generate Key';
                        }
                    });
                }
            }
            
            // Copy API key to clipboard
            window.copyApiKey = function() {
                const input = document.getElementById('new-api-key-value');
                input.select();
                document.execCommand('copy');
                showToast('✓ API key copied to clipboard!', 'success');
            }
            
            // Load governance rules
            async function loadGovernanceRules() {
                try {
                    const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                    if (!currentUser) return;
                    
                    const response = await fetch('/api/governance.php?path=list', {
                        credentials: 'include',
                        headers: {
                            'X-User-ID': currentUser.id,
                            'X-User-Email': currentUser.email
                        }
                    });
                    
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        console.warn('Governance API returned non-JSON response. Tables may not exist yet.');
                        const rulesList = document.getElementById('governance-rules-list');
                        if (rulesList) {
                            rulesList.innerHTML = '<div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4 text-center"><p class="text-yellow-500"><strong>⚠️ Database Setup Required</strong></p><p class="text-sm text-[var(--text-secondary)] mt-2">Run the database migration to enable governance features.</p><p class="text-sm mt-2"><a href="/api/check.php" target="_blank" style="color: var(--primary);">Check System Status →</a></p></div>';
                        }
                        return;
                    }
                    
                    const data = await response.json();
                    
                    // Check if Pro plan is required
                    if (response.status === 403 && data.requires_pro) {
                        const rulesList = document.getElementById('governance-rules-list');
                        if (rulesList) {
                            rulesList.innerHTML = `
                                <div class="border rounded-xl p-8 text-center" style="background-color: rgba(156, 122, 173, 0.1); border-color: rgba(156, 122, 173, 0.3);">
                                    <div class="text-5xl mb-4" style="color: #9c7ead;">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                    <h3 class="text-xl font-bold mb-2">Governance is a Pro Feature</h3>
                                    <p class="text-[var(--text-secondary)] mb-4">
                                        Upgrade to Pro to create brand governance rules and maintain consistent messaging.
                                    </p>
                                    <button onclick="showProUpgradePrompt('Governance')" class="btn btn-primary px-6 py-3 rounded-full font-semibold transition-all" style="background-color: #9c7ead; border-color: #9c7ead;" onmouseover="this.style.backgroundColor='#8b6c9c'" onmouseout="this.style.backgroundColor='#9c7ead'">
                                        <i class="fas fa-rocket mr-2"></i>Upgrade to Pro
                                    </button>
                                </div>
                            `;
                        }
                        return;
                    }
                    
                    if (data.success && data.rules) {
                        const rulesList = document.getElementById('governance-rules-list');
                        if (rulesList) {
                            if (data.rules.length === 0) {
                                rulesList.innerHTML = '<p class="text-center text-[var(--text-secondary)] py-8">No governance rules defined yet. Click "Add Rule" to create your first rule.</p>';
                            } else {
                                rulesList.innerHTML = data.rules.map(rule => `
                                    <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)] flex items-center justify-between">
                                        <div class="flex-1">
                                            <h3 class="font-semibold">${rule.name}</h3>
                                            <p class="text-sm text-[var(--text-secondary)] mt-1">${rule.description || ''}</p>
                                            <div class="flex items-center gap-3 mt-2 text-xs">
                                                <span class="px-2 py-1 bg-primary/20 text-primary rounded">${rule.rule_type}</span>
                                                <span class="px-2 py-1 bg-${rule.severity === 'error' ? 'red' : 'yellow'}-500/20 text-${rule.severity === 'error' ? 'red' : 'yellow'}-400 rounded">${rule.severity}</span>
                                                <span class="text-[var(--text-secondary)]">Created ${new Date(rule.created_at).toLocaleDateString()}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <label class="switch">
                                                <input type="checkbox" ${rule.enabled ? 'checked' : ''} onchange="toggleRule(${rule.id}, this.checked)">
                                                <span class="slider"></span>
                                            </label>
                                            <button onclick="deleteRule(${rule.id})" class="text-[#9c7ead] hover:text-red-500 px-2">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                `).join('');
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error loading governance rules:', error);
                    const rulesList = document.getElementById('governance-rules-list');
                    if (rulesList) {
                        rulesList.innerHTML = '<div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4 text-center"><p class="text-yellow-500"><strong>⚠️ Setup Required</strong></p><p class="text-sm text-[var(--text-secondary)] mt-2">Run database migration to enable this feature.</p><p class="text-sm mt-2"><a href="/api/check.php" target="_blank" style="color: var(--primary);">Diagnose Issue →</a></p></div>';
                    }
                }
            }
            
            // Toggle rule enabled/disabled
            window.toggleRule = async function(ruleId, enabled) {
                try {
                    const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                    const response = await fetch(`/api/governance.php?id=${ruleId}`, {
                        method: 'PUT',
                        credentials: 'include',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-User-ID': currentUser.id,
                            'X-User-Email': currentUser.email
                        },
                        body: JSON.stringify({ enabled })
                    });
                    const data = await response.json();
                    if (data.success) {
                        showToast(`✓ Rule ${enabled ? 'enabled' : 'disabled'}`, 'success');
                    }
                } catch (error) {
                    console.error('Error toggling rule:', error);
                }
            }
            
            // Delete rule
            window.deleteRule = async function(ruleId) {
                if (!confirm('Are you sure you want to delete this rule?')) return;
                
                try {
                    const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                    const response = await fetch(`/api/governance.php?id=${ruleId}`, {
                        method: 'DELETE',
                        credentials: 'include',
                        headers: {
                            'X-User-ID': currentUser.id,
                            'X-User-Email': currentUser.email
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        showToast('✓ Rule deleted successfully', 'success');
                        loadGovernanceRules();
                    }
                } catch (error) {
                    console.error('Error deleting rule:', error);
                    showToast('Failed to delete rule', 'error');
                }
            }
            
            // Load API keys
            async function loadApiKeys() {
                try {
                    const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                    if (!currentUser) return;
                    
                    const response = await fetch('/api/api_keys.php', {
                        credentials: 'include',
                        headers: {
                            'X-User-ID': currentUser.id,
                            'X-User-Email': currentUser.email
                        }
                    });
                    
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        console.warn('API Keys endpoint returned non-JSON response. Tables may not exist yet.');
                        const keysList = document.getElementById('api-keys-list');
                        if (keysList) {
                            keysList.innerHTML = '<div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4 text-center"><p class="text-yellow-500"><strong>⚠️ Database Setup Required</strong></p><p class="text-sm text-[var(--text-secondary)] mt-2">Run the database migration to enable API key features.</p><p class="text-sm mt-2"><a href="/api/check.php" target="_blank" style="color: var(--primary);">Check System Status →</a></p></div>';
                        }
                        return;
                    }
                    
                    const data = await response.json();
                    
                    // Check if Pro plan is required
                    if (response.status === 403 && data.requires_pro) {
                        const keysList = document.getElementById('api-keys-list');
                        if (keysList) {
                            keysList.innerHTML = `
                                <div class="border rounded-xl p-8 text-center" style="background-color: rgba(156, 122, 173, 0.1); border-color: rgba(156, 122, 173, 0.3);">
                                    <div class="text-5xl mb-4" style="color: #9c7ead;">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                    <h3 class="text-xl font-bold mb-2">API Keys are a Pro Feature</h3>
                                    <p class="text-[var(--text-secondary)] mb-4">
                                        Upgrade to Pro to create API keys and integrate Stella with external applications.
                                    </p>
                                    <button onclick="showProUpgradePrompt('Stella Api')" class="btn btn-primary px-6 py-3 rounded-full font-semibold transition-all" style="background-color: #9c7ead; border-color: #9c7ead;" onmouseover="this.style.backgroundColor='#8b6c9c'" onmouseout="this.style.backgroundColor='#9c7ead'">
                                        <i class="fas fa-rocket mr-2"></i>Upgrade to Pro
                                    </button>
                                </div>
                            `;
                        }
                        return;
                    }
                    
                    if (data.success && data.keys) {
                        const keysList = document.getElementById('api-keys-list');
                        if (keysList) {
                            if (data.keys.length === 0) {
                                keysList.innerHTML = '<p class="text-center text-[var(--text-secondary)] py-8">No API keys generated yet. Click "Generate API Key" to create one.</p>';
                            } else {
                                keysList.innerHTML = data.keys.map(key => `
                                    <div class="p-4 bg-white/5 rounded-xl border border-[var(--border-dark)] flex items-center justify-between">
                                        <div class="flex-1">
                                            <h3 class="font-semibold">${key.name}</h3>
                                            <p class="text-sm text-[var(--text-secondary)] font-mono mt-1">${key.masked_key}</p>
                                            <p class="text-xs text-[var(--text-secondary)] mt-1">
                                                ${key.last_used_at ? 'Last used ' + new Date(key.last_used_at).toLocaleString() : 'Never used'}
                                            </p>
                                        </div>
                                        <button onclick="deleteApiKey(${key.id})" class="text-[#9c7ead] hover:text-red-500 px-2">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                `).join('');
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error loading API keys:', error);
                    const keysList = document.getElementById('api-keys-list');
                    if (keysList) {
                        keysList.innerHTML = '<div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4 text-center"><p class="text-yellow-500"><strong>⚠️ Setup Required</strong></p><p class="text-sm text-[var(--text-secondary)] mt-2">Run database migration to enable this feature.</p><p class="text-sm mt-2"><a href="/api/check.php" target="_blank" style="color: var(--primary);">Diagnose Issue →</a></p></div>';
                    }
                }
            }
            
            // Delete API key
            window.deleteApiKey = async function(keyId) {
                if (!confirm('Are you sure you want to delete this API key? This cannot be undone and will break any integrations using it.')) return;
                
                try {
                    const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                    const response = await fetch(`/api/api_keys.php?id=${keyId}`, {
                        method: 'DELETE',
                        credentials: 'include',
                        headers: {
                            'X-User-ID': currentUser.id,
                            'X-User-Email': currentUser.email
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        showToast('✓ API key deleted successfully', 'success');
                        loadApiKeys();
                    }
                } catch (error) {
                    console.error('Error deleting API key:', error);
                    showToast('Failed to delete API key', 'error');
                }
            }
            
            // Load analytics data
            async function loadAnalyticsData(days = 14) {
                try {
                    const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                    if (!currentUser) {
                        console.warn('No user session found for analytics');
                        return;
                    }
                    
                    // Validate days parameter
                    if (!Number.isInteger(days) || days < 1 || days > 365) {
                        days = 14; // Default to 14 days if invalid
                    }
                    
                    // Use local activities data for consistency with "Recent Team Actions"
                    const totalActivities = activities.length;
                    
                    // Fetch analytics events data
                    const res = await fetch(`/api/analytics.php?action=stats&days=${days}`, {
                        headers: { 'X-User-ID': currentUser.id, 'X-User-Email': currentUser.email }
                    });
                    const api = await res.json();
                    const totalEvents = api?.stats?.total_events ?? 0;
                    
                    // Update stat elements if they exist
                    const totalMembersEl = document.getElementById('stat-total-members');
                    const totalActivitiesEl = document.getElementById('stat-total-activities');
                    
                    if (totalMembersEl) totalMembersEl.textContent = (teamMembers.length + 1).toString();
                    if (totalActivitiesEl) totalActivitiesEl.textContent = totalEvents.toString();
                    
                    // Count downloads and shares from activities
                    let downloads = 0;
                    let shares = 0;
                    
                    activities.forEach(activity => {
                        if (activity.message && activity.message.toLowerCase().includes('download')) {
                            downloads++;
                        }
                        if (activity.message && activity.message.toLowerCase().includes('share')) {
                            shares++;
                        }
                    });
                    
                    const downloadsEl = document.getElementById('stat-total-downloads');
                    const sharesEl = document.getElementById('stat-total-shares');
                    
                    if (downloadsEl) downloadsEl.textContent = downloads;
                    if (sharesEl) sharesEl.textContent = shares;
                    
                    // Update asset breakdown
                    const totalAssets = assets.length;
                    const assetsInKits = assets.filter(a => brandKits.some(k => k.assets && k.assets.find(ka => ka.id === a.id))).length;
                    const standaloneAssets = totalAssets - assetsInKits;
                    
                    const totalEl = document.getElementById('asset-total');
                    const inKitsEl = document.getElementById('asset-in-kits');
                    const standaloneEl = document.getElementById('asset-standalone');
                    const inKitsBarEl = document.getElementById('asset-in-kits-bar');
                    const standaloneBarEl = document.getElementById('asset-standalone-bar');
                    
                    if (totalEl) totalEl.textContent = totalAssets;
                    if (inKitsEl) inKitsEl.textContent = assetsInKits;
                    if (standaloneEl) standaloneEl.textContent = standaloneAssets;
                    
                    if (totalAssets > 0) {
                        if (inKitsBarEl) inKitsBarEl.style.width = ((assetsInKits / totalAssets) * 100) + '%';
                        if (standaloneBarEl) standaloneBarEl.style.width = ((standaloneAssets / totalAssets) * 100) + '%';
                    }
                    
                    // Update recent activities in analytics
                    const analyticsActivitiesEl = document.getElementById('analytics-recent-activities');
                    if (analyticsActivitiesEl && activities.length > 0) {
                        analyticsActivitiesEl.innerHTML = activities.slice(0, 5).map(activity => `
                            <div class="flex items-start space-x-3 p-3 bg-white/5 rounded-xl">
                                <div class="stat-icon text-lg w-10 h-10 flex-shrink-0">
                                    <i class="fas ${activity.icon}"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-sm">${activity.message}</p>
                                    <p class="text-xs text-[var(--text-secondary)]">${activity.detail} • ${activity.time}</p>
                                </div>
                            </div>
                        `).join('');
                    } else if (analyticsActivitiesEl) {
                        analyticsActivitiesEl.innerHTML = '<p class="text-center text-[var(--text-secondary)] py-4 text-sm">No recent activity</p>';
                    }
                    
                    // Update most active members
                    const topMembersEl = document.getElementById('top-members-list');
                    if (topMembersEl) {
                        if (activities.length > 0) {
                            // Count activities by user (simplified - assume current user for most activities)
                            const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                            const userActivityCount = activities.length;
                            
                            topMembersEl.innerHTML = `
                                <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-white text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-sm">${currentUser.full_name || currentUser.email}</p>
                                            <p class="text-xs text-[var(--text-secondary)]">You</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-sm">${userActivityCount}</p>
                                        <p class="text-xs text-[var(--text-secondary)]">actions</p>
                                    </div>
                                </div>
                            `;
                        } else {
                            topMembersEl.innerHTML = '<p class="text-center text-[var(--text-secondary)] py-4 text-sm">No activity data yet</p>';
                        }
                    }
                    
                    // Create activity chart using Chart.js
                    const chartCanvas = document.getElementById('activity-chart');
                    if (chartCanvas && typeof Chart !== 'undefined') {
                        // Generate chart data from local activities
                        const labels = [];
                        const data = [];
                        const activityCounts = new Map();
                        
                        // Count activities by day based on their time
                        activities.forEach(activity => {
                            // Parse activity time - handle different formats
                            let activityDate;
                            if (activity.time === 'Just now') {
                                activityDate = new Date();
                            } else if (activity.time && activity.time.includes(':')) {
                                // Format like "2:42:13 PM" - assume today
                                activityDate = new Date();
                            } else {
                                // Default to today for unknown formats
                                activityDate = new Date();
                            }
                            
                            const dayKey = activityDate.toISOString().slice(0, 10);
                            activityCounts.set(dayKey, (activityCounts.get(dayKey) || 0) + 1);
                        });
                        
                        // Generate labels and data for the last N days
                        const today = new Date();
                        for (let i = days - 1; i >= 0; i--) {
                            const dt = new Date(today);
                            dt.setDate(dt.getDate() - i);
                            const key = dt.toISOString().slice(0, 10);
                            labels.push(dt.toLocaleDateString());
                            data.push(activityCounts.get(key) || 0);
                        }
                        
                        // Destroy existing chart if it exists
                        if (window.teamActivityChart) {
                            window.teamActivityChart.destroy();
                        }
                        
                        // Create new chart
                        window.teamActivityChart = new Chart(chartCanvas, {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Team Actions',
                                    data: data,
                                    backgroundColor: 'rgba(156, 126, 173, 0.3)',
                                    borderColor: 'rgba(156, 126, 173, 1)',
                                    borderWidth: 1,
                                    borderRadius: 8
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        padding: 12,
                                        cornerRadius: 8
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                                        ticks: { color: '#888' }
                                    },
                                    x: {
                                        grid: { display: false },
                                        ticks: { color: '#888' }
                                    }
                                }
                            }
                        });
                        
                        // Update chart status
                        const statusEl = document.getElementById('activity-chart-status');
                        if (statusEl) {
                            statusEl.textContent = `Showing ${totalActivities} team activities over the last ${days} days`;
                        }
                    }
                    
                } catch (error) {
                    console.error('Error loading analytics:', error);
                    const statusEl = document.getElementById('activity-chart-status');
                    if (statusEl) statusEl.textContent = 'Error loading analytics data';
                }
            }
            
            // Add period selector functionality
            document.addEventListener('DOMContentLoaded', function() {
                const periodButtons = document.querySelectorAll('[data-activity-days]');
                periodButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const days = parseInt(this.dataset.activityDays);
                        loadAnalyticsData(days);
                        
                        // Update active button
                        periodButtons.forEach(btn => btn.classList.remove('bg-primary', 'text-white'));
                        this.classList.add('bg-primary', 'text-white');
                    });
                });
            });
            
            // Update analytics period selector
            if (document.getElementById('analytics-period')) {
                document.getElementById('analytics-period').addEventListener('change', loadAnalyticsData);
            }
            
            // Open activity modal with all activities
            window.openActivityModal = function() {
                const modal = document.getElementById('activity-modal');
                const activityList = document.getElementById('all-activity-list');
                
                if (activities.length === 0) {
                    activityList.innerHTML = '<p class="text-center text-[var(--text-secondary)] py-8">No activities recorded yet.</p>';
                } else {
                    activityList.innerHTML = activities.map(activity => `
                        <div class="flex items-start space-x-3 p-3 bg-white/5 rounded-xl hover:bg-white/10 transition-colors">
                            <div class="stat-icon text-lg w-10 h-10 flex-shrink-0">
                                <i class="fas ${activity.icon}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-sm">${activity.message}</p>
                                <p class="text-xs text-[var(--text-secondary)] truncate">${activity.detail}</p>
                                <p class="text-xs text-[var(--text-secondary)] mt-1">${activity.time}</p>
                            </div>
                        </div>
                    `).join('');
                }
                
                modal.classList.add('active');
            }
            
            // Download activity log as CSV
            window.downloadActivityLog = function() {
                if (activities.length === 0) {
                    showToast('No activities to download', 'info');
                    return;
                }
                
                // Create CSV content
                let csv = 'Timestamp,Action,Details\n';
                activities.forEach(activity => {
                    const message = activity.message.replace(/"/g, '""');
                    const detail = (activity.detail || '').replace(/"/g, '""');
                    const time = activity.time || '';
                    csv += `"${time}","${message}","${detail}"\n`;
                });
                
                // Create download link
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', `stella_activity_log_${new Date().toISOString().split('T')[0]}.csv`);
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                showToast('✓ Activity log downloaded!', 'success');
            }

            function showConfirmationModal(title, message) {
                document.getElementById('confirmation-title').textContent = title;
                document.getElementById('confirmation-message').innerHTML = message;
                document.getElementById('confirmation-modal').classList.add('active');
            }


             function setupBrandKitCreation() {
                const createKitBtn = document.getElementById('create-kit-btn');
                const nameInput = document.getElementById('kit-name');
                const descInput = document.getElementById('kit-description');
                const logoInput = document.getElementById('kit-logo-input');
                const logoPreview = document.getElementById('logo-preview');
                const modal = document.getElementById('new-brand-kit-modal');
                let logoFile = null;

                logoInput.addEventListener('change', () => {
                    const file = logoInput.files[0];
                    if (file) {
                        logoFile = file;
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            logoPreview.src = e.target.result;
                            logoPreview.style.display = 'block';
                            document.querySelector('#new-brand-kit-modal .file-input-label').textContent = file.name;
                        };
                        reader.readAsDataURL(file);
                    }
                });

                createKitBtn.addEventListener('click', async () => {
                    const name = nameInput.value;
                    const description = descInput.value;
                    if (!name) {
                        showToast('Please enter a kit name.', 'error');
                        return;
                    }
                    
                    // Check usage limits before creating kit
                    if (!checkUsageLimit('kits')) {
                        showToast('You\'ve reached your kit limit. Upgrade to Pro for unlimited kits!', 'error');
                        return;
                    }
                    
                    // Disable button during creation
                    createKitBtn.disabled = true;
                    createKitBtn.textContent = 'Creating...';
                    
                    try {
                        let logoAssetId = null;
                        
                        // Upload logo first if provided
                        if (logoFile) {
                            const user = JSON.parse(sessionStorage.getItem('stella_user'));
                            const formData = new FormData();
                            formData.append('file', logoFile);
                            formData.append('type', 'logo');
                            formData.append('name', logoFile.name);
                            formData.append('user_id', user.id);
                            
                            const uploadResponse = await fetch('/api/upload.php', {
                                method: 'POST',
                                body: formData
                            });
                            
                            const uploadData = await uploadResponse.json();
                            if (uploadData.success) {
                                logoAssetId = uploadData.asset.id;
                            } else {
                                showToast('Logo upload failed: ' + (uploadData.message || 'Unknown error'), 'error');
                                createKitBtn.disabled = false;
                                createKitBtn.textContent = 'Create Kit';
                                return;
                            }
                        }
                        
                        // Create brand kit
                        const user = JSON.parse(sessionStorage.getItem('stella_user'));
                        const formData = new FormData();
                        formData.append('name', name);
                        formData.append('description', description);
                        formData.append('user_id', user.id);
                        if (logoAssetId) {
                            formData.append('logo_asset_id', logoAssetId);
                        }
                        
                        const response = await fetch('/api/brand_kits.php?action=create', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            const newKit = {
                                id: data.brand_kit.id,
                                name: data.brand_kit.name,
                                description: data.brand_kit.description,
                                logoUrl: data.brand_kit.logo_url || null,
                                isPrivate: false,
                                assets: []
                            };
                            
                            brandKits.push(newKit);
                            ls.set(getUserDataKey('stella_brandKits'), brandKits);
                            logActivity('fa-swatchbook', 'You created a new brand kit', `"${name}"`, 'Just now');
                            
                            // Update usage tracking
                            updateUsage('kits');
                            
                            if (!gettingStartedState.kitCreated) {
                                gettingStartedState.kitCreated = true;
                                ls.set(getUserDataKey('stella_getting_started'), gettingStartedState);
                                renderGettingStarted();
                                showToast('✅ Great! You created your first Brand Kit!', 'success');
                            }

                            renderBrandKits();
                            updateDashboardStats();
                            
                            // Reset form and close modal
                            nameInput.value = '';
                            descInput.value = '';
                            logoInput.value = '';
                            logoPreview.style.display = 'none';
                            logoPreview.src = '';
                            logoFile = null;
                            document.querySelector('#new-brand-kit-modal .file-input-label').textContent = 'Choose File';
                            modal.classList.remove('active');
                            showToast('Brand Kit created successfully!', 'success');
                        } else {
                            showToast('Failed to create brand kit: ' + (data.message || 'Unknown error'), 'error');
                        }
                    } catch (error) {
                        console.error('Brand kit creation error:', error);
                        showToast('Failed to create brand kit. Please try again.', 'error');
                    } finally {
                        createKitBtn.disabled = false;
                        createKitBtn.textContent = 'Create Kit';
                    }
                });
            }

            function renderBrandKits() {
                const grid = document.getElementById('brand-kits-grid');
                if(!grid) return;
                
                while (grid.children.length > 1) {
                    grid.removeChild(grid.lastChild);
                }

                brandKits.forEach(kit => {
                    const card = document.createElement('div');
                    card.className = 'glass-card brand-kit-card aspect-square rounded-2xl p-4 flex flex-col';
                    card.dataset.kitId = kit.id;

                    // Build proper logo URL using preview_url from API if available
                    const logoAsset = assets.find(a => a.id === kit.logo_asset_id);
                    const logoHtml = kit.logo_asset_id && logoAsset ? `
                        <img src="${logoAsset.preview_url || (logoAsset.share_token ? `/api/image_proxy.php?t=${logoAsset.share_token}&size=200` : `/api/image_proxy.php?id=${kit.logo_asset_id}&size=200`)}" 
                             class="brand-kit-logo" 
                             alt="${kit.name} Logo" 
                             onload="this.style.opacity='0.05'"
                             onerror="this.style.display='none'">
                    ` : '';

                    card.innerHTML = `
                        ${logoHtml}
                        <div class="relative z-10 flex flex-col h-full">
                            <div>
                                <h3 class="font-bold text-lg truncate pr-8">${kit.name}</h3>
                                <p class="text-sm text-[var(--text-secondary)] text-ellipsis overflow-hidden h-10">${kit.description || 'No description.'}</p>
                            </div>
                            <div class="mt-auto text-xs text-[var(--text-secondary)] relative z-10">${kit.assets.length} Assets</div>
                        </div>
                        <div class="absolute top-3 right-3 z-20">
                            <button class="card-menu-btn text-white/70 hover:text-white hover:bg-black/50">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                            <div class="card-menu w-36 bg-[var(--card-dark)] border border-[var(--border-dark)] rounded-md shadow-lg">
                                <button class="share-kit-btn block w-full text-left px-3 py-2 text-sm hover:bg-white/5"><i class="fas fa-share-alt mr-2"></i>Share</button>
                                <button class="edit-kit-btn block w-full text-left px-3 py-2 text-sm hover:bg-white/5"><i class="fas fa-edit mr-2"></i>Edit</button>
                                <button class="delete-kit-btn-from-menu block w-full text-left px-3 py-2 text-sm text-[#9c7ead] hover:text-red-500 hover:bg-white/5"><i class="fas fa-trash mr-2"></i>Delete</button>
                            </div>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            }
             
            function setupBrandKitActions() {
                const grid = document.getElementById('brand-kits-grid');
                const editModal = document.getElementById('edit-brand-kit-modal');

                grid.addEventListener('click', e => {
                    const menuBtn = e.target.closest('.card-menu-btn');
                    const shareBtn = e.target.closest('.share-kit-btn');
                    const editBtn = e.target.closest('.edit-kit-btn');
                    const deleteBtn = e.target.closest('.delete-kit-btn-from-menu');
                    const card = e.target.closest('.brand-kit-card');

                    if (shareBtn) {
                        e.stopPropagation();
                        const kitId = parseFloat(card.dataset.kitId);
                        const kit = brandKits.find(k => k.id === kitId);
                        if (kit) {
                            openShareModal('brand-kit', kit.name, kitId);
                        }
                        return;
                    }

                    if (menuBtn) {
                        e.stopPropagation();
                        document.querySelectorAll('.card-menu-btn.active').forEach(btn => {
                            if (btn !== menuBtn) btn.classList.remove('active');
                        });
                        menuBtn.classList.toggle('active');
                        return;
                    }

                    if (editBtn) {
                         e.stopPropagation();
                        const kitId = Number(card.dataset.kitId);
                        openEditModal(kitId);
                        return;
                    }

                    if (deleteBtn) {
                        e.stopPropagation();
                        const kitId = Number(card.dataset.kitId);
                        if (confirm('Are you sure you want to delete this brand kit?')) {
                            brandKits = brandKits.filter(k => k.id !== kitId);
                            ls.set(getUserDataKey('stella_brandKits'), brandKits);
                            renderBrandKits();
                            updateDashboardStats();
                            showToast('Brand Kit deleted.', 'info');
                        }
                        return;
                    }
                    
                    if (card && !card.querySelector('button').contains(e.target)) {
                         const kitId = Number(card.dataset.kitId);
                         showBrandKitDetail(kitId);
                    }
                });

                // Global click to close menus
                document.body.addEventListener('click', (e) => {
                    if (!e.target.closest('.card-menu-btn')) {
                        document.querySelectorAll('.card-menu-btn.active').forEach(btn => btn.classList.remove('active'));
                    }
                });

                // Edit Modal Logic
                const saveChangesBtn = document.getElementById('save-kit-changes-btn');
                const deleteKitBtn = document.getElementById('delete-kit-btn');
                
                saveChangesBtn.addEventListener('click', () => {
                     const kitId = Number(editModal.querySelector('#edit-kit-id').value);
                     const kitIndex = brandKits.findIndex(k => k.id === kitId);
                     if(kitIndex > -1) {
                         brandKits[kitIndex].name = editModal.querySelector('#edit-kit-name').value;
                         brandKits[kitIndex].description = editModal.querySelector('#edit-kit-description').value;
                         brandKits[kitIndex].isPrivate = editModal.querySelector('#edit-kit-private').checked;
                         ls.set(getUserDataKey('stella_brandKits'), brandKits);
                         renderBrandKits();
                         showToast('Brand Kit updated.', 'success');
                         editModal.classList.remove('active');
                    }
                });
                 deleteKitBtn.addEventListener('click', () => {
                    const kitId = Number(editModal.querySelector('#edit-kit-id').value);
                     if (confirm('Are you sure you want to delete this brand kit?')) {
                         brandKits = brandKits.filter(k => k.id !== kitId);
                         ls.set(getUserDataKey('stella_brandKits'), brandKits);
                         renderBrandKits();
                         updateDashboardStats();
                         showToast('Brand Kit deleted.', 'info');
                         editModal.classList.remove('active');
                    }
                });

                 function openEditModal(kitId) {
                    const kit = brandKits.find(k => k.id === kitId);
                    if (kit) {
                        editModal.querySelector('#edit-kit-id').value = kit.id;
                        editModal.querySelector('#edit-kit-name').value = kit.name;
                        editModal.querySelector('#edit-kit-description').value = kit.description;
                        editModal.querySelector('#edit-kit-private').checked = kit.isPrivate;
                        
                        const preview = editModal.querySelector('#edit-logo-preview');
                        if (kit.logoUrl) {
                            preview.src = kit.logoUrl;
                            preview.style.display = 'block';
                        } else {
                            preview.style.display = 'none';
                        }
                        editModal.classList.add('active');
                    }
                 }
            }
             
            function showBrandKitDetail(kitId) {
                const kit = brandKits.find(k => k.id === kitId);
                if (!kit) return;
                
                document.getElementById('brand-kits-overview').classList.remove('active');
                const detailView = document.getElementById('brand-kit-detail-view');
                detailView.innerHTML = `
                    <div class="flex items-center mb-6">
                        <button id="back-to-kits-btn" class="glow-btn btn-secondary p-2 rounded-full mr-4 w-10 h-10 flex items-center justify-center"><i class="fas fa-arrow-left"></i></button>
                        <h1 class="text-2xl font-bold text-gray-900">${kit.name}</h1>
                    </div>
                    <div class="glass-card p-6 rounded-2xl">
                       <div class="flex justify-between items-center mb-4">
                           <h2 class="text-xl font-bold">Assets in ${kit.name}</h2>
                           <button class="kit-asset-upload-btn glow-btn btn-primary px-4 py-2 rounded-full flex items-center" data-modal-target="upload-asset-detail-modal" data-kit-id="${kit.id}"><i class="fas fa-upload mr-2"></i> Upload</button>
                       </div>
                       <div id="kit-asset-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"></div>
                       <div id="kit-asset-empty-state" class="text-center py-10 text-[var(--text-secondary)]"><i class="fas fa-folder-open text-4xl mb-4"></i><p>No assets in this kit yet.</p></div>
                   </div>
                `;
                detailView.classList.add('active');
                
                renderKitAssets(kitId);

                // Add event listeners for this specific view
                detailView.addEventListener('click', e => {
                    const shareBtn = e.target.closest('.kit-asset-share-btn');
                    const copyBtn = e.target.closest('.asset-copy-btn');
                    const downloadBtn = e.target.closest('.asset-download-btn');
                    const removeBtn = e.target.closest('.kit-asset-remove-btn');
                    const menuBtn = e.target.closest('.card-menu-btn');
                    const card = e.target.closest('.asset-card');

                    if (shareBtn) {
                        const assetId = parseFloat(shareBtn.dataset.id);
                        const assetName = shareBtn.dataset.name;
                        openShareModal('asset', assetName, assetId);
                        return;
                    }

                    if (downloadBtn) {
                        const assetId = parseFloat(downloadBtn.dataset.id);
                        const assetName = downloadBtn.dataset.name;
                        
                        // Check download permissions first
                        fetch(`/api/download_requests.php?action=check&asset_id=${assetId}`, {
                            headers: getAuthHeaders()
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (data.can_download) {
                                    // User can download directly
                        const downloadUrl = `/api/image_proxy.php?id=${assetId}&download=true`;
                        
                        // Track activity
                        trackActivity('download', `Downloaded asset: ${assetName}`, { asset_id: assetId, asset_name: assetName });
                        
                        fetch(downloadUrl, {
                            headers: getAuthHeaders()
                        })
                        .then(response => response.blob())
                        .then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = assetName;
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            window.URL.revokeObjectURL(url);
                            showToast('Download started', 'success');
                        })
                        .catch(error => {
                            console.error('Download failed:', error);
                            showToast('Download failed', 'error');
                                    });
                                } else if (data.requires_approval) {
                                    if (data.request_status === 'pending') {
                                        showToast('Download request is pending approval', 'warning');
                                    } else if (data.request_status === 'denied') {
                                        showToast('Download request was denied', 'error');
                                    } else {
                                        // Create download request
                                        fetch('/api/download_requests.php?action=create', {
                                            method: 'POST',
                                            headers: {
                                                ...getAuthHeaders(),
                                                'Content-Type': 'application/json'
                                            },
                                            body: JSON.stringify({
                                                asset_id: assetId
                                            })
                                        })
                                        .then(response => response.json())
                                        .then(result => {
                                            if (result.success) {
                                                showToast('Download request submitted for approval', 'success');
                                            } else {
                                                showToast(result.message || 'Failed to submit download request', 'error');
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Request failed:', error);
                                            showToast('Failed to submit download request', 'error');
                                        });
                                    }
                                } else {
                                    showToast('You do not have permission to download this asset', 'error');
                                }
                            } else {
                                showToast('Failed to check download permissions', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Permission check failed:', error);
                            showToast('Failed to check download permissions', 'error');
                        });
                        return;
                    }

                    if (copyBtn) {
                        const assetCard = copyBtn.closest('.asset-card');
                        const assetId = assetCard?.dataset.id;
                        let url = copyBtn.dataset.url;
                        
                        // If no URL or invalid URL, try to get from localStorage or generate new one
                        if (!url || url === '#no-share-token') {
                            if (assetId) {
                                // Try to get saved URL from localStorage
                                const savedUrls = JSON.parse(localStorage.getItem('stella_asset_urls') || '{}');
                                url = savedUrls[assetId];
                                
                                if (!url) {
                                    // Generate new URL using share token
                                    const asset = assets.find(a => a.id == assetId);
                                    if (asset && asset.share_token) {
                                        const baseUrl = window.location.origin;
                                        url = `${baseUrl}/public/view.php?t=${asset.share_token}`;
                                        
                                        // Save the URL for future use
                                        savedUrls[assetId] = url;
                                        localStorage.setItem('stella_asset_urls', JSON.stringify(savedUrls));
                                    }
                                }
                            }
                            
                            if (!url || url === '#no-share-token') {
                                showToast('Share link not available for this asset', 'error');
                                return;
                            }
                        }
                        
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(url).then(() => {
                                showToast('URL copied to clipboard!', 'success');
                                
                                // Save URL for future use if not already saved
                                if (assetId) {
                                    const savedUrls = JSON.parse(localStorage.getItem('stella_asset_urls') || '{}');
                                    savedUrls[assetId] = url;
                                    localStorage.setItem('stella_asset_urls', JSON.stringify(savedUrls));
                                }
                            }).catch((err) => {
                                console.error('Clipboard failed:', err);
                                fallbackCopyToClipboard(url);
                            });
                        } else {
                            fallbackCopyToClipboard(url);
                        }
                        return;
                    }

                    if (menuBtn) {
                        e.stopPropagation();
                        detailView.querySelectorAll('.asset-card .card-menu-btn.active').forEach(btn => {
                            if (btn !== menuBtn) btn.classList.remove('active');
                        });
                        menuBtn.classList.toggle('active');
                        
                        detailView.querySelectorAll('.asset-card').forEach(c => c.classList.remove('menu-open'));
                        if (menuBtn.classList.contains('active')) {
                            card.classList.add('menu-open');
                        }
                        return;
                    }

                    if (removeBtn) {
                        e.stopPropagation();
                        const assetIdToRemove = parseFloat(removeBtn.dataset.id);
                        const kitIdToUpdate = parseInt(removeBtn.dataset.kitId, 10);
                        
                        const kitIndex = brandKits.findIndex(k => k.id === kitIdToUpdate);
                        if (kitIndex > -1) {
                            brandKits[kitIndex].assets = brandKits[kitIndex].assets.filter(asset => asset.id !== assetIdToRemove);
                            ls.set(getUserDataKey('stella_brandKits'), brandKits);
                            
                            renderKitAssets(kitIdToUpdate);
                            renderBrandKits();
                            
                            showToast('Asset removed from kit.', 'info');
                        }
                    }
                });

                detailView.querySelector('#back-to-kits-btn').addEventListener('click', () => {
                    detailView.classList.remove('active');
                    document.getElementById('brand-kits-overview').classList.add('active');
                });
            }

            function renderKitAssets(kitId) {
                const kit = brandKits.find(k => k.id === kitId);
                const detailView = document.getElementById('brand-kit-detail-view');
                if (!kit || !detailView) return;

                const grid = detailView.querySelector('#kit-asset-grid');
                const emptyState = detailView.querySelector('#kit-asset-empty-state');
                
                grid.innerHTML = '';
                const hasAssets = kit.assets && kit.assets.length > 0;
                emptyState.style.display = hasAssets ? 'none' : 'block';
                grid.style.display = hasAssets ? 'grid' : 'none';

                if(hasAssets) {
                    kit.assets.forEach(asset => {
                        const isImage = asset.type.startsWith('image/');
                        const card = document.createElement('div');
                        card.className = 'asset-card glass-card p-3 rounded-lg flex flex-col';
                        card.dataset.id = asset.id;

                        if (asset.name.toLowerCase().includes('black')) {
                            card.classList.add('light-bg');
                        }

                        const previewUrl = asset.previewUrl || asset.downloadUrl || asset.url;
                        const downloadLink = asset.downloadUrl || asset.url;
                        const shareLink = asset.url;
                        
                        card.innerHTML = `
                            <div class="aspect-square bg-black/20 rounded-md flex items-center justify-center mb-2 overflow-hidden">
                                ${isImage ? `<img src="${previewUrl}" class="w-full h-full object-cover" crossorigin="anonymous" onerror="this.onerror=null; this.style.display='none'; this.parentElement.innerHTML='<i class=\\'fas fa-image text-4xl text-[var(--text-secondary)]\\' title=\\'Preview unavailable\\'></i>'">` : `<i class="fas fa-file-alt text-4xl text-[var(--text-secondary)]"></i>`}
                            </div>
                            <p class="text-sm font-medium truncate" title="${asset.name}">${asset.name}</p>
                            <div class="flex justify-between items-center mt-1">
                                <p class="text-xs text-[var(--text-secondary)]">${asset.size}</p>
                                <div class="relative asset-card-actions">
                                    <button class="card-menu-btn text-white/70 hover:text-white p-1">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <div class="asset-card-menu w-40 bg-[var(--card-dark)] border border-[var(--border-dark)] rounded-md shadow-lg">
                                        <button data-id="${asset.id}" data-name="${asset.name}" class="kit-asset-share-btn block w-full text-left px-3 py-2 text-sm hover:bg-white/5"><i class="fas fa-share-alt mr-2"></i>Share</button>
                                        <button data-id="${asset.id}" data-name="${asset.name}" class="asset-download-btn block w-full text-left px-3 py-2 text-sm hover:bg-white/5"><i class="fas fa-download mr-2"></i>Download</button>
                                        <button data-url="${shareLink}" class="asset-copy-btn block w-full text-left px-3 py-2 text-sm hover:bg-white/5"><i class="fas fa-copy mr-2"></i>Copy URL</button>
                                        <button data-id="${asset.id}" data-kit-id="${kitId}" class="kit-asset-remove-btn block w-full text-left px-3 py-2 text-sm text-[#9c7ead] hover:text-red-500 hover:bg-white/5"><i class="fas fa-trash mr-2"></i>Remove from Kit</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        grid.appendChild(card);
                    });
                }
            }

            // --- ASSET HUB MODULE ---
            function setupAssetHub() {
                const categoryFilter = document.getElementById('asset-category-filter');
                const modal = document.getElementById('upload-asset-detail-modal');
                const modalDropArea = document.getElementById('modal-asset-upload-area');
                const modalFileInput = document.getElementById('modal-asset-file-input');
                const assetNameInput = document.getElementById('asset-upload-name');
                const assetCategorySelect = document.getElementById('asset-upload-category');
                const assetPreview = document.getElementById('asset-upload-preview');
                const saveAssetBtn = document.getElementById('save-asset-btn');

                let currentFile = null;
                let currentFileUrl = null;

                categoryFilter.addEventListener('change', renderAssets);
                
                // Modal Drag & Drop
                modalDropArea.addEventListener('dragover', (e) => { e.preventDefault(); modalDropArea.classList.add('drag-over'); });
                modalDropArea.addEventListener('dragleave', () => modalDropArea.classList.remove('drag-over'));
                modalDropArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    modalDropArea.classList.remove('drag-over');
                    if (e.dataTransfer.files.length > 0) {
                        handleFileSelection(e.dataTransfer.files[0]);
                    }
                });
                modalFileInput.addEventListener('change', () => {
                    if (modalFileInput.files.length > 0) {
                        handleFileSelection(modalFileInput.files[0]);
                    }
                });

                function handleFileSelection(file) {
                    currentFile = file;
                    assetNameInput.value = file.name;

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        currentFileUrl = e.target.result;
                        if(file.type.startsWith('image/')) {
                             assetPreview.src = currentFileUrl;
                             assetPreview.style.display = 'block';
                        } else {
                             assetPreview.style.display = 'none';
                        }
                    };
                    reader.readAsDataURL(file);
                }
                
                saveAssetBtn.addEventListener('click', async () => {
                    if (!currentFile) {
                        showToast('Please select a file to upload.', 'error');
                        return;
                    }
                    const selectedKitId = document.getElementById('asset-upload-kit').value;
                    
                    // Disable button during upload
                    saveAssetBtn.disabled = true;
                    saveAssetBtn.textContent = 'Uploading...';
                    
                    try {
                        // Upload to Nextcloud via API
                        const user = JSON.parse(sessionStorage.getItem('stella_user'));
                        const formData = new FormData();
                        formData.append('file', currentFile);
                        formData.append('type', 'asset');
                        formData.append('name', assetNameInput.value || currentFile.name);
                        formData.append('user_id', user.id);
                        if (selectedKitId) {
                            formData.append('brand_kit_id', selectedKitId);
                        }
                        
                        const response = await fetch('/api/upload.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            const newAsset = {
                                id: data.asset.id,
                                name: data.asset.name,
                                url: data.asset.url, // Public share link
                                downloadUrl: data.asset.download_url, // Authenticated download
                                previewUrl: data.asset.preview_url, // Authenticated preview
                                shareToken: data.asset.share_token,
                                type: data.asset.type,
                                size: (data.asset.size / 1024).toFixed(2) + ' KB',
                                category: assetCategorySelect.value
                            };
                            
                            assets.push(newAsset);
                            ls.set(getUserDataKey('stella_assets'), assets);
                            
                            if (selectedKitId) {
                                const kitIndex = brandKits.findIndex(k => k.id == selectedKitId);
                                if(kitIndex > -1) {
                                    brandKits[kitIndex].assets.push(newAsset);
                                    ls.set(getUserDataKey('stella_brandKits'), brandKits);
                                }
                            }

                            if (!gettingStartedState.assetUploaded) {
                                gettingStartedState.assetUploaded = true;
                                ls.set(getUserDataKey('stella_getting_started'), gettingStartedState);
                                renderGettingStarted();
                                showToast('✅ You uploaded your first asset!', 'success');
                            }

                            logActivity('fa-image', 'You uploaded a new asset', `"${newAsset.name}"`, 'Just now');
                            
                            // Reload assets from database to get all assets across devices
                            await reloadAssetsFromDatabase();
                            
                            // Use global refresh function
                            if (window.refreshAssetHub) {
                                window.refreshAssetHub();
                            }
                            renderBrandKits();
                            updateDashboardStats();
                            showToast(`Asset "${newAsset.name}" uploaded successfully`, 'success');

                            // Reset modal and close
                            modal.classList.remove('active');
                            currentFile = null;
                            currentFileUrl = null;
                            assetNameInput.value = '';
                            assetCategorySelect.value = 'other';
                            assetPreview.style.display = 'none';
                            modalFileInput.value = '';
                        } else {
                            showToast('Upload failed: ' + (data.message || 'Unknown error'), 'error');
                        }
                    } catch (error) {
                        console.error('Upload error:', error);
                        showToast('Upload failed. Please try again.', 'error');
                    } finally {
                        saveAssetBtn.disabled = false;
                        saveAssetBtn.textContent = 'Save Asset';
                    }
                });

                function renderAssets() {
                    const grid = document.getElementById('asset-grid');
                    const emptyState = document.getElementById('asset-empty-state');
                    const selectedCategory = categoryFilter.value;
                    if(!grid || !emptyState) return;

                    // Filter out brand kit logos and only show regular assets
                    const regularAssets = assets.filter(asset => asset.type !== 'logo');
                    const filteredAssets = selectedCategory === 'all' 
                        ? regularAssets 
                        : regularAssets.filter(asset => asset.category === selectedCategory);

                    grid.innerHTML = '';
                    emptyState.style.display = filteredAssets.length === 0 ? 'block' : 'none';
                    grid.style.display = filteredAssets.length > 0 ? 'grid' : 'none';
                    
                    filteredAssets.forEach(asset => {
                        const isImage = asset.type.startsWith('image/');
                        const card = document.createElement('div');
                        card.className = 'asset-card glass-card p-3 rounded-lg flex flex-col';
                        card.dataset.id = asset.id;
                        
                        if (asset.name.toLowerCase().includes('black')) {
                            card.classList.add('light-bg');
                        }

                        // Use preview URL from API if available, otherwise fallback to image proxy
                        const previewUrl = isImage ? (asset.preview_url || (asset.share_token ? `/api/image_proxy.php?t=${asset.share_token}&size=300` : null)) : null;
                        const downloadLink = asset.share_token ? `/api/image_proxy.php?t=${asset.share_token}&download=true` : `/api/image_proxy.php?id=${asset.id}&download=true`;
                        const shareLink = asset.share_token ? `/public/view.php?t=${asset.share_token}` : `#no-share-token`;
                        
                        card.innerHTML = `
                            <div class="aspect-square bg-black/20 rounded-md flex items-center justify-center mb-2 overflow-hidden relative">
                                ${isImage ? `
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <div class="loading-spinner w-6 h-6 border-2 border-[var(--text-secondary)] border-t-transparent rounded-full animate-spin"></div>
                                    </div>
                                    <img src="${previewUrl}" 
                                         class="w-full h-full object-cover opacity-0 transition-opacity duration-300" 
                                         crossorigin="anonymous" 
                                         onload="this.style.opacity='1'; this.previousElementSibling.style.display='none';"
                                         onerror="this.style.display='none'; this.previousElementSibling.style.display='none'; this.parentElement.innerHTML='<i class=\\'fas fa-image text-4xl text-[var(--text-secondary)]\\' title=\\'Preview unavailable\\'></i>'">
                                ` : `<i class="fas fa-file-alt text-4xl text-[var(--text-secondary)]"></i>`}
                            </div>
                            <p class="text-sm font-medium truncate" title="${asset.name}">${asset.name}</p>
                            <div class="flex justify-between items-center mt-1">
                                <p class="text-xs text-[var(--text-secondary)]">${asset.size}</p>
                                <div class="relative asset-card-actions">
                                    <button class="card-menu-btn text-white/70 hover:text-white p-1">
                                       <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <div class="asset-card-menu w-36 bg-[var(--card-dark)] border border-[var(--border-dark)] rounded-md shadow-lg">
                                        <button data-id="${asset.id}" data-name="${asset.name}" class="asset-share-btn block w-full text-left px-3 py-2 text-sm hover:bg-white/5"><i class="fas fa-share-alt mr-2"></i>Share</button>
                                        <button data-id="${asset.id}" data-name="${asset.name}" class="asset-download-btn block w-full text-left px-3 py-2 text-sm hover:bg-white/5"><i class="fas fa-download mr-2"></i>Download</button>
                                        <button data-url="${shareLink}" class="asset-copy-btn block w-full text-left px-3 py-2 text-sm hover:bg-white/5"><i class="fas fa-copy mr-2"></i>Copy URL</button>
                                        <button data-id="${asset.id}" class="asset-delete-btn block w-full text-left px-3 py-2 text-sm text-[#9c7ead] hover:text-red-500 hover:bg-white/5"><i class="fas fa-trash mr-2"></i>Delete</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        grid.appendChild(card);
                    });
                }

                document.getElementById('asset-hub-page').addEventListener('click', (e) => {
                    const shareBtn = e.target.closest('.asset-share-btn');
                    const copyBtn = e.target.closest('.asset-copy-btn');
                    const downloadBtn = e.target.closest('.asset-download-btn');
                    const deleteBtn = e.target.closest('.asset-delete-btn');
                    const menuBtn = e.target.closest('.card-menu-btn');
                    const card = e.target.closest('.asset-card');

                    if(shareBtn) {
                        const assetId = parseFloat(shareBtn.dataset.id);
                        const assetName = shareBtn.dataset.name;
                        openShareModal('asset', assetName, assetId);
                        return;
                    }

                    if(downloadBtn) {
                        const assetId = parseFloat(downloadBtn.dataset.id);
                        const assetName = downloadBtn.dataset.name;
                        
                        // Check download permissions first
                        fetch(`/api/download_requests.php?action=check&asset_id=${assetId}`, {
                            headers: getAuthHeaders()
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (data.can_download) {
                                    // User can download directly
                        const downloadUrl = `/api/image_proxy.php?id=${assetId}&download=true`;
                        
                        // Track activity
                        trackActivity('download', `Downloaded asset: ${assetName}`, { asset_id: assetId, asset_name: assetName });
                        
                        fetch(downloadUrl, {
                            headers: getAuthHeaders()
                        })
                        .then(response => response.blob())
                        .then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = assetName;
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            window.URL.revokeObjectURL(url);
                            showToast('Download started', 'success');
                        })
                        .catch(error => {
                            console.error('Download failed:', error);
                            showToast('Download failed', 'error');
                                    });
                                } else if (data.requires_approval) {
                                    if (data.request_status === 'pending') {
                                        showToast('Download request is pending approval', 'warning');
                                    } else if (data.request_status === 'denied') {
                                        showToast('Download request was denied', 'error');
                                    } else {
                                        // Create download request
                                        fetch('/api/download_requests.php?action=create', {
                                            method: 'POST',
                                            headers: {
                                                ...getAuthHeaders(),
                                                'Content-Type': 'application/json'
                                            },
                                            body: JSON.stringify({
                                                asset_id: assetId
                                            })
                                        })
                                        .then(response => response.json())
                                        .then(result => {
                                            if (result.success) {
                                                showToast('Download request submitted for approval', 'success');
                                            } else {
                                                showToast(result.message || 'Failed to submit download request', 'error');
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Request failed:', error);
                                            showToast('Failed to submit download request', 'error');
                                        });
                                    }
                                } else {
                                    showToast('You do not have permission to download this asset', 'error');
                                }
                            } else {
                                showToast('Failed to check download permissions', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Permission check failed:', error);
                            showToast('Failed to check download permissions', 'error');
                        });
                        return;
                    }

                     if (menuBtn) {
                         e.stopPropagation();
                         document.querySelectorAll('.asset-card .card-menu-btn.active').forEach(btn => {
                             if (btn !== menuBtn) btn.classList.remove('active');
                         });
                         menuBtn.classList.toggle('active');

                          document.querySelectorAll('.asset-card').forEach(c => c.classList.remove('menu-open'));
                         if(menuBtn.classList.contains('active')) {
                              card.classList.add('menu-open');
                         }
                         return;
                    }

                    if(copyBtn) {
                        const assetCard = copyBtn.closest('.asset-card');
                        const assetId = assetCard?.dataset.id;
                        let url = copyBtn.dataset.url;
                        
                        // If no URL or invalid URL, try to get from localStorage or generate new one
                        if (!url || url === '#no-share-token') {
                            if (assetId) {
                                // Try to get saved URL from localStorage
                                const savedUrls = JSON.parse(localStorage.getItem('stella_asset_urls') || '{}');
                                url = savedUrls[assetId];
                                
                                if (!url) {
                                    // Generate new URL using share token
                                    const asset = assets.find(a => a.id == assetId);
                                    if (asset && asset.share_token) {
                                        const baseUrl = window.location.origin;
                                        url = `${baseUrl}/public/view.php?t=${asset.share_token}`;
                                        
                                        // Save the URL for future use
                                        savedUrls[assetId] = url;
                                        localStorage.setItem('stella_asset_urls', JSON.stringify(savedUrls));
                                    }
                                }
                            }
                            
                            if (!url || url === '#no-share-token') {
                                showToast('Share link not available for this asset', 'error');
                                return;
                            }
                        }
                        
                        // Try modern clipboard API first
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(url).then(() => {
                                showToast('URL copied to clipboard!', 'success');
                                
                                // Save URL for future use if not already saved
                                if (assetId) {
                                    const savedUrls = JSON.parse(localStorage.getItem('stella_asset_urls') || '{}');
                                    savedUrls[assetId] = url;
                                    localStorage.setItem('stella_asset_urls', JSON.stringify(savedUrls));
                                }
                                
                                // Track activity
                                const assetName = assetCard?.querySelector('.asset-name')?.textContent || 'Unknown asset';
                                trackActivity('copy', `Copied URL for asset: ${assetName}`, { asset_name: assetName, url: url });
                            }).catch((err) => {
                                console.error('Clipboard API failed:', err);
                                fallbackCopyToClipboard(url);
                            });
                        } else {
                            // Fallback for older browsers
                            fallbackCopyToClipboard(url);
                        }
                    }
                    
                    if(deleteBtn) {
                        const idToDelete = parseFloat(deleteBtn.dataset.id);
                        if(confirm('Are you sure you want to delete this asset?')) {
                            // Call API to delete from database and Nextcloud
                            deleteAsset(idToDelete);
                        }
                    }
                });

                renderAssets();
                
                // Expose refresh function globally
                window.refreshAssetHub = renderAssets;
            }

            function populateBrandKitDropdowns(selectElement) {
                selectElement.innerHTML = '<option value="">None</option>'; // Reset
                brandKits.forEach(kit => {
                    const option = document.createElement('option');
                    option.value = kit.id;
                    option.textContent = kit.name;
                    selectElement.appendChild(option);
                });
            }

            // --- AUTHENTICATION MODULE ---
            function initializeAuthSystem() {
                const authOverlay = document.createElement('div');
                authOverlay.id = 'auth-overlay';
                authOverlay.style.cssText = `
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: #000; display: flex; justify-content: center; align-items: center;
                    z-index: 9999; font-family: 'Geist', sans-serif; transition: opacity 0.3s ease-in-out;
                `;
                
                function showForgotPasswordForm() {
                    authOverlay.innerHTML = `
                        <div style="background: #111; padding: 2rem; border-radius: 20px; width: 90%; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                            <div style="text-align: center; margin-bottom: 2rem;">
                                <div class="logo-icon text-4xl" style="margin-bottom: 0.75rem; display: inline-block;"><span class="logo-icon-ray"></span><span class="logo-icon-ray"></span></div>
                                <h1 style="color: #fff; font-weight: 600; font-size: 1.5rem;">Reset Password</h1>
                                <p style="color: #888; margin: 0.25rem 0 0;">We'll send you a reset link</p>
                            </div>
                            <form id="forgot-form">
                                <div style="margin-bottom: 1.5rem;">
                                    <label style="display: block; color: #ddd; margin-bottom: 0.5rem; font-size: 0.9rem;">Email Address</label>
                                    <input type="email" id="forgot-email-main" required style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #333; background: #222; color: #fff; font-size: 1rem; box-sizing: border-box;">
                                </div>
                                <button type="submit" style="width: 100%; padding: 12px; border-radius: 12px; border: none; background: #9c7ead; color: white; font-weight: 600; font-size: 1rem; cursor: pointer; transition: background 0.2s; margin-bottom: 1rem;">Send Reset Link</button>
                                <p id="forgot-message" style="text-align: center; margin: 0 0 1rem; display: none; font-size: 0.875rem;"></p>
                                <div style="text-align: center;">
                                    <a href="#" id="back-to-login-main" style="color: #9c7ead; text-decoration: none; font-size: 0.9rem; font-weight: 600;">← Back to sign in</a>
                                </div>
                            </form>
                        </div>
                    `;
                    
                    const forgotForm = authOverlay.querySelector('#forgot-form');
                    forgotForm.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        const email = document.getElementById('forgot-email-main').value;
                        const messageEl = document.getElementById('forgot-message');
                        const submitBtn = forgotForm.querySelector('button[type="submit"]');
                        
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Sending...';
                        messageEl.style.display = 'none';
                        
                        try {
                            const response = await fetch('/api/auth.php?action=forgot-password', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ email })
                            });
                            
                            const data = await response.json();
                            
                            if (data.success) {
                                // Send email via EmailJS if email data is provided
                                if (data.send_email && data.email_data) {
                                    try {
                                        await emailjs.send(
                                            'service_7cdagjc',
                                            'template_tfxibta',
                                            {
                                                to_name: data.email_data.to_name,
                                                user_email: data.email_data.to_email,
                                                to_email: data.email_data.to_email,
                                                reply_to: data.email_data.to_email,
                                                UserName: data.email_data.to_name,
                                                ResetURL: data.email_data.reset_url
                                            }
                                        );
                                    } catch (emailError) {
                                        console.error('Email sending failed:', emailError);
                                    }
                                }
                                
                                messageEl.style.color = '#10b981';
                                messageEl.textContent = data.message;
                                messageEl.style.display = 'block';
                                
                                setTimeout(() => {
                                    showLogin();
                                }, 3000);
                            } else {
                                messageEl.style.color = '#ff4444';
                                messageEl.textContent = data.message;
                                messageEl.style.display = 'block';
                                submitBtn.disabled = false;
                                submitBtn.textContent = 'Send Reset Link';
                            }
                        } catch (error) {
                            console.error('Forgot password error:', error);
                            messageEl.style.color = '#ff4444';
                            messageEl.textContent = 'Connection error. Please try again.';
                            messageEl.style.display = 'block';
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Send Reset Link';
                        }
                    });
                    
                    document.getElementById('back-to-login-main').addEventListener('click', (e) => {
                        e.preventDefault();
                        showLogin();
                    });
                }

                function showLogin() {
                    authOverlay.innerHTML = `
                        <div style="background: #111; padding: 2rem; border-radius: 20px; width: 90%; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                            <div style="text-align: center; margin-bottom: 2rem;">
                                <div class="logo-icon text-4xl" style="margin-bottom: 0.75rem; display: inline-block;"><span class="logo-icon-ray"></span><span class="logo-icon-ray"></span></div>
                                <h1 style="color: #fff; font-weight: 600; font-size: 1.5rem;">Stella.</h1>
                                <p style="color: #888; margin: 0.25rem 0 0;">Sign in to your dashboard</p>
                            </div>
                            <form id="login-form">
                                <div style="margin-bottom: 1.5rem;">
                                    <label style="display: block; color: #ddd; margin-bottom: 0.5rem; font-size: 0.9rem;">Email</label>
                                    <input type="email" id="login-email-main" required style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #333; background: #222; color: #fff; font-size: 1rem; box-sizing: border-box;">
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <label style="display: block; color: #ddd; margin-bottom: 0.5rem; font-size: 0.9rem;">Password</label>
                                    <input type="password" id="login-password-main" required style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #333; background: #222; color: #fff; font-size: 1rem; box-sizing: border-box;">
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                                    <label style="display: flex; align-items: center; color: #ddd; font-size: 0.875rem; cursor: pointer;">
                                        <input type="checkbox" id="remember-me-main" style="margin-right: 0.5rem; cursor: pointer; width: 16px; height: 16px;">
                                        Remember me
                                    </label>
                                    <a href="#" id="forgot-password-main-link" style="color: #9c7ead; font-size: 0.875rem; text-decoration: none; transition: color 0.2s;">Forgot password?</a>
                                </div>
                                <button type="submit" style="width: 100%; padding: 12px; border-radius: 12px; border: none; background: #9c7ead; color: white; font-weight: 600; font-size: 1rem; cursor: pointer; transition: background 0.2s;">Sign In</button>
                                <p id="login-error" style="color: #ff4444; text-align: center; margin: 1rem 0 0; display: none;">Invalid credentials</p>
                            </form>
                        </div>
                    `;
                    authOverlay.querySelector('#login-form').addEventListener('submit', handleLogin);
                    
                    // Forgot password link
                    document.getElementById('forgot-password-main-link').addEventListener('click', (e) => {
                        e.preventDefault();
                        showForgotPasswordForm();
                    });
                }
                
                async function handleLogin(e) {
                    e.preventDefault();
                    const email = document.getElementById('login-email-main').value;
                    const password = document.getElementById('login-password-main').value;
                    const rememberMe = document.getElementById('remember-me-main')?.checked || false;
                    const errorElement = document.getElementById('login-error');
                    
                    try {
                        // Call the actual auth API
                        const response = await fetch('/api/auth.php?action=login', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email, password, remember_me: rememberMe })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success && data.user) {
                            const user = data.user; // This includes id, full_name, email
                            sessionStorage.setItem('stella_auth', 'authenticated');
                            sessionStorage.setItem('stella_user', JSON.stringify(user));
                            
                            // Store in localStorage if remember me is checked
                            if (rememberMe) {
                                localStorage.setItem('stella_remember', 'true');
                                localStorage.setItem('stella_user', JSON.stringify(user));
                            }
                            
                            updateUserInfo(user);
                            loadUserWorkspace(user);

                            authOverlay.style.opacity = '0';
                            setTimeout(() => authOverlay.style.display = 'none', 300);
                            showToast(`Welcome back, ${user.full_name}!`, 'success');
                        } else {
                            errorElement.textContent = data.message || 'Invalid credentials';
                            errorElement.style.display = 'block';
                        }
                    } catch (error) {
                        console.error('Login error:', error);
                        errorElement.textContent = 'Login failed. Please try again.';
                        errorElement.style.display = 'block';
                    }
                }
                
                function updateUserInfo(user) {
                    document.getElementById('desktop-user-name').textContent = user.full_name || 'User';
                    document.getElementById('desktop-user-email').textContent = user.email;
                    document.getElementById('mobile-user-name').textContent = user.full_name || 'User';
                    document.getElementById('mobile-user-email').textContent = user.email;
                    document.querySelector('#dashboard-page h2').textContent = `Welcome back, ${user.full_name}!`;
                }

                function handleLogout() {
                    sessionStorage.clear();
                    // Clear localStorage remember me
                    localStorage.removeItem('stella_remember');
                    localStorage.removeItem('stella_user');
                    // Clear state variables
                    brandKits = []; assets = []; teamMembers = []; pendingInvites = []; activities = []; gettingStartedState = {}; userPrefix = '';
                    authOverlay.style.display = 'flex';
                    setTimeout(() => authOverlay.style.opacity = '1', 10);
                    showLogin();
                }

                document.getElementById('logout-btn').addEventListener('click', handleLogout);
                document.getElementById('mobile-logout-btn').addEventListener('click', handleLogout);
                document.body.appendChild(authOverlay);

                // Check session storage first, then localStorage for remembered users
                let isAuthenticated = sessionStorage.getItem('stella_auth') === 'authenticated';
                let user = sessionStorage.getItem('stella_user') ? JSON.parse(sessionStorage.getItem('stella_user')) : null;
                
                // If not in session but remember me was checked, restore from localStorage
                if (!isAuthenticated && localStorage.getItem('stella_remember') === 'true') {
                    const rememberedUser = localStorage.getItem('stella_user');
                    if (rememberedUser) {
                        user = JSON.parse(rememberedUser);
                        sessionStorage.setItem('stella_auth', 'authenticated');
                        sessionStorage.setItem('stella_user', JSON.stringify(user));
                        isAuthenticated = true;
                    }
                }
                
                if (isAuthenticated && user) {
                    updateUserInfo(user);
                    loadUserWorkspace(user);
                    authOverlay.style.display = 'none';
                } else {
                    showLogin();
                }
            }

            // --- HELP GUIDE MODULE ---
            function initializeHelpGuide() {
                const config = {
                    buttonIcon: '<i class="fas fa-question-circle"></i>',
                    buttonSize: '60px',
                    mainColor: 'var(--primary)',
                    backgroundColor: 'var(--card-dark)',
                    fontFamily: "'Geist', sans-serif",
                };

                 const helpContent = {
                    'dashboard-page': `
                        <h4 class="font-bold text-lg mb-2 text-white">Dashboard Overview</h4>
                        <p class="mb-4 text-[var(--text-secondary)]">The dashboard gives you a quick snapshot of your workspace.</p>
                        <ul class="space-y-3 list-disc list-inside text-[var(--text-secondary)]">
                            <li><strong>Getting Started:</strong> A checklist to help you set up your account.</li>
                            <li><strong>Recent Activity:</strong> A feed of the latest actions taken by you and your team.</li>
                            <li><strong>Team Overview:</strong> See who is on your team at a glance.</li>
                            <li><strong>Usage Snapshot:</strong> Track your usage of brand kits, assets, and AI scans against your plan's limits.</li>
                        </ul>
                    `,
                    'brand-kits-page': `
                        <h4 class="font-bold text-lg mb-2 text-white">Managing Brand Kits</h4>
                        <p class="mb-4 text-[var(--text-secondary)]">Brand Kits are central repositories for your brand's identity, including logos, color palettes, fonts, and key assets.</p>
                        <ul class="space-y-3 list-disc list-inside text-[var(--text-secondary)]">
                            <li><strong>Create a Kit:</strong> Click the "New Brand Kit" button to start. Give it a name, description, and an optional logo.</li>
                            <li><strong>View a Kit:</strong> Click on any kit card to see its detailed view and manage the assets within it.</li>
                            <li><strong>Edit/Delete:</strong> Hover over a kit and click the three-dots menu to edit its details or delete it.</li>
                            <li><strong>Private Kits:</strong> In the edit menu, you can mark a kit as "Private," making it visible only to you and admins.</li>
                        </ul>
                    `,
                    'asset-hub-page': `
                        <h4 class="font-bold text-lg mb-2 text-white">Using the Asset Hub</h4>
                        <p class="mb-4 text-[var(--text-secondary)]">The Asset Hub is a centralized library for all your brand's digital files.</p>
                        <ul class="space-y-3 list-disc list-inside text-[var(--text-secondary)]">
                            <li><strong>Upload Assets:</strong> Click the "Upload" button or drag and drop files into the upload modal. You can name your asset, assign a category, and optionally add it directly to a brand kit.</li>
                            <li><strong>Filter:</strong> Use the category dropdown to quickly find the assets you're looking for.</li>
                            <li><strong>Manage Assets:</strong> Click the three-dots menu on an asset card to download it, copy its URL, or delete it.</li>
                        </ul>
                    `,
                    'team-settings-page': `
                        <h4 class="font-bold text-lg mb-2 text-white">Team Settings & Permissions</h4>
                        <p class="mb-4 text-[var(--text-secondary)]">Manage your team members and configure workspace-wide permissions.</p>
                        <ul class="space-y-3 list-disc list-inside text-[var(--text-secondary)]">
                            <li><strong>Invite Members:</strong> Click "Invite Member" and enter their email address to send an invitation. Pending invites will show with a PENDING status.</li>
                            <li><strong>Manage Members:</strong> You can see a list of all current members, pending invites, and their roles.</li>
                            <li><strong>Revoke Invites:</strong> For pending invites, you can click "Revoke" to cancel the invitation.</li>
                            <li><strong>Permissions:</strong> Use the toggles to control what different roles can do within your workspace, such as creating brand kits or sharing assets publicly.</li>
                        </ul>
                    `,
                    'governance-page': `
                        <h4 class="font-bold text-lg mb-2 text-white">Governance & Compliance</h4>
                        <p class="mb-4 text-[var(--text-secondary)]">Ensure your brand content meets standards and compliance requirements.</p>
                        <ul class="space-y-3 list-disc list-inside text-[var(--text-secondary)]">
                            <li><strong>AI Content Governance:</strong> Coming soon - AI-powered checks for tone, voice, and style guide compliance.</li>
                            <li><strong>Brand Guidelines:</strong> Configure approval workflows, version control, and audit logging for your brand assets.</li>
                            <li><strong>Compliance Status:</strong> View your current compliance status with GDPR, CCPA, and SOC 2 Type II standards.</li>
                        </ul>
                    `,
                    'analytics-page': `
                        <h4 class="font-bold text-lg mb-2 text-white">Analytics & Insights</h4>
                        <p class="mb-4 text-[var(--text-secondary)]">Track your brand asset performance and team engagement with powerful analytics.</p>
                        <ul class="space-y-3 list-disc list-inside text-[var(--text-secondary)]">
                            <li><strong>Overview Metrics:</strong> See total asset views, downloads, shares, and active team members at a glance.</li>
                            <li><strong>Asset Activity:</strong> View a 7-day chart of asset views and downloads to understand usage trends.</li>
                            <li><strong>Top Assets:</strong> Identify which assets are performing best to inform your brand strategy.</li>
                            <li><strong>Team Activity:</strong> Monitor which team members are most active in the workspace.</li>
                            <li><strong>Category Breakdown:</strong> See which types of assets are most popular and downloaded.</li>
                        </ul>
                    `,
                    'support-page': `
                        <h4 class="font-bold text-lg mb-2 text-white">Getting Support</h4>
                        <p class="mb-4 text-[var(--text-secondary)]">If you encounter any issues or have questions, we're here to help.</p>
                        <p class="text-[var(--text-secondary)]">Simply fill out the form with your email, a clear subject, and a detailed message describing your issue. Our support team will review your request and get back to you, typically within 24 hours.</p>
                    `
                };

                const styleSheet = `
                    .help-guide-btn {
                        position: fixed; bottom: 2rem; right: 2rem; width: ${config.buttonSize}; height: ${config.buttonSize};
                        background-color: ${config.mainColor}; color: white; border: none; border-radius: 9999px;
                        display: flex; align-items: center; justify-content: center; font-size: 24px; cursor: pointer;
                        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25); z-index: 1000; transition: transform 0.2s ease-in-out;
                    }
                    .help-guide-btn:hover { transform: scale(1.1); }
                    .help-guide-modal {
                        position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.8); z-index: 2000; display: flex;
                        align-items: center; justify-content: center; opacity: 0; visibility: hidden; transition: opacity 0.3s ease;
                    }
                    .help-guide-modal.active { opacity: 1; visibility: visible; }
                    .help-guide-modal-content {
                        background-color: ${config.backgroundColor}; color: #ededed; border-radius: 1.5rem; padding: 2rem;
                        width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto; position: relative;
                        border: 1px solid var(--border-dark); font-family: ${config.fontFamily};
                    }
                `;
                const styleElement = document.createElement('style');
                styleElement.textContent = styleSheet;
                document.head.appendChild(styleElement);
                
                const componentHTML = `
                    <button class="help-guide-btn" id="help-guide-button" title="Help Guide">${config.buttonIcon}</button>
                    <div class="help-guide-modal" id="help-guide-modal">
                        <div class="modal-content" style="max-width: 500px">
                             <div class="modal-header">
                                 <h3 class="text-xl font-medium">Help Guide</h3>
                                 <button id="help-guide-modal-close" class="text-gray-400 hover:text-primary text-3xl font-light">&times;</button>
                             </div>
                             <div class="modal-body">
                                 
                             </div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', componentHTML);

                const button = document.getElementById('help-guide-button');
                const modal = document.getElementById('help-guide-modal');
                const modalClose = document.getElementById('help-guide-modal-close');
                
                button.addEventListener('click', () => {
                    const activePage = document.querySelector('.page.active');
                    const modalBody = modal.querySelector('.modal-body');
                    
                    if (activePage && helpContent[activePage.id]) {
                        modalBody.innerHTML = helpContent[activePage.id];
                    } else {
                        modalBody.innerHTML = '<p>Help content for this page is not available.</p>';
                    }
                    modal.classList.add('active');
                });
                
                modalClose.addEventListener('click', () => modal.classList.remove('active'));
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) modal.classList.remove('active');
                });
            }
            
            // --- PRODUCT TOUR MODULE ---
            function initializeProductTour(user) {
                const welcomeModal = document.getElementById('welcome-modal');
                const tourKey = getUserDataKey('stella_tour_completed');
                
                function startGuidedTour() {
                    const tour = new Shepherd.Tour({
                        useModalOverlay: true,
                        defaultStepOptions: {
                            classes: 'shadow-md',
                            scrollTo: { behavior: 'smooth', block: 'center' }
                        }
                    });

                    const onEndTour = () => ls.set(tourKey, true);
                    tour.on('complete', onEndTour);
                    tour.on('cancel', onEndTour);

                    // Step 1: Introduction to Getting Started
                    tour.addStep({
                        id: 'intro',
                        title: 'Welcome to Stella!',
                        text: 'Let\'s get you set up! We\'ll walk you through creating your first brand kit, uploading an asset, and inviting your team. This will only take a few minutes.',
                        attachTo: { element: '#getting-started-card', on: 'top' },
                        buttons: [
                            { text: 'Skip Tour', action: tour.cancel, secondary: true },
                            { text: 'Let\'s Go!', action: tour.next }
                        ]
                    });

                    // Step 2: Create Brand Kit
                    tour.addStep({
                        id: 'step-1',
                        title: 'Step 1: Create a Brand Kit',
                        text: 'A Brand Kit is your central hub for organizing logos, colors, fonts, and assets. Click this button to create your first one!',
                        attachTo: { element: '#dashboard-actions > button:first-child', on: 'bottom' },
                        buttons: [
                            { text: 'Back', action: tour.back, secondary: true },
                            { 
                                text: 'Create Brand Kit', 
                                action: function() {
                                    tour.complete();
                                    document.querySelector('#dashboard-actions > button:first-child').click();
                                    showToast('Fill in your brand kit details and click "Create"', 'info');
                                }
                            }
                        ]
                    });

                    tour.start();
                }
                
                function startQuickTour() {
                    const tour = new Shepherd.Tour({
                        useModalOverlay: true,
                        defaultStepOptions: {
                            classes: 'shadow-md',
                            scrollTo: { behavior: 'smooth', block: 'center' }
                        }
                    });

                    const onEndTour = () => ls.set(tourKey, true);
                    tour.on('complete', onEndTour);
                    tour.on('cancel', onEndTour);

                    tour.addStep({
                        id: 'step-1',
                        title: 'Create a Brand Kit',
                        text: 'Start by creating a Brand Kit. This is where you\'ll store your logos, colors, fonts, and other brand assets.',
                        attachTo: { element: '#dashboard-actions > button:first-child', on: 'bottom' },
                        buttons: [{ text: 'Next', action: tour.next }]
                    });
                    tour.addStep({
                        id: 'step-2',
                        title: 'Upload Your First Asset',
                        text: 'Next, upload an asset like a logo or marketing material to your central Asset Hub.',
                        attachTo: { element: '#dashboard-actions > button:last-child', on: 'bottom' },
                        buttons: [
                            { text: 'Back', action: tour.back, secondary: true },
                            { text: 'Next', action: tour.next }
                        ]
                    });
                    tour.addStep({
                        id: 'step-3',
                        title: 'Invite Your Team',
                        text: 'Finally, invite your colleagues to collaborate with you in Stella.',
                        attachTo: { element: '[data-tour-step="3"]', on: 'bottom' },
                        buttons: [
                            { text: 'Back', action: tour.back, secondary: true },
                            { text: 'Finish', action: tour.complete }
                        ]
                    });

                    tour.start();
                }

                if (!ls.get(tourKey, false) && !gettingStartedState.completed && !gettingStartedState.dismissed) {
                    welcomeModal.classList.add('active');
                }

                document.getElementById('start-tour-btn').addEventListener('click', () => {
                    welcomeModal.classList.remove('active');
                    setTimeout(startGuidedTour, 300);
                });

                document.getElementById('skip-tour-btn').addEventListener('click', () => {
                    welcomeModal.classList.remove('active');
                    ls.set(tourKey, true);
                });
            }
            
            // --- SHARE MODAL SETUP ---
            function setupShareModal() {
                const modal = document.getElementById('share-modal');
                const copyLinkBtn = document.getElementById('copy-share-link-btn');
                const sendInviteBtn = document.getElementById('send-share-invite-btn');
                const inviteEmailInput = document.getElementById('share-invite-email');
                const permissionSelect = document.getElementById('share-permission-select');
                const sharedWithList = document.getElementById('shared-with-list');
                const revokeBtn = document.getElementById('revoke-share-btn');
                
                let currentShareData = {
                    type: '',
                    name: '',
                    id: null,
                    sharedWith: []
                };
                
                // Copy share link
                if (copyLinkBtn) {
                    copyLinkBtn.addEventListener('click', () => {
                        const linkInput = document.getElementById('share-link-input');
                        linkInput.select();
                        navigator.clipboard.writeText(linkInput.value).then(() => {
                            showToast('Share link copied to clipboard!', 'success');
                            
                            // Track activity
                            const shareType = currentShareData.type === 'brand-kit' ? 'brand kit' : 'asset';
                            trackActivity('share', `Copied share link for ${shareType}: ${currentShareData.name}`, { 
                                type: currentShareData.type, 
                                name: currentShareData.name, 
                                id: currentShareData.id 
                            });
                            
                            copyLinkBtn.innerHTML = '<i class="fas fa-check"></i>';
                            setTimeout(() => {
                                copyLinkBtn.innerHTML = '<i class="fas fa-copy"></i>';
                            }, 2000);
                        });
                    });
                }
                
                // Send share invite
                if (sendInviteBtn) {
                    sendInviteBtn.addEventListener('click', () => {
                        const email = inviteEmailInput.value.trim();
                        const permission = permissionSelect.value;
                        
                        if (!email) {
                            showToast('Please enter an email address', 'error');
                            return;
                        }
                        
                        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                            showToast('Please enter a valid email address', 'error');
                            return;
                        }
                        
                        // Check if already shared with this email
                        if (currentShareData.sharedWith.some(item => item.email === email)) {
                            showToast('Already shared with this user', 'info');
                            return;
                        }
                        
                        // Add to shared list
                        currentShareData.sharedWith.push({
                            email: email,
                            permission: permission,
                            addedAt: new Date().toISOString()
                        });
                        
                        renderSharedWithList();
                        inviteEmailInput.value = '';
                        showToast(`Invite sent to ${email}`, 'success');
                        logActivity('fa-share-alt', 'You shared content', `with ${email}`, 'Just now');
                    });
                }
                
                // Revoke all access
                if (revokeBtn) {
                    revokeBtn.addEventListener('click', () => {
                        if (confirm('Are you sure you want to revoke access for all collaborators?')) {
                            currentShareData.sharedWith = [];
                            renderSharedWithList();
                            showToast('All access revoked', 'info');
                        }
                    });
                }
                
                function renderSharedWithList() {
                    if (!sharedWithList) return;
                    
                    if (currentShareData.sharedWith.length === 0) {
                        sharedWithList.innerHTML = '<p class="text-xs text-[var(--text-secondary)] text-center py-2">No collaborators yet</p>';
                        return;
                    }
                    
                    sharedWithList.innerHTML = currentShareData.sharedWith.map(item => `
                        <div class="flex items-center justify-between p-2 bg-white/5 rounded-lg">
                            <div class="flex items-center space-x-2 flex-1">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center text-white text-xs font-bold">
                                    ${item.email.charAt(0).toUpperCase()}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate">${item.email}</p>
                                    <p class="text-xs text-[var(--text-secondary)]">${item.permission === 'view' ? 'Can View' : 'Can Edit'}</p>
                                </div>
                            </div>
                            <button class="remove-collaborator-btn text-[#9c7ead] hover:text-red-500 text-sm" data-email="${item.email}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `).join('');
                    
                    // Add remove handlers
                    sharedWithList.querySelectorAll('.remove-collaborator-btn').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const emailToRemove = btn.dataset.email;
                            currentShareData.sharedWith = currentShareData.sharedWith.filter(item => item.email !== emailToRemove);
                            renderSharedWithList();
                            showToast('Collaborator removed', 'info');
                        });
                    });
                }
                
                window.openShareModal = function(type, name, id) {
                    currentShareData = {
                        type: type,
                        name: name,
                        id: id,
                        sharedWith: []
                    };
                    
                    // Update modal title and content
                    document.getElementById('share-modal-title').textContent = `Share ${type === 'brand-kit' ? 'Brand Kit' : 'Asset'}`;
                    document.getElementById('share-item-name').textContent = name;
                    
                    // Get the actual share link from the asset
                    let shareUrl = '';
                    if (type === 'asset') {
                        const asset = assets.find(a => a.id === id);
                        shareUrl = asset ? asset.url : '';
                    } else if (type === 'brand-kit') {
                        const kit = brandKits.find(k => k.id === id);
                        // For brand kits, could create a special brand kit view page
                        shareUrl = kit ? `${window.location.origin}/view-brand-kit/${id}` : '';
                    }
                    
                    document.getElementById('share-link-input').value = shareUrl || 'Generating...';
                    
                    renderSharedWithList();
                    modal.classList.add('active');
                };
            }

            // --- GETTING STARTED SETUP ---
            function setupGettingStarted() {
                const dismissBtn = document.getElementById('dismiss-getting-started');
                if (dismissBtn) {
                    dismissBtn.addEventListener('click', () => {
                        const modal = document.getElementById('confirmation-modal');
                        const title = document.getElementById('confirmation-title');
                        const body = document.getElementById('confirmation-body');
                        const btnContainer = document.getElementById('confirmation-btn-container');
                        
                        title.textContent = 'Dismiss Getting Started?';
                        body.innerHTML = '<p class="text-[var(--text-secondary)]">Are you sure you want to dismiss this? You can always complete these steps later, but this card won\'t show again.</p>';
                        btnContainer.innerHTML = `
                            <button id="cancel-dismiss-btn" class="glow-btn btn-secondary px-4 py-2 rounded-full mr-2">Cancel</button>
                            <button id="confirm-dismiss-btn" class="glow-btn btn-primary px-4 py-2 rounded-full">Dismiss</button>
                        `;
                        
                        modal.classList.add('active');
                        
                        document.getElementById('cancel-dismiss-btn').addEventListener('click', () => {
                            modal.classList.remove('active');
                        });
                        
                        document.getElementById('confirm-dismiss-btn').addEventListener('click', () => {
                            gettingStartedState.dismissed = true;
                            ls.set(getUserDataKey('stella_getting_started'), gettingStartedState);
                            document.getElementById('getting-started-card').style.display = 'none';
                            modal.classList.remove('active');
                            showToast('Getting Started dismissed', 'info');
                        });
                    });
                }
            }


            // --- TEAM MANAGEMENT ---
            async function loadTeamMembersFromAPI() {
                try {
                    const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                    if (!currentUser) return;

                    const response = await fetch('/api/team.php?action=list-members', {
                        headers: getAuthHeaders()
                    });
                    
                    if (!response.ok) {
                        console.error('Failed to load team members:', response.status, response.statusText);
                        return;
                    }
                    
                    const data = await response.json();

                    if (data && data.success && data.members) {
                        // Update team members with data from API
                        teamMembers = data.members.map(member => ({
                            id: member.id,
                            name: member.full_name,
                            email: member.email,
                            role: member.team_role || member.role,
                            status: member.team_status || 'active',
                            last_active: member.last_active,
                            created_at: member.created_at,
                            updated_at: member.updated_at
                        }));

                        // Save to local storage for offline access
                        ls.set(getUserDataKey('stella_teamMembers'), teamMembers);

                        // Update UI
                        renderTeamMembers();
                        renderTeamOverview();
                        
                        // Update navigation permissions after team members are loaded
                        updateNavigationPermissions();
                    }
                } catch (error) {
                    console.error('Error loading team members from API:', error);
                }
            }

            async function loadPendingInvitesFromAPI() {
                try {
                    const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                    if (!currentUser) return;

                    const response = await fetch('/api/team.php?action=list-pending-invites', {
                        headers: getAuthHeaders()
                    });
                    
                    if (!response.ok) {
                        console.error('Failed to load pending invites:', response.status, response.statusText);
                        return;
                    }
                    
                    const data = await response.json();

                    if (data && data.success && data.invites) {
                        // Update pending invites with data from API
                        pendingInvites = data.invites.map(invite => ({
                            id: invite.id,
                            email: invite.email,
                            role: invite.role,
                            token: invite.token,
                            created_at: invite.created_at,
                            expires_at: invite.expires_at
                        }));

                        // Save to local storage for offline access
                        ls.set(getUserDataKey('stella_pendingInvites'), pendingInvites);

                        // Update UI
                        renderTeamMembers();
                        renderTeamOverview();
                    }
                } catch (error) {
                    console.error('Error loading pending invites from API:', error);
                }
            }

            // Helper function to check if current user is owner
            function isCurrentUserOwner() {
                const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                if (!currentUser) return false;
                const currentUserMember = teamMembers.find(member => member.id === currentUser.id);
                return currentUserMember && currentUserMember.role === 'Owner';
            }

            // Helper function to check if current user has admin permissions
            function hasAdminPermissions() {
                const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                if (!currentUser) return false;
                
                // Find the user in team members to get their actual role
                const currentUserMember = teamMembers.find(member => member.id === currentUser.id);
                
                // Only grant admin permissions if user is explicitly marked as Owner or Admin
                if (currentUserMember && ['Owner', 'Admin'].includes(currentUserMember.role)) {
                    return true;
                }
                
                // If not found in team members, check if user is the workspace owner
                // Only if they have workspace_owner_id set to their own ID (not null/undefined)
                if (currentUser.workspace_owner_id && currentUser.workspace_owner_id === currentUser.id) {
                    return true;
                }
                
                return false;
            }

            function renderTeamMembers() {
                const tbody = document.getElementById('team-members-tbody');
                if(!tbody) return; 
                
                const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                tbody.innerHTML = '';
                
                if (!currentUser) return;
                
                // Find current user's role in the team
                const currentUserMember = teamMembers.find(member => member.id === currentUser.id);
                const isOwner = currentUserMember && currentUserMember.role === 'Owner';
                const userRole = currentUserMember ? currentUserMember.role : 'Team Member';
                const roleIcon = getRoleIcon(userRole);
                const roleColor = getRoleColor(userRole);
                
                // Current user row with enhanced styling
                tbody.innerHTML += `
                    <tr class="border-b border-[var(--border-dark)] bg-gradient-to-r from-purple-500/5 to-transparent">
                        <td class="p-3 md:p-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-br ${roleColor} rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="${roleIcon} text-white text-xs md:text-sm"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-sm md:text-base truncate">${currentUser.full_name} (You)</p>
                                    <p class="sm:hidden text-xs text-[var(--text-secondary)] truncate">${currentUser.email}</p>
                                </div>
                            </div>
                        </td>
                        <td class="p-3 md:p-4 hidden sm:table-cell text-[var(--text-secondary)] text-sm">
                            <span class="truncate block">${currentUser.email}</span>
                        </td>
                        <td class="p-3 md:p-4">
                            <span class="status-badge bg-gradient-to-r ${roleColor} text-white px-2 md:px-3 py-1 rounded-full text-xs font-medium inline-block">
                                <i class="${roleIcon} mr-1"></i>${userRole}
                            </span>
                        </td>
                        <td class="p-3 md:p-4 text-right text-[var(--text-secondary)]">
                            <span class="text-xs">${isOwner ? 'Full Access' : 'Limited Access'}</span>
                        </td>
                    </tr>
                `;

                // Team members with enhanced styling (exclude owner)
                teamMembers.filter(member => member.id !== currentUser.id).forEach(member => {
                    const roleIcon = getRoleIcon(member.role);
                    const roleColor = getRoleColor(member.role);
                    const lastActive = member.last_active ? formatLastActive(member.last_active) : 'Recently';
                    
                    const row = `
                        <tr class="border-b border-[var(--border-dark)] hover:bg-white/5 transition-colors">
                            <td class="p-3 md:p-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-br ${roleColor} rounded-full flex items-center justify-center flex-shrink-0">
                                        <i class="${roleIcon} text-white text-xs md:text-sm"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium text-sm md:text-base truncate">${member.name}</p>
                                        <p class="sm:hidden text-xs text-[var(--text-secondary)] truncate">${member.email}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 md:p-4 hidden sm:table-cell text-[var(--text-secondary)] text-sm">
                                <span class="truncate block">${member.email}</span>
                            </td>
                            <td class="p-3 md:p-4">
                                <span class="status-badge ${roleColor} text-white px-2 md:px-3 py-1 rounded-full text-xs font-medium inline-block">
                                    <i class="${roleIcon} mr-1"></i>${member.role}
                                </span>
                            </td>
                            <td class="p-3 md:p-4 text-right">
                                ${hasAdminPermissions() ? `
                                <div class="flex flex-col sm:flex-row gap-1 sm:gap-2">
                                    <button data-id="${member.id}" class="edit-member-btn text-green-400 hover:text-green-300 hover:bg-green-500/10 px-2 md:px-3 py-1 rounded-md transition-colors text-xs md:text-sm min-h-[32px] md:min-h-[36px]">
                                        <i class="fas fa-edit mr-1"></i><span class="hidden sm:inline">Edit</span>
                                    </button>
                                    <button data-id="${member.id}" class="remove-member-btn text-[#9c7ead] hover:text-red-500 hover:bg-red-500/10 px-2 md:px-3 py-1 rounded-md transition-colors text-xs md:text-sm min-h-[32px] md:min-h-[36px]">
                                        <i class="fas fa-user-times mr-1"></i><span class="hidden sm:inline">Remove</span>
                                    </button>
                                </div>
                                ` : '<span class="text-xs text-[var(--text-secondary)]">View Only</span>'}
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
                
                // Pending invites with enhanced styling (only show for admin users)
                if (hasAdminPermissions()) {
                pendingInvites.forEach(invite => {
                    const roleText = invite.role || 'Team Member';
                    const roleIcon = getRoleIcon(roleText);
                    const roleColor = getRoleColor(roleText);
                    const inviteDate = invite.created_at ? formatInviteDate(invite.created_at) : 'Recently';
                    
                    const row = `
                        <tr class="border-b border-[var(--border-dark)] opacity-80 hover:opacity-100 transition-opacity">
                            <td class="p-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-full flex items-center justify-center">
                                        <i class="fas fa-clock text-white text-sm"></i>
                                    </div>
                                    <div>
                                <p class="font-medium">${invite.email}</p>
                                <p class="sm:hidden text-xs text-[var(--text-secondary)]">${roleText} - PENDING</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 hidden sm:table-cell text-[var(--text-secondary)]">${invite.email}</td>
                            <td class="p-4">
                                <span class="status-badge bg-gradient-to-r from-yellow-500 to-orange-500 text-black px-3 py-1 rounded-full text-xs font-medium">
                                    <i class="fas fa-clock mr-1"></i>${roleText} (PENDING)
                                </span>
                            </td>
                            <td class="p-4 text-right space-x-2 whitespace-nowrap">
                                <span class="text-xs text-[var(--text-secondary)] mr-2">Invited ${inviteDate}</span>
                                ${hasAdminPermissions() ? `
                                <button data-email="${invite.email}" class="revoke-invite-btn text-[#9c7ead] hover:text-red-500 hover:bg-red-500/10 px-3 py-1 rounded-md transition-colors text-sm">
                                    <i class="fas fa-times mr-1"></i>Revoke
                                </button>
                                ` : ''}
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
                }
            }

            // Helper functions for team member rendering
            function getRoleIcon(role) {
                switch(role) {
                    case 'Admin': return 'fas fa-shield-alt';
                    case 'Team Member': return 'fas fa-user';
                    case 'Viewer': return 'fas fa-eye';
                    default: return 'fas fa-user';
                }
            }

            function getRoleColor(role) {
                switch(role) {
                    case 'Admin': return 'from-blue-500 to-blue-600';
                    case 'Team Member': return 'from-green-500 to-green-600';
                    case 'Viewer': return 'from-gray-500 to-gray-600';
                    default: return 'from-gray-500 to-gray-600';
                }
            }

            function formatLastActive(lastActive) {
                const now = new Date();
                const active = new Date(lastActive);
                const diffMs = now - active;
                const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
                
                if (diffDays === 0) return 'Today';
                if (diffDays === 1) return 'Yesterday';
                if (diffDays < 7) return `${diffDays} days ago`;
                return active.toLocaleDateString();
            }

            function formatInviteDate(createdAt) {
                const now = new Date();
                const created = new Date(createdAt);
                const diffMs = now - created;
                const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
                
                if (diffDays === 0) return 'today';
                if (diffDays === 1) return 'yesterday';
                if (diffDays < 7) return `${diffDays} days ago`;
                return created.toLocaleDateString();
            }

            function setupTeamActions() {
                const settingsPage = document.getElementById('team-settings-page');
                const editModal = document.getElementById('edit-member-modal');

                // Enhanced team actions with validation
                settingsPage.addEventListener('click', (e) => {
                    const revokeBtn = e.target.closest('.revoke-invite-btn');
                    const removeBtn = e.target.closest('.remove-member-btn');
                    const editBtn = e.target.closest('.edit-member-btn');

                    if(revokeBtn) {
                        const email = revokeBtn.dataset.email;
                        showConfirmationModal(
                            'Revoke Invitation',
                            `Are you sure you want to revoke the invitation sent to <strong>${email}</strong>?<br><br>This action cannot be undone.`,
                            () => {
                                revokeInvite(email);
                            }
                        );
                        return;
                    }

                    if(removeBtn) {
                        const memberId = parseInt(removeBtn.dataset.id);
                        const member = teamMembers.find(m => m.id === memberId);
                        if(member) {
                            showConfirmationModal(
                                'Remove Team Member',
                                `Are you sure you want to remove <strong>${member.name}</strong> from your team?<br><br>They will lose access to all team resources immediately.`,
                                () => {
                                    removeMember(memberId);
                                }
                            );
                        }
                        return;
                    }

                    if(editBtn) {
                        const memberId = parseInt(editBtn.dataset.id);
                        const member = teamMembers.find(m => m.id === memberId);
                        if(member) {
                            openEditMemberModal(member);
                        }
                        return;
                    }
                });

                // Enhanced save member changes with validation
                document.getElementById('save-member-changes-btn').addEventListener('click', () => {
                    saveMemberChanges();
                });
                
                document.getElementById('ban-ip-btn').addEventListener('click', () => {
                     showToast('IP Ban functionality is not yet implemented.', 'info');
                });

                document.getElementById('reset-password-btn').addEventListener('click', () => {
                    const memberId = parseInt(editModal.querySelector('#edit-member-id').value);
                    const member = teamMembers.find(m => m.id === memberId);
                    if (member) {
                        showConfirmationModal(
                            'Reset Password',
                            `Are you sure you want to reset the password for <strong>${member.name}</strong>?<br><br>They will receive an email with instructions to set a new password.`,
                            () => {
                                showToast('Password reset email sent successfully!', 'success');
                                logActivity('fas fa-key', 'Reset password', `Reset password for ${member.name}`, new Date().toLocaleTimeString());
                            }
                        );
                    }
                });

                document.getElementById('view-activity-btn').addEventListener('click', () => {
                    const memberId = parseInt(editModal.querySelector('#edit-member-id').value);
                    const member = teamMembers.find(m => m.id === memberId);
                    if (member) {
                        showToast(`Viewing activity for ${member.name} - Feature coming soon!`, 'info');
                    }
                });

                // Role change handler
                document.getElementById('edit-member-role').addEventListener('change', (e) => {
                    const roleInfo = document.getElementById('role-info');
                    if (roleInfo) {
                        roleInfo.innerHTML = getRoleDescription(e.target.value);
                    }
                });

                // Download requests functionality
                setupDownloadRequests();
            }

            // Download requests management
            async function setupDownloadRequests() {
                const tbody = document.getElementById('download-requests-tbody');
                if (!tbody) return;

                // Load download requests
                await loadDownloadRequests();

                // Add event listeners for approve/deny buttons
                tbody.addEventListener('click', async (e) => {
                    const approveBtn = e.target.closest('.approve-request-btn');
                    const denyBtn = e.target.closest('.deny-request-btn');

                    if (approveBtn) {
                        const requestId = parseInt(approveBtn.dataset.id);
                        await handleDownloadRequest(requestId, 'approve');
                    }

                    if (denyBtn) {
                        const requestId = parseInt(denyBtn.dataset.id);
                        await handleDownloadRequest(requestId, 'deny');
                    }
                });
            }

            async function loadDownloadRequests() {
                try {
                    const response = await fetch('/api/download_requests.php?action=list', {
                        headers: getAuthHeaders()
                    });
                    const data = await response.json();

                    if (data.success) {
                        renderDownloadRequests(data.requests);
                    } else {
                        console.error('Failed to load download requests:', data.message);
                    }
                } catch (error) {
                    console.error('Error loading download requests:', error);
                }
            }

            function renderDownloadRequests(requests) {
                const tbody = document.getElementById('download-requests-tbody');
                if (!tbody) return;

                if (requests.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="p-8 text-center text-[var(--text-secondary)]">
                                <i class="fas fa-download text-4xl mb-4"></i>
                                <p>No download requests yet</p>
                            </td>
                        </tr>
                    `;
                    return;
                }

                tbody.innerHTML = requests.map(request => {
                    const statusClass = {
                        'pending': 'bg-yellow-500/10 text-yellow-500 border-yellow-500/30',
                        'approved': 'bg-green-500/10 text-green-500 border-green-500/30',
                        'denied': 'bg-red-500/10 text-red-500 border-red-500/30'
                    }[request.status] || 'bg-gray-500/10 text-gray-500 border-gray-500/30';

                    const requestedDate = new Date(request.requested_at).toLocaleDateString();
                    const requestedTime = new Date(request.requested_at).toLocaleTimeString();

                    return `
                        <tr class="border-b border-[var(--border-dark)]">
                            <td class="p-3">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-file-alt text-[var(--text-secondary)]"></i>
                                    <div>
                                        <p class="font-medium">${request.asset_name}</p>
                                        <p class="text-xs text-[var(--text-secondary)]">${request.asset_type}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 hidden sm:table-cell">
                                <div>
                                    <p class="font-medium">${request.requester_name}</p>
                                    <p class="text-xs text-[var(--text-secondary)]">${request.requester_email}</p>
                                </div>
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded-full text-xs border ${statusClass}">
                                    ${request.status.charAt(0).toUpperCase() + request.status.slice(1)}
                                </span>
                            </td>
                            <td class="p-3 hidden md:table-cell">
                                <div>
                                    <p class="text-sm">${requestedDate}</p>
                                    <p class="text-xs text-[var(--text-secondary)]">${requestedTime}</p>
                                </div>
                            </td>
                            <td class="p-3 text-right">
                                ${request.status === 'pending' ? `
                                    <div class="flex space-x-2 justify-end">
                                        <button class="approve-request-btn px-3 py-1 bg-green-500/10 text-green-500 border border-green-500/30 rounded-full text-xs hover:bg-green-500/20 transition-colors" data-id="${request.id}">
                                            <i class="fas fa-check mr-1"></i>Approve
                                        </button>
                                        <button class="deny-request-btn px-3 py-1 bg-[#9c7ead]/10 text-[#9c7ead] border border-[#9c7ead]/30 rounded-full text-xs hover:bg-red-500/20 hover:text-red-500 hover:border-red-500/30 transition-colors" data-id="${request.id}">
                                            <i class="fas fa-times mr-1"></i>Deny
                                        </button>
                                    </div>
                                ` : `
                                    <span class="text-xs text-[var(--text-secondary)]">
                                        ${request.reviewed_at ? `Reviewed ${new Date(request.reviewed_at).toLocaleDateString()}` : ''}
                                    </span>
                                `}
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            async function handleDownloadRequest(requestId, action) {
                try {
                    const response = await fetch(`/api/download_requests.php?action=${action}`, {
                        method: 'POST',
                        headers: {
                            ...getAuthHeaders(),
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            request_id: requestId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast(`Download request ${action}d successfully`, 'success');
                        await loadDownloadRequests(); // Reload the list
                    } else {
                        showToast(data.message || `Failed to ${action} download request`, 'error');
                    }
                } catch (error) {
                    console.error(`Error ${action}ing download request:`, error);
                    showToast(`Failed to ${action} download request`, 'error');
                }
            }

            // Enhanced team management functions
            async function revokeInvite(email) {
                try {
                    const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                    if (!currentUser) {
                        showToast('Please log in to perform this action', 'error');
                        return;
                    }

                    // Find the invite to get the token
                    const invite = pendingInvites.find(inv => inv.email === email);
                    if (!invite) {
                        showToast('Invitation not found', 'error');
                        return;
                    }

                    // Call API to revoke the invitation
                    const response = await fetch('/api/team.php?action=revoke-invite', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-User-ID': currentUser.id,
                            'X-User-Email': currentUser.email
                        },
                        body: JSON.stringify({
                            email: email,
                            token: invite.token
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Remove from local storage
                            pendingInvites = pendingInvites.filter(inv => inv.email !== email);
                            ls.set(getUserDataKey('stella_pendingInvites'), pendingInvites);
                        
                        // Update UI
                            renderTeamMembers();
                            renderTeamOverview();
                        
                        showToast(`Invitation to ${email} has been revoked`, 'success');
                        logActivity('fas fa-user-times', 'Revoked invitation', `Revoked invitation for ${email}`, new Date().toLocaleTimeString());
                    } else {
                        showToast(data.message || 'Failed to revoke invitation', 'error');
                    }
                } catch (error) {
                    console.error('Error revoking invite:', error);
                    showToast('Failed to revoke invitation', 'error');
                }
            }

            async function removeMember(memberId) {
                try {
                    const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                    if (!currentUser) {
                        showToast('Please log in to perform this action', 'error');
                        return;
                    }

                    const member = teamMembers.find(m => m.id === memberId);
                    if (!member) {
                        showToast('Member not found', 'error');
                        return;
                    }

                    // Call API to remove member
                    const response = await fetch('/api/team.php?action=remove-member', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-User-ID': currentUser.id,
                            'X-User-Email': currentUser.email
                        },
                        body: JSON.stringify({
                            email: member.email
                        })
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        // Remove from local storage
                        teamMembers = teamMembers.filter(m => m.id !== memberId);
                        ls.set(getUserDataKey('stella_teamMembers'), teamMembers);
                        
                        // Update UI
                        renderTeamMembers();
                        renderTeamOverview();
                        
                        // Track activity
                        trackActivity('team', `Removed team member: ${member.name} (${member.email})`, { 
                            member_id: memberId, 
                            member_email: member.email 
                        });
                        
                        showToast('Team member removed successfully', 'success');
                    } else {
                        showToast(data.message || 'Failed to remove team member', 'error');
                    }
                } catch (error) {
                    console.error('Error removing member:', error);
                    showToast('Failed to remove member', 'error');
                }
            }

            function openEditMemberModal(member) {
                const editModal = document.getElementById('edit-member-modal');
                if (!editModal) {
                    showToast('Edit modal not found', 'error');
                    return;
                }

                // Populate modal with member data
                editModal.querySelector('#edit-member-id').value = member.id;
                editModal.querySelector('#edit-member-email').textContent = member.email;
                editModal.querySelector('#edit-member-name').value = member.name || '';
                editModal.querySelector('#edit-member-role').value = member.role;
                
                // Update role description
                updateRoleDescription(member.role);
                
                // Setup action button handlers
                setupEditMemberActions(member);
                
                // Show role-specific information
                const roleInfo = editModal.querySelector('#role-info');
                if (roleInfo) {
                    roleInfo.innerHTML = getRoleDescription(member.role);
                }
                
                            editModal.classList.add('active');
                        }

            async function saveMemberChanges() {
                const editModal = document.getElementById('edit-member-modal');
                if (!editModal) return;

                const memberId = parseInt(editModal.querySelector('#edit-member-id').value);
                const newRole = editModal.querySelector('#edit-member-role').value;
                const newName = editModal.querySelector('#edit-member-name').value.trim();
                const memberIndex = teamMembers.findIndex(m => m.id === memberId);

                if (memberIndex === -1) {
                    showToast('Member not found', 'error');
                    return;
                }

                const member = teamMembers[memberIndex];
                const oldRole = member.role;
                const oldName = member.name;

                // Validate role change
                if (newRole !== oldRole) {
                    if (!confirm(`Are you sure you want to change ${member.name}'s role from ${oldRole} to ${newRole}?`)) {
                        return;
                    }
                }

                try {
                    const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                    if (!currentUser) {
                        showToast('Please log in to perform this action', 'error');
                        return;
                    }

                    // Call API to update role if changed
                    if (newRole !== oldRole) {
                        const response = await fetch('/api/team.php?action=update-role', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-User-ID': currentUser.id,
                                'X-User-Email': currentUser.email
                            },
                            body: JSON.stringify({
                                email: member.email,
                                role: newRole
                            })
                        });

                        const data = await response.json();
                        if (!data.success) {
                            showToast(data.message || 'Failed to update role', 'error');
                            return;
                        }
                    }

                    // Update member data locally
                    teamMembers[memberIndex].role = newRole;
                    if (newName && newName !== oldName) {
                        teamMembers[memberIndex].name = newName;
                    }
                    teamMembers[memberIndex].updated_at = new Date().toISOString();

                    // Save to local storage
                    ls.set(getUserDataKey('stella_teamMembers'), teamMembers);
                    
                    // Update UI
                    renderTeamMembers();
                    editModal.classList.remove('active');
                    
                    // Show success message
                    let message = 'Member updated successfully';
                    if (newRole !== oldRole) {
                        message = `${member.name}'s role changed from ${oldRole} to ${newRole}`;
                    }
                    showToast(message, 'success');
                    
                    // Track activity
                    trackActivity('team', `Updated team member: ${member.name} - Role: ${oldRole} → ${newRole}`, { 
                        member_id: memberId, 
                        member_email: member.email,
                        old_role: oldRole,
                        new_role: newRole
                    });
                } catch (error) {
                    console.error('Error updating member:', error);
                    showToast('Failed to update member', 'error');
                }
            }

            function updateRoleDescription(role) {
                const roleInfo = document.getElementById('role-info');
                if (roleInfo) {
                    roleInfo.innerHTML = getRoleDescription(role);
                }
            }

            function getRoleDescription(role) {
                const descriptions = {
                    'Owner': 'Full access to all features and settings. Cannot be changed.',
                    'Admin': 'Can manage team members, settings, and all content. Cannot change owner.',
                    'Team Member': 'Can create, edit, and download content. Cannot manage team.',
                    'Viewer': 'Can only view content. Cannot create, edit, or download.'
                };
                return descriptions[role] || 'Role description not available';
            }

            function setupEditMemberActions(member) {
                // Ban IP Address button
                const banIpBtn = document.getElementById('ban-ip-btn');
                if (banIpBtn) {
                    banIpBtn.onclick = () => banMemberIP(member);
                }

                // Reset Password button
                const resetPasswordBtn = document.getElementById('reset-password-btn');
                if (resetPasswordBtn) {
                    resetPasswordBtn.onclick = () => resetMemberPassword(member);
                }

                // View Activity button
                const viewActivityBtn = document.getElementById('view-activity-btn');
                if (viewActivityBtn) {
                    viewActivityBtn.onclick = () => viewMemberActivity(member);
                }

                // Role change handler
                const roleSelect = document.getElementById('edit-member-role');
                if (roleSelect) {
                    roleSelect.onchange = (e) => updateRoleDescription(e.target.value);
                }
            }

            function banMemberIP(member) {
                showConfirmationModal(
                    'Ban IP Address',
                    `Are you sure you want to ban the IP address for <strong>${member.name}</strong>?<br><br>This will prevent them from accessing the platform from their current IP address.`,
                    () => {
                        // TODO: Implement IP banning functionality
                        showToast('IP banning functionality not yet implemented', 'info');
                    }
                );
            }

            function resetMemberPassword(member) {
                showConfirmationModal(
                    'Reset Password',
                    `Are you sure you want to reset the password for <strong>${member.name}</strong>?<br><br>They will receive an email with instructions to set a new password.`,
                    () => {
                        // TODO: Implement password reset functionality
                        showToast('Password reset functionality not yet implemented', 'info');
                    }
                );
            }

            function viewMemberActivity(member) {
                // Filter activities for this member
                const memberActivities = activities.filter(activity => 
                    activity.user_email === member.email || 
                    activity.user_id === member.id
                );

                if (memberActivities.length === 0) {
                    showToast('No activity found for this member', 'info');
                    return;
                }

                // Create activity modal content
                let activityHtml = `
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <h4 class="font-medium text-white mb-3">Activity for ${member.name}</h4>
                `;

                memberActivities.slice(0, 10).forEach(activity => {
                    activityHtml += `
                        <div class="bg-white/5 rounded-lg p-3 border border-white/10">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <i class="${activity.icon} text-[#9c7ead]"></i>
                                    <span class="text-sm text-white">${activity.action}</span>
                                </div>
                                <span class="text-xs text-[var(--text-secondary)]">${activity.time}</span>
                            </div>
                            <p class="text-xs text-[var(--text-secondary)] mt-1">${activity.description}</p>
                        </div>
                    `;
                });

                activityHtml += '</div>';

                showConfirmationModal(
                    'Member Activity',
                    activityHtml,
                    null,
                    'Close'
                );
            }

            // --- DASHBOARD DATA ---
            function logActivity(icon, message, detail, time) {
                activities.unshift({ icon, message, detail, time });
                if (activities.length > 5) {
                    activities.pop();
                }
                ls.set(getUserDataKey('stella_activities'), activities);
                renderActivities();
            }

            // Track activity to database
            async function trackActivity(type, description, metadata = {}) {
                try {
                    const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                    if (!currentUser) return;

                    await fetch('/api/activities.php?action=track', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-User-ID': currentUser.id,
                            'X-User-Email': currentUser.email
                        },
                        body: JSON.stringify({
                            type: type,
                            description: description,
                            metadata: metadata
                        })
                    });
                } catch (error) {
                    console.error('Failed to track activity:', error);
                }
            }

            // Load activities from API
            async function loadActivitiesFromAPI() {
                try {
                    const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));
                    if (!currentUser || !currentUser.id) {
                        console.warn('No user session for activities');
                        return;
                    }

                    const response = await fetch('/api/activities.php?action=list&limit=20', {
                        credentials: 'include',
                        headers: {
                            'X-User-ID': currentUser.id?.toString() || '',
                            'X-User-Email': currentUser.email || ''
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.success) {
                            // Convert API activities to local format
                            activities = data.activities.map(activity => ({
                                icon: getActivityIcon(activity.type),
                                message: activity.description,
                                detail: `${activity.user_name} • ${activity.time_ago}`,
                                time: activity.time_ago
                            }));
                            
                            ls.set(getUserDataKey('stella_activities'), activities);
                            renderActivities();
                        }
                    }
                } catch (error) {
                    console.error('Failed to load activities:', error);
                }
            }

            // Get icon for activity type
            function getActivityIcon(type) {
                const icons = {
                    'download': 'fa-download',
                    'share': 'fa-share-alt',
                    'copy': 'fa-copy',
                    'upload': 'fa-upload',
                    'create': 'fa-plus',
                    'delete': 'fa-trash',
                    'edit': 'fa-edit',
                    'view': 'fa-eye',
                    'team': 'fa-users',
                    'general': 'fa-circle'
                };
                return icons[type] || 'fa-circle';
            }

            function renderActivities() {
                const list = document.getElementById('recent-activity-list');
                if(!list) return;
                list.innerHTML = '';
                if(activities.length === 0) {
                    list.innerHTML = `<p class="text-sm text-center text-[var(--text-secondary)] py-4">No recent activity.</p>`;
                    return;
                }
                activities.slice(0, 3).forEach(act => { // Show only top 3
                    list.innerHTML += `
                        <div class="flex items-start space-x-3 p-3 hover:bg-white/5 rounded-3xl transition-colors">
                            <div class="stat-icon w-10 h-10 text-lg flex-shrink-0"><i class="fas ${act.icon}"></i></div>
                            <div>
                                <p class="font-medium">${act.message}</p>
                                <p class="text-sm text-[var(--text-secondary)]">${act.detail}</p>
                                <p class="text-xs text-[var(--text-secondary)] opacity-70 mt-1">${act.time}</p>
                            </div>
                        </div>
                    `;
                });
            }

            function updateDashboardStats() {
                const currentUser = JSON.parse(sessionStorage.getItem('stella_user') || '{}');
                const userPlan = currentUser.plan_type || 'free';
                
                // Kits
                const kitsCount = brandKits.length;
                const kitsLimit = userPlan === 'pro' ? '∞' : 1;
                const kitsProgress = userPlan === 'pro' ? 0 : Math.min((kitsCount / 1) * 100, 100);
                document.getElementById('usage-kits-count').innerHTML = `${kitsCount} <span class="text-sm font-normal text-[var(--text-secondary)]">/ ${kitsLimit}</span>`;
                document.getElementById('usage-kits-progress').style.width = `${kitsProgress}%`;

                // Team Members
                const membersCount = teamMembers.length + 1; // +1 for current user
                const membersLimit = userPlan === 'pro' ? '∞' : 2;
                const membersProgress = userPlan === 'pro' ? 0 : Math.min((membersCount / 2) * 100, 100);
                const membersEl = document.getElementById('usage-members-count');
                if (membersEl) {
                    membersEl.innerHTML = `${membersCount} <span class="text-sm font-normal text-[var(--text-secondary)]">/ ${membersLimit}</span>`;
                }
                const membersProgressEl = document.getElementById('usage-members-progress');
                if (membersProgressEl) {
                    membersProgressEl.style.width = `${membersProgress}%`;
                }
                
                // Storage - calculate actual usage from assets
                const totalBytes = assets.reduce((sum, asset) => sum + (parseInt(asset.size) || 0), 0);
                const totalMB = (totalBytes / (1024 * 1024)).toFixed(0);
                const totalGB = (totalBytes / (1024 * 1024 * 1024)).toFixed(2);
                const storageLimit = userPlan === 'pro' ? '∞' : '1 GB';
                const storageProgress = userPlan === 'pro' ? 0 : Math.min((totalBytes / (1024 * 1024 * 1024)) * 100, 100);
                const storageEl = document.getElementById('usage-storage-count');
                if (storageEl) {
                    const display = totalGB >= 1 ? `${totalGB} GB` : `${totalMB} MB`;
                    storageEl.innerHTML = `${display} <span class="text-sm font-normal text-[var(--text-secondary)]">/ ${storageLimit}</span>`;
                }
                const storageProgressEl = document.getElementById('usage-storage-progress');
                if (storageProgressEl) {
                    storageProgressEl.style.width = `${storageProgress}%`;
                }
            }
            
            function renderTeamOverview() {
                const countEl = document.getElementById('team-overview-count');
                const listEl = document.getElementById('team-overview-list');
                const currentUser = JSON.parse(sessionStorage.getItem('stella_user'));

                if(!countEl || !listEl || !currentUser) return;
                
                // Filter out current user from teamMembers to avoid duplicates
                const filteredTeamMembers = teamMembers.filter(member => 
                    member.email !== currentUser.email && 
                    member.id !== currentUser.id &&
                    member.member_email !== currentUser.email
                );
                
                const totalMembers = filteredTeamMembers.length + 1 + pendingInvites.length;
                countEl.textContent = totalMembers;
                
                listEl.innerHTML = '';
                
                listEl.innerHTML += `
                    <div class="flex items-center space-x-3">
                        <img src="https://placehold.co/40x40/1a1a1a/9c7ead?text=U" alt="User" class="w-8 h-8 rounded-full">
                        <div><p class="font-medium text-sm">${currentUser.full_name} (You)</p><p class="text-xs text-[var(--text-secondary)]">Admin</p></div>
                    </div>`;

                // Show first 2 members or pending invites combined
                const combined = [...filteredTeamMembers, ...pendingInvites.map(inv => ({ name: inv.email, email: inv.email, role: 'Pending' }))];
                combined.slice(0, 2).forEach(member => {
                    const initial = member.name ? member.name.charAt(0).toUpperCase() : member.email.charAt(0).toUpperCase();
                    const isPending = member.role === 'Pending';
                     listEl.innerHTML += `
                        <div class="flex items-center space-x-3 ${isPending ? 'opacity-70' : ''}">
                            <img src="https://placehold.co/40x40/1a1a1a/9c7ead?text=${initial}" alt="${member.name || member.email}" class="w-8 h-8 rounded-full">
                            <div><p class="font-medium text-sm">${member.name || member.email}</p><p class="text-xs text-[var(--text-secondary)]">${member.role}</p></div>
                        </div>`;
                });
            }
            
            function renderGettingStarted() {
                const listEl = document.getElementById('getting-started-list');
                const progressEl = document.getElementById('getting-started-progress');
                const cardEl = document.getElementById('getting-started-card');
                if(!listEl || !progressEl || !cardEl) return;
                
                let completedCount = 0;
                
                if (gettingStartedState.kitCreated) completedCount++;
                if (gettingStartedState.assetUploaded) completedCount++;
                if (gettingStartedState.teamInvited) completedCount++;

                // Hide card permanently if completed or dismissed
                if (gettingStartedState.completed || gettingStartedState.dismissed) {
                    cardEl.style.display = 'none';
                    cardEl.style.marginBottom = '0';
                    return;
                }

                // Auto-complete and celebrate if all tasks done
                if (completedCount === 3 && !gettingStartedState.completed) {
                    gettingStartedState.completed = true;
                    ls.set(getUserDataKey('stella_getting_started'), gettingStartedState);
                    showCompletionCelebration();
                    setTimeout(() => {
                        cardEl.classList.add('hiding');
                        setTimeout(() => {
                            cardEl.style.display = 'none';
                        }, 800);
                    }, 3000);
                    return;
                }
                
                cardEl.style.display = 'block';

                const kitItem = `
                    <div data-tour-step="1" class="${gettingStartedState.kitCreated ? '' : 'opacity-70'} flex justify-between items-center p-3 bg-white/5 rounded-3xl hover:bg-white/10 transition-colors">
                        <div class="flex-1 pr-4 flex items-center"><div class="w-8 h-8 rounded-full ${gettingStartedState.kitCreated ? 'bg-primary text-white' : 'bg-[var(--border-dark)] text-white'} flex items-center justify-center mr-4 flex-shrink-0"><i class="fas ${gettingStartedState.kitCreated ? 'fa-check' : 'fa-swatchbook'}"></i></div>
                        <div><p class="font-medium">Create your first Brand Kit</p><p class="text-xs text-[var(--text-secondary)]">Organize your brand's identity and assets.</p></div></div>
                        <button onclick="document.querySelector('.nav-item[data-page=\\'brand-kits\\']').click()" class="glow-btn btn-secondary px-3 py-1 rounded-full text-sm flex-shrink-0">View</button>
                    </div>`;

                const assetItem = `
                    <div data-tour-step="2" class="${gettingStartedState.assetUploaded ? '' : 'opacity-70'} flex justify-between items-center p-3 bg-white/5 rounded-3xl hover:bg-white/10 transition-colors">
                        <div class="flex-1 pr-4 flex items-center"><div class="w-8 h-8 rounded-full ${gettingStartedState.assetUploaded ? 'bg-primary text-white' : 'bg-[var(--border-dark)] text-white'} flex items-center justify-center mr-4 flex-shrink-0"><i class="fas ${gettingStartedState.assetUploaded ? 'fa-check' : 'fa-upload'}"></i></div>
                        <div><p class="font-medium">Upload your first asset</p><p class="text-xs text-[var(--text-secondary)]">Add your logos, images, or documents.</p></div></div>
                        <button data-modal-target="upload-asset-detail-modal" class="glow-btn btn-secondary px-3 py-1 rounded-full text-sm flex-shrink-0">Upload</button>
                    </div>`;

                const teamItem = `
                    <div data-tour-step="3" class="${gettingStartedState.teamInvited ? '' : 'opacity-70'} flex justify-between items-center p-3 bg-white/5 rounded-3xl hover:bg-white/10 transition-opacity">
                        <div class="flex-1 pr-4 flex items-center"><div class="w-8 h-8 rounded-full ${gettingStartedState.teamInvited ? 'bg-primary text-white' : 'bg-[var(--border-dark)] text-white'} flex items-center justify-center mr-4 flex-shrink-0"><i class="fas ${gettingStartedState.teamInvited ? 'fa-check' : 'fa-users'}"></i></div>
                        <div><p class="font-medium">Invite your team</p><p class="text-xs text-[var(--text-secondary)]">Collaborate with your colleagues.</p></div></div>
                        <button data-modal-target="invite-member-modal" class="glow-btn btn-primary px-3 py-1 rounded-full text-sm flex-shrink-0">Invite</button>
                    </div>`;

                // Add a helpful banner if no tasks are completed
                let bannerHtml = '';
                if (completedCount === 0) {
                    bannerHtml = `
                        <div class="mb-4 p-3 bg-primary/10 rounded-xl border border-primary/20 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-lightbulb text-primary text-xl"></i>
                                <p class="text-sm"><span class="font-semibold text-white">New here?</span> <span class="text-[var(--text-secondary)]">Take our quick walkthrough to get started</span></p>
                            </div>
                            <button id="start-walkthrough-btn" class="glow-btn btn-primary px-3 py-1 rounded-full text-sm flex-shrink-0">
                                <i class="fas fa-play mr-1"></i> Start
                            </button>
                        </div>
                    `;
                }
                
                // Add progress bar
                const progressBarHtml = `
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-white">Your Progress</span>
                            <span class="text-sm font-semibold text-primary">${Math.round((completedCount / 3) * 100)}%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-bar-fill transition-all duration-500" style="width: ${(completedCount / 3) * 100}%;"></div>
                        </div>
                    </div>
                `;
                
                listEl.innerHTML = progressBarHtml + bannerHtml + kitItem + assetItem + teamItem;
                progressEl.textContent = `${completedCount} of 3 complete`;
                
                // Add walkthrough button listener
                const walkthroughBtn = document.getElementById('start-walkthrough-btn');
                if (walkthroughBtn) {
                    walkthroughBtn.addEventListener('click', () => {
                        startGettingStartedWalkthrough();
                    });
                }
                
                // Add celebration confetti effect when tasks complete
                if (completedCount > 0) {
                    const completedTasks = document.querySelectorAll('[data-tour-step] .fa-check');
                    completedTasks.forEach(task => {
                        task.parentElement.style.animation = 'pulse 0.5s ease-in-out';
                    });
                }
            }
            
            // Add walkthrough function
            function startGettingStartedWalkthrough() {
                const tour = new Shepherd.Tour({
                    useModalOverlay: true,
                    defaultStepOptions: {
                        classes: 'shadow-md',
                        scrollTo: { behavior: 'smooth', block: 'center' }
                    }
                });

                tour.addStep({
                    id: 'intro',
                    title: 'Welcome to Your Dashboard!',
                    text: 'This is your Getting Started checklist. Complete these three simple tasks to unlock the full power of Stella. We\'ll guide you through each one!',
                    attachTo: { element: '#getting-started-card', on: 'top' },
                    buttons: [
                        { text: 'Skip', action: tour.cancel, secondary: true },
                        { text: 'Next', action: tour.next }
                    ]
                });

                tour.addStep({
                    id: 'step-1',
                    title: 'Task 1: Create Your First Brand Kit',
                    text: 'A Brand Kit is where you organize your brand\'s identity. Click the "View" button to navigate to Brand Kits and create one.',
                    attachTo: { element: '[data-tour-step="1"]', on: 'bottom' },
                    buttons: [
                        { text: 'Back', action: tour.back, secondary: true },
                        { text: 'Next', action: tour.next }
                    ]
                });

                tour.addStep({
                    id: 'step-2',
                    title: 'Task 2: Upload Your First Asset',
                    text: 'Upload logos, images, documents, or any brand materials. Click "Upload" to add your first asset.',
                    attachTo: { element: '[data-tour-step="2"]', on: 'bottom' },
                    buttons: [
                        { text: 'Back', action: tour.back, secondary: true },
                        { text: 'Next', action: tour.next }
                    ]
                });

                tour.addStep({
                    id: 'step-3',
                    title: 'Task 3: Invite Your Team',
                    text: 'Collaboration is key! Click "Invite" to add team members who can help manage your brand assets.',
                    attachTo: { element: '[data-tour-step="3"]', on: 'bottom' },
                    buttons: [
                        { text: 'Back', action: tour.back, secondary: true },
                        { text: 'Got It!', action: tour.complete }
                    ]
                });

                tour.addStep({
                    id: 'final',
                    title: 'You\'re Ready!',
                    text: 'Complete each task at your own pace. Once you finish all three, this card will automatically disappear and you\'ll get a celebration!',
                    attachTo: { element: '#getting-started-progress', on: 'bottom' },
                    buttons: [
                        { text: 'Let\'s Do This!', action: tour.complete }
                    ]
                });

                tour.start();
            }
            
            function showCompletionCelebration() {
                const modal = document.getElementById('confirmation-modal');
                const title = document.getElementById('confirmation-title');
                const body = document.getElementById('confirmation-body');
                const btnContainer = document.getElementById('confirmation-btn-container');
                
                if (!modal || !title || !body || !btnContainer) return;
                
                title.textContent = 'Congratulations!';
                body.innerHTML = `
                    <div class="text-center py-4">
                        <div class="text-5xl mb-4 text-primary"><i class="fas fa-check-circle"></i></div>
                        <h3 class="text-2xl font-bold text-white mb-3">You're All Set!</h3>
                        <p class="text-[var(--text-secondary)] mb-4">You've completed all the getting started steps. You're now ready to manage your brand with Stella!</p>
                        <div class="space-y-2 text-left bg-white/5 rounded-xl p-4 mb-4">
                            <div class="flex items-center space-x-2 text-green-500"><i class="fas fa-check-circle"></i><span>Created your first Brand Kit</span></div>
                            <div class="flex items-center space-x-2 text-green-500"><i class="fas fa-check-circle"></i><span>Uploaded your first asset</span></div>
                            <div class="flex items-center space-x-2 text-green-500"><i class="fas fa-check-circle"></i><span>Invited your team</span></div>
                        </div>
                        <p class="text-sm text-[var(--text-secondary)]">The Getting Started card will now be hidden. Happy brand building!</p>
                    </div>
                `;
                btnContainer.innerHTML = '';
                
                // Hide default OK button since we don't need it
                const defaultOkBtn = document.getElementById('confirmation-ok-btn');
                if (defaultOkBtn) {
                    defaultOkBtn.style.display = 'none';
                }
                
                modal.classList.add('active');
                
                // Auto-close after 3 seconds
                setTimeout(() => {
                    modal.classList.remove('active');
                    if (defaultOkBtn) {
                        defaultOkBtn.style.display = '';
                    }
                }, 3000);
            }

            // --- UTILITY FUNCTIONS ---
            
            window.showToast = function(message, type = 'info') {
                const toast = document.getElementById('toast-notification');
                toast.textContent = message;
                toast.className = ''; 
                toast.classList.add(type, 'show');
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 3000);
            }
            
            // Show Pro upgrade prompt
            window.showProUpgradePrompt = function(featureName) {
                const modal = document.getElementById('confirmation-modal');
                const title = document.getElementById('confirmation-title');
                const body = document.getElementById('confirmation-body');
                const btnContainer = document.getElementById('confirmation-btn-container');
                const defaultOkBtn = document.getElementById('confirmation-ok-btn');
                
                if (!modal || !title || !body || !btnContainer) return;
                
                title.innerHTML = 'Upgrade to Pro';
                body.innerHTML = `
                    <div class="text-center py-4">
                        <div class="text-6xl mb-4" style="color: #9c7ead;">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-3">${featureName} is a Pro Feature</h3>
                        <p class="text-[var(--text-secondary)] mb-6">
                            Unlock the full power of Stella with advanced features like API Keys, Governance Rules, and Analytics.
                        </p>
                        <div class="space-y-3 text-left bg-white/5 rounded-xl p-4 mb-6">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>API Keys for external integrations</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>Brand Governance & Content Rules</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>Advanced Analytics & Insights</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>Priority Support</span>
                            </div>
                        </div>
                        <p class="text-sm text-[var(--text-secondary)]">
                            Starting at just <span class="text-white font-bold">$15/month</span>
                        </p>
                    </div>
                `;
                
                btnContainer.innerHTML = `
                    <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
                        <button class="btn btn-primary px-6 py-3 rounded-full font-semibold transition-all" style="background-color: #9c7ead; border-color: #9c7ead;" onmouseover="this.style.backgroundColor='#8b6c9c'" onmouseout="this.style.backgroundColor='#9c7ead'" onclick="document.getElementById('confirmation-modal').classList.remove('active'); document.querySelectorAll('.nav-item[data-page=\\'billing\\']')[0].click();">
                            <i class="fas fa-rocket mr-2"></i>Upgrade to Pro
                        </button>
                        <button class="btn btn-secondary px-6 py-3 rounded-full font-semibold" onclick="document.getElementById('confirmation-modal').classList.remove('active');">
                            Maybe Later
                        </button>
                    </div>
                `;
                
                if (defaultOkBtn) {
                    defaultOkBtn.style.display = 'none';
                }
                
                modal.classList.add('active');
            }
            
            // --- APP INITIALIZATION ---
            injectPageContent();
            setupNavigation();
            setupMobileMenu();
            setupModals();
            setupBrandKitCreation();
            setupBrandKitActions();
            setupAssetHub();
            setupShareModal();
            setupGettingStarted();
            initializeAuthSystem();
            initializeHelpGuide();
            setupTeamActions();
        });
        
        // ===== INVITE ACCEPTANCE FUNCTIONS =====
        // These are defined outside DOMContentLoaded so they can be called independently
        
        async function handleInviteAcceptance(token) {
            try {
                // Verify the invite
                const response = await fetch('/api/auth.php?action=verify-invite', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    showInviteError(data.message);
                    return;
                }
                
                // Show invite acceptance form
                showInviteAcceptanceForm(token, data.invite);
                
            } catch (error) {
                console.error('Invite verification error:', error);
                showInviteError('Failed to verify invite. Please try again or contact support.');
            }
        }
        
        function showInviteAcceptanceForm(token, inviteData) {
            // Create overlay
            const overlay = document.createElement('div');
            overlay.id = 'invite-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.98);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 99999;
                padding: 20px;
                backdrop-filter: blur(10px);
            `;
            
            overlay.innerHTML = `
                <div style="background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%); padding: 3rem; border-radius: 24px; max-width: 520px; width: 100%; box-shadow: 0 25px 70px rgba(156, 126, 173, 0.4); border: 2px solid rgba(156, 126, 173, 0.3); position: relative; overflow: hidden;">
                    <div style="position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(156, 126, 173, 0.1) 0%, transparent 70%); pointer-events: none;"></div>
                    
                    <div style="text-align: center; margin-bottom: 2.5rem; position: relative; z-index: 1;">
                        <div style="position: relative; width: 1em; height: 1em; display: inline-block; margin-bottom: 1.5rem; font-size: 4rem; filter: drop-shadow(0 0 20px #9c7ead); animation: pulse 2s ease-in-out infinite;">
                            <span style="position: absolute; top: 50%; left: 50%; width: 100%; height: 3px; background: linear-gradient(to right, rgba(156, 126, 173, 0), #9c7ead, rgba(156, 126, 173, 0)); transform-origin: center; transform: translate(-50%, -50%) rotate(45deg); box-shadow: 0 0 15px #9c7ead;"></span>
                            <span style="position: absolute; top: 50%; left: 50%; width: 100%; height: 3px; background: linear-gradient(to right, rgba(156, 126, 173, 0), #9c7ead, rgba(156, 126, 173, 0)); transform-origin: center; transform: translate(-50%, -50%) rotate(-45deg); box-shadow: 0 0 15px #9c7ead;"></span>
                        </div>
                        <h1 style="color: #fff; font-weight: 800; font-size: 2rem; margin-bottom: 0.75rem; letter-spacing: -0.5px;">Welcome to the Team! 🎉</h1>
                        <p style="color: #aaa; font-size: 1rem; margin: 0; line-height: 1.6;">
                            <strong style="color: #9c7ead; font-size: 1.1rem;">${inviteData.inviter_name}</strong> has invited you to collaborate<br>in their Stella workspace
                        </p>
                        <div style="margin-top: 1rem; padding: 0.75rem 1.5rem; background: rgba(156, 126, 173, 0.1); border-radius: 12px; border: 1px solid rgba(156, 126, 173, 0.2);">
                            <p style="color: #9c7ead; font-size: 0.9rem; margin: 0; font-weight: 600;">${inviteData.email}</p>
                        </div>
                    </div>
                    
                    <form id="invite-accept-form" style="margin-top: 2rem; position: relative; z-index: 1;">
                        <div style="margin-bottom: 1.75rem;">
                            <label style="display: block; color: #ddd; margin-bottom: 0.75rem; font-size: 0.95rem; font-weight: 600;">Your Full Name</label>
                            <input type="text" id="invite-full-name" required style="width: 100%; padding: 16px 18px; border-radius: 14px; border: 2px solid #333; background: rgba(255,255,255,0.05); color: #fff; font-size: 1rem; box-sizing: border-box; transition: all 0.3s; font-weight: 500;" placeholder="e.g. Jane Smith">
                        </div>
                        
                        <div style="margin-bottom: 2rem;">
                            <label style="display: block; color: #ddd; margin-bottom: 0.75rem; font-size: 0.95rem; font-weight: 600;">Create Password</label>
                            <input type="password" id="invite-password" required minlength="8" style="width: 100%; padding: 16px 18px; border-radius: 14px; border: 2px solid #333; background: rgba(255,255,255,0.05); color: #fff; font-size: 1rem; box-sizing: border-box; transition: all 0.3s; font-weight: 500;" placeholder="Minimum 8 characters">
                            <p style="color: #888; font-size: 0.8rem; margin-top: 0.5rem; margin-bottom: 0;">Use a strong password with letters, numbers & symbols</p>
                        </div>
                        
                        <button type="submit" style="width: 100%; padding: 16px; border-radius: 14px; border: none; background: linear-gradient(135deg, #9c7ead 0%, #826a94 100%); color: white; font-weight: 700; font-size: 1.05rem; cursor: pointer; transition: all 0.3s; box-shadow: 0 6px 20px rgba(156, 126, 173, 0.4); letter-spacing: 0.5px;">
                            Accept Invite & Join Workspace →
                        </button>
                        
                        <p id="invite-error" style="color: #ff6b6b; text-align: center; margin: 1.25rem 0 0; display: none; font-size: 0.9rem; background: rgba(255, 107, 107, 0.1); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255, 107, 107, 0.2);"></p>
                    </form>
                    
                    <div style="margin-top: 2.5rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); text-align: center; position: relative; z-index: 1;">
                        <p style="color: #999; font-size: 0.875rem; margin: 0 0 0.75rem 0;">Already have an account?</p>
                        <a href="/" style="color: #9c7ead; text-decoration: none; font-weight: 700; font-size: 0.95rem; transition: all 0.2s; display: inline-block;">Sign in instead →</a>
                    </div>
                </div>
                
                <style>
                    @keyframes pulse {
                        0%, 100% { filter: drop-shadow(0 0 20px #9c7ead); }
                        50% { filter: drop-shadow(0 0 30px #9c7ead) drop-shadow(0 0 40px #9c7ead); }
                    }
                    #invite-accept-form input:focus {
                        outline: none;
                        border-color: #9c7ead !important;
                        box-shadow: 0 0 0 3px rgba(156, 126, 173, 0.2) !important;
                    }
                    #invite-accept-form button:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 8px 25px rgba(156, 126, 173, 0.5) !important;
                    }
                    #invite-accept-form button:active {
                        transform: translateY(0);
                    }
                </style>
            `;
            
            document.body.appendChild(overlay);
            setTimeout(() => document.getElementById('invite-full-name').focus(), 100);
            
            document.getElementById('invite-accept-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const fullName = document.getElementById('invite-full-name').value.trim();
                const password = document.getElementById('invite-password').value;
                const errorElement = document.getElementById('invite-error');
                const submitBtn = e.target.querySelector('button[type="submit"]');
                
                if (fullName.length < 2) {
                    errorElement.textContent = '⚠️ Please enter your full name';
                    errorElement.style.display = 'block';
                    return;
                }
                
                if (password.length < 8) {
                    errorElement.textContent = '⚠️ Password must be at least 8 characters';
                    errorElement.style.display = 'block';
                    return;
                }
                
                submitBtn.disabled = true;
                submitBtn.textContent = '⏳ Creating your account...';
                errorElement.style.display = 'none';
                
                try {
                    const response = await fetch('/api/auth.php?action=accept-invite', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            token: token,
                            full_name: fullName,
                            password: password
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success && data.user) {
                        sessionStorage.setItem('stella_auth', 'authenticated');
                        sessionStorage.setItem('stella_user', JSON.stringify(data.user));
                        sessionStorage.setItem('stella_is_new_member', 'true');
                        
                        submitBtn.textContent = '✓ Account created! Loading workspace...';
                        submitBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                        
                        setTimeout(() => {
                            window.location.href = '/dashboard/';
                        }, 1500);
                    } else {
                        errorElement.textContent = '❌ ' + (data.message || 'Failed to create account');
                        errorElement.style.display = 'block';
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Accept Invite & Join Workspace →';
                    }
                } catch (error) {
                    console.error('Accept invite error:', error);
                    errorElement.textContent = '❌ Network error. Please check your connection and try again.';
                    errorElement.style.display = 'block';
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Accept Invite & Join Workspace →';
                }
            });
        }
        
        function showInviteError(message) {
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.98);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 99999;
                padding: 20px;
                backdrop-filter: blur(10px);
            `;
            
            overlay.innerHTML = `
                <div style="background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%); padding: 3rem; border-radius: 24px; max-width: 460px; width: 100%; text-align: center; border: 2px solid rgba(255, 68, 68, 0.3); box-shadow: 0 25px 70px rgba(255, 68, 68, 0.3);">
                    <div style="width: 80px; height: 80px; margin: 0 auto 1.5rem; border-radius: 50%; background: rgba(255, 68, 68, 0.15); display: flex; align-items: center; justify-content: center; border: 2px solid rgba(255, 68, 68, 0.3);">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ff4444" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    </div>
                    <h2 style="color: #fff; font-size: 1.75rem; margin-bottom: 1.25rem; font-weight: 700;">Invite Not Valid</h2>
                    <p style="color: #bbb; font-size: 1rem; margin-bottom: 2.5rem; line-height: 1.6;">${message}</p>
                    <a href="/" style="display: inline-block; padding: 14px 36px; border-radius: 14px; background: linear-gradient(135deg, #9c7ead 0%, #826a94 100%); color: white; text-decoration: none; font-weight: 700; transition: all 0.3s; box-shadow: 0 6px 20px rgba(156, 126, 173, 0.4); font-size: 1rem;">← Back to Homepage</a>
                </div>
            `;
            
            document.body.appendChild(overlay);
        }

        // --- BILLING & USAGE TRACKING ---
        
        // Stripe configuration - will be set by server
        const STRIPE_CONFIG = {
            publishableKey: window.STRIPE_PK || '',
            productId: window.STRIPE_PROD || '',
            priceId: window.STRIPE_PRICE_ID || ''
        };

        // User plan configuration
        const PLAN_LIMITS = {
            free: {
                name: 'Free Trial',
                price: 0,
                description: '5 kits, 2 users, 5GB storage',
                limits: {
                    kits: 5,
                    users: 2,
                    storage: 5 // GB
                }
            },
            pro: {
                name: 'Pro',
                price: 15,
                description: 'Unlimited kits, 10 users, 10GB storage',
                limits: {
                    kits: -1, // -1 means unlimited
                    users: 10,
                    storage: 10 // GB
                }
            }
        };

        // Current user plan (default to free)
        let currentUserPlan = 'free';

        // Usage tracking
        let currentUsage = {
            kits: 0,
            users: 1, // At least the owner
            storage: 0 // GB
        };

        // Load billing page data
        function loadBillingData() {
            updateCurrentPlanDisplay();
            updateUsageDisplay();
            loadBillingHistory();
        }

        // Update current plan display
        function updateCurrentPlanDisplay() {
            const currentUser = JSON.parse(sessionStorage.getItem('stella_user') || '{}');
            const userPlan = currentUser.plan_type || 'free';
            
            if (userPlan === 'pro') {
                document.getElementById('current-plan-name').textContent = 'Pro';
                document.getElementById('current-plan-description').textContent = 'Unlimited kits, users, storage';
                document.getElementById('current-plan-price').textContent = '$15';
            } else {
                document.getElementById('current-plan-name').textContent = 'Free Trial';
                document.getElementById('current-plan-description').textContent = '1 kit, 2 users, 1GB storage';
                document.getElementById('current-plan-price').textContent = '$0';
            }
        }

        // Update usage display
        function updateUsageDisplay() {
            const currentUser = JSON.parse(sessionStorage.getItem('stella_user') || '{}');
            const userPlan = currentUser.plan_type || 'free';
            
            // Calculate actual usage
            const kitsUsed = brandKits.length;
            const usersUsed = teamMembers.length + 1; // +1 for current user
            const totalBytes = assets.reduce((sum, asset) => sum + (parseInt(asset.size) || 0), 0);
            const storageUsed = (totalBytes / (1024 * 1024 * 1024)).toFixed(2);
            
            // Set limits based on plan
            const storageLimit = userPlan === 'pro' ? '∞' : 1;
            const kitsLimit = userPlan === 'pro' ? '∞' : 1;
            const usersLimit = userPlan === 'pro' ? '∞' : 2;
            
            // Update storage usage
            const storagePercent = userPlan === 'pro' ? 0 : Math.min((parseFloat(storageUsed) / 1) * 100, 100);
            document.getElementById('storage-used').textContent = `${storageUsed} GB`;
            document.getElementById('storage-progress').style.width = `${storagePercent}%`;
            document.getElementById('storage-limit').textContent = userPlan === 'pro' ? 'unlimited' : `of 1 GB used`;
            
            // Update kits usage
            const kitsPercent = userPlan === 'pro' ? 0 : Math.min((kitsUsed / 1) * 100, 100);
            document.getElementById('kits-used').textContent = kitsUsed.toString();
            document.getElementById('kits-progress').style.width = `${kitsPercent}%`;
            document.getElementById('kits-limit').textContent = userPlan === 'pro' ? 'unlimited' : `of 1 kit used`;
            
            // Update users usage
            const usersPercent = userPlan === 'pro' ? 0 : Math.min((usersUsed / 2) * 100, 100);
            document.getElementById('users-used').textContent = usersUsed.toString();
            document.getElementById('users-progress').style.width = `${usersPercent}%`;
            document.getElementById('users-limit').textContent = userPlan === 'pro' ? 'unlimited' : `of 2 users used`;
            
            // Update plan buttons
            const currentPlanBtn = document.getElementById('current-plan-btn');
            const upgradeBtn = document.getElementById('upgrade-to-pro-btn');
            
            if (userPlan === 'free') {
                currentPlanBtn.textContent = 'Current Plan';
                currentPlanBtn.disabled = true;
                upgradeBtn.textContent = 'Upgrade to Pro';
                upgradeBtn.onclick = () => upgradeToPro();
            } else {
                currentPlanBtn.textContent = 'Manage Subscription';
                currentPlanBtn.disabled = false;
                currentPlanBtn.onclick = () => manageSubscription();
                upgradeBtn.textContent = 'Current Plan';
                upgradeBtn.disabled = true;
            }
        }

        // Load billing history
        function loadBillingHistory() {
            const billingHistory = document.getElementById('billing-history');
            
            // For now, show empty state
            billingHistory.innerHTML = `
                <div class="text-center text-[var(--text-secondary)] py-8">
                    <i class="fas fa-receipt text-4xl mb-4 opacity-50"></i>
                    <p>No billing history yet</p>
                </div>
            `;
        }

        // Upgrade to Pro plan
        async function upgradeToPro() {
            try {
                // Show loading state
                const upgradeBtn = document.getElementById('upgrade-to-pro-btn');
                const originalText = upgradeBtn.textContent;
                upgradeBtn.textContent = 'Processing...';
                upgradeBtn.disabled = true;

                // Wait for config to load
                let attempts = 0;
                while (!window.STRIPE_CONFIG_LOADED && attempts < 50) {
                    await new Promise(resolve => setTimeout(resolve, 100));
                    attempts++;
                }
                
                if (!window.STRIPE_PRICE_ID) {
                    throw new Error('Stripe configuration not loaded. Please refresh the page.');
                }
                
                // Check if using HTTPS or test mode
                const isHTTPS = window.location.protocol === 'https:';
                const isTestMode = window.STRIPE_PK && window.STRIPE_PK.startsWith('pk_test_');
                
                if (!isHTTPS && !isTestMode) {
                    showToast('Live Stripe requires HTTPS. Please use test keys (pk_test_) in development or enable HTTPS.', 'error');
                    upgradeBtn.textContent = originalText;
                    upgradeBtn.disabled = false;
                    return;
                }

                // Get current user info
                const currentUser = JSON.parse(sessionStorage.getItem('stella_user') || '{}');
                
                // Create Stripe checkout session
                const response = await fetch('/api/create-checkout-session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-User-ID': currentUser.id?.toString() || ''
                    },
                    body: JSON.stringify({
                        priceId: window.STRIPE_PRICE_ID,
                        userId: currentUser.id,
                        customerEmail: currentUser.email,
                        successUrl: `${window.location.origin}/dashboard/?success=true&session_id={CHECKOUT_SESSION_ID}`,
                        cancelUrl: `${window.location.origin}/dashboard/?page=billing`
                    })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Checkout API Error:', errorText);
                    throw new Error('Failed to create checkout session');
                }

                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                const { sessionId } = data;

                // Redirect to Stripe Checkout
                let stripe;
                try {
                    stripe = Stripe(STRIPE_CONFIG.publishableKey || window.STRIPE_PK);
                } catch (stripeError) {
                    if (stripeError.message && stripeError.message.includes('HTTPS')) {
                        throw new Error('Stripe requires HTTPS for live keys. Use test keys (pk_test_) in development or enable HTTPS.');
                    }
                    throw stripeError;
                }
                
                const { error } = await stripe.redirectToCheckout({ sessionId });

                if (error) {
                    throw new Error(error.message);
                }

            } catch (error) {
                console.error('Upgrade error:', error);
                showToast('Failed to start upgrade process. Please try again.', 'error');
                
                // Reset button
                const upgradeBtn = document.getElementById('upgrade-to-pro-btn');
                upgradeBtn.textContent = 'Upgrade to Pro';
                upgradeBtn.disabled = false;
            }
        }

        // Manage subscription
        function manageSubscription() {
            // Redirect to Stripe Customer Portal
            window.open('/api/create-portal-session.php', '_blank');
        }

        // Check if user can perform action based on limits
        function checkUsageLimit(type, increment = 1) {
            const plan = PLAN_LIMITS[currentUserPlan];
            const limit = plan.limits[type];
            const current = currentUsage[type];
            
            if (limit === -1) return true; // Unlimited
            
            return (current + increment) <= limit;
        }

        // Update usage when actions are performed
        function updateUsage(type, increment = 1) {
            currentUsage[type] += increment;
            updateUsageDisplay();
        }

        // Decrease usage when items are removed
        function decreaseUsage(type, decrement = 1) {
            currentUsage[type] = Math.max(0, currentUsage[type] - decrement);
            updateUsageDisplay();
        }

        // Load usage from API
        async function loadUsageFromAPI() {
            try {
                const currentUser = JSON.parse(sessionStorage.getItem('stella_user') || '{}');
                if (!currentUser.id) {
                    console.warn('No user session found, using default usage');
                    return;
                }
                
                const response = await fetch('/api/usage.php', {
                    headers: {
                        'Content-Type': 'application/json',
                        'X-User-ID': currentUser.id || '',
                        'X-User-Email': currentUser.email || ''
                    }
                });
                
                if (response.ok) {
                    const usage = await response.json();
                    currentUsage = usage;
                    updateUsageDisplay();
                } else {
                    console.warn('Failed to load usage from API, using default values');
                    const errorText = await response.text();
                    console.error('API Error:', errorText);
                }
            } catch (error) {
                console.error('Failed to load usage:', error);
            }
        }

        // Load user plan from API
        async function loadUserPlanFromAPI() {
            try {
                const currentUser = JSON.parse(sessionStorage.getItem('stella_user') || '{}');
                if (!currentUser.id) {
                    console.warn('No user session found, using default plan');
                    return;
                }
                
                const response = await fetch('/api/user-plan.php', {
                    headers: {
                        'Content-Type': 'application/json',
                        'X-User-ID': currentUser.id?.toString() || ''
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.user) {
                        // Update current plan
                        currentUserPlan = data.user.plan_type || 'free';
                        
                        // Update user in sessionStorage
                        currentUser.plan_type = data.user.plan_type;
                        currentUser.subscription_status = data.user.subscription_status;
                        sessionStorage.setItem('stella_user', JSON.stringify(currentUser));
                        
                        // Update UI
                        updateCurrentPlanDisplay();
                        updateUsageDisplay();
                        
                        console.log('✅ User plan loaded from database:', data.user.plan_type);
                    } else {
                        console.warn('Failed to load user plan:', data.message);
                    }
                } else {
                    console.warn('Failed to load user plan from API, using default plan');
                    const errorText = await response.text();
                    console.error('API Error:', errorText);
                }
            } catch (error) {
                console.error('Failed to load user plan:', error);
            }
        }

        // Handle successful payment
        async function handlePaymentSuccess() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === 'true') {
                const sessionId = urlParams.get('session_id');
                
                // Call API to upgrade user plan in database
                if (sessionId) {
                    try {
                        const currentUser = JSON.parse(sessionStorage.getItem('stella_user') || '{}');
                        const response = await fetch('/api/upgrade-plan.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-User-ID': currentUser.id?.toString() || ''
                            },
                            body: JSON.stringify({
                                session_id: sessionId,
                                user_id: currentUser.id
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            showToast('🎉 Welcome to Pro! Your subscription is now active.', 'success');
                            
                            // Reload plan from database to ensure it's updated
                            await loadUserPlanFromAPI();
                        } else {
                            showToast('Payment successful, but upgrade failed. Please contact support.', 'warning');
                        }
                    } catch (error) {
                        console.error('Failed to upgrade plan:', error);
                        showToast('Payment successful, but upgrade failed. Please contact support.', 'warning');
                    }
                } else {
                    // Fallback if no session_id - mark user as pro directly
                    try {
                        const currentUser = JSON.parse(sessionStorage.getItem('stella_user') || '{}');
                        const markProResponse = await fetch('/api/mark-pro.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-User-ID': currentUser.id?.toString() || ''
                            }
                        });
                        
                        const markProData = await markProResponse.json();
                        
                        if (markProData.success) {
                            showToast('🎉 Welcome to Pro! Your subscription is now active.', 'success');
                            // Reload plan from database
                            await loadUserPlanFromAPI();
                        } else {
                            showToast('Payment received, please refresh to see your Pro plan.', 'warning');
                        }
                    } catch (error) {
                        console.error('Failed to mark as pro:', error);
                        showToast('Payment received, please refresh to see your Pro plan.', 'warning');
                    }
                }
                
                // Clean URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }

        // Initialize billing when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Load billing data when billing page is shown
            const billingNav = document.querySelector('[data-page="billing"]');
            if (billingNav) {
                billingNav.addEventListener('click', () => {
                    setTimeout(() => {
                        console.log('Loading billing data...');
                        loadBillingData();
                        loadUsageFromAPI();
                        loadUserPlanFromAPI();
                    }, 100);
                });
            }
            
            // Handle payment success
            handlePaymentSuccess();
            
            // Debug: Check if user session exists
            const currentUser = JSON.parse(sessionStorage.getItem('stella_user') || '{}');
            console.log('Current user session:', currentUser);
        });
    </script>
</body>
</html>