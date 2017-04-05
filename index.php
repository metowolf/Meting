// If you installed via composer, just use this code to requrie autoloader on the top of your projects.
require 'vendor/autoload.php';

// Else use require file
// require 'Meting.php';

// Initialize to netease API
$API = new Meting('netease');

// Enjoy
$result = $API->format(true)->search('Soldier');
$data = json_decode($result);
header('Content-type: application/json; charset=UTF-8');
echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
