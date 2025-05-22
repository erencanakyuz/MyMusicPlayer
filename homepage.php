<?php
// homepage.php
// Student Name: [Your Name]
// Student ID: [Your Student ID]
// Description: Displays user's playlists, last played songs, and artists from their country.
// Handles search functionality for playlists, songs, and artists.

session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
    <link rel="stylesheet" href="style.css">
    <style>
        /* Inline style for the new playlist form toggle */
        #newPlaylistForm {
            display: none; /* Hidden by default */
        }
    </style>
</head>
<body>
    <div class="homepage-container">
        <header class="homepage-header">
            <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
            <div> <!-- Wrapper for links -->
                <a href="playlistpage.php">My Playlists</a>
                <a href="logout.php">Logout</a>
            </div>
        </header>

        <div class="search-container main-search">
            <form action="homepage.php" method="GET">
                <input type="text" name="search_query" placeholder="Search songs, albums, artists..." value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <?php if (!empty($search_query) && (empty($search_results_songs) && empty($search_results_albums) && empty($search_results_artists))): ?>
            <p class="info-message">No results found for "<?php echo htmlspecialchars($search_query); ?>". <a href="homepage.php">Clear Search</a></p>
        <?php endif; ?>

        <?php if (!empty($search_results_songs) || !empty($search_results_albums) || !empty($search_results_artists)): ?>
            <div class="search-results-container content-section">
                <h2>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h2>
                <?php if (!empty($search_results_songs)): ?>
                    <h3>Songs</h3>
                    <ul class="song-list">
                        <?php foreach ($search_results_songs as $song): ?>
                            <li class="song-item">
                                <a href="currentmusic.php?song_id=<?php echo $song['song_id']; ?>">
                                    <img src="<?php echo htmlspecialchars($song['image'] ?? 'default_song.png'); ?>" alt="<?php echo htmlspecialchars($song['title']); ?>">
                                    <div class="song-info">
                                        <h4><?php echo htmlspecialchars($song['title']); ?></h4>
                                        <p><?php echo htmlspecialchars($song['artist_name'] ?? 'Unknown Artist'); ?> - <?php echo htmlspecialchars($song['album_name'] ?? 'Unknown Album'); ?></p>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if (!empty($search_results_albums)): ?>
                    <h3>Albums</h3>
                    <div class="grid-container">
                        <?php foreach ($search_results_albums as $album): ?>
                            <div class="grid-item album-item">
                                <a href="albumpage.php?album_id=<?php echo $album['album_id']; ?>">
                                    <img src="<?php echo htmlspecialchars($album['image'] ?? 'default_album.png'); ?>" alt="<?php echo htmlspecialchars($album['name']); ?>">
                                    <div class="info">
                                        <h4><?php echo htmlspecialchars($album['name']); ?></h4>
                                        <p><?php echo htmlspecialchars($album['artist_name'] ?? 'Unknown Artist'); ?></p>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($search_results_artists)): ?>
                    <h3>Artists</h3>
                     <div class="grid-container">
                        <?php foreach ($search_results_artists as $artist): ?>
                            <div class="grid-item artist-item">
                                <a href="artistpage.php?artist_id=<?php echo $artist['artist_id']; ?>">
                                    <img src="<?php echo htmlspecialchars($artist['image'] ?? 'default_artist.png'); ?>" alt="<?php echo htmlspecialchars($artist['name']); ?>">
                                    <div class="info">
                                        <h4><?php echo htmlspecialchars($artist['name']); ?></h4>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                 <p><a href="homepage.php">Clear Search / Back to Homepage</a></p>
            </div>
        <?php else: ?>
            <main class="homepage-main-content">
                <aside class="left-column">
                    <section class="content-section">
                        <h2>Top Artists</h2>
                        <?php if (!empty($top_artists)): ?>
                            <div class="grid-container">
                                <?php foreach ($top_artists as $artist): ?>
                                <div class="grid-item artist-item">
                                    <a href="artistpage.php?artist_id=<?php echo $artist['artist_id']; ?>">
                                        <img src="<?php echo htmlspecialchars($artist['image'] ?? 'default_artist.png'); ?>" alt="<?php echo htmlspecialchars($artist['name']); ?>">
                                        <div class="info">
                                            <h3><?php echo htmlspecialchars($artist['name']); ?></h3>
                                        </div>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No top artists to display.</p>
                        <?php endif; ?>
                    </section>

                    <!-- SECTION REMOVED: Top Albums -->
                    <!-- 
                    <section class="content-section">
                        <h2>Top Albums</h2>
                        <?php if (!empty($top_albums)): ?>
                            <div class="grid-container">
                                <?php foreach ($top_albums as $album): ?>
                                <div class="grid-item album-item">
                                    <a href="albumpage.php?album_id=<?php echo $album['album_id']; ?>">
                                        <img src="<?php echo htmlspecialchars($album['image'] ?? 'default_album.png'); ?>" alt="<?php echo htmlspecialchars($album['name']); ?>">
                                        <div class="info">
                                            <h3><?php echo htmlspecialchars($album['name']); ?></h3>
                                            <p><?php echo htmlspecialchars($album['artist_name'] ?? 'Unknown Artist'); ?></p>
                                        </div>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No top albums to display.</p>
                        <?php endif; ?>
                    </section>
                    -->
                </aside>

                <section class="right-column">
                    <section class="content-section">
                        <h2>Top Songs</h2>
                        <?php if (!empty($top_songs)): ?>
                            <ul class="song-list">
                                <?php foreach ($top_songs as $song): ?>
                                <li class="song-item">
                                    <a href="currentmusic.php?song_id=<?php echo $song['song_id']; ?>">
                                        <img src="<?php echo htmlspecialchars($song['image'] ?? 'default_song.png'); ?>" alt="<?php echo htmlspecialchars($song['title']); ?>">
                                        <div class="song-info">
                                            <h4><?php echo htmlspecialchars($song['title']); ?></h4>
                                            <p><?php echo htmlspecialchars($song['artist_name'] ?? 'Unknown Artist'); ?> - <?php echo htmlspecialchars($song['album_name'] ?? 'Unknown Album'); ?></p>
                                        </div>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No top songs to display.</p>
                        <?php endif; ?>
                    </section>

                    <section class="content-section">
                        <h2>My Playlists</h2>
                        <?php if (!empty($user_playlists)): ?>
                            <div class="grid-container playlist-grid">
                                <?php foreach ($user_playlists as $playlist): ?>
                                <div class="grid-item playlist-item">
                                    <a href="playlistpage.php?playlist_id=<?php echo $playlist['playlist_id']; ?>">
                                        <div class="info">
                                            <h3><?php echo htmlspecialchars($playlist['name']); ?></h3>
                                            <?php if (!empty($playlist['description'])): ?>
                                                <p><?php echo htmlspecialchars($playlist['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>You have no playlists. <a href="playlistpage.php?action=create">Create one?</a></p>
                        <?php endif; ?>
                    </section>
                </section>
            </main>
        <?php endif; ?>
    </div>
</body>
</html>