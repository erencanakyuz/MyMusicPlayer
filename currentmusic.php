<?php
// currentmusic.php
// Student Name: [Your Name]
// Student ID: [Your Student ID]
// Description: Displays detailed information about a selected song.
// Updates the user's play history for the currently playing song.

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require_once 'config.php';

$song_id = isset($_GET['song_id']) ? (int)$_GET['song_id'] : 0;
if ($song_id <= 0) {
    header("Location: homepage.php"); // Redirect if no valid song ID
    exit();
}

$user_id = $_SESSION['user_id'];
$current_song = null;
$message = '';
$message_type = '';

// Fetch song details
$stmt_song = $conn->prepare("
    SELECT S.title, S.duration, S.genre, S.release_date, S.image,
           A.name AS artist_name, AL.name AS album_name
    FROM SONGS S
    JOIN ALBUMS AL ON S.album_id = AL.album_id
    JOIN ARTISTS A ON AL.artist_id = A.artist_id
    WHERE S.song_id = ?
");
$stmt_song->bind_param("i", $song_id);
$stmt_song->execute();
$result_song = $stmt_song->get_result();

if ($result_song->num_rows > 0) {
    $current_song = $result_song->fetch_assoc();

    // Update PLAY_HISTORY for the current user and song
    $insert_history_stmt = $conn->prepare("INSERT INTO PLAY_HISTORY (user_id, song_id, playtime) VALUES (?, ?, NOW())");
    $insert_history_stmt->bind_param("ii", $user_id, $song_id);
    if ($insert_history_stmt->execute()) {
        $message = "Now playing: " . htmlspecialchars($current_song['title']) . ". Play history updated.";
        $message_type = 'success';
    } else {
        $message = "Error updating play history: " . $conn->error;
        $message_type = 'error';
    }
    $insert_history_stmt->close();

} else {
    $message = "Song not found.";
    $message_type = 'error';
    $current_song = null;
}
$stmt_song->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Now Playing: <?php echo $current_song ? htmlspecialchars($current_song['title']) : 'Song Not Found'; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Now Playing</h1>
        <p><a href="homepage.php">Back to Homepage</a></p>

        <?php if ($message): ?>
            <p class="<?php echo $message_type; ?>-message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if ($current_song): ?>
            <div style="text-align: center; margin-top: 30px;">
                <img src="<?php echo htmlspecialchars($current_song['image']); ?>" alt="Song/Album Art" style="width: 300px; height: 300px; object-fit: cover; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.2);">
                <h2><?php echo htmlspecialchars($current_song['title']); ?></h2>
                <p><strong>Artist:</strong> <?php echo htmlspecialchars($current_song['artist_name']); ?></p>
                <p><strong>Album:</strong> <?php echo htmlspecialchars($current_song['album_name']); ?></p>
                <p><strong>Genre:</strong> <?php echo htmlspecialchars($current_song['genre']); ?></p>
                <p><strong>Duration:</strong> <?php echo gmdate("i:s", $current_song['duration']); ?></p>
                <p><strong>Release Date:</strong> <?php echo htmlspecialchars($current_song['release_date']); ?></p>
            </div>
        <?php else: ?>
            <p>Could not retrieve song details. Please go back to the <a href="homepage.php">homepage</a>.</p>
        <?php endif; ?>
    </div>
</body>
</html>