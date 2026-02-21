let token;
let questions = [];
let qIndex = 0;
let currentLang = 'en';
let playerName = '';
const urlParams = new URLSearchParams(window.location.search);
const quizId = urlParams.get('q') || 1;
// Prefer global SITE_ID injected by PHP, fallback to URL param (though URL param might be missing if we used 'site')
const siteId = (typeof window.SITE_ID !== 'undefined') ? window.SITE_ID : urlParams.get('sid');

// Basic UI Translations (For static elements)
const uiInfo = {
    'en': {
        welcome: "Discover your Smirnoff Ice personality.",
        start: "START QUIZ",
        progress: "Question",
        who: "Who put you on?",
        share: "Share My Vibe 📲",
        nameTitle: "What's your name?",
        namePlaceholder: "Enter your name...",
        continue: "CONTINUE",
        ageTitle: "Are you 18 years or older?",
        ageYes: "YES, I AM 18+",
        ageNo: "NO, I'M UNDER 18",
        ageLegal: "By continuing, you agree to our terms of service and confirm you are of legal drinking age.",
        deniedTitle: "Sorry",
        deniedMsg: "You must be 18+ to enter.",
        vibeFlavor: "Your Flavour is a vibe"
    },
    'sw': {
        welcome: "Gundua haiba yako ya Smirnoff Ice.",
        start: "ANZA QUIZ",
        progress: "Swali",
        who: "Nani kakuleta?",
        share: "Shiriki Vibe Yangu 📲",
        nameTitle: "Unaitwa nani?",
        namePlaceholder: "Andika jina lako...",
        continue: "ENDELEA",
        ageTitle: "Una miaka 18 au zaidi?",
        ageYes: "NDIO, NINA 18+",
        ageNo: "HAPANA, SINA 18",
        ageLegal: "Kwa kuendelea, unakubaliana na vigezo na masharti yetu na unathibitisha una umri halali wa kunywa pombe.",
        deniedTitle: "Pole",
        deniedMsg: "Hairuhusiwi kwa walio chini ya miaka 18.",
        vibeFlavor: "Fleva yako, Vibe yako"
    }
};

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('assets/sw.js')
        .then(reg => {
            console.log('SW Registered');
            reg.update(); // Check for updates immediately
        })
        .catch(err => console.log('SW Fail', err));
}

// Aggressively preload videos into cache
function preloadVideos() {
    const videos = [
        // Video removed to prevent bandwidth contention
        // 'assets/smice2.mp4'
    ];

    // For HLS, preloading segments is complex. Preload manifest only.
    const hlsManifest = 'assets/hls/playlist.m3u8';
    fetch(hlsManifest, { method: 'HEAD' })
        .then(r => {
            if (r.ok) console.log('HLS Manifest found');
            else console.log('HLS Manifest missing (fallback to MP4)');
        })
        .catch(e => console.log('HLS check failed'));
}

// Start preloading immediately
// Start preloading immediately
window.addEventListener('load', () => {
    preloadVideos();
    enterApp();
});

function enterApp() {
    // Splash screen is removed from DOM/hidden by design now.
    // We just ensure main container is visible and start video.
    const mainContainer = document.getElementById('main-container');
    const videoScreen = document.getElementById('video-screen');

    if (mainContainer) mainContainer.classList.remove('hidden');
    if (videoScreen) videoScreen.classList.remove('hidden');

    // Attempt video playback immediately
    // Note: Audio will likely be blocked unless muted.
    // startVideoAction handles the muted fallback logic.
    startVideoAction();

    // Global listener to unmute on ANY interaction
    const unmuteHandler = () => {
        const video = document.getElementById('intro-video');
        if (video) {
            video.muted = false;
            video.play().catch(e => console.log("Still blocked:", e));
        }
        document.removeEventListener('click', unmuteHandler);
        document.removeEventListener('touchstart', unmuteHandler);
    };
    document.addEventListener('click', unmuteHandler);
    document.addEventListener('touchstart', unmuteHandler);
}

