<?php
declare(strict_types=1);

namespace Joomla\Component\SiteHealth\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

class DisplayController extends BaseController
{
    protected $default_view = 'health';

    public function display($cachable = false, $urlparams = []): static
    {
        $app = Factory::getApplication();
        $app->getLanguage()->load(
            'com_sitehealth',
            JPATH_ADMINISTRATOR . '/components/com_sitehealth/language'
        );

        $user = $app->getIdentity();

        if (!$user || !$user->authorise('core.manage', 'com_sitehealth')) {
            throw new \RuntimeException('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN', 403);
        }

        return parent::display($cachable, $urlparams);
    }
}
