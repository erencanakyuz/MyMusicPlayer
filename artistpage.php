<?php
// artistpage.php
// Student Name: Eren Can Akyüz
// Student ID: 20070006024
// Description: Displays artist details, their albums, and top songs.
// Allows user to 'follow' the artist, updating relevant user and artist data.

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$artist_id = isset($_GET['artist_id']) ? (int)$_GET['artist_id'] : 0;
if ($artist_id <= 0) {
    header("Location: homepage.php"); // Redirect if no valid artist ID
    exit();
}

$user_id = $_SESSION['user_id'];
$artist_data = null;
$message = '';
$message_type = '';

// --- Handle Follow Artist Action ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['follow_artist'])) {
    // For simplicity, we'll just increment artist's listeners and user's follower_num for this project.
    // A more complex system would involve a separate 'FOLLOWS' table.

    // Increment artist's listeners count
    $stmt_update_artist = $conn->prepare("UPDATE ARTISTS SET listeners = listeners + 1 WHERE artist_id = ?");
    $stmt_update_artist->bind_param("i", $artist_id);
    if ($stmt_update_artist->execute()) {
        $message = "You are now following this artist!";
        $message_type = 'success';
        // Optionally update user's most_played_artist or similar logic here
    } else {
        $message = "Error following artist: " . $conn->error;
        $message_type = 'error';
    }
    $stmt_update_artist->close();
}


// --- Fetch Artist Details ---
$stmt_artist = $conn->prepare("SELECT * FROM ARTISTS WHERE artist_id = ?");
$stmt_artist->bind_param("i", $artist_id);
$stmt_artist->execute();
$result_artist = $stmt_artist->get_result();

if ($result_artist->num_rows > 0) {
    $artist_data = $result_artist->fetch_assoc();
} else {
    $message = "Artist not found.";
    $message_type = 'error';
    $artist_data = null;
}
$stmt_artist->close();

// --- Fetch Artist's Last 5 Albums ---
$artist_albums = [];
if ($artist_data) {
    $stmt_albums = $conn->prepare("SELECT album_id, name, release_date, image FROM ALBUMS WHERE artist_id = ? ORDER BY release_date DESC LIMIT 5");
    $stmt_albums->bind_param("i", $artist_id);
    $stmt_albums->execute();
    $result_albums = $stmt_albums->get_result();
    while ($row = $result_albums->fetch_assoc()) {
        $artist_albums[] = $row;
    }
    $stmt_albums->close();
}

// --- Fetch Artist's Top 5 Most Listened Songs (by rank) ---
$artist_top_songs = [];
if ($artist_data) {
    $stmt_top_songs = $conn->prepare("SELECT song_id, title, duration, image FROM SONGS WHERE album_id IN (SELECT album_id FROM ALBUMS WHERE artist_id = ?) ORDER BY `rank` DESC LIMIT 5");
    $stmt_top_songs->bind_param("i", $artist_id);
    $stmt_top_songs->execute();
    $result_top_songs = $stmt_top_songs->get_result();
    while ($row = $result_top_songs->fetch_assoc()) {
        $artist_top_songs[] = $row;
    }
    $stmt_top_songs->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    <title>Artist: <?php echo $artist_data ? htmlspecialchars($artist_data['name']) : 'Artist Not Found'; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Artist Details</h1>
        <p><a href="homepage.php">Back to Homepage</a></p>

        <?php if ($message): ?>
            <p class="<?php echo $message_type; ?>-message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if ($artist_data): ?>
            <div class="artist-header">
                <img src="<?php echo htmlspecialchars($artist_data['image']); ?>" alt="<?php echo htmlspecialchars($artist_data['name']); ?>">
                <div class="artist-details">
                    <h2><?php echo htmlspecialchars($artist_data['name']); ?></h2>
                    <p><strong>Genre:</strong> <?php echo htmlspecialchars($artist_data['genre']); ?></p>
                    <p><strong>Listeners:</strong> <?php echo number_format($artist_data['listeners']); ?></p>
                    <p><strong>Total Music:</strong> <?php echo htmlspecialchars($artist_data['total_num_music']); ?></p>
                    <p><strong>Total Albums:</strong> <?php echo htmlspecialchars($artist_data['total_albums']); ?></p>
                    <p><strong>Joined:</strong> <?php echo htmlspecialchars($artist_data['date_joined']); ?></p>                    <p><strong>Bio:</strong> <?php echo htmlspecialchars($artist_data['bio']); ?></p>
                    <form action="artistpage.php?artist_id=<?php echo $artist_id; ?>" method="post" class="inline-form">
                        <button type="submit" name="follow_artist">Follow Artist</button>
                    </form>
                </div>
            </div>

            <div class="artist-content">
                <div class="artist-albums section">
                    <h3>Last 5 Albums</h3>
                    <?php if (!empty($artist_albums)): ?>
                        <?php foreach ($artist_albums as $album): ?>
                            <div class="album-item">
                                <img src="<?php echo htmlspecialchars($album['image']); ?>" alt="Album Image">
                                <div class="album-info">
                                    <h4><a href="albumpage.php?album_id=<?php echo htmlspecialchars($album['album_id']); ?>"><?php echo htmlspecialchars($album['name']); ?></a></h4>
                                    <p>Release Date: <?php echo htmlspecialchars($album['release_date']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No albums found for this artist.</p>
                    <?php endif; ?>
                </div>

                <div class="artist-top-songs section">
                    <h3>Top 5 Most Listened Songs</h3>
                    <?php if (!empty($artist_top_songs)): ?>
                        <?php foreach ($artist_top_songs as $song): ?>
                            <div class="top-song-item">
                                <img src="<?php echo htmlspecialchars($song['image']); ?>" alt="Song Image">
                                <div class="top-song-info">
                                    <h4><a href="currentmusic.php?song_id=<?php echo htmlspecialchars($song['song_id']); ?>"><?php echo htmlspecialchars($song['title']); ?></a></h4>
                                    <p>Duration: <?php echo gmdate("i:s", $song['duration']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No top songs found for this artist.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <p>Could not retrieve artist details.</p>
        <?php endif; ?>
    </div>
</body>
</html>