function startVideoAction() {
    const video = document.getElementById('intro-video');
    const controls = document.getElementById('video-controls');
    const loader = document.getElementById('video-loading');

    if (!video) return;

    // HIde UI immediately
    if (controls) controls.style.display = 'none';
    if (loader) loader.style.display = 'none';

    video.currentTime = 0;
    video.muted = false; // Hope for unmuted autoplay (may require user interaction)

    // HLS Initialization Logic
    const hlsSrc = 'assets/hls/playlist.m3u8';

    // Check if HLS supported and video setup
    if (Hls.isSupported()) {
        const hls = new Hls();
        hls.loadSource(hlsSrc);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, function () {
            startPlayback();
        });
        hls.on(Hls.Events.ERROR, function (event, data) {
            if (data.fatal) {
                console.log("HLS Error, falling back to MP4.");
                hls.destroy();
                video.src = 'assets/smice2.mp4';
                startPlayback();
            }
        });
    }
    // Safari/Native HLS Support
    else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = hlsSrc;
        video.addEventListener('loadedmetadata', function () {
            startPlayback();
        });
        // Ensure error fallback?
        video.addEventListener('error', function () {
            // Fallback to MP4 (already in source?) No, we overwrote src.
            // If src fails, we might need to reset src to MP4.
            console.log("Native HLS failed.");
            video.src = 'assets/smice2.mp4';
            startPlayback();
        });
    } else {
        // Fallback or non-HLS Env
        // But wait, the video tag already has source?
        // Let's ensure it plays properly.
        // If we didn't touch src, it should play the <source> tag.
        // But since we want to dynamically choose HLS:
        video.src = 'assets/smice2.mp4';
        startPlayback();
    }

    function startPlayback() {
        // Function to attempt play and unmute
        let playPromise = video.play();

        // Stall detection
        let stallCheck = setTimeout(() => {
            if (video.currentTime < 0.1) {
                console.log("Video stalled, showing skip button early");
                enableSkip();
            }
        }, 4000);

        if (playPromise !== undefined) {
            playPromise.then(_ => {
                console.log("Playback started");
                enableSkip();
                // Removed automatic unmuting to prevent autoplay block
                // try {
                //     video.muted = false;
                // } catch (e) { console.log("Unmute failed"); }
            }).catch(e => {
                console.log("Autoplay failed (likely due to unmuted policy):", e);
                // Autoplay with sound failed. Show a "Tap to Unmute/Play" button overlay?
                // Or fallback to muted, then show Unmute button.
                video.muted = true;
                video.play().then(() => {
                    enableSkip();
                    // Optional: Show an "Unmute" button or message here
                    // For now, we just fallback to muted to ensure video is seen.
                    console.log("Falling back to muted autoplay.");
                });
            });
        }
    }

    // Helper to show skip button
    const enableSkip = () => {
        setTimeout(() => {
            const btn = controls.querySelector('.video-btn');
            if (btn) {
                btn.innerText = "START QUIZ";
                btn.onclick = videoFinished;
            }
            controls.style.display = 'flex';
        }, 3000);
    };

    video.onended = videoFinished;
}
window.startVideoAction = startVideoAction;



function videoFinished() {
    const video = document.getElementById('intro-video');
    if (video) {
        video.pause();
        video.onended = null;
    }
    document.getElementById('video-screen').classList.add('hidden');
    document.getElementById('lang-screen').classList.remove('hidden');
    updateBackground(''); // Clear background for lang screen
}

function toggleRotate() {
    const video = document.getElementById('intro-video');
    if (video) {
        video.classList.toggle('rotated');
    }
}



