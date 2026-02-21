<?php
include 'api/db.php';
// Fetch Site Name
// Validate Site from Link
$site_id = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
// Support 'site' param for code/name slug? User used ?site=elements
$site_slug = isset($_GET['site']) ? trim($_GET['site']) : '';
$site_name = "";

if ($site_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM sites WHERE id = ?");
        $stmt->execute([$site_id]);
        if($row = $stmt->fetch()) {
            $site_name = $row['name'];
        }
    } catch (Exception $e) { }
} elseif (!empty($site_slug)) {
    try {
        // Assuming sites table has 'code' or we check 'name'. Let's check 'name' ~ slug or 'id' if numeric?
        // User passed ?site=elements. Let's assume this maps to site NAME or a new CODE column.
        // Let's try matching name for now using LIKE or exact.
        $stmt = $pdo->prepare("SELECT id, name FROM sites WHERE name LIKE ? OR id = ?");
        $stmt->execute([$site_slug, intval($site_slug)]);
        if($row = $stmt->fetch()) {
            $site_name = $row['name'];
            $site_id = $row['id']; // Important: Set ID for downstream use
        }
    } catch (Exception $e) { }
}

// if (empty($site_name)) {
//     die("<div style='background-color: #1a1a2e; color:white; height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; font-family:sans-serif;'>
//         <h1>Invalid Link</h1>
//         <p>Please rescan the QR Code.</p>
//     </div>");
// }

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>window.SITE_ID = <?php echo json_encode($site_id); ?>;</script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <title>Quizzify - Your Flavour is a vibe</title>
    <link rel="icon" href="assets/logo.png" type="image/png">
    <link rel="manifest" href="assets/manifest.json">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="assets/splash.png">
    <link rel="preload" as="image" href="assets/logo.png">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #1a1a2e;
            /* Fallback */
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
            position: relative;
        }

        /* Blurred Background for the App */
        :root {
            --bg-image: url('assets/splash.png');
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: var(--bg-image);
            background-size: cover;
            background-position: center;
            filter: brightness(0.4);
            z-index: -1;
            transform: scale(1.1);
            /* Remove blur edges */
            transition: background-image 0.5s ease-in-out;
        }

        .quiz-container {
            max-width: 600px;
            width: 100%;
            padding: 20px;
            text-align: center;
            z-index: 2;
            position: relative;
        }

        /* Splash Screen Styles */
        #splash-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #d41b2c;
            /* Smirnoff Red fallback */
            background-image: url('assets/splash.png');
            background-size: cover;
            background-position: center;
            z-index: 100;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 50px;
            cursor: pointer;
        }

        .btn-option {
            width: 100%;
            margin: 10px 0;
            padding: 15px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .btn-option:hover,
        .btn-option:active {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.02);
            border-color: #fff;
        }

        h2 {
            font-weight: 700;
            margin-bottom: 30px;
        }

        .hidden {
            display: none !important;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        .fade-out {
            animation: fadeOut 0.5s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
                visibility: hidden;
            }
        }

        .result-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .result-original {
            border-color: #00d2ff;
            box-shadow: 0 0 30px rgba(0, 210, 255, 0.3);
        }

        .result-pineapple {
            border-color: #ffe600;
            box-shadow: 0 0 30px rgba(255, 230, 0, 0.3);
        }

        .result-guarana {
            border-color: #ff0055;
            box-shadow: 0 0 30px rgba(255, 0, 85, 0.3);
        }

        .lang-btn {
            width: 48%;
            margin: 1%;
            padding: 20px;
            font-size: 1.2rem;
            backdrop-filter: blur(5px);
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-get-started {
            background: white;
            color: #d41b2c;
            font-weight: bold;
            padding: 15px 40px;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            border: none;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Placeholder styling */
        #player-name::placeholder {
            color: rgba(255, 255, 255, 0.6) !important;
            /* Off-white */
            font-style: italic;
        }

        /* Info Icon */
        .info-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 30px;
            height: 30px;
            border: 2px solid white;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            z-index: 101;
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
        }

        .info-icon:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        /* Disclaimer Modal */
        .disclaimer-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .disclaimer-modal.show {
            opacity: 1;
            pointer-events: all;
        }

        .disclaimer-content {
            background: #fff;
            color: #333;
            padding: 30px;
            border-radius: 15px;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .result-image {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 50;
        }

        .next-btn {
            position: fixed;
            bottom: 40px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid white;
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1.2rem;
            z-index: 55;
            cursor: pointer;
            animation: pulse 2s infinite;
        }

        .text-shadow {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        }

        /* Final Action Container (Inputs on top of image) */
        .final-actions {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 30px 20px 50px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9), transparent);
            z-index: 60;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .quiz-logo {
            max-width: 150px;
            height: auto;
            display: block;
            margin: 0 auto;
            filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.3));
        }

        /* Back Button */
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 102;
            color: white;
            opacity: 0.8;
            transition: all 0.3s;
        }

        .back-btn:hover {
            opacity: 1;
            transform: translateX(-5px);
        }

        .back-btn svg {
            width: 32px;
            filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.5));
        }

        /* Celebration Effects - Rising Bubbles and Falling Ice */
        #celebration-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            pointer-events: none;
            z-index: 100;
            overflow: hidden;
        }

        .vibe-element {
            position: absolute;
            pointer-events: none;
        }

        /* Bubbles rising up (Fizz) */
        .bubble {
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: inset 0 0 5px rgba(255, 255, 255, 0.5);
            animation: rise linear forwards;
            bottom: -50px;
        }

        /* Ice cubes falling down */
        .ice-cube {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(2px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            animation: fall linear forwards;
            top: -50px;
        }

        @keyframes rise {
            0% {
                transform: translateY(0) scale(0.5) rotate(0deg);
                opacity: 0;
            }
            20% {
                opacity: 0.8;
            }
            100% {
                transform: translateY(-110vh) scale(1.2) rotate(360deg);
                opacity: 0;
            }
        }

        @keyframes fall {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0;
            }
            20% {
                opacity: 1;
            }
            100% {
                transform: translateY(110vh) rotate(720deg);
                opacity: 0;
            }
        }
        
        /* Custom Select Styling */
        .custom-select {
            appearance: none;
            background-color: rgba(33, 37, 41, 0.9);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            color: white !important;
        }
        .custom-select:focus {
            background-color: rgba(33, 37, 41, 1);
            border-color: #ff0055;
            box-shadow: 0 0 0 0.25rem rgba(255, 0, 85, 0.25);
        }
        /* Fullscreen Video Screen */
        #video-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #000;
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .video-wrapper {
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #intro-video {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Show all of it by default */
            cursor: pointer;
        }
        #intro-video.rotated {
            transform: rotate(90deg);
            width: 100vh;
            height: 100vw;
            object-fit: cover; /* Fullscreen when rotated */
        }
        .video-controls-overlay {
            position: fixed;
            bottom: 30px;
            left: 0;
            right: 0;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            pointer-events: none;
        }
        .video-btn {
            pointer-events: auto;
            background: rgba(255, 255, 255, 0.9);
            color: #d41b2c;
            border: 2px solid white;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            font-size: 1.2rem;
            animation: pulse 2s infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .video-btn:active {
            transform: scale(0.95);
        }
        #video-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: rgba(255,255,255,0.5);
            z-index: 10;
        }

    </style>
