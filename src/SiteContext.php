<?php

namespace MangaDiyari\Core;

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/SettingRepository.php';
require_once __DIR__ . '/MenuRepository.php';

class SiteContext
{
    public static function build(): array
    {
        $config = require __DIR__ . '/../config.php';
        $pdo = Database::getConnection();
        $settings = (new SettingRepository($pdo))->all();
        $menuRepository = new MenuRepository($pdo);

        $siteName = $settings['site_name'] ?? ($config['site']['name'] ?? 'Manga Diyarı');
        $tagline = $settings['site_tagline'] ?? ($config['site']['tagline'] ?? '');
        $logo = $settings['site_logo'] ?? '';

        $menus = [
            'primary' => $menuRepository->getByLocation('primary'),
            'footer' => $menuRepository->getByLocation('footer'),
        ];

        $ads = [
            'header' => $settings['ad_header'] ?? '',
            'sidebar' => $settings['ad_sidebar'] ?? '',
            'footer' => $settings['ad_footer'] ?? '',
        ];

        $analytics = [
            'google' => $settings['analytics_google'] ?? '',
            'search_console' => $settings['analytics_search_console'] ?? '',
        ];

        return [
            'site' => [
                'name' => $siteName,
                'tagline' => $tagline,
                'logo' => $logo,
            ],
            'menus' => $menus,
            'ads' => $ads,
            'analytics' => $analytics,
            'settings' => $settings,
        ];
    }
}