function goBack() {
    // Determine current screen
    const videoScreen = document.getElementById('video-screen');
    const langScreen = document.getElementById('lang-screen');
    const ageScreen = document.getElementById('age-screen');
    const nameScreen = document.getElementById('name-screen');
    const startScreen = document.getElementById('start-screen');
    const quizScreen = document.getElementById('quiz-screen');
    const resultScreen = document.getElementById('result-screen');
    const splash = document.getElementById('splash-screen');


    if (!videoScreen.classList.contains('hidden')) {
        // Back to Splash
        const video = document.getElementById('intro-video');
        if (video) video.pause();
        splash.style.display = 'flex';
        splash.classList.remove('fade-out');
        document.getElementById('main-container').classList.add('hidden');
        document.getElementById('back-btn').classList.add('hidden');
        videoScreen.classList.add('hidden');
    }
    else if (!langScreen.classList.contains('hidden')) {
        // Back to Video
        langScreen.classList.add('hidden');
        videoScreen.classList.remove('hidden');
        const video = document.getElementById('intro-video');
        if (video) {
            video.currentTime = 0;
            video.play();
        }
    }

    else if (!ageScreen.classList.contains('hidden')) {
        // Back to Lang
        ageScreen.classList.add('hidden');
        langScreen.classList.remove('hidden');
    }
    else if (!nameScreen.classList.contains('hidden')) {
        // Back to Age
        nameScreen.classList.add('hidden');
        ageScreen.classList.remove('hidden');
    }
    else if (!startScreen.classList.contains('hidden')) {
        // Back to Name
        startScreen.classList.add('hidden');
        nameScreen.classList.remove('hidden');
    }
    else if (!quizScreen.classList.contains('hidden')) {
        // Back in Quiz
        if (qIndex > 0) {
            qIndex--;
            showQuestion();
        } else {
            // Back to Start
            quizScreen.classList.add('hidden');
            startScreen.classList.remove('hidden');
            updateBackground(''); // Reset background
        }
    }
    else if (!resultScreen.classList.contains('hidden')) {
        // Back to Start (Reset)
        resultScreen.classList.add('hidden');
        startScreen.classList.remove('hidden');
        updateBackground(''); // Reset bg (No splash)
    }
}

function toggleDisclaimer(e) {
    if (e) e.stopPropagation();
    let popup = document.getElementById('disclaimer-popup');
    popup.classList.toggle('show');
}

function setLang(lang) {
    currentLang = lang;

    // Update simple UI Text
    document.getElementById('welcome-text').innerText = uiInfo[lang].welcome;
    document.getElementById('start-screen').querySelector('h1').innerText = uiInfo[lang].vibeFlavor;
    document.title = "Quizzify - " + uiInfo[lang].vibeFlavor;
    document.getElementById('start-btn').innerText = uiInfo[lang].start;
    document.getElementById('progress-text').innerText = uiInfo[lang].progress;


    document.getElementById('name-title').innerText = uiInfo[lang].nameTitle;
    document.getElementById('name-btn').innerText = uiInfo[lang].continue;
    document.getElementById('player-name').placeholder = uiInfo[lang].namePlaceholder;

    // Age Screen Texts
    document.getElementById('age-title').innerText = uiInfo[lang].ageTitle;
    document.getElementById('age-yes').innerText = uiInfo[lang].ageYes;
    document.getElementById('age-no').innerText = uiInfo[lang].ageNo;
    document.getElementById('age-legal').innerText = uiInfo[lang].ageLegal;

    // Denied Screen Texts
    document.getElementById('denied-title').innerText = uiInfo[lang].deniedTitle;
    document.getElementById('denied-msg').innerText = uiInfo[lang].deniedMsg;

    // Move to Age screen instead of Name screen
    document.getElementById('lang-screen').classList.add('hidden');
    document.getElementById('age-screen').classList.remove('hidden');
}

