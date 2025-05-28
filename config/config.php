/**
 * File di configurazione
 *
 * Questo file contiene le impostazioni globali per l'applicazione.
 */

// Avvio il buffering dell'output all'inizio
ob_start();

// Avvio la sessione se non è già attiva
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configurazione dell'applicazione
define('APP_NAME', 'ElectroCharge');
define('APP_URL', 'http://localhost/ev-charging-system');
define('ADMIN_EMAIL', 'admin@example.com');

// Imposto il fuso orario predefinito
date_default_timezone_set('UTC');

// Impostazioni per la gestione degli errori
// In produzione, impostare error_reporting a E_ALL e display_errors a 0
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definizione dei percorsi
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes/');
define('PAGES_PATH', ROOT_PATH . '/pages/');
define('ADMIN_PATH', ROOT_PATH . '/admin/');
define('ASSETS_PATH', ROOT_PATH . '/assets/');

// Includo la connessione al database
require_once ROOT_PATH . '/config/database.php';

// Includo le funzioni di utilità
require_once INCLUDES_PATH . 'functions.php';

// Inizializzo le impostazioni dell'applicazione
$settings = [
    'booking_duration_minutes' => 60, // Durata predefinita della prenotazione in minuti
    'booking_expiry_minutes' => 10,   // Minuti dopo i quali una prenotazione scade se l'utente non si presenta
    'price_per_kwh' => 0.35,         // Prezzo per kWh in valuta
    'min_booking_interval' => 30,     // Intervallo minimo di prenotazione in minuti
];

// Gestione della sessione utente
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireAdmin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }

    // Controllo se l'utente è nella tabella Admins
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT admin_id FROM Admins WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        setFlashMessage('error', 'Non hai i permessi per accedere a questa pagina.');
        redirect('index.php');
    }
}

function logout() {
    session_unset();
    session_destroy();
    redirect('login.php');
}

// Funzione di reindirizzamento
function redirect($page) {
    // Controllo che non sia già stato inviato output
    if (!headers_sent()) {
        header("Location: " . APP_URL . "/" . $page);
        exit;
    } else {
        echo '<script>window.location.href="' . APP_URL . '/' . $page . '";</script>';
        exit;
    }
}

// Sistema di messaggi flash
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Protezione CSRF
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Funzione per sanificare i dati di input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Termino il buffering dell'output solo se non è già stato terminato
if (ob_get_level() > 0) {
    ob_end_flush();
}