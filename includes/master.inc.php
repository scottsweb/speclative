<?PHP
    // Application flag
    define('speclative', true);

    // Determine our absolute document root
    define('DOC_ROOT', realpath(dirname(__FILE__) . '/../'));

    // Global include files
    require DOC_ROOT . '/includes/functions.inc.php'; // __autoload() is contained in this file
    require DOC_ROOT . '/includes/class.dbobject.php';
    require DOC_ROOT . '/includes/class.objects.php';

    // Fix magic quotes
    if(get_magic_quotes_gpc())
    {
        $_POST    = fix_slashes($_POST);
        $_GET     = fix_slashes($_GET);
        $_REQUEST = fix_slashes($_REQUEST);
        $_COOKIE  = fix_slashes($_COOKIE);
    }

    // Load our config settings
    $Config = Config::getConfig();

    // Object for tracking and displaying error messages
    $Error = Error::getError();