</head>
<body>
    <!-- Back Button -->
    <div id="back-btn" class="back-btn hidden" onclick="goBack()">
        <svg viewBox="0 0 24 24">
            <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" />
        </svg>
    </div>

    <!-- Disclaimer Modal -->
    <div id="disclaimer-popup" class="disclaimer-modal" onclick="toggleDisclaimer(event)">
        <div class="disclaimer-content" onclick="event.stopPropagation()">
            <h4 class="text-danger fw-bold mb-3">⚠️ DRINK RESPONSIBLY</h4>
            <p>Not to be sold to persons under the age of 18.</p>
            <p>Please drink responsibly.</p>
            <p class="small text-muted mt-3">By continuing, you agree to our terms of service.</p>
            <button class="btn btn-dark rounded-pill px-4 mt-3" onclick="toggleDisclaimer(event)">CLOSE</button>
        </div>
    </div>

    <!-- Audio -->
    <audio id="tada-sound" src="assets/tada.mp3" preload="auto"></audio>

    <!-- Celebration Overlay -->
    <div id="celebration-container" class="hidden"></div>



    <!-- Splash Screen Removed -->
    <!-- <div id="splash-screen" onclick="enterApp()">...</div> -->

    <div class="quiz-container fade-in" id="main-container">

        <!-- Video Screen -->
        <div id="video-screen">
            <div class="video-wrapper">
                <video id="intro-video" autoplay playsinline width="100%" webkit-playsinline preload="auto">
                    <source src="assets/smice2.mp4" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                <div id="video-loading">LOADING VIBE...</div>
                <div id="video-controls" class="video-controls-overlay" style="justify-content: center; display: none;">
                    <button class="video-btn" onclick="videoFinished()">
                        START QUIZ
                    </button> 
                </div>
            </div>
        </div>





        <!-- Language Selection -->

        <div id="lang-screen" class="hidden">
            <div class="mb-4">
                <img src="assets/logo.png" alt="Smirnoff Ice" style="max-width: 150px; height: auto;">
            </div>
            <?php if(!empty($site_name) && $site_name != "Smirnoff Ice"): ?>
                <h2 class="text-warning mb-2" style="font-size: 1.2rem; text-transform: uppercase; letter-spacing: 2px;"><?php echo htmlspecialchars($site_name); ?></h2>
            <?php endif; ?>
            <h1 class="mb-4">Select Language / Chagua Lugha</h1>
            <div class="d-flex flex-wrap justify-content-center">
                <button class="btn btn-outline-light lang-btn" onclick="setLang('en')">🇺🇸 English</button>
                <button class="btn btn-outline-light lang-btn" onclick="setLang('sw')">🇹🇿 Swahili</button>
            </div>
        </div>

        <!-- Age Verification Screen -->
        <div id="age-screen" class="hidden">
            <h1 class="mb-5" id="age-title">Are you 18 years or older?</h1>
            <div class="d-flex flex-column align-items-center gap-3">
                <button class="btn btn-lg btn-success px-5 py-3 rounded-pill fw-bold w-100" onclick="verifyAge(true)"
                    id="age-yes">YES, I AM 18+</button>
                <button class="btn btn-lg btn-outline-light px-5 py-3 rounded-pill fw-bold w-100"
                    onclick="verifyAge(false)" id="age-no">NO, I'M UNDER 18</button>
            </div>
            <p class="mt-4 text-white-50 small" id="age-legal">By continuing, you agree to our terms of service and
                confirm you are of legal drinking age.</p>
        </div>

        <!-- Age Denied Screen -->
        <div id="age-denied" class="hidden">
            <h1 class="display-1 mb-4">🚫</h1>
            <h2 class="mb-4" id="denied-title">Sorry</h2>
            <p class="lead" id="denied-msg">You must be 18+ to enter.</p>
            <button class="btn btn-outline-light mt-4 rounded-pill" onclick="location.reload()">Back</button>
        </div>

        <!-- Name Input Screen -->
        <div id="name-screen" class="hidden">
            <h1 class="mb-4" id="name-title">What's your name?</h1>
            <div class="mb-4">
                <input type="text" id="player-name"
                    class="form-control form-control-lg text-center bg-transparent text-white border-white"
                    placeholder="Enter your name..." style="border-radius: 50px; font-size: 1.5rem;">
            </div>
            <button class="btn btn-lg btn-light px-5 py-3 rounded-pill fw-bold" onclick="saveName()"
                id="name-btn">CONTINUE</button>
        </div>



        <!-- Start Screen -->
        <div id="start-screen" class="hidden">
            <h1 class="mb-4" id="main-title">Your Flavour is a vibe</h1>
            <p class="lead mb-2" id="welcome-text">Discover your Smirnoff Ice personality.</p>
            <h2 class="mb-5 display-6 text-warning" id="display-player-name"></h2>
            <button class="btn btn-lg btn-danger px-5 py-3 rounded-pill fw-bold" onclick="startQuiz()"
                id="start-btn">START QUIZ</button>
        </div>

        <!-- Quiz Screen -->
        <div id="quiz-screen" class="hidden">
            <img src="assets/logo.png" alt="Smirnoff Ice" class="quiz-logo mb-3" loading="lazy">
            <h2 id="question">Loading...</h2>
            <div id="options"></div>
            <div class="mt-4 text-white-50">
                <span id="progress-text">Question</span> <span id="current-q">1</span> / <span id="total-q">6</span>
            </div>
        </div>

        <!-- Result Screen -->
        <div id="result-screen" class="hidden">
            <!-- Dynamic Result Image -->
            <img id="result-img" class="result-image hidden">

            <!-- Manual Next Button for Image 1 -->
            <button id="result-next-btn" class="next-btn hidden" onclick="showPhase2()">NEXT &rarr;</button>

            <!-- Old content container - hidden by default, used for data if needed or falls back -->
            <div id="result-content" class="hidden"></div>

            <div id="final-actions" class="final-actions hidden">
                <div id="influencer-section" class="w-100" style="max-width: 400px;">
                    <button class="btn btn-warning w-100 rounded-pill fw-bold mb-3 hidden"
                        onclick="viewSocialProof()">SEE RESULTS &rarr;</button>
                    
                </div>
            </div>
        </div>

        <!-- Social Proof Screen (Final) -->
        <div id="social-proof-screen" class="hidden quiz-container">


            <div class="my-5">
                <h1 class="display-4 fw-bold text-uppercase text-shadow" id="final-player-name"></h1>
                <img src="assets/crown.png" loading="lazy"
                    style="width:60px; position: relative; top: -100px; right: -120px; transform: rotate(20deg);"
                    class="hidden" id="crown-icon">
            </div>




        </div>
    </div>

    <script src="assets/app.js?v=12"></script>
</body>

</html>