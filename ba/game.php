<?php
session_start();
if (!isset($_SESSION['ba_user_id'])) {
    header("Location: login.php");
    exit;
}
$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
$cid = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
if(!$tid && !$cid) { die("Invalid Access"); }
$queryStr = $tid ? "tid=$tid" : "cid=$cid";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spin & Win | Smirnoff</title>
    <link rel="stylesheet" href="../assets/css/ba.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body { overflow: hidden; text-align: center; }
        .game-title { text-transform: uppercase; font-size: 1.5rem; letter-spacing: 5px; color: #fff; margin-top: 20px; text-shadow: 0 0 10px #f00; }
        .spin-btn {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 80px; height: 80px;
            border-radius: 50%;
            background: #fff;
            border: 5px solid var(--primary);
            color: var(--primary);
            font-weight: bold;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 20;
            box-shadow: 0 0 30px #f00;
            display: flex;
            align-items: center; justify-content: center;
            text-transform: uppercase;
        }
        .spin-btn:active { transform: translate(-50%, -50%) scale(0.95); }
        .spin-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    </style>
</head>
<body>

    <div class="game-title">Spin The Wheel</div>

    <div class="wheel-container">
        <div class="spin-arrow"></div>
        <canvas id="wheelCanvas" width="500" height="500"></canvas>
        <button id="spinBtn" class="spin-btn">SPIN</button>
    </div>

    <div id="resultModal" class="modal">
        <div class="modal-content">
            <h2>CONGRATULATIONS!</h2>
            <p>You have won:</p>
            <div class="prize-reveal" id="prizeText">...</div>
            <button class="btn-neon" onclick="finish()">Next Customer</button>
        </div>
    </div>
    
    <div id="confetti-container"></div>


    <script>
        const queryStr = "<?php echo $queryStr; ?>";
        const canvas = document.getElementById('wheelCanvas');
        const ctx = canvas.getContext('2d');
        const spinBtn = document.getElementById('spinBtn');
        let segments = [];
        let startAngle = 0;
        let arc = 0;
        let spinTimeout = null;
        let spinAngleStart = 10;
        let spinTime = 0;
        let spinTimeTotal = 0;
        let isSpinning = false;
        
        // Colors
        const colors = ["#e60000", "#111111", "#cc0000", "#333333"];

        // Init
        fetch(`../api/get_prizes.php?${queryStr}`)
            .then(res => res.json())
            .then(data => {
                if(data.length === 0) {
                    alert('No prizes available for this tier.');
                    window.location = 'index.php';
                    return;
                }
                segments = data;
                arc = Math.PI * 2 / segments.length;
                drawWheel();
            });

        function drawWheel() {
            if (canvas.getContext) {
                const outsideRadius = 240;
                const textRadius = 160;
                const insideRadius = 50;

                ctx.clearRect(0,0,500,500);

                ctx.strokeStyle = "rgba(0,0,0,0)";
                ctx.lineWidth = 0;
                
                // Font settings
                ctx.font = 'bold 16px Outfit, sans-serif';

                for(let i = 0; i < segments.length; i++) {
                    const angle = startAngle + i * arc;
                    ctx.fillStyle = colors[i % colors.length];
                    
                    ctx.beginPath();
                    ctx.arc(250, 250, outsideRadius, angle, angle + arc, false);
                    ctx.arc(250, 250, insideRadius, angle + arc, angle, true);
                    ctx.stroke();
                    ctx.fill();

                    ctx.save();
                    ctx.shadowOffsetX = -1;
                    ctx.shadowOffsetY = -1;
                    ctx.shadowBlur    = 0;
                    ctx.shadowColor   = "rgb(220,220,220)";
                    ctx.fillStyle = "white";
                    ctx.translate(250 + Math.cos(angle + arc / 2) * textRadius, 
                                  250 + Math.sin(angle + arc / 2) * textRadius);
                    ctx.rotate(angle + arc / 2 + Math.PI); // Rotate text to point inward
                    const text = segments[i].name;
                    ctx.fillText(text, -ctx.measureText(text).width / 2, 0);
                    ctx.restore();
                } 
            }
        }

        spinBtn.addEventListener('click', () => {
            if(isSpinning) return;
            spinBtn.disabled = true;
            isSpinning = true;

            // Request result from server
            fetch('../api/spin_process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: queryStr
            })
            .then(res => res.json())
            .then(data => {
                if(data.error) {
                    alert(data.error);
                    window.location = 'index.php';
                    return;
                }
                
                // Determine target angle
                const winningId = data.id;
                const name = data.text;
                
                // Find index of winner
                const winnerIndex = segments.findIndex(s => s.id == winningId);
                
                // Calculate rotation needed to put this segment at TOP (270deg or -PI/2)
                // The arrow is at top (-90 degrees visually).
                // In canvas, 0 is at 3 o'clock. 
                // We need to rotate so that the center of the winning segment matches -PI/2.
                
                // Current Angle of center of segment i = startAngle + i*arc + arc/2
                // We want: Angle_Final + i*arc + arc/2 = -PI/2 (mod 2PI)
                
                // But easier: spin randomly + known delta.
                // Let's rely on time-based spin and "snap" to result? 
                // Or just physics simulation that stops at the right place?
                // PRE-CALCULATED ROTATION is best.
                
                // Angle to stop:
                // We want the arrow (at top, -Math.PI/2) to point to index.
                // The segment is at [index * arc, (index+1) * arc].
                // Center is index * arc + arc/2.
                // Required rotation R such that: (index*arc + arc/2 + R) % 2PI = -PI/2
                
                const segmentAngle = winnerIndex * arc + arc / 2;
                const targetRotation = (3 * Math.PI / 2) - segmentAngle; 
                // 270 deg is 3PI/2.
                
                // Add extra spins
                const extraSpins = Math.PI * 2 * 10; // 10 spins
                const totalRotation = extraSpins + targetRotation;
                
                // Animation Params
                spinTimeTotal = 5000;
                spinTime = 0;
                // We need to start from current 'startAngle' (which is 0 usually)
                // Actually startAngle is the variable we animate.
                // We need to end at totalRotation (normalized).
                // Let's use an easing function.
                
                animateSpin(totalRotation - startAngle, data.text);
            });
        });

        function animateSpin(deltaObj, prizeName) {
            const startTime = Date.now();
            const initialStartAngle = startAngle;
            
            function tick() {
                const now = Date.now();
                const elapsed = now - startTime;
                
                if (elapsed < spinTimeTotal) {
                    // Ease out cubic
                    const t = elapsed / spinTimeTotal;
                    const t1 = t - 1;
                    const ease = (t1 * t1 * t1 + 1); // 0 to 1
                    
                    const currentDelta = deltaObj * ease;
                    startAngle = initialStartAngle + currentDelta;
                    drawWheel();
                    requestAnimationFrame(tick);
                } else {
                    // Done
                    startAngle = initialStartAngle + deltaObj;
                    drawWheel();
                    showResult(prizeName);
                }
            }
            tick();
        }

        function showResult(text) {
            document.getElementById('prizeText').innerText = text;
            document.getElementById('resultModal').classList.add('active');
            

            
            createConfetti();
        }

        function finish() {
            window.location = 'index.php';
        }

        function createConfetti() {
            const container = document.getElementById('confetti-container');
            container.innerHTML = '';
            
            const elementCount = 80;

            for (let i = 0; i < elementCount; i++) {
                let piece = document.createElement('div');
                piece.classList.add('vibe-element');

                // Randomly decide between bubble and ice cube
                const isBubble = Math.random() > 0.4;

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

                piece.style.left = Math.random() * 100 + 'vw';
                piece.style.animationDuration = (Math.random() * 4 + 3) + 's';
                piece.style.animationDelay = (Math.random() * 3) + 's';

                container.appendChild(piece);
            }

            // Cleanup
            setTimeout(() => {
                container.innerHTML = '';
            }, 10000);
        }
    </script>
</body>
</html>
