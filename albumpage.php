<?php
// albumpage.php
// Student Name: Eren Can Akyüz
 // Student ID: 20220702128

 //NOTES:
// Description: Displays all songs in a selected album.
// Adding new songs to an album is NOT allowed here.

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$album_id = isset($_GET['album_id']) ? (int)$_GET['album_id'] : 0;
if ($album_id <= 0) {
    header("Location: homepage.php"); // Redirect if no valid album ID
    exit();
}

$album_title = "Unknown Album";
$artist_name = "";
$message = '';
$message_type = '';

// Fetch album details
$stmt_album_details = $conn->prepare("
    SELECT AL.name AS album_name, AL.image AS album_image, AL.release_date, A.name AS artist_name, A.artist_id
    FROM ALBUMS AL
    JOIN ARTISTS A ON AL.artist_id = A.artist_id
    WHERE AL.album_id = ?
");
$stmt_album_details->bind_param("i", $album_id);
$stmt_album_details->execute();
$result_album_details = $stmt_album_details->get_result();

if ($result_album_details->num_rows > 0) {
    $album_data = $result_album_details->fetch_assoc();
    $album_title = $album_data['album_name'];
    $artist_name = $album_data['artist_name'];
    $artist_id_for_link = $album_data['artist_id'];
    $album_image = $album_data['album_image'];
    $release_date = $album_data['release_date'];
} else {
    $message = "Album not found.";
    $message_type = 'error';
    $album_data = null;
}
$stmt_album_details->close();

// Fetch Songs in the Album
$album_songs = [];
if ($album_data) {
    $stmt_songs = $conn->prepare("
        SELECT song_id, title, duration, genre, image
        FROM SONGS
        WHERE album_id = ?
        ORDER BY title ASC
    ");
    $stmt_songs->bind_param("i", $album_id);
    $stmt_songs->execute();
    $result_songs = $stmt_songs->get_result();
    while ($row = $result_songs->fetch_assoc()) {
        $album_songs[] = $row;
    }
    $stmt_songs->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Album: <?php echo ($album_title); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Album: <?php echo ($album_title); ?></h1>
        <p><a href="homepage.php">Back to Homepage</a> | <a href="artistpage.php?artist_id=<?php echo ($artist_id_for_link); ?>">Back to Artist</a></p>

        <?php if ($message): ?>
            <p class="<?php echo $message_type; ?>-message"><?php echo ($message); ?></p>
        <?php endif; ?>

        <?php if ($album_data): ?>            <div class="album-container">
                <img src="<?php echo ($album_image); ?>" alt="Album Cover" class="album-cover">
                <h3>Artist: <a href="artistpage.php?artist_id=<?php echo ($artist_id_for_link); ?>"><?php echo ($artist_name); ?></a></h3>
                <p>Release Date: <?php echo ($release_date); ?></p>
            </div>

            <h2>Songs in Album</h2>
            <?php if (!empty($album_songs)): ?>
                <div class="song-list">
                    <?php foreach ($album_songs as $song): ?>
                        <div class="song-list-item">
                            <div class="song-info">                                <img src="<?php echo ($song['image']); ?>" alt="Song Image" class="song-thumbnail">
                                <h4><a href="currentmusic.php?song_id=<?php echo ($song['song_id']); ?>"><?php echo ($song['title']); ?></a></h4>
                                <p>Genre: <?php echo ($song['genre']); ?> | Duration: <?php echo gmdate("i:s", $song['duration']); ?></p>
                            </div>
                            <div class="song-actions">                                <form action="currentmusic.php" method="get" class="inline-form">
                                    <input type="hidden" name="song_id" value="<?php echo ($song['song_id']); ?>">
                                    <button type="submit">Play</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>This album has no songs.</p>
            <?php endif; ?>
        <?php else: ?>
            <p>Could not retrieve album details.</p>
        <?php endif; ?>
    </div>
</body>
</html>