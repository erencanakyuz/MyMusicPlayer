<?php
// generate_data.php
// Student Name: Eren Can Akyüz
 // Student ID: 20220702128
// Description: Script to generate SQL INSERT statements for all database tables.
// The generated data is written to 'music_player_data.sql'.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define minimum counts for entities
define('MIN_USERS', 100);
define('MIN_ARTISTS', 100);
define('MIN_ALBUMS', 200);
define('MIN_SONGS', 1000);
define('MIN_PLAYLISTS', 500);
define('MIN_PLAYLIST_SONGS', 500); // Links between playlists and songs
define('MIN_PLAY_HISTORY', 100);
define('MIN_COUNTRIES', 20); // At least 10 countries, setting higher for diversity

$output_file = 'music_player_data.sql';
$fp = fopen($output_file, 'w');

if (!$fp) {
    die("Could not open file $output_file for writing.");
}

fwrite($fp, "-- SQL INSERT statements for Music Player Database\n");
fwrite($fp, "-- Generated by generate_data.php\n");
fwrite($fp, "-- Student Name: [Eren Can Akyüz]\n");
fwrite($fp, "-- Student ID: [20220702128]\n\n");
fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n");
fwrite($fp, "TRUNCATE TABLE PLAY_HISTORY;\n"); //disabled ?
fwrite($fp, "TRUNCATE TABLE PLAYLIST_SONGS;\n");
fwrite($fp, "TRUNCATE TABLE PLAYLISTS;\n");
fwrite($fp, "TRUNCATE TABLE SONGS;\n");
fwrite($fp, "TRUNCATE TABLE ALBUMS;\n");
fwrite($fp, "TRUNCATE TABLE ARTISTS;\n");
fwrite($fp, "TRUNCATE TABLE USERS;\n");
fwrite($fp, "TRUNCATE TABLE COUNTRY;\n");
fwrite($fp, "SET FOREIGN_KEY_CHECKS = 1;\n\n");

// --- Helper Functions ---
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function generate_random_date($start_date = '2000-01-01', $end_date = '2024-12-31') {
    $min = strtotime($start_date);
    $max = strtotime($end_date);
    $random_timestamp = mt_rand($min, $max);
    return date('Y-m-d', $random_timestamp);
}

function generate_random_time() {
    return date('H:i:s', mt_rand(0, 86400)); // Seconds in a day
}

function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// --- Read data from input.txt ---
$input_names = [];
$input_genres = [];
$input_countries = [];

$lines = file('input.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$current_section = '';
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;

    if (str_starts_with($line, '-- Names')) {
        $current_section = 'names';
    } elseif (str_starts_with($line, '-- Genres')) {
        $current_section = 'genres';
    } elseif (str_starts_with($line, '-- Countries')) {
        $current_section = 'countries';
    } elseif (str_starts_with($line, '--')) {
        // Skip other comment lines
        continue;
    } else {
        if ($current_section == 'names') {
            $input_names[] = $line;
        } elseif ($current_section == 'genres') {
            $input_genres[] = $line;
        } elseif ($current_section == 'countries') {
            $input_countries[] = $line;
        }
    }
}

if (empty($input_names)) die("Error: 'Names' section not found or empty in input.txt\n");
if (empty($input_genres)) die("Error: 'Genres' section not found or empty in input.txt\n");
if (empty($input_countries)) die("Error: 'Countries' section not found or empty in input.txt\n");

//  enough unique names for users and artists
$full_names = [];
$name_count = count($input_names);
for ($i = 0; $i < max(MIN_USERS, MIN_ARTISTS); $i++) {
    $first = $input_names[array_rand($input_names)];
    $last = $input_names[array_rand($input_names)]; // Using names as last names too 
    $full_names[] = $first . ' ' . $last;
}
$full_names = array_unique($full_names); // Ensure unique full names
shuffle($full_names); // Randomize after making unique

// --- Data Arrays to store generated IDs for FKs ---
$country_ids = [];
$user_ids = [];
$artist_ids = [];
$album_ids = [];
$song_ids = [];
$playlist_ids = [];

