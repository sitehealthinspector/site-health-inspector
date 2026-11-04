<?php
namespace Joomla\Component\SiteHealth\Administrator\View\Health;

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    protected $health;

    public function display($tpl = null)
    {
        $this->health = $this->getModel()->getHealthData();
        HTMLHelper::_('stylesheet', 'media/com_sitehealth/css/admin.css', ['version' => '0.9.39']);

        return parent::display($tpl);
    }
}