function verifyAge(isAdult) {
    if (isAdult) {
        document.getElementById('age-screen').classList.add('hidden');
        document.getElementById('name-screen').classList.remove('hidden');
    } else {
        document.getElementById('age-screen').classList.add('hidden');
        document.getElementById('age-denied').classList.remove('hidden');
        document.getElementById('back-btn').classList.add('hidden'); // Hide back button on denied
    }
}

function saveName() {
    let input = document.getElementById('player-name').value;
    if (input.trim() === '') {
        alert(currentLang === 'en' ? "Please enter your name" : "Tafadhali ingiza jina lako");
        return;
    }
    playerName = input;
    document.getElementById('display-player-name').innerText = "Hey, " + playerName + "!";

    document.getElementById('name-screen').classList.add('hidden');
    document.getElementById('start-screen').classList.remove('hidden');
}

function startQuiz() {
    fetch('api/start.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ quiz_id: quizId, player_name: playerName, site_id: siteId })
    })
        .then(r => r.json())
        .then(d => {
            token = d.token;
            loadQuestions();
        });
}

function loadQuestions() {
    fetch(`api/get_questions.php?quiz_id=${quizId}&lang=${currentLang}`)
        .then(r => r.json())
        .then(d => {
            questions = d;
            document.getElementById('start-screen').classList.add('hidden');
            document.getElementById('quiz-screen').classList.remove('hidden');
            showQuestion();
            preloadResultImages(); // Start downloading result assets secretly
        });
}

function preloadResultImages() {
    const assets = [
        'assets/original1.png', 'assets/original2.png',
        'assets/pineapple1.png', 'assets/pineapple2.png',
        'assets/guarana1.png', 'assets/guarana2.png',
        'assets/crown.png'
    ];
    assets.forEach(src => {
        const img = new Image();
        img.src = src;
    });
}

function showQuestion() {
    let q = questions[qIndex];
    document.getElementById("question").innerText = q.question;
    document.getElementById("current-q").innerText = qIndex + 1;
    document.getElementById("total-q").innerText = questions.length;

    let html = '';
    q.options.forEach(o => {
        html += `<button class="btn btn-option" onclick="answer(${q.id}, ${o.id})">${o.option_text}</button>`;
    });
    document.getElementById("options").innerHTML = html;

    // Solid Background for Quiz
    document.documentElement.style.setProperty('--bg-image', 'none');
    document.body.style.backgroundColor = '#4AC3D8';
}

function updateBackground(imageUrl) {
    let style = document.documentElement.style;
    if (!imageUrl || imageUrl.trim() === '') {
        style.setProperty('--bg-image', 'none');
    } else {
        style.setProperty('--bg-image', `url('${imageUrl}')`);
    }
    document.body.style.backgroundColor = ''; // Reset to default when using image/normal flow
}

function answer(qid, oid) {
    fetch("api/submit_answer.php", {
        method: "POST",
        body: JSON.stringify({ token: token, question_id: qid, option_id: oid })
    });

    qIndex++;
    if (qIndex < questions.length) {
        showQuestion();
    } else {
        finish();
    }
}

function finish() {
    document.getElementById('quiz-screen').classList.add('hidden');
    document.getElementById('result-screen').classList.remove('hidden');
    updateBackground(''); // Clear bg to let result images take over

    fetch(`api/finish.php?token=${token}&lang=${currentLang}`)
        .then(r => r.json())
        .then(d => {
            playResultSequence(d.result);
        });
}

let currentFlavor = 'original';

function playResultSequence(flavor) {
    let img = document.getElementById('result-img');
    let nextBtn = document.getElementById('result-next-btn');

    // Safety check for flavor key
    if (!['original', 'pineapple', 'guarana'].includes(flavor)) flavor = 'original';
    currentFlavor = flavor;

    // Phase 1: Image 1
    // Hide actions if re-running
    document.getElementById('final-actions').classList.add('hidden');

    img.src = `assets/${flavor}1.png`;
    img.classList.remove('hidden');

    // Play Sound & Animation
    // Play Sound & Animation removed request
    // let audio = document.getElementById('tada-sound');
    // if (audio) { ... }



    startCelebration();

    // Show Next Button
    nextBtn.classList.remove('hidden');
}

