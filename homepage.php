<?php
// homepage.php
// Student Name: Eren Can Akyüz
 // Student ID: 20220702128
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
    SELECT S.song_id, S.title, S.image, A.name AS artist_name, PH.playtime
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

// 4. Top 5 Songs (Based on rank)
$top_songs = [];
$stmt_songs = $conn->prepare("
    SELECT S.song_id, S.title, S.image, A.name AS artist_name, AL.name AS album_name
    FROM SONGS S
    JOIN ALBUMS AL ON S.album_id = AL.album_id
    JOIN ARTISTS A ON AL.artist_id = A.artist_id
    ORDER BY S.`rank` DESC
    LIMIT 5
");
$stmt_songs->execute();
$result_songs = $stmt_songs->get_result();
while ($row = $result_songs->fetch_assoc()) {
    $top_songs[] = $row;
}
$stmt_songs->close();

// 5. User's Playlists
$user_playlists = [];
$stmt_user_playlists = $conn->prepare("
    SELECT playlist_id, title, description, image
    FROM PLAYLISTS 
    WHERE user_id = ?
");
$stmt_user_playlists->bind_param("i", $user_id);
$stmt_user_playlists->execute();
$result_user_playlists = $stmt_user_playlists->get_result();
while ($row = $result_user_playlists->fetch_assoc()) {
    $user_playlists[] = $row;
}
$stmt_user_playlists->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hello, <?php echo ($user_name); ?>!</title>    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="homepage-container">
        <header class="homepage-header">
            <h1>Welcome, <?php echo ($user_name); ?>!</h1>
            <div> 
                <a href="playlistpage.php">My Playlists</a>
                <a href="logout.php">Logout</a>
            </div>
        </header>

        <div class="search-container main-search">
            <form action="homepage.php" method="GET">
                <input type="text" name="search_query" placeholder="Search songs, albums, artists..." value="<?php echo ($search_query ?? ''); ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <?php if (!empty($search_query) && (empty($search_results_songs) && empty($search_results_albums) && empty($search_results_artists))): ?>
            <p class="info-message">No results found for "<?php echo ($search_query); ?>". <a href="homepage.php">Clear Search</a></p>
        <?php endif; ?>

        <?php if (!empty($search_results_songs) || !empty($search_results_albums) || !empty($search_results_artists)): ?>
            <div class="search-results-container content-section">
                <h2>Search Results for "<?php echo ($search_query); ?>"</h2>
                <?php if (!empty($search_results_songs)): ?>
                    <h3>Songs</h3>
                    <ul class="song-list">
                        <?php foreach ($search_results_songs as $song): ?>
                            <li class="song-item">
                                <a href="currentmusic.php?song_id=<?php echo $song['song_id']; ?>">
                                    <img src="<?php echo ($song['image'] ?? 'default_song.png'); ?>" alt="<?php echo ($song['title']); ?>">
                                    <div class="song-info">
                                        <h4><?php echo ($song['title']); ?></h4>
                                        <p><?php echo ($song['artist_name'] ?? 'Unknown Artist'); ?> - <?php echo ($song['album_name'] ?? 'Unknown Album'); ?></p>
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
                                    <img src="<?php echo ($album['image'] ?? 'default_album.png'); ?>" alt="<?php echo ($album['name']); ?>">
                                    <div class="info">
                                        <h4><?php echo ($album['name']); ?></h4>
                                        <p><?php echo ($album['artist_name'] ?? 'Unknown Artist'); ?></p>
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
                                    <img src="<?php echo ($artist['image'] ?? 'default_artist.png'); ?>" alt="<?php echo ($artist['name']); ?>">
                                    <div class="info">
                                        <h4><?php echo ($artist['name']); ?></h4>
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
                        <h2>My Playlists</h2>
                        <div class="playlist-header">
                            <div></div>
                            <button id="showNewPlaylistFormBtn" class="button add-button">+</button>
                        </div>
                        
                        <!-- New Playlist Form (Hidden by Default) -->
                        <div id="newPlaylistForm" class="new-playlist-form">
                            <form action="homepage.php" method="POST">
                                <div>
                                    <label for="new_playlist_title">Playlist Title:</label>
                                    <input type="text" id="new_playlist_title" name="new_playlist_title" required>
                                </div>
                                <div>
                                    <label for="new_playlist_description">Description (Optional):</label>
                                    <textarea id="new_playlist_description" name="new_playlist_description"></textarea>
                                </div>
                                <div>
                                    <label for="new_playlist_image">Image URL (Optional):</label>
                                    <input type="text" id="new_playlist_image" name="new_playlist_image">
                                </div>
                                <button type="submit">Create Playlist</button>
                                <button type="button" id="cancelNewPlaylistBtn">Cancel</button>
                            </form>
                        </div>

                        <?php if (!empty($user_playlists)): ?>
                            <div class="grid-container playlist-grid">                                <?php foreach ($user_playlists as $playlist): ?>
                                <div class="grid-item playlist-item">
                                    <a href="playlistpage.php?playlist_id=<?php echo $playlist['playlist_id']; ?>">
                                        <img src="<?php echo ($playlist['image'] ?? 'default_playlist.png'); ?>" alt="<?php echo ($playlist['title']); ?>">
                                        <div class="info">
                                            <h3><?php echo ($playlist['title']); ?></h3>
                                            <?php if (!empty($playlist['description'])): ?>
                                                <p><?php echo ($playlist['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>You have no playlists. Use the + button to create one.</p>
                        <?php endif; ?>
                    </section>
                </aside>

                <section class="right-column">
                    <section class="content-section">
                        <h2>Last Played Songs</h2>
                        <div class="search-container history-search">
                            <form action="homepage.php" method="POST">
                                <input type="text" name="history_search_query" placeholder="Search in your play history...">
                                <button type="submit">Search</button>
                            </form>
                        </div>
                        <?php if (!empty($last_played_songs)): ?>
                            <ul class="song-list">
                                <?php foreach ($last_played_songs as $song): ?>
                                <li class="song-item">
                                    <a href="currentmusic.php?song_id=<?php echo $song['song_id']; ?>">
                                        <img src="<?php echo ($song['image'] ?? 'default_song.png'); ?>" alt="<?php echo ($song['title']); ?>">
                                        <div class="song-info">
                                            <h4><?php echo ($song['title']); ?></h4>
                                            <p><?php echo ($song['artist_name']); ?></p>
                                            <p class="playtime"><?php echo date('d M Y, H:i', strtotime($song['playtime'])); ?></p>
                                        </div>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No play history to display. Start listening to some music!</p>
                        <?php endif; ?>
                    </section>

                    <section class="content-section">
                        <h2>Top Artists from Your Country</h2>
                        <div class="search-container artist-search">
                            <form action="homepage.php" method="POST">
                                <input type="text" name="artist_search_query" placeholder="Search for artists...">
                                <button type="submit">Search</button>
                            </form>
                        </div>
                        <?php if (!empty($top_artists)): ?>
                            <div class="grid-container">
                                <?php foreach ($top_artists as $artist): ?>
                                <div class="grid-item artist-item">
                                    <a href="artistpage.php?artist_id=<?php echo $artist['artist_id']; ?>">
                                        <img src="<?php echo ($artist['image'] ?? 'default_artist.png'); ?>" alt="<?php echo ($artist['name']); ?>">
                                        <div class="info">
                                            <h3><?php echo ($artist['name']); ?></h3>
                                            <p>Listeners: <?php echo number_format($artist['listeners']); ?></p>
                                        </div>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No top artists to display from your country.</p>
                        <?php endif; ?>
                    </section>
                </section>
            </main>
        <?php endif; ?>
    </div>

    <script>
        // sooo little JavaScript code to handle the new playlist form toggle
        document.addEventListener('DOMContentLoaded', function() {
            const showFormBtn = document.getElementById('showNewPlaylistFormBtn');
            const cancelFormBtn = document.getElementById('cancelNewPlaylistBtn');
            const playlistForm = document.getElementById('newPlaylistForm');
            
            if (showFormBtn && cancelFormBtn && playlistForm) {
                showFormBtn.addEventListener('click', function() {
                    playlistForm.style.display = 'block';
                });
                
                cancelFormBtn.addEventListener('click', function() {
                    playlistForm.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>