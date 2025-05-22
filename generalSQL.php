<?php
// generalSQL.php
// Student Name: Eren Can Akyüz
// Student ID: 20070006024
// Description: Page for genre-related and country-related operations.
// Allows custom SQL queries (for demonstration) and displays predefined reports.

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$custom_query_results = [];
$predefined_results = [];
$message = '';
$message_type = '';

// --- Handle Custom SQL Query Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['custom_sql_query'])) {
    $user_query = trim($_POST['custom_sql_query']); // Do NOT sanitize this for custom queries, but be aware of security implications for a real app.

    if (!empty($user_query)) {
        // Execute the query
        // WARNING: This is highly insecure for a public application.
        // For a university project demonstrating raw SQL interaction, it might be acceptable,
        // but always acknowledge SQL Injection vulnerability.
        $result = $conn->query($user_query);

        if ($result) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $custom_query_results[] = $row;
                }
                $message = "Custom query executed successfully!";
                $message_type = 'success';
            } else {
                $message = "Custom query executed, but returned no rows or was a DML operation.";
                $message_type = 'success';
            }
        } else {
            $message = "Error executing custom query: " . $conn->error;
            $message_type = 'error';
        }
    } else {
        $message = "Please enter a SQL query.";
        $message_type = 'error';
    }
}

// --- Predefined Queries ---

// Query 1: Top 5 Genres Listened To (based on user's top_genre for simplicity, or aggregated PLAY_HISTORY)
$predefined_queries['top_genres'] = "
    SELECT U.top_genre AS genre, COUNT(U.user_id) AS user_count
    FROM USERS U
    GROUP BY U.top_genre
    ORDER BY user_count DESC
    LIMIT 5;
";

// Query 2: Top 5 Most Listened Songs Across All Users (by total plays)
$predefined_queries['top_songs_overall'] = "
    SELECT S.title, S.image, A.name AS artist_name, COUNT(PH.song_id) AS play_count
    FROM PLAY_HISTORY PH
    JOIN SONGS S ON PH.song_id = S.song_id
    JOIN ALBUMS AL ON S.album_id = AL.album_id
    JOIN ARTISTS A ON AL.artist_id = A.artist_id
    GROUP BY S.song_id, S.title, S.image, A.name
    ORDER BY play_count DESC
    LIMIT 5;
";

// Query 3: Number of Users per Country
$predefined_queries['users_per_country'] = "
    SELECT C.country_name, COUNT(U.user_id) AS user_count
    FROM COUNTRY C
    JOIN USERS U ON C.country_id = U.country_id
    GROUP BY C.country_name
    ORDER BY user_count DESC
    LIMIT 5;
";

// Query 4: Artists with Most Albums
$predefined_queries['artists_most_albums'] = "
    SELECT A.name AS artist_name, A.image, COUNT(AL.album_id) AS album_count
    FROM ARTISTS A
    JOIN ALBUMS AL ON A.artist_id = AL.artist_id
    GROUP BY A.artist_id, A.name, A.image
    ORDER BY album_count DESC
    LIMIT 5;
";

// Query 5: Songs with longest duration
$predefined_queries['longest_songs'] = "
    SELECT S.title, S.duration, A.name AS artist_name
    FROM SONGS S
    JOIN ALBUMS AL ON S.album_id = AL.album_id
    JOIN ARTISTS A ON AL.artist_id = A.artist_id
    ORDER BY S.duration DESC
    LIMIT 5;
";


