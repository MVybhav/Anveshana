<?php
session_start();

// For demo, simulate a logged-in user
// In production, use your authentication/session logic
// Removed hardcoded user_id for production readiness. 

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "anveshana"; // edit your DB name

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("DB connection failed.");

$sql = "SELECT r.*, u.username FROM reels r JOIN users u ON r.user_id = u.id WHERE r.status = 'active' ORDER BY r.id DESC";
$result = $conn->query($sql);

function already_liked($conn, $reel_id, $user_id) {
    $q = $conn->query("SELECT 1 FROM reel_likes WHERE user_id=$user_id AND reel_id=$reel_id");
    return $q && $q->num_rows > 0;
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <title>Reels</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="" />
  <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
    href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Plus+Jakarta+Sans:wght@400;500;700;800"/>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <style>
    body {font-family: 'Plus Jakarta Sans', 'Noto Sans', sans-serif;}
    main { height: calc(100vh - 64px); } /* Adjust 64px based on header height */
    .like-animated {
      animation: pop 0.4s;
    }
    @keyframes pop {
      0% { transform: scale(1); }
      50% { transform: scale(1.3); }
      100% { transform: scale(1); }
    }
    .reel-shadow {
      box-shadow: 0 8px 28px #000a;
    }
  </style>
</head>
<body class="bg-[#181111] relative flex flex-col min-h-screen text-white">
  <!-- HEADER -->
  <header class="flex items-center justify-between border-b border-b-[#382929] px-5 md:px-10 py-3">
    <div class="flex items-center justify-center w-full">
      <h2 class="font-bold text-2xl tracking-tight">Anveshana Travel Diaries</h2>
    </div>
  </header>
  <!-- MAIN TRAVEL DIARIES FEED -->
  <main class="flex flex-col items-center justify-center w-full h-full overflow-hidden">
    <?php if ($result && $result->num_rows > 0): ?>
      <div class="w-full h-full flex flex-col items-center max-w-[520px] mx-auto">
        <?php while($row = $result->fetch_assoc()): 
          $reel_id = intval($row['id']);
          $user_id = isset($_SESSION['userid']) ? intval($_SESSION['userid']) : 0;
          $liked = ($user_id > 0) ? already_liked($conn, $reel_id, $user_id) : false;
        ?>
        <div class="relative w-full h-full flex flex-col items-center">
          <!-- REEL VIDEO -->
            <div class="relative w-full h-0 pb-[177.77%] bg-black flex items-center justify-center overflow-hidden rounded-lg">
                <div class="absolute top-0 left-0 w-full h-full">
            <video class="object-cover w-full h-full" 
              src="<?= htmlspecialchars($row['video_path']) ?>" 
              loop autoplay muted playsinline
              preload="auto"
              data-reel-id="<?=$reel_id?>"></video>
            <div class="video-error-message hidden absolute inset-0 flex items-center justify-center bg-black bg-opacity-75 text-white text-center p-4 rounded-[2rem]">
              <p>Video failed to load. Please try again later.</p>
            </div>
          </div>
          <!-- OVERLAY INFO -->
          <div class="absolute inset-x-0 bottom-0 p-6 bg-gradient-to-t from-black/70 to-transparent text-white flex flex-col">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2">
                <img src="https://via.placeholder.com/40" alt="User Avatar" class="rounded-full size-10 object-cover" />
                <p class="font-semibold"><?= htmlspecialchars($row['username']) ?></p>
                <button class="ml-2 px-3 py-1 bg-white/20 rounded-full text-sm font-medium">Follow</button>
              </div>
              <div class="flex flex-col items-center">
                <button type="button" class="outline-none like-btn group p-2 rounded-full" aria-label="Like reel">
                  <svg class="heart w-7 h-7 <?= $liked ? 'text-pink-400' : 'text-[#b89d9f] group-hover:text-pink-400' ?>" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M16.81 3.58c-1.88 0-3.55 1.09-4.32 2.72-.77-1.62-2.44-2.72-4.32-2.72C5.01 3.58 2.5 6.07 2.5 9.21c0 4.63 7.12 8.68 8.06 9.22.29.18.67.18.96 0 .95-.54 8.08-4.59 8.08-9.22 0-3.14-2.51-5.63-5.29-5.63Z"/>
                  </svg>
                  <span class="count-label text-xs mt-0.5"><?= intval($row['likes_count']) ?></span>
                </button>
                <div class="flex flex-col items-center mt-2">
                  <svg width="24" height="24" fill="none" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2Zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8Zm-1-13h2v6h-2V7Zm0 8h2v2h-2v-2Z"/></svg>
                  <span class="text-xs mt-0.5">1</span>
                </div>
                <div class="flex flex-col items-center mt-2">
                  <svg width="24" height="24" fill="none" viewBox="0 0 24 24"><path fill="currentColor" d="M20.5 11.5c.828 0 1.5.672 1.5 1.5s-.672 1.5-1.5 1.5h-1.086l-2.914 2.914a1.5 1.5 0 0 1-2.121 0L11.5 14.5H7.5c-.828 0-1.5-.672-1.5-1.5s.672-1.5 1.5-1.5h4.086l2.914-2.914a1.5 1.5 0 0 1 2.121 0L17.5 9.5H20.5Z"/></svg>
                  <span class="text-xs mt-0.5">1</span>
                </div>
              </div>
            </div>
            <p class="font-semibold text-base pb-0.5"><?= htmlspecialchars($row['title']) ?></p>
            <?php if ($row['description']): ?>
              <p class="text-[#bba0a2] text-sm mb-2"><?= htmlspecialchars($row['description']) ?></p>
            <?php endif; ?>
            <div class="flex items-center gap-2 text-[#b89d9f] text-sm">
              <svg width="18" height="18" fill="none" viewBox="0 0 20 20"><path d="M10 3C15 2.999 17 6.501 17 10.001C17 13.501 15 17.003 10 17.001C5 16.999 3 13.501 3 10.001C3 6.501 5 2.999 10 3Z" fill="currentColor"></path></svg>
              <span><?= intval($row['views_count']) ?></span>
            </div>
            <div class="flex items-center gap-2 mt-2">
              <svg width="20" height="20" fill="none" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2Zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8Zm-1-13h2v6h-2V7Zm0 8h2v2h-2v-2Z"/></svg>
              <span class="text-sm">Echoes Of Us (Instrumental)</span>
            </div>
          </div>
        </div>
        <?php endwhile ?>
      </div>
    <?php else: ?>
      <div class="text-center text-lg text-[#8b7577] my-32">No reels found.</div>
    <?php endif; ?>
  </main>
<script>
document.querySelectorAll('.like-btn').forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    const reelId = this.closest('.relative.w-full.h-full.flex.flex-col.items-center').querySelector('video').dataset.reelId;
    const countSpan = btn.querySelector('.count-label');
    const heartIcon = btn.querySelector('.heart');
    const isLiked = heartIcon.classList.contains('text-pink-400');

    // Prevent multiple clicks while processing
    if (btn.disabled) return;
    btn.disabled = true;
    btn.classList.add('like-animated');

    // Optimistically update UI
    if (isLiked) {
      countSpan.textContent = parseInt(countSpan.textContent) - 1;
      heartIcon.classList.remove('text-pink-400');
      heartIcon.classList.add('text-[#b89d9f]');
    } else {
      countSpan.textContent = parseInt(countSpan.textContent) + 1;
      heartIcon.classList.add('text-pink-400');
      heartIcon.classList.remove('text-[#b89d9f]');
    }

    // AJAX persist to backend
    const fd = new FormData();
    fd.append('reel_id', reelId);
    fetch('like.php', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {

      if (data.status === 'success') {
        // UI already updated optimistically
      } else {
        // Revert UI on error
        alert(data.message);
        if (isLiked) {
          countSpan.textContent = parseInt(countSpan.textContent) + 1;
          heartIcon.classList.add('text-pink-400');
          heartIcon.classList.remove('text-[#b89d9f]');
        } else {
          countSpan.textContent = parseInt(countSpan.textContent) - 1;
          heartIcon.classList.remove('text-pink-400');
          heartIcon.classList.add('text-[#b89d9f]');
        }
      }
      btn.disabled = false;
      btn.classList.remove('like-animated');
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while processing your request.');
      // Revert UI on network error
      if (isLiked) {
        countSpan.textContent = parseInt(countSpan.textContent) + 1;
        heartIcon.classList.add('text-pink-400');
        heartIcon.classList.remove('text-[#b89d9f]');
      } else {
        countSpan.textContent = parseInt(countSpan.textContent) - 1;
        heartIcon.classList.remove('text-pink-400');
        heartIcon.classList.add('text-[#b89d9f]');
      }
      btn.disabled = false;
      btn.classList.remove('like-animated');
    });
  });
});
// Optional: count a view when a video enters view (using IntersectionObserver for production apps)
// Here, we increment with an autoplayed video for demo purposes.
document.querySelectorAll('video').forEach(function (video) {
  video.addEventListener('play', function () {
    if (!video.dataset.viewed) {
      fetch('view.php?reel_id=' + video.dataset.reelId, {method:'POST'});
      video.dataset.viewed = "1";
    }
  });
});

document.querySelectorAll('video').forEach(video => {
  video.addEventListener('error', function() {
    console.error('Error loading video:', this.src);
    this.style.display = 'none'; // Hide the broken video element
    const errorMessage = this.nextElementSibling; // Assuming the error message div is the next sibling
    if (errorMessage && errorMessage.classList.contains('video-error-message')) {
      errorMessage.classList.remove('hidden');
    }
  });
});
</script>
</body>
</html>