function startCelebration() {
    let container = document.getElementById('celebration-container');
    container.innerHTML = '';
    container.classList.remove('hidden');

    // Number of elements
    const elementCount = 100;

    for (let i = 0; i < elementCount; i++) {
        let piece = document.createElement('div');
        piece.classList.add('vibe-element');

        // Randomly decide between bubble and ice cube
        const isBubble = Math.random() > 0.4; // More bubbles than ice cubes

        if (isBubble) {
            piece.classList.add('bubble');
            let size = Math.random() * 20 + 5 + 'px';
            piece.style.width = size;
            piece.style.height = size;
        } else {
            piece.classList.add('ice-cube');
            let size = Math.random() * 25 + 15 + 'px';
            piece.style.width = size;
            piece.style.height = size;
            piece.style.borderRadius = (Math.random() * 5 + 3) + 'px';
        }

        // Random horizontal position
        piece.style.left = Math.random() * 100 + 'vw';

        // Random animation duration and delay
        piece.style.animationDuration = (Math.random() * 4 + 3) + 's'; // 3-7s
        piece.style.animationDelay = (Math.random() * 3) + 's'; // 0-3s delay

        container.appendChild(piece);
    }

    // Cleanup
    setTimeout(() => {
        container.innerHTML = '';
        container.classList.add('hidden');
    }, 10000); // Wait longer for all elements to finish
}

function showPhase2() {
    let img = document.getElementById('result-img');
    let nextBtn = document.getElementById('result-next-btn');
    let actions = document.getElementById('final-actions');

    // Hide Next Button
    nextBtn.classList.add('hidden');

    // Phase 2: Image 2
    img.src = `assets/${currentFlavor}2.png`;
    img.classList.add('fade-in');

    // Phase 3: Show Actions after delay (e.g., 2s reading time)
    setTimeout(() => {
        actions.classList.remove('hidden');
        actions.classList.add('fade-in');
    }, 2000);
}

function showResult(key, content) {
    // Deprecated in favor of playResultSequence, keeping empty to prevent errors if referenced
}

function viewSocialProof() {
    fetch("api/bonus.php", {
        method: "POST",
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            'token': token
        })
    })
        .then(r => r.json())
        .then(d => {
            if (d.status === 'success') {
                showSocialProof(d);
            }
        });
}

function showSocialProof(data) {
    document.getElementById('result-screen').classList.add('hidden');
    document.getElementById('social-proof-screen').classList.remove('hidden');

    // Restore blurred background
    updateBackground(`assets/${currentFlavor}1.png`); // Use the result image as blur bg? Or Splash? 
    // Request asked for blur background. Let's use the bottle image blur or splash.
    // Splash logic is in app.js. 
    // Let's force update css var.
    let style = document.documentElement.style;
    style.setProperty('--bg-image', `url('assets/${currentFlavor}1.png')`);


    // Set Texts
    let flavorName = currentFlavor.charAt(0).toUpperCase() + currentFlavor.slice(1);
    document.querySelectorAll('.flavor-name').forEach(el => el.innerText = flavorName);

    document.getElementById('final-player-name').innerText = playerName || "You";

    let listHtml = '';
    data.others.forEach(name => {
        // ensure @ prefix style
        let cleanName = name.startsWith('@') ? name : '@' + name;
        listHtml += `<li>${cleanName}</li>`;
    });
    document.getElementById('others-list').innerHTML = listHtml;
}

function shareResult() {
    if (navigator.share) {
        navigator.share({
            title: 'My Vibe',
            text: 'Check my Smirnoff Vibe! / Cheki vibe yangu!',
            url: window.location.href
        });
    } else {
        alert("Link: " + window.location.href);
    }
}