// --- 1. Generate COUNTRY data ---
fwrite($fp, "\n-- COUNTRY Data --\n");
$countries_to_generate = max(count($input_countries), MIN_COUNTRIES);
$input_country_codes = [
    'Türkiye' => 'TR', 'Palastine' => 'PS',
];

$country_id_counter = 1;
foreach ($input_countries as $country_name) {
    $country_code = $input_country_codes[$country_name] ?? strtoupper(substr($country_name, 0, 2));
    fwrite($fp, "INSERT INTO COUNTRY (country_id, country_name, country_code) VALUES ({$country_id_counter}, '{$country_name}', '{$country_code}');\n");
    $country_ids[] = $country_id_counter;
    $country_id_counter++;
}
if ($country_id_counter - 1 < MIN_COUNTRIES) {
    for ($i = $country_id_counter; $i <= MIN_COUNTRIES; $i++) {
        $rand_name = generate_random_string(8);
        $rand_code = generate_random_string(2);
        fwrite($fp, "INSERT INTO COUNTRY (country_id, country_name, country_code) VALUES ({$i}, 'Random {$rand_name}', '{$rand_code}');\n");
        $country_ids[] = $i;
    }
}


// --- 2. Generate USERS data ---
fwrite($fp, "\n-- USERS Data --\n");
$user_id_counter = 1;
$used_usernames = [];

while ($user_id_counter <= MIN_USERS) {
    // Rastgele isim parçaları
    $name_first_part = $input_names[array_rand($input_names)];
    $name_last_part = $input_names[array_rand($input_names)];
    $full_name = $name_first_part . ' ' . $name_last_part;

    // --- Benzersiz Username Oluşturma ---
    // Başlangıç kullanıcı adı: ismin boşlukları silinmiş küçük harf hali
    $base_username = strtolower(str_replace(' ', '', $name_first_part));
    $username_suffix = 0;
    $username = $base_username; // İlk deneme

    // Eğer bu kullanıcı adı zaten kullanılıyorsa, sonuna sayı ekleyerek benzersiz hale getir
    while (in_array($username, $used_usernames)) {
        $username_suffix++;
        $username = $base_username . $username_suffix;
    }
    $used_usernames[] = $username; // Yeni benzersiz kullanıcı adını kaydediyoruz

    // --- Benzersiz Email Oluşturma ---
    // E-posta adresinin ön kısmı için isim parçalarını kullan, alfanumerik karakterleri temizle
    $email_prefix_base = strtolower(preg_replace('/[^a-z0-9]/', '', $name_first_part . $name_last_part));
    // Benzersizliği garantilemek için doğrudan user_id_counter'ı e-posta'nın bir parçası yapıyoruz
    // Bu yöntem, her zaman benzersiz bir e-posta adresi üretecektir çünkü user_id_counter benzersizdir.
    $email = $email_prefix_base . $user_id_counter . "@example.com";

    $password = hash_password("password{$user_id_counter}"); // Örnek password
    $country_id = $country_ids[array_rand($country_ids)];
    $age = rand(18, 70);
    $date_joined = generate_random_date('2010-01-01', '2023-12-31');
    $last_login = generate_random_date($date_joined, '2024-05-20');
    $follower_num = rand(0, 1000);
    $subscription_type = ['Free', 'Premium'][rand(0, 1)];
    $top_genre = $input_genres[array_rand($input_genres)];
    $num_songs_liked = rand(0, 500);
    $most_played_artist = $full_names[array_rand($full_names)];
    $image_url = "https://picsum.photos/id/" . rand(1, 100) . "/100/100";

    fwrite($fp, "INSERT INTO USERS (user_id, country_id, age, name, username, email, password, date_joined, last_login, follower_num, subscription_type, top_genre, num_songs_liked, most_played_artist, image) VALUES (");
    fwrite($fp, "{$user_id_counter}, {$country_id}, {$age}, '{$full_name}', '{$username}', '{$email}', '{$password}', '{$date_joined}', '{$last_login}', {$follower_num}, '{$subscription_type}', '{$top_genre}', {$num_songs_liked}, '{$most_played_artist}', '{$image_url}');\n");
    $user_ids[] = $user_id_counter;
    $user_id_counter++;
}

