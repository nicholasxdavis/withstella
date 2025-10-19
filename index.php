<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stella AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary: #9c7ead;
        }
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background-color: #000;
            overflow: hidden;
        }
        #loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            transition: opacity 0.5s ease-out;
        }
        .shimmer-wrapper {
            width: 80px; /* Adjusted to better fit the icon size */
            height: 80px;
            position: relative;
        }
        /* The shimmer effect is an animated gradient overlay */
        .shimmer {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            background: linear-gradient(100deg, rgba(255, 255, 255, 0) 20%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0) 80%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite linear;
        }
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        #content-wrapper {
            visibility: hidden;
            height: 100vh;
            overflow-y: auto;
        }

        /* --- Logo Icon Styles (copied from the UI) --- */
        .logo-icon { 
            position: absolute; 
            top: 50%; 
            left: 50%;
            transform: translate(-50%, -50%);
            width: 1em; 
            height: 1em; 
            filter: drop-shadow(0 0 6px var(--primary)); 
        }
        .logo-icon-ray { 
            position: absolute; 
            top: 50%; 
            left: 50%; 
            width: 100%; 
            height: 2px; 
            background: linear-gradient(to right, rgba(156, 126, 173, 0), var(--primary), rgba(156, 126, 173, 0)); 
            transform-origin: center; 
        }
        .logo-icon-ray:first-child { transform: translate(-50%, -50%) rotate(45deg); }
        .logo-icon-ray:last-child { transform: translate(-50%, -50%) rotate(-45deg); }
    </style>
</head>
<body>

    <!-- The loader element that shows on initial page load -->
    <div id="loader">
        <div class="shimmer-wrapper">
            <!-- Stella Logo Icon (instead of an image) -->
            <div class="logo-icon text-6xl">
                <span class="logo-icon-ray"></span>
                <span class="logo-icon-ray"></span>
            </div>
            <!-- The shimmer effect overlay -->
            <div class="shimmer"></div>
        </div>
    </div>

    <script>
        // Just redirect to index.html
        window.location.href = '/index.html';
    </script>

</body>
</html>
