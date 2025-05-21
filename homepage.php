<?php
// homepage.php
// Student Name: [Your Name]
// Student ID: [Your Student ID]
// Description: Displays user's playlists, last played songs, and artists from their country.
// Handles search functionality for playlists, songs, and artists.

session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require_once 'config.php'; // Include database configuration

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_country_id = $_SESSION['country_id'];

// --- Handle Search Queries ---
$search_error = '';
$search_success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['main_search_query'])) {
        $query = sanitize_input($conn, $_POST['main_search_query']);

        // Try searching for playlist
        $stmt_playlist = $conn->prepare("SELECT playlist_id FROM PLAYLISTS WHERE user_id = ? AND title LIKE ?");
        $like_query = "%" . $query . "%";
        $stmt_playlist->bind_param("is", $user_id, $like_query);
        $stmt_playlist->execute();
        $result_playlist = $stmt_playlist->get_result();

        if ($result_playlist->num_rows > 0) {
            $playlist = $result_playlist->fetch_assoc();
            header("Location: playlistpage.php?playlist_id=" . $playlist['playlist_id']);
            exit();
        }
        $stmt_playlist->close();

        // If not a playlist, try searching for a song
        $stmt_song = $conn->prepare("SELECT song_id FROM SONGS WHERE title LIKE ? LIMIT 1");
        $stmt_song->bind_param("s", $like_query);
        $stmt_song->execute();
        $result_song = $stmt_song->get_result();

        if ($result_song->num_rows > 0) {
            $song = $result_song->fetch_assoc();
            // Update PLAY_HISTORY
            $insert_history = $conn->prepare("INSERT INTO PLAY_HISTORY (user_id, song_id, playtime) VALUES (?, ?, NOW())");
            $insert_history->bind_param("ii", $user_id, $song['song_id']);
            $insert_history->execute();
            $insert_history->close();

            header("Location: currentmusic.php?song_id=" . $song['song_id']);
            exit();
        }
        $stmt_song->close();

        $search_error = "No playlist or song found matching '{$query}'.";

    } elseif (isset($_POST['artist_search_query'])) {
        $query = sanitize_input($conn, $_POST['artist_search_query']);
        $stmt_artist = $conn->prepare("SELECT artist_id FROM ARTISTS WHERE name LIKE ? LIMIT 1");
        $like_query = "%" . $query . "%";
        $stmt_artist->bind_param("s", $like_query);
        $stmt_artist->execute();
        $result_artist = $stmt_artist->get_result();

        if ($result_artist->num_rows > 0) {
            $artist = $result_artist->fetch_assoc();
            header("Location: artistpage.php?artist_id=" . $artist['artist_id']);
            exit();
        } else {
            $search_error = "Artist '{$query}' not found.";
        }
        $stmt_artist->close();

    } elseif (isset($_POST['history_search_query'])) {
        $query = sanitize_input($conn, $_POST['history_search_query']);
        $stmt_history_song = $conn->prepare("SELECT song_id FROM SONGS WHERE title LIKE ? LIMIT 1");
        $like_query = "%" . $query . "%";
        $stmt_history_song->bind_param("s", $like_query);
        $stmt_history_song->execute();
        $result_history_song = $stmt_history_song->get_result();

        if ($result_history_song->num_rows > 0) {
            $song = $result_history_song->fetch_assoc();
            // Update PLAY_HISTORY
            $insert_history = $conn->prepare("INSERT INTO PLAY_HISTORY (user_id, song_id, playtime) VALUES (?, ?, NOW())");
            $insert_history->bind_param("ii", $user_id, $song['song_id']);
            $insert_history->execute();
            $insert_history->close();

            header("Location: currentmusic.php?song_id=" . $song['song_id']);
            exit();
        } else {
            $search_error = "Song '{$query}' not found in history search.";
        }
        $stmt_history_song->close();
    } elseif (isset($_POST['new_playlist_title'])) {
        $new_playlist_title = sanitize_input($conn, $_POST['new_playlist_title']);
        $new_playlist_description = sanitize_input($conn, $_POST['new_playlist_description']);
        $new_playlist_image = sanitize_input($conn, $_POST['new_playlist_image']);

        if (empty($new_playlist_title)) {
            $search_error = "Playlist title cannot be empty.";
        } else {
            // Check if playlist title already exists for this user
            $check_stmt = $conn->prepare("SELECT playlist_id FROM PLAYLISTS WHERE user_id = ? AND title = ?");
            $check_stmt->bind_param("is", $user_id, $new_playlist_title);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) {
                $search_error = "You already have a playlist with this title.";
            } else {
                $insert_playlist_stmt = $conn->prepare("INSERT INTO PLAYLISTS (user_id, title, description, date_created, image) VALUES (?, ?, ?, NOW(), ?)");
                $insert_playlist_stmt->bind_param("isss", $user_id, $new_playlist_title, $new_playlist_description, $new_playlist_image);
                if ($insert_playlist_stmt->execute()) {
                    $search_success = "Playlist '{$new_playlist_title}' created successfully!";
                } else {
                    $search_error = "Error creating playlist: " . $conn->error;
                }
                $insert_playlist_stmt->close();
            }
            $check_stmt->close();
        }
    }
}

