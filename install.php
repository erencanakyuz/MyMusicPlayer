<?php
// install.php
// Student Name: [Your Name]
// Student ID: [Your Student ID]
// Description: Script to create the database, tables, and populate them with initial data.
// After execution, redirects to login.php.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root"; // Your MySQL username
$password = "mysql"; // Your MySQL password


$dbname = "ErenCan_Akyuz_musicplayer"; 

// Create connection without selecting a database first
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Attempting to create database '{$dbname}' and tables...<br>";

// Drop database if it exists (for fresh installation on repeated runs)
$sql_drop_db = "DROP DATABASE IF EXISTS `{$dbname}`";
if ($conn->query($sql_drop_db) === TRUE) {
    echo "Existing database `{$dbname}` dropped successfully (if it existed).<br>";
} else {
    echo "Error dropping database: " . $conn->error . "<br>";
}

// Create database
$sql_create_db = "CREATE DATABASE `{$dbname}`";
if ($conn->query($sql_create_db) === TRUE) {
    echo "Database `{$dbname}` created successfully.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the newly created database
$conn->select_db($dbname);

// SQL for creating tables
// Define CREATE TABLE statements directly here or load from a file.
// For simplicity in this project, I'll list them here.
$create_tables_sql = "
-- COUNTRY Table
CREATE TABLE COUNTRY (
    country_id INT AUTO_INCREMENT PRIMARY KEY,
    country_name VARCHAR(100) NOT NULL UNIQUE,
    country_code VARCHAR(10) UNIQUE
);

-- USERS Table
CREATE TABLE USERS (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    country_id INT NOT NULL,
    age INT,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    date_joined DATE NOT NULL,
    last_login DATETIME,
    follower_num INT DEFAULT 0,
    subscription_type ENUM('Free', 'Premium') DEFAULT 'Free',
    top_genre VARCHAR(100),
    num_songs_liked INT DEFAULT 0,
    most_played_artist VARCHAR(255),
    image VARCHAR(255),
    FOREIGN KEY (country_id) REFERENCES COUNTRY(country_id)
);

-- ARTISTS Table
CREATE TABLE ARTISTS (
    artist_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    genre VARCHAR(100),
    date_joined DATE,
    total_num_music INT DEFAULT 0,
    total_albums INT DEFAULT 0,
    listeners BIGINT DEFAULT 0,
    bio TEXT,
    country_id INT,
    image VARCHAR(255),
    FOREIGN KEY (country_id) REFERENCES COUNTRY(country_id)
);

-- ALBUMS Table
CREATE TABLE ALBUMS (
    album_id INT AUTO_INCREMENT PRIMARY KEY,
    artist_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    release_date DATE,
    genre VARCHAR(100),
    music_number INT DEFAULT 0, -- Number of songs in the album
    image VARCHAR(255),
    FOREIGN KEY (artist_id) REFERENCES ARTISTS(artist_id)
);

-- SONGS Table
CREATE TABLE SONGS (
    song_id INT AUTO_INCREMENT PRIMARY KEY,
    album_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    duration INT, -- in seconds
    genre VARCHAR(100),
    release_date DATE,
    popularity_rank INT, -- popularity rank
    image VARCHAR(255), -- can use album image
    FOREIGN KEY (album_id) REFERENCES ALBUMS(album_id)
);

-- PLAYLISTS Table
CREATE TABLE PLAYLISTS (
    playlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    date_created DATE NOT NULL,
    image VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES USERS(user_id)
);

-- PLAYLIST_SONGS Table (Many-to-Many relationship)
CREATE TABLE PLAYLIST_SONGS (
    playlistsong_id INT AUTO_INCREMENT PRIMARY KEY,
    playlist_id INT NOT NULL,
    song_id INT NOT NULL,
    date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (playlist_id) REFERENCES PLAYLISTS(playlist_id),
    FOREIGN KEY (song_id) REFERENCES SONGS(song_id),
    UNIQUE (playlist_id, song_id) -- A song can only be in a playlist once
);

-- PLAY_HISTORY Table
CREATE TABLE PLAY_HISTORY (
    play_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    song_id INT NOT NULL,
    playtime DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id),
    FOREIGN KEY (song_id) REFERENCES SONGS(song_id)
);
";

// Execute table creation queries
if ($conn->multi_query($create_tables_sql)) {
    echo "Tables created successfully.<br>";
    // Consume all results from multi_query
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
} else {
    die("Error creating tables: " . $conn->error);
}

// Import data from music_player_data.sql
$sql_data_file = 'music_player_data.sql';
if (!file_exists($sql_data_file)) {
    die("Error: Data file '{$sql_data_file}' not found. Please run generate_data.php first.");
}

$sql_content = file_get_contents($sql_data_file);

if ($conn->multi_query($sql_content)) {
    echo "Data imported successfully from `{$sql_data_file}`.<br>";
    // Consume all results from multi_query
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
} else {
    die("Error importing data: " . $conn->error);
}

echo "Database initialization complete! Redirecting to login page...";

$conn->close();

// Redirect to login page
header("Location: login.php");
exit();
?>