<?php
declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;

class com_sitehealthInstallerScript implements InstallerScriptInterface
{
    private const MINIMUM_JOOMLA = '4.4.0';
    private const MINIMUM_PHP_J4_J5 = '8.1.0';
    private const MINIMUM_PHP_J6 = '8.3.0';

    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function update(InstallerAdapter $adapter): bool
    {
        $legacyManifest = JPATH_ADMINISTRATOR . '/components/com_sitehealth/com_sitehealth.xml';

        if (is_file($legacyManifest)) {
            @unlink($legacyManifest);
        }

        return true;
    }

    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        $app = Factory::getApplication();

        // Site Health Inspector supports the Joomla 4.4, 5.x and 6.x major branches.
        // PHP 8.1 is the supported project floor for Joomla 4.4/5.x usage;
        // Joomla 6 itself requires PHP 8.3 or newer.
        if (version_compare(JVERSION, self::MINIMUM_JOOMLA, '<')) {
            $app->enqueueMessage(
                sprintf(
                    'Site Health Inspector cannot be installed or updated. Joomla %s or newer is required. Detected Joomla version: %s.',
                    self::MINIMUM_JOOMLA,
                    JVERSION
                ),
                'error'
            );

            return false;
        }

        $major = (int) explode('.', JVERSION, 2)[0];

        if ($major < 4 || $major > 6) {
            $app->enqueueMessage(
                sprintf(
                    'Site Health Inspector supports Joomla 4.4.x, 5.x and 6.x. Detected Joomla version: %s.',
                    JVERSION
                ),
                'error'
            );

            return false;
        }

        $minimumPhp = $major >= 6 ? self::MINIMUM_PHP_J6 : self::MINIMUM_PHP_J4_J5;

        if (version_compare(PHP_VERSION, $minimumPhp, '<')) {
            $app->enqueueMessage(
                sprintf(
                    'Site Health Inspector cannot be installed or updated on Joomla %s. PHP %s or newer is required. Detected PHP version: %s.',
                    $major,
                    $minimumPhp,
                    PHP_VERSION
                ),
                'error'
            );

            return false;
        }

        return true;
    }

    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        return true;
    }
}