// --- 3. Generate ARTISTS data ---
fwrite($fp, "\n-- ARTISTS Data --\n");
$artist_id_counter = 1;
$used_artist_names = [];
while ($artist_id_counter <= MIN_ARTISTS) {
    // Sanatçı adı için rastgele bir tam isim seçiyoruz
    $base_artist_name_candidate = $full_names[array_rand($full_names)]; // Geçici olarak isim adayı

    $artist_name_suffix = 0;
    $current_artist_name_to_use = $base_artist_name_candidate; // Kullanılacak son isim

    // Benzersiz bir sanatçı adı bulunana kadar döngü
    while (in_array($current_artist_name_to_use, $used_artist_names)) {
        $artist_name_suffix++;
        $current_artist_name_to_use = $base_artist_name_candidate . ' ' . $artist_name_suffix;
    }
    $used_artist_names[] = $current_artist_name_to_use; // Benzersiz 

    
    $genre = $input_genres[array_rand($input_genres)];
    $date_joined = generate_random_date('2005-01-01', '2022-12-31');
    $total_num_music = rand(5, 200);
    $total_albums = rand(1, 15);
    $listeners = rand(1000, 10000000);
    $bio = "A passionate artist creating " . $genre . " music.";
    $country_id = $country_ids[array_rand($country_ids)];
    $image_url = "https://picsum.photos/id/" . rand(101, 200) . "/150/150";

    fwrite($fp, "INSERT INTO ARTISTS (artist_id, name, genre, date_joined, total_num_music, total_albums, listeners, bio, country_id, image) VALUES (");
    fwrite($fp, "{$artist_id_counter}, '{$current_artist_name_to_use}', '{$genre}', '{$date_joined}', {$total_num_music}, {$total_albums}, {$listeners}, '{$bio}', {$country_id}, '{$image_url}');\n");
    $artist_ids[] = $artist_id_counter;
    $artist_id_counter++;
}

// --- 4. Generate ALBUMS data ---
fwrite($fp, "\n-- ALBUMS Data --\n");
$album_id_counter = 1;
$album_id_to_image_map = []; 

while ($album_id_counter <= MIN_ALBUMS) {
    $artist_id = $artist_ids[array_rand($artist_ids)];
    $name = "Album " . generate_random_string(8);
    $release_date = generate_random_date('2008-01-01', '2024-05-01');
    $genre = $input_genres[array_rand($input_genres)];
    $music_number = rand(5, 20); // Number of songs in the album
    $image_url = "https://picsum.photos/id/" . rand(201, 300) . "/200/200";

    fwrite($fp, "INSERT INTO ALBUMS (album_id, artist_id, name, release_date, genre, music_number, image) VALUES (");
    fwrite($fp, "{$album_id_counter}, {$artist_id}, '{$name}', '{$release_date}', '{$genre}', {$music_number}, '{$image_url}');\n");
    $album_ids[] = $album_id_counter;
    $album_id_to_image_map[$album_id_counter] = $image_url; 
    $album_id_counter++;
}

// --- 5. Generate SONGS data ---
fwrite($fp, "\n-- SONGS Data --\n");
$song_id_counter = 1;
while ($song_id_counter <= MIN_SONGS) {
    $album_id = $album_ids[array_rand($album_ids)];
    // Fetch album image to use for song
    $song_image_url = $album_id_to_image_map[$album_id] ?? "https://picsum.photos/id/" . rand(201, 300) . "/200/200"; // Albüm resmi yoksa yedek

    $title = "Song " . generate_random_string(10);
    $duration = rand(120, 480); // Duration in seconds (2 to 8 minutes)
    $genre = $input_genres[array_rand($input_genres)];
    $release_date = generate_random_date('2008-01-01', '2024-05-01'); // Can be same as album 
    $song_rank = rand(1, 1000); // Higher rank is more popular

    fwrite($fp, "INSERT INTO SONGS (song_id, album_id, title, duration, genre, release_date, `rank`, image) VALUES (");
    fwrite($fp, "{$song_id_counter}, {$album_id}, '{$title}', {$duration}, '{$genre}', '{$release_date}', {$song_rank}, '{$song_image_url}');\n");
    $song_ids[] = $song_id_counter;
    $song_id_counter++;
}

