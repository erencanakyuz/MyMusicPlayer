<?php
// playlistpage.php
// Student Name: [Your Name]
// Student ID: [Your Student ID]
// Description: Displays all songs in a selected playlist and allows adding new songs to it.

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$playlist_id = isset($_GET['playlist_id']) ? (int)$_GET['playlist_id'] : 0;
if ($playlist_id <= 0) {
    header("Location: homepage.php"); // Redirect if no valid playlist ID
    exit();
}

$user_id = $_SESSION['user_id'];
$playlist_title = "Unknown Playlist";
$message = '';
$message_type = ''; // success or error

// Fetch playlist details to verify ownership
$stmt_playlist_details = $conn->prepare("SELECT title FROM PLAYLISTS WHERE playlist_id = ? AND user_id = ?");
$stmt_playlist_details->bind_param("ii", $playlist_id, $user_id);
$stmt_playlist_details->execute();
$result_playlist_details = $stmt_playlist_details->get_result();
if ($result_playlist_details->num_rows > 0) {
    $playlist_data = $result_playlist_details->fetch_assoc();
    $playlist_title = $playlist_data['title'];
} else {
    header("Location: homepage.php"); // Playlist not found or not owned by user
    exit();
}
$stmt_playlist_details->close();

// --- Handle Add Song to Playlist ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_song_query'])) {
    $song_search_query = sanitize_input($conn, $_POST['add_song_query']);

    // Search for the song
    $stmt_find_song = $conn->prepare("SELECT song_id, title, image FROM SONGS WHERE title LIKE ? LIMIT 1");
    $like_query = "%" . $song_search_query . "%";
    $stmt_find_song->bind_param("s", $like_query);
    $stmt_find_song->execute();
    $result_find_song = $stmt_find_song->get_result();

    if ($result_find_song->num_rows > 0) {
        $found_song = $result_find_song->fetch_assoc();
        // Check if song is already in playlist
        $stmt_check_exists = $conn->prepare("SELECT playlistsong_id FROM PLAYLIST_SONGS WHERE playlist_id = ? AND song_id = ?");
        $stmt_check_exists->bind_param("ii", $playlist_id, $found_song['song_id']);
        $stmt_check_exists->execute();
        $result_check_exists = $stmt_check_exists->get_result();

        if ($result_check_exists->num_rows == 0) {
            // Add song to playlist
            $stmt_add_song = $conn->prepare("INSERT INTO PLAYLIST_SONGS (playlist_id, song_id) VALUES (?, ?)");
            $stmt_add_song->bind_param("ii", $playlist_id, $found_song['song_id']);
            if ($stmt_add_song->execute()) {
                $message = "'" . htmlspecialchars($found_song['title']) . "' added to playlist!";
                $message_type = 'success';
            } else {
                $message = "Error adding song: " . $conn->error;
                $message_type = 'error';
            }
            $stmt_add_song->close();
        } else {
            $message = "'" . htmlspecialchars($found_song['title']) . "' is already in this playlist.";
            $message_type = 'error';
        }
        $stmt_check_exists->close();
    } else {
        $message = "Song '" . htmlspecialchars($song_search_query) . "' not found.";
        $message_type = 'error';
    }
    $stmt_find_song->close();
}


// --- Fetch Songs in the Playlist ---
$playlist_songs = [];
$stmt_songs = $conn->prepare("
    SELECT S.song_id, S.title, S.duration, S.genre, S.image, A.name AS artist_name, C.country_name AS artist_country
    FROM PLAYLIST_SONGS PS
    JOIN SONGS S ON PS.song_id = S.song_id
    JOIN ALBUMS AL ON S.album_id = AL.album_id
    JOIN ARTISTS A ON AL.artist_id = A.artist_id
    JOIN COUNTRY C ON A.country_id = C.country_id
    WHERE PS.playlist_id = ?
    ORDER BY PS.date_added ASC
");
$stmt_songs->bind_param("i", $playlist_id);
$stmt_songs->execute();
$result_songs = $stmt_songs->get_result();
while ($row = $result_songs->fetch_assoc()) {
    $playlist_songs[] = $row;
}
$stmt_songs->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Playlist: <?php echo htmlspecialchars($playlist_title); ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Playlist: <?php echo htmlspecialchars($playlist_title); ?></h1>
        <p><a href="homepage.php">Back to Homepage</a></p>

        <?php if ($message): ?>
            <p class="<?php echo $message_type; ?>-message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <div class="add-song-form">
            <h3>Add Song to this Playlist</h3>
            <form action="playlistpage.php?playlist_id=<?php echo $playlist_id; ?>" method="post" style="border: none; padding: 0;">
                <label for="add_song_query">Search Song by Title:</label>
                <input type="text" id="add_song_query" name="add_song_query" placeholder="e.g., 'Bohemian Rhapsody'" required>
                <button type="submit">Add Song</button>
            </form>
        </div>

        <h2>Songs in this Playlist</h2>
        <?php if (!empty($playlist_songs)): ?>
            <div class="song-list">
                <?php foreach ($playlist_songs as $song): ?>
                    <div class="song-list-item">
                        <div class="song-info">
                            <img src="<?php echo htmlspecialchars($song['image']); ?>" alt="Song Image" style="width: 50px; height: 50px; float: left; margin-right: 10px;">
                            <h4><a href="currentmusic.php?song_id=<?php echo htmlspecialchars($song['song_id']); ?>"><?php echo htmlspecialchars($song['title']); ?></a></h4>
                            <p>Artist: <?php echo htmlspecialchars($song['artist_name']); ?> (<?php echo htmlspecialchars($song['artist_country']); ?>)</p>
                            <p>Duration: <?php echo gmdate("i:s", $song['duration']); ?></p>
                        </div>
                        <div class="song-actions">
                            <form action="currentmusic.php" method="get" style="display: inline-block;">
                                <input type="hidden" name="song_id" value="<?php echo htmlspecialchars($song['song_id']); ?>">
                                <button type="submit">Play</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>This playlist is empty. Use the search above to add songs!</p>
        <?php endif; ?>
    </div>
</body>
</html>