

## 🛠️ Technologies Used

*   **Backend:** PHP
*   **Database:** MySQL
*   **Frontend:** HTML, CSS (with minimal JavaScript for UI enhancements on the homepage)
*   **Web Server:** Apache / Nginx (or any server compatible with PHP)

## 🚀 Setup and Installation

1.  **Prerequisites:**
    *   A web server (e.g., XAMPP, WAMP, MAMP, or a standalone Apache/Nginx setup).
    *   PHP (version 7.x or 8.x recommended).
    *   MySQL database server.

2.  **Clone the Repository:**
    ```bash
    git clone <repository-url>
    cd <project-directory>
    ```

3.  **Configure Database:**
    *   Open `config.php`.
    *   Update the database connection details:
        ```php
        $servername = "localhost";
        $dbname = "ErenCan_Akyuz_musicplayer"; // This DB will be created by install.php
        ```

4.  **Prepare `input.txt`:**
    *   Create a file named `input.txt` in the root project directory.
    *   Populate it with data for names, genres, and countries. The `generate_data.php` script uses this file.
    *   Format:
        ```
        -- Names
        Eren
        Ayşe
        John
        Jane
        ...

        -- Genres
        Pop
        Rock
        Jazz
        Classical
        Electronic
        Hip Hop
        ...

        -- Countries
        Türkiye
        Germany
        USA
        Japan
        Palestine
        ...
        ```
    *Ensure each section has a sufficient number of unique entries.*

5.  **Generate Sample Data:**
    *   Run the `generate_data.php` script. You can do this via the command line (recommended):
        ```bash
        php generate_data.php
        ```
        This will create a file named `music_player_data.sql` containing all the `INSERT` statements for sample data.
    *   Alternatively, if your server is configured to execute PHP scripts directly, you might be able to access `generate_data.php` through your browser.

6.  **Initialize Database and Tables:**
    *   Open your web browser and navigate to `install.php`:
        `http://localhost/your_project_folder/install.php`
    *   This script will:
        1.  Drop the database `ErenCan_Akyuz_musicplayer` if it exists.
        2.  Create the database `ErenCan_Akyuz_musicplayer`.
        3.  Create all the required tables (USERS, ARTISTS, ALBUMS, SONGS, etc.).
        4.  Import the sample data from `music_player_data.sql`.
    *   Upon successful completion, it will redirect you to `login.php`.

7.  **Start Using the Application:**
    *   If not redirected, navigate to `login.php`:
        `http://localhost/your_project_folder/login.php`
    *   Register a new user or log in with sample user credentials (if known from generated data; typically `usernameX` / `passwordX`). One of the first users generated by `generate_data.php` might be `eren1` (or similar, depending on `input.txt`) with password `password1`.

## 💡 Key Code Highlights & Skills Demonstrated

This project showcases proficiency in several key areas of web development:

*   **🔒 Security Best Practices:**
    *   **User Authentication:** Robust session management (`$_SESSION`) to protect authenticated routes.
    *   **Password Security:** Use of `password_hash()` and `password_verify()` for secure storage and verification of user passwords.
    *   **SQL Injection Prevention:** Consistent use of **prepared statements** (`$conn->prepare()`, `bind_param()`, `execute()`) for all database queries involving user input.
    *   **Input Sanitization:** A custom `sanitize_input()` function (`mysqli_real_escape_string()`, `strip_tags()`) is used as an additional layer, primarily for data that might be displayed directly or used in non-SQL contexts.

*   **🧱 Modular & Organized Code:**
    *   **Separation of Concerns:**
        *   `config.php`: Centralized database connection and common utility functions.
        *   Dedicated PHP files for each major page/functionality (e.g., `homepage.php`, `albumpage.php`, `artistpage.php`).
        *   `style.css`: All presentation logic separated into a CSS file.
    *   **Reusable Components:** While not a full MVC, similar patterns for fetching and displaying data are used across pages.

*   **📈 Database Design & Management:**
    *   **Relational Schema:** A well-structured relational database with tables like `USERS`, `ARTISTS`, `ALBUMS`, `SONGS`, `PLAYLISTS`, `PLAYLIST_SONGS`, and `PLAY_HISTORY`, utilizing foreign keys for data integrity and `UNIQUE` constraints where appropriate.
    *   **Data Generation:** The `generate_data.php` script demonstrates the ability to create large, structured datasets for testing and demonstration.
    *   **Automated Setup:** `install.php` script for easy one-click database and table creation, including data population.
    *   **Efficient Queries:** Use of JOINs, aggregate functions (COUNT), ordering (ORDER BY), and limiting (LIMIT) to fetch specific data efficiently (e.g., in `generalSQL.php` reports, homepage sections).

*   **⚙️ Dynamic Content & User Interaction:**
    *   PHP is used extensively to generate HTML content dynamically based on database information and user sessions.
    *   User interactions like following an artist, playing a song (simulated by updating play history), and adding songs to playlists update the database and provide feedback to the user.
    *   Clear feedback messages (`$message`, `$message_type`) for user actions.

*   **📊 SQL Proficiency & Reporting:**
    *   The `generalSQL.php` page features several predefined SQL reports, showcasing complex queries that join multiple tables and use aggregate functions.
    *   An interface for running custom SQL queries, demonstrating an understanding of direct database interaction (intended for advanced users/admins).

## 🔮 Potential Future Improvements

*   **Real Audio Playback:** Integrate an HTML5 audio player or a JavaScript library for actual music streaming.
*   **AJAX Integration:** Enhance user experience by using AJAX for actions like adding songs to playlists or following artists without full page reloads.
*   **Advanced Search & Filtering:** Implement more sophisticated search filters (e.g., by genre, release year).
*   **User Profile Customization:** Allow users to edit their profile information and upload custom avatars.
*   **API Development:** Create a RESTful API to expose application data, potentially for a mobile client.
*   **Enhanced UI/UX:** Further refine the user interface and experience with more advanced CSS and JavaScript.