// --- Fetch Data for Display ---

// 1. User's Playlists
$playlists = [];
$stmt_playlists = $conn->prepare("SELECT playlist_id, title, image FROM PLAYLISTS WHERE user_id = ?");
$stmt_playlists->bind_param("i", $user_id);
$stmt_playlists->execute();
$result_playlists = $stmt_playlists->get_result();
while ($row = $result_playlists->fetch_assoc()) {
    $playlists[] = $row;
}
$stmt_playlists->close();

// 2. User's Last 10 Played Songs
$last_played_songs = [];
$stmt_history = $conn->prepare("
    SELECT S.song_id, S.title, S.image, A.name AS artist_name
    FROM PLAY_HISTORY PH
    JOIN SONGS S ON PH.song_id = S.song_id
    JOIN ALBUMS AL ON S.album_id = AL.album_id
    JOIN ARTISTS A ON AL.artist_id = A.artist_id
    WHERE PH.user_id = ?
    ORDER BY PH.playtime DESC
    LIMIT 10
");
$stmt_history->bind_param("i", $user_id);
$stmt_history->execute();
$result_history = $stmt_history->get_result();
while ($row = $result_history->fetch_assoc()) {
    $last_played_songs[] = $row;
}
$stmt_history->close();

// 3. Top 5 Artists from User's Country
$top_artists = [];
$stmt_artists = $conn->prepare("
    SELECT artist_id, name, listeners, image
    FROM ARTISTS
    WHERE country_id = ?
    ORDER BY listeners DESC
    LIMIT 5
");
$stmt_artists->bind_param("i", $user_country_id);
$stmt_artists->execute();
$result_artists = $stmt_artists->get_result();
while ($row = $result_artists->fetch_assoc()) {
    $top_artists[] = $row;
}
$stmt_artists->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hello, <?php echo htmlspecialchars($user_name); ?>!</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Inline style for the new playlist form toggle */
        #newPlaylistForm {
            display: none; /* Hidden by default */
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-controls">
            <h1>Hello, <?php echo htmlspecialchars($user_name); ?>!</h1>
            <form action="logout.php" method="post">
                <button type="submit" class="button">Logout</button>
            </form>
        </div>

        <?php if ($search_error): ?>
            <p class="error-message"><?php echo htmlspecialchars($search_error); ?></p>
        <?php endif; ?>
        <?php if ($search_success): ?>
            <p class="success-message"><?php echo htmlspecialchars($search_success); ?></p>
        <?php endif; ?>

        <div class="homepage-layout">
            <div class="left-panel">
                <div class="search-bar-container">
                    <form action="homepage.php" method="post" style="display: flex; flex-grow: 1; border: none; padding: 0;">
                        <input type="text" name="main_search_query" placeholder="Search Playlists or Songs..." style="margin-bottom: 0;">
                        <button type="submit">Search</button>
                    </form>
                    <button class="button" onclick="document.getElementById('newPlaylistForm').style.display = document.getElementById('newPlaylistForm').style.display === 'none' ? 'block' : 'none';">+</button>
                </div>

                <h2>Your Playlists</h2>
                <div id="newPlaylistForm" class="playlist-add-form">
                    <h3>Create New Playlist</h3>
                    <form action="homepage.php" method="post" style="border: none; padding: 0;">
                        <label for="new_playlist_title">Title:</label>
                        <input type="text" id="new_playlist_title" name="new_playlist_title" required>
                        <label for="new_playlist_description">Description:</label>
                        <textarea id="new_playlist_description" name="new_playlist_description" rows="3"></textarea>
                        <label for="new_playlist_image">Image URL:</label>
                        <input type="text" id="new_playlist_image" name="new_playlist_image" value="https://picsum.photos/180/180?random=<?php echo rand(1,1000); ?>">
                        <button type="submit">Create Playlist</button>
                    </form>
                </div>
                
                <?php if (!empty($playlists)): ?>
                    <div class="playlist-list">
                        <?php foreach ($playlists as $playlist): ?>
                            <div class="playlist-item">
                                <img src="<?php echo htmlspecialchars($playlist['image']); ?>" alt="Playlist Image">
                                <div class="item-details">
                                    <h4><a href="playlistpage.php?playlist_id=<?php echo htmlspecialchars($playlist['playlist_id']); ?>"><?php echo htmlspecialchars($playlist['title']); ?></a></h4>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>You don't have any playlists yet. Create one!</p>
                <?php endif; ?>
            </div>

            <div class="right-panel">
                <div class="section">
                    <h2>Last 10 Played Songs</h2>
                    <form action="homepage.php" method="post" class="search-bar-container" style="border: none; padding: 0;">
                        <input type="text" name="history_search_query" placeholder="Search a song in history..." style="margin-bottom: 0;">
                        <button type="submit">Search</button>
                    </form>
                    <?php if (!empty($last_played_songs)): ?>
                        <div class="song-list">
                            <?php foreach ($last_played_songs as $song): ?>
                                <div class="song-item">
                                    <img src="<?php echo htmlspecialchars($song['image']); ?>" alt="Song Image">
                                    <div class="item-details">
                                        <h4><a href="currentmusic.php?song_id=<?php echo htmlspecialchars($song['song_id']); ?>"><?php echo htmlspecialchars($song['title']); ?></a></h4>
                                        <p><?php echo htmlspecialchars($song['artist_name']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No songs played recently.</p>
                    <?php endif; ?>
                </div>

                <div class="section">
                    <h2>Top Artists from Your Country</h2>
                    <form action="homepage.php" method="post" class="search-bar-container" style="border: none; padding: 0;">
                        <input type="text" name="artist_search_query" placeholder="Search for artists..." style="margin-bottom: 0;">
                        <button type="submit">Search</button>
                    </form>
                    <?php if (!empty($top_artists)): ?>
                        <div class="artist-list">
                            <?php foreach ($top_artists as $artist): ?>
                                <div class="artist-item">
                                    <img src="<?php echo htmlspecialchars($artist['image']); ?>" alt="Artist Image">
                                    <div class="item-details">
                                        <h4><a href="artistpage.php?artist_id=<?php echo htmlspecialchars($artist['artist_id']); ?>"><?php echo htmlspecialchars($artist['name']); ?></a></h4>
                                        <p>Listeners: <?php echo number_format($artist['listeners']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No top artists found from your country.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>