<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapController
{
    #[Route('/sitemap.xml', name: 'app_sitemap', defaults: ['_format' => 'xml'])]
    public function index(UrlGeneratorInterface $urlGenerator): Response
    {
        $urls = [
            ['route' => 'app_home', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['route' => 'app_services', 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['route' => 'app_apropos', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['route' => 'app_realisation', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['route' => 'app_contact', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['route' => 'app_mentions', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['route' => 'app_cgv', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['route' => 'app_politique_confidentialite', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $loc = $urlGenerator->generate($url['route'], [], UrlGeneratorInterface::ABSOLUTE_URL);

            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
            $xml .= '    <lastmod>' . date('Y-m-d') . "</lastmod>\n";
            $xml .= '    <changefreq>' . $url['changefreq'] . "</changefreq>\n";
            $xml .= '    <priority>' . $url['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return new Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