foreach ($predefined_queries as $key => $sql) {
    $result = $conn->query($sql);
    $predefined_results[$key] = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $predefined_results[$key][] = $row;
        }
    } else {
        $predefined_results[$key] = ['error' => 'Query failed: ' . $conn->error];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General SQL Operations</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>General SQL Operations</h1>
        <p><a href="homepage.php">Back to Homepage</a></p>

        <?php if ($message): ?>
            <p class="<?php echo $message_type; ?>-message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <div class="section sql-input-section">
            <h2>Run Custom SQL Query</h2>
            <p class="error-text">
                WARNING: This feature is for demonstration purposes. Running arbitrary SQL queries can be a security risk (SQL Injection).
            </p>
            <form action="generalSQL.php" method="post">
                <label for="custom_sql_query">Enter your SQL query (SELECT statements recommended):</label>
                <textarea id="custom_sql_query" name="custom_sql_query" rows="5" placeholder="e.g., SELECT * FROM USERS LIMIT 10;"></textarea>
                <button type="submit">Execute Query</button>
            </form>

            <?php if (!empty($custom_query_results)): ?>
                <h3>Custom Query Results:</h3>
                <table>
                    <thead>
                        <tr>
                            <?php foreach (array_keys($custom_query_results[0]) as $col_name): ?>
                                <th><?php echo htmlspecialchars($col_name); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($custom_query_results as $row): ?>
                            <tr>
                                <?php foreach ($row as $value): ?>
                                    <td><?php echo htmlspecialchars($value); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif (isset($_POST['custom_sql_query']) && empty($custom_query_results) && $message_type != 'error'): ?>
                <p>Query executed, but no results found or it was a DML operation.</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Predefined Reports</h2>
            <div class="sql-results-grid">
                <div class="sql-result-slot">
                    <h3>Top 5 Genres by User Preference</h3>
                    <?php if (!empty($predefined_results['top_genres'])): ?>
                        <ul>
                            <?php foreach ($predefined_results['top_genres'] as $row): ?>
                                <li><?php echo htmlspecialchars($row['genre']); ?> (<?php echo htmlspecialchars($row['user_count']); ?> users)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No data available.</p>
                    <?php endif; ?>
                </div>

                <div class="sql-result-slot">
                    <h3>Top 5 Most Played Songs Overall</h3>
                    <?php if (!empty($predefined_results['top_songs_overall'])): ?>
                        <ul>
                            <?php foreach ($predefined_results['top_songs_overall'] as $row): ?>
                                <li>                                    <img src="<?php echo htmlspecialchars($row['image']); ?>" class="thumbnail-image">
                                    <?php echo htmlspecialchars($row['title']); ?> by <?php echo htmlspecialchars($row['artist_name']); ?>(<?php echo htmlspecialchars($row['play_count']); ?> plays)
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No data available.</p>
                    <?php endif; ?>
                </div>

                <div class="sql-result-slot">
                    <h3>Top 5 Countries by User Count</h3>
                    <?php if (!empty($predefined_results['users_per_country'])): ?>
                        <ul>
                            <?php foreach ($predefined_results['users_per_country'] as $row): ?>
                                <li><?php echo htmlspecialchars($row['country_name']); ?> (<?php echo htmlspecialchars($row['user_count']); ?> users)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No data available.</p>
                    <?php endif; ?>
                </div>

                <div class="sql-result-slot">
                    <h3>Top 5 Artists with Most Albums</h3>
                    <?php if (!empty($predefined_results['artists_most_albums'])): ?>
                        <ul>
                            <?php foreach ($predefined_results['artists_most_albums'] as $row): ?>
                                <li>                                    <img src="<?php echo htmlspecialchars($row['image']); ?>" class="thumbnail-image">
                                    <?php echo htmlspecialchars($row['artist_name']); ?> (<?php echo htmlspecialchars($row['album_count']); ?> albums)
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No data available.</p>
                    <?php endif; ?>
                </div>

                <div class="sql-result-slot">
                    <h3>Top 5 Longest Songs</h3>
                    <?php if (!empty($predefined_results['longest_songs'])): ?>
                        <ul>
                            <?php foreach ($predefined_results['longest_songs'] as $row): ?>
                                <li>
                                    <?php echo htmlspecialchars($row['title']); ?> by <?php echo htmlspecialchars($row['artist_name']); ?> (<?php echo gmdate("i:s", $row['duration']); ?>)
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No data available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>