// --- 6. Generate PLAYLISTS data ---
fwrite($fp, "\n-- PLAYLISTS Data --\n");
$playlist_id_counter = 1;
while ($playlist_id_counter <= MIN_PLAYLISTS) {
    $user_id = $user_ids[array_rand($user_ids)];
    $title = "My " . $input_genres[array_rand($input_genres)] . " Playlist " . rand(1,100);
    $description = "A collection of " . $input_genres[array_rand($input_genres)] . " tunes.";
    $date_created = generate_random_date('2020-01-01', '2024-05-20');
    $image_url = "https://picsum.photos/id/" . rand(301, 400) . "/180/180";

    fwrite($fp, "INSERT INTO PLAYLISTS (playlist_id, user_id, title, description, date_created, image) VALUES (");
    fwrite($fp, "{$playlist_id_counter}, {$user_id}, '{$title}', '{$description}', '{$date_created}', '{$image_url}');\n");
    $playlist_ids[] = $playlist_id_counter;
    $playlist_id_counter++;
}

// --- 7. Generate PLAYLIST_SONGS data ---
fwrite($fp, "\n-- PLAYLIST_SONGS Data --\n");
$playlistsong_id_counter = 1;
$used_playlist_song_combinations = []; //benzersiz kombinasyonları saklamak için

$attempts = 0; // Sonsuz döngüyü önlemek için deneme sayacı
$max_attempts_per_song = 50; // Her şarkı için maksimum deneme

while ($playlistsong_id_counter <= MIN_PLAYLIST_SONGS) {
    $playlist_id = $playlist_ids[array_rand($playlist_ids)];
    $song_id = $song_ids[array_rand($song_ids)];

    // Kombinasyonu benzersiz bir anahtara dönüştür (örn: "354_426")
    $combination_key = "{$playlist_id}-{$song_id}";

    // Bu kombinasyon zaten kullanıldıysa, yeni bir tane bulana kadar devam et
    // Veya maksimum deneme sayısına ulaştıysak döngüyü kır (tüm kombinasyonlar tükenmiş olabilir)
    if (in_array($combination_key, $used_playlist_song_combinations)) {
        $attempts++;
        if ($attempts >= $max_attempts_per_song * MIN_PLAYLIST_SONGS) { // Çok fazla deneme yapıldıysa döngüyü kır
            echo "\nWARNING: Could not generate enough unique PLAYLIST_SONGS combinations. Generated " . ($playlistsong_id_counter - 1) . " unique entries.\n";
            break;
        }
        continue; // Yeni bir rastgele kombinasyon dene
    }

    // Benzersiz kombinasyonu bulduk, ekle
    $used_playlist_song_combinations[] = $combination_key;
    $attempts = 0; // Başarılı bir eklemeden sonra deneme sayacını sıfırla

    $date_added = generate_random_date('2023-01-01', '2024-05-20');

    fwrite($fp, "INSERT INTO PLAYLIST_SONGS (playlistsong_id, playlist_id, song_id, date_added) VALUES (");
    fwrite($fp, "{$playlistsong_id_counter}, {$playlist_id}, {$song_id}, '{$date_added}');\n");
    $playlistsong_id_counter++;
}


// --- 8. Generate PLAY_HISTORY data ---
fwrite($fp, "\n-- PLAY_HISTORY Data --\n");
$play_id_counter = 1;
for ($i = 0; $i < MIN_PLAY_HISTORY; $i++) {
    $user_id = $user_ids[array_rand($user_ids)];
    $song_id = $song_ids[array_rand($song_ids)];
    $playtime = generate_random_date('2024-01-01', '2024-05-20') . ' ' . generate_random_time();

    fwrite($fp, "INSERT INTO PLAY_HISTORY (play_id, user_id, song_id, playtime) VALUES (");
    fwrite($fp, "{$play_id_counter}, {$user_id}, {$song_id}, '{$playtime}');\n");
    $play_id_counter++;
}


fwrite($fp, "\nSET FOREIGN_KEY_CHECKS = 1;\n"); // Re-enable foreign key checks

fclose($fp);

echo "Data generation complete. SQL inserts written to `{$output_file}`NEXT:GO install.php .\n";


?>