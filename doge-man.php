php
<?php
header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Doge-Man: Crypto Chase</title>
    <style>
        canvas {
            border: 1px solid black;
            background: #000;
        }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: #222;
        }
    </style>
</head>
<body>
    <canvas id="gameCanvas" width="800" height="600"></canvas>

    <script>
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');
        const tileSize = 40;

        // Load images
        const dogeImg = new Image();
        dogeImg.src = 'https://cryptologos.cc/logos/dogecoin-doge-logo.png';
        const btcImg = new Image();
        btcImg.src = 'https://cryptologos.cc/logos/bitcoin-btc-logo.png';

        // Simple maze (1 = wall, 0 = path)
        const maze = [
            [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
            [1,0,0,0,1,0,0,0,0,1,0,0,0,0,1,0,0,0,0,1],
            [1,0,1,0,1,0,1,1,0,1,0,1,1,0,1,0,1,0,1,1],
            [1,0,1,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,0,1],
            [1,0,1,1,1,1,0,1,1,1,1,1,0,1,1,1,1,0,1,1],
            [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
            [1,1,1,1,1,1,0,1,1,0,1,1,0,1,1,1,1,1,1,1],
            [1,0,0,0,0,0,0,1,0,0,0,1,0,0,0,0,0,0,0,1],
            [1,0,1,1,1,1,0,1,1,1,1,1,0,1,1,1,1,0,1,1],
            [1,0,0,0,1,0,0,0,0,1,0,0,0,0,1,0,0,0,0,1],
            [1,1,1,0,1,0,1,1,0,1,0,1,1,0,1,0,1,1,1,1],
            [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
            [1,0,1,1,1,1,1,1,0,1,1,1,1,1,1,1,1,0,1,1],
            [1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1],
            [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1]
        ];

        // Game objects
        const doge = { x: tileSize, y: tileSize, speed: 4, size: tileSize * 0.8 };
        const ghosts = [
            { x: 18 * tileSize, y: 1 * tileSize, speed: 2, size: tileSize * 0.8, mode: 'scatter' },
            { x: 18 * tileSize, y: 5 * tileSize, speed: 2, size: tileSize * 0.8, mode: 'chase' },
            { x: 18 * tileSize, y: 9 * tileSize, speed: 2, size: tileSize * 0.8, mode: 'scatter' },
            { x: 18 * tileSize, y: 13 * tileSize, speed: 2, size: tileSize * 0.8, mode: 'chase' }
        ];

        const dots = [];
        for (let y = 0; y < maze.length; y++) {
            for (let x = 0; x < maze[y].length; x++) {
                if (maze[y][x] === 0) {
                    dots.push({ x: x * tileSize + tileSize/2, y: y * tileSize + tileSize/2, size: 5 });
                }
            }
        }

        let score = 0;
        const keys = {};
        window.addEventListener('keydown', (e) => keys[e.key] = true);
        window.addEventListener('keyup', (e) => keys[e.key] = false);

        function collides(x, y, size) {
            const tileX = Math.floor(x / tileSize);
            const tileY = Math.floor(y / tileSize);
            return maze[tileY] && maze[tileY][tileX] === 1;
        }

        function moveDoge() {
            let newX = doge.x;
            let newY = doge.y;

            if (keys['ArrowUp']) newY -= doge.speed;
            if (keys['ArrowDown']) newY += doge.speed;
            if (keys['ArrowLeft']) newX -= doge.speed;
            if (keys['ArrowRight']) newX += doge.speed;

            if (!collides(newX, newY, doge.size)) {
                doge.x = Math.max(doge.size/2, Math.min(canvas.width - doge.size/2, newX));
                doge.y = Math.max(doge.size/2, Math.min(canvas.height - doge.size/2, newY));
            }
        }

        function moveGhosts() {
            ghosts.forEach(ghost => {
                // Switch modes periodically
                if (Math.random() < 0.01) {
                    ghost.mode = ghost.mode === 'chase' ? 'scatter' : 'chase';
                }

                const tileX = Math.floor(ghost.x / tileSize);
                const tileY = Math.floor(ghost.y / tileSize);
                const directions = [
                    { dx: 0, dy: -ghost.speed },
                    { dx: 0, dy: ghost.speed },
                    { dx: -ghost.speed, dy: 0 },
                    { dx: ghost.speed, dy: 0 }
                ];

                let targetX = ghost.mode === 'chase' ? doge.x : ghost.x + (Math.random() - 0.5) * 400;
                let targetY = ghost.mode === 'chase' ? doge.y : ghost.y + (Math.random() - 0.5) * 400;

                let bestDirection = null;
                let bestDistance = Infinity;

                directions.forEach(dir => {
                    const newX = ghost.x + dir.dx;
                    const newY = ghost.y + dir.dy;
                    if (!collides(newX, newY, ghost.size)) {
                        const distance = Math.sqrt(
                            Math.pow(targetX - newX, 2) +
                            Math.pow(targetY - newY, 2)
                        );
                        if (distance < bestDistance) {
                            bestDistance = distance;
                            bestDirection = dir;
                        }
                    }
                });

                if (bestDirection) {
                    ghost.x += bestDirection.dx;
                    ghost.y += bestDirection.dy;
                }

                // Collision with Doge
                const distance = Math.sqrt(
                    Math.pow(doge.x - ghost.x, 2) +
                    Math.pow(doge.y - ghost.y, 2)
                );
                if (distance < doge.size) {
                    alert('Game Over! Score: ' + score);
                    resetGame();
                }
            });
        }

        function checkDots() {
            dots.forEach((dot, index) => {
                const distance = Math.sqrt(
                    Math.pow(doge.x - dot.x, 2) +
                    Math.pow(doge.y - dot.y, 2)
                );
                if (distance < doge.size/2 + dot.size) {
                    dots.splice(index, 1);
                    score += 10;
                }
            });
        }

        function resetGame() {
            doge.x = tileSize;
            doge.y = tileSize;
            ghosts[0].x = 18 * tileSize; ghosts[0].y = 1 * tileSize;
            ghosts[1].x = 18 * tileSize; ghosts[1].y = 5 * tileSize;
            ghosts[2].x = 18 * tileSize; ghosts[2].y = 9 * tileSize;
            ghosts[3].x = 18 * tileSize; ghosts[3].y = 13 * tileSize;
            score = 0;
            dots.length = 0;
            for (let y = 0; y < maze.length; y++) {
                for (let x = 0; x < maze[y].length; x++) {
                    if (maze[y][x] === 0) {
                        dots.push({ x: x * tileSize + tileSize/2, y: y * tileSize + tileSize/2, size: 5 });
                    }
                }
            }
        }

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Draw maze
            ctx.fillStyle = 'blue';
            for (let y = 0; y < maze.length; y++) {
                for (let x = 0; x < maze[y].length; x++) {
                    if (maze[y][x] === 1) {
                        ctx.fillRect(x * tileSize, y * tileSize, tileSize, tileSize);
                    }
                }
            }

            // Draw dots
            ctx.fillStyle = 'white';
            dots.forEach(dot => {
                ctx.beginPath();
                ctx.arc(dot.x, dot.y, dot.size, 0, Math.PI * 2);
                ctx.fill();
            });

            // Draw Doge
            ctx.drawImage(dogeImg, doge.x - doge.size/2, doge.y - doge.size/2, doge.size, doge.size);

            // Draw ghosts
            ghosts.forEach(ghost => {
                ctx.drawImage(btcImg, ghost.x - ghost.size/2, ghost.y - ghost.size/2, ghost.size, ghost.size);
            });

            // Draw score
            ctx.fillStyle = 'white';
            ctx.font = '20px Arial';
            ctx.fillText('Score: ' + score, 10, 30);
        }

        function gameLoop() {
            moveDoge();
            moveGhosts();
            checkDots();
            draw();
            requestAnimationFrame(gameLoop);
        }

        Promise.all([
            new Promise(resolve => dogeImg.onload = resolve),
            new Promise(resolve => btcImg.onload = resolve)
        ]).then(() => gameLoop());
    </script>
</body>
</html>
