<?php
// currentmusic.php
// Student Name: Eren Can Akyüz
 // Student ID: 20220702128
// Description: Displays detailed information about a selected song.
// Updates the user's play history for the currently playing song.

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
$target_playlist_id = null;
$target_playlist_name = "Selected Playlist"; // Default name

// Check if this page was loaded with an action to add to a playlist
$action = isset($_GET['action']) ? $_GET['action'] : null;
if ($action === 'add_to_playlist' && isset($_GET['target_playlist_id'])) {
    $target_playlist_id = (int)$_GET['target_playlist_id'];

    // Fetch target playlist name for display
    $stmt_playlist_name = $conn->prepare("SELECT title FROM PLAYLISTS WHERE playlist_id = ? AND user_id = ?");
    $stmt_playlist_name->bind_param("ii", $target_playlist_id, $user_id);
    $stmt_playlist_name->execute();
    $result_playlist_name = $stmt_playlist_name->get_result();
    if ($result_playlist_name->num_rows > 0) {
        $target_playlist_data = $result_playlist_name->fetch_assoc();
        $target_playlist_name = $target_playlist_data['title'];
    } else {
        // Playlist not found or not owned by user, invalidate action
        $target_playlist_id = null; 
        $message = "Target playlist not found or you do not own it.";
        $message_type = 'error';
    }
    $stmt_playlist_name->close();
}

// Handle the actual song addition if the form is submitted from this page
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_add_to_playlist']) && isset($_POST['song_id_to_add']) && isset($_POST['playlist_id_to_add_to'])) {
    $song_id_to_add = (int)$_POST['song_id_to_add'];
    $playlist_id_to_add_to = (int)$_POST['playlist_id_to_add_to'];

    // Verify song_id matches current page's song_id for security
    if ($song_id_to_add === $song_id && $playlist_id_to_add_to > 0) {
        // Check if song is already in the target playlist
        $stmt_check_exists = $conn->prepare("SELECT playlistsong_id FROM PLAYLIST_SONGS WHERE playlist_id = ? AND song_id = ?");
        $stmt_check_exists->bind_param("ii", $playlist_id_to_add_to, $song_id_to_add);
        $stmt_check_exists->execute();
        $result_check_exists = $stmt_check_exists->get_result();

        if ($result_check_exists->num_rows == 0) {
            // Add song to playlist
            // The PDF requires PLAYLIST_SONGS to have date_added.
            // We'll use NOW() for the date_added.
            $stmt_add_song = $conn->prepare("INSERT INTO PLAYLIST_SONGS (playlist_id, song_id, date_added) VALUES (?, ?, NOW())");
            $stmt_add_song->bind_param("ii", $playlist_id_to_add_to, $song_id_to_add);
            if ($stmt_add_song->execute()) {
                $message = "Successfully added to playlist '" . ($target_playlist_name) . "'!";
                $message_type = 'success';
            } else {
                $message = "Error adding song: " . $conn->error;
                $message_type = 'error';
            }
            $stmt_add_song->close();
        } else {
            $message = "This song is already in the playlist '" . ($target_playlist_name) . "'.";
            $message_type = 'error';
        }
        $stmt_check_exists->close();
    } else {
        $message = "Invalid request for adding song to playlist.";
        $message_type = 'error';
    }
    // Unset action to prevent re-displaying the add button after submission
    $action = null; 
    $target_playlist_id = null;
}

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
        $message = "Now playing: " . ($current_song['title']) . ". Play history updated.";
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
<head>    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Now Playing: <?php echo $current_song ? ($current_song['title']) : 'Song Not Found'; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Now Playing</h1>
        <p><a href="homepage.php">Back to Homepage</a></p>

        <?php if ($message): ?>
            <p class="<?php echo $message_type; ?>-message"><?php echo ($message); ?></p>
        <?php endif; ?>

        <?php if ($current_song): ?>            <div class="now-playing-container">
                <img src="<?php echo ($current_song['image']); ?>" alt="Song/Album Art" class="song-album-art">
                <h2><?php echo ($current_song['title']); ?></h2>
                <p><strong>Artist:</strong> <?php echo ($current_song['artist_name']); ?></p>
                <p><strong>Album:</strong> <?php echo ($current_song['album_name']); ?></p>
                <p><strong>Genre:</strong> <?php echo ($current_song['genre']); ?></p>
                <p><strong>Duration:</strong> <?php echo gmdate("i:s", $current_song['duration']); ?></p>
                <p><strong>Release Date:</strong> <?php echo ($current_song['release_date']); ?></p>

                <?php if ($action === 'add_to_playlist' && $target_playlist_id && $current_song): ?>                    <div class="add-to-playlist-action add-playlist-section">
                        <h3>Add to Playlist</h3>
                        <p>Add "<?php echo ($current_song['title']); ?>" to "<?php echo ($target_playlist_name); ?>"?</p>
                        <form action="currentmusic.php?song_id=<?php echo $song_id; ?>" method="post">
                            <input type="hidden" name="song_id_to_add" value="<?php echo $song_id; ?>">
                            <input type="hidden" name="playlist_id_to_add_to" value="<?php echo $target_playlist_id; ?>">
                            <button type="submit" name="confirm_add_to_playlist">Confirm Add</button>
                        </form>
                    </div>
                <?php endif; ?>

            </div>
        <?php else: ?>
            <p>Could not retrieve song details. Please go back to the <a href="homepage.php">homepage</a>.</p>
        <?php endif; ?>
    </div>
</body>
